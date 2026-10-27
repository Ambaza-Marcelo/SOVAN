<?php

namespace App\Services\Stock;

use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Material;
use App\Services\Accounting\LedgerTotalsService;
use App\Services\Material\MaterialFlowOrchestrator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Sortie Autre (SAU) : type appliqué par défaut à toute sortie directe de stock.
 *
 * Crédit : compte de stock du magasin d'origine (valeur CUMP de la sortie).
 * Débit  : compte propre à la fiche article (Material, RawMaterial, Item) —
 *          jamais un compte d'ajustement global.
 *
 * La sortie normale (SN) est réservée au module de facturation et la sortie
 * transfert (ST) au module de transfert entre magasins : toutes deux refusées ici.
 */
class SauStockoutJournalService
{
    public const SAU = 'SAU';
    public const SN = 'SN';
    public const ST = 'ST';

    /** @var LedgerTotalsService */
    protected $ledger;

    public function __construct(LedgerTotalsService $ledger)
    {
        $this->ledger = $ledger;
    }

    public static function normalizeType(?string $type): string
    {
        $type = strtoupper(trim((string) $type));

        return $type === '' ? self::SAU : $type;
    }

    public static function isSau(?string $type): bool
    {
        return self::normalizeType($type) === self::SAU;
    }

    /**
     * @throws SauStockoutException
     */
    public function assertDirectStockoutType(?string $type): void
    {
        if (self::normalizeType($type) === self::SN) {
            throw new SauStockoutException(
                'La sortie normale (SN) est réservée au module de facturation. '
                . 'Utilisez la sortie autre (SAU) pour une sortie directe de stock.'
            );
        }

        if (self::normalizeType($type) === self::ST) {
            throw new SauStockoutException(
                'La sortie transfert (ST) se fait uniquement via le module de transfert entre magasins. '
                . 'Utilisez la sortie autre (SAU) pour une sortie directe de stock.'
            );
        }
    }

    /**
     * @throws SauStockoutException
     */
    public function resolveDebitAccountId(?Model $item, string $fallbackLabel = ''): int
    {
        if (!$item) {
            throw new SauStockoutException('Article introuvable pour la ligne de sortie ' . $fallbackLabel . '.');
        }

        if ($item instanceof Material) {
            try {
                return app(MaterialFlowOrchestrator::class)->resolveStockoutDebitAccountId($item);
            } catch (RuntimeException $e) {
                throw new SauStockoutException($e->getMessage(), 0, $e);
            }
        }

        $accountId = (int) ($item->default_account_id ?? 0);
        if ($accountId <= 0) {
            throw new SauStockoutException(
                'Compte comptable par défaut manquant pour l\'article : ' . ($item->name ?? $fallbackLabel)
                . '. Renseignez-le sur la fiche article avant d\'approuver la sortie (SAU).'
            );
        }

        return $accountId;
    }

    /**
     * Résout le compte de débit de chaque ligne avant toute écriture.
     *
     * @param  array<int, array{item: ?Model, amount: float, label?: string}>  $lines
     * @return array<int, array{item: ?Model, amount: float, label: string, account_id: int}>
     *
     * @throws SauStockoutException
     */
    public function resolveLines(array $lines): array
    {
        $resolved = [];
        foreach ($lines as $line) {
            $item = $line['item'] ?? null;
            $label = (string) ($line['label'] ?? ($item->name ?? ''));
            $resolved[] = [
                'item' => $item,
                'amount' => round((float) ($line['amount'] ?? 0), 2),
                'label' => $label,
                'account_id' => $this->resolveDebitAccountId($item, $label),
            ];
        }

        return $resolved;
    }

    /**
     * Crédit stock / débit compte article, solde recalculé par le grand livre.
     *
     * @param  array<int, array{amount: float, label: string, account_id: int}>  $resolvedLines
     * @param  array{date: mixed, document_no: string, description: ?string}  $meta
     * @return float montant total imputé
     *
     * @throws SauStockoutException
     */
    public function post(?int $stockAccountId, array $resolvedLines, array $meta): float
    {
        $total = 0.0;
        foreach ($resolvedLines as $line) {
            $total += (float) $line['amount'];
        }
        $total = round($total, 2);

        if ($total <= 0) {
            return 0.0;
        }

        if (empty($stockAccountId)) {
            throw new SauStockoutException('Compte de stock du magasin d\'origine manquant pour la sortie (SAU).');
        }

        $date = $meta['date'] ?? Carbon::now();
        $documentNo = (string) ($meta['document_no'] ?? '');
        $description = (string) ($meta['description'] ?? '');
        $now = Carbon::now();

        $creditState = $this->ledger->update($stockAccountId, 'credit', $total);
        $rows = [[
            'date' => $date,
            'document_no' => $documentNo,
            'description' => $description,
            'account_id' => $stockAccountId,
            'libelle' => $description,
            'credit' => $total,
            'balance' => $creditState['balance'],
            'created_at' => $now,
        ]];

        foreach ($resolvedLines as $line) {
            $amount = (float) $line['amount'];
            if ($amount <= 0) {
                continue;
            }

            $debitState = $this->ledger->update($line['account_id'], 'debit', $amount);
            $rows[] = [
                'date' => $date,
                'document_no' => $documentNo,
                'description' => $description,
                'account_id' => $line['account_id'],
                'libelle' => trim($description . ' - ' . $line['label'], ' -'),
                'debit' => $amount,
                'balance' => $debitState['balance'],
                'created_at' => $now,
            ];
        }

        // Lignes débit / crédit aux colonnes différentes : insertion unitaire.
        foreach ($rows as $row) {
            AccountJournalEntryDetail::insert([$row]);
        }

        return $total;
    }
}
