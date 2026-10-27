<?php

namespace App\Services\Material;

use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountSetting;
use App\Models\Material;
use App\Services\Accounting\AccountingPeriodGuard;
use App\Services\Accounting\JournalPostingService;
use App\Services\DepreciationService;
use Carbon\Carbon;
use InvalidArgumentException;
use RuntimeException;

/**
 * Orchestration Material selon is_stockable / is_amortizable.
 *
 * Déclencheur unique : statut APPROUVÉE (status = 4). Tant que le document
 * d'entrée / sortie n'est pas approuvé, aucun flux physique ni comptable
 * stratégique n'est exécuté.
 *
 * CAS 1 : stockable + non amortissable → stock classe 3 ; sortie SAU → charge.
 * CAS 2 : non stockable + amortissable → immobilisation classe 2 dès l'entrée APPROUVÉE ;
 *         amortissement immédiat (service_start_date = date d'approbation).
 * CAS 3 : stockable + amortissable → entrée en stock (classe 3) sans amortissement ;
 *         sortie SAU APPROUVÉE = virement 3→2 + mise en service + plan linéaire.
 */
class MaterialFlowOrchestrator
{
    public const CASE_STOCKABLE = 'case_1_stockable';
    public const CASE_IMMATERIAL = 'case_2_non_stockable_amortizable';
    public const CASE_STOCK_THEN_ASSET = 'case_3_stockable_amortizable';
    public const CASE_NONE = 'none';

    /** @var DepreciationService */
    protected $depreciation;

    public function __construct(DepreciationService $depreciation)
    {
        $this->depreciation = $depreciation;
    }

    public function resolveCase(Material $material): string
    {
        $stockable = (bool) ($material->is_stockable ?? true);
        $amortizable = (bool) ($material->is_amortizable ?? false);

        if ($stockable && !$amortizable) {
            return self::CASE_STOCKABLE;
        }
        if (!$stockable && $amortizable) {
            return self::CASE_IMMATERIAL;
        }
        if ($stockable && $amortizable) {
            return self::CASE_STOCK_THEN_ASSET;
        }

        return self::CASE_NONE;
    }

    public function affectsPhysicalStock(Material $material): bool
    {
        return (bool) ($material->is_stockable ?? true);
    }

    /**
     * Compte à débiter sur une sortie autre (SAU) selon le cas.
     */
    public function resolveStockoutDebitAccountId(Material $material): int
    {
        $case = $this->resolveCase($material);

        if ($case === self::CASE_STOCK_THEN_ASSET) {
            $accountId = (int) ($material->immobilization_account_id ?? 0);
            if ($accountId <= 0) {
                throw new RuntimeException(
                    'Compte d\'immobilisation (classe 2) manquant pour l\'article : ' . $material->name
                    . '. Renseignez-le sur la fiche matériel.'
                );
            }

            return $accountId;
        }

        $accountId = (int) ($material->default_account_id ?? 0);
        if ($accountId <= 0) {
            throw new RuntimeException(
                'Compte de charge manquant pour l\'article : ' . $material->name
                . '. Renseignez le compte de charge sur la fiche article.'
            );
        }

        return $accountId;
    }

    /**
     * Compte à débiter sur une entrée (stockin) selon le cas.
     * CAS 2 → immobilisation ; sinon compte stock magasin (passé en argument).
     */
    public function resolveStockinDebitAccountId(Material $material, ?int $storeStockAccountId): int
    {
        if ($this->resolveCase($material) === self::CASE_IMMATERIAL) {
            $accountId = (int) ($material->immobilization_account_id ?? 0);
            if ($accountId <= 0) {
                throw new RuntimeException(
                    'Compte d\'immobilisation (classe 2) manquant pour l\'article non stockable : ' . $material->name
                );
            }

            return $accountId;
        }

        $accountId = (int) ($storeStockAccountId ?? 0);
        if ($accountId <= 0) {
            throw new RuntimeException('Compte de stock magasin manquant pour l\'entrée matériel.');
        }

        return $accountId;
    }

    /**
     * Après entrée APPROUVÉE (CAS 2) : date de mise en service + plan d'amortissement.
     */
    public function activateOnApprovedEntry(Material $material, $approvalDate, float $acquisitionValue): void
    {
        if ($this->resolveCase($material) !== self::CASE_IMMATERIAL) {
            return;
        }

        $this->activateDepreciation($material, $approvalDate, $acquisitionValue);
    }

    /**
     * Après sortie APPROUVÉE (CAS 3, SN) : mise en service + plan.
     */
    public function activateOnApprovedStockout(Material $material, $approvalDate, float $transferValue): void
    {
        if ($this->resolveCase($material) !== self::CASE_STOCK_THEN_ASSET) {
            return;
        }

        $this->activateDepreciation($material, $approvalDate, $transferValue);
    }

    /**
     * Enregistre service_start_date, base d'amortissement si vide, et valide le plan linéaire.
     */
    public function activateDepreciation(Material $material, $approvalDate, ?float $baseValue = null): array
    {
        if (!(bool) ($material->is_amortizable ?? false)) {
            return [];
        }

        $date = $approvalDate instanceof Carbon
            ? $approvalDate->copy()->startOfDay()
            : Carbon::parse((string) $approvalDate)->startOfDay();

        // Toujours poser la date d'approbation comme date de mise en service
        $material->service_start_date = $date->toDateString();

        if ($baseValue !== null && $baseValue > 0 && (float) ($material->depreciation_base ?? 0) <= 0) {
            $material->depreciation_base = round($baseValue, 2);
        }

        if (empty($material->duration_years) || (int) $material->duration_years < 1) {
            throw new InvalidArgumentException(
                'Durée d\'amortissement manquante pour l\'article : ' . $material->name
            );
        }

        $material->save();
        $material->refresh();

        return $this->depreciation->generateLinearSchedule($material);
    }

    /**
     * Construit une ligne d'écriture + met à jour le ledger.
     *
     * @return array<string, mixed>
     */
    public function buildJournalLine(
        $date,
        string $documentNo,
        string $description,
        int $accountId,
        float $debit,
        float $credit,
        string $libelle
    ): array {
        $type = $debit > 0 ? 'debit' : 'credit';
        $amount = $debit > 0 ? $debit : $credit;
        $ledger = $this->updateLedgerTotals($accountId, $type, $amount);

        return [
            'date' => $date,
            'document_no' => $documentNo,
            'description' => $description,
            'account_id' => $accountId,
            'libelle' => $libelle,
            'debit' => $debit > 0 ? $debit : 0,
            'credit' => $credit > 0 ? $credit : 0,
            'balance' => $ledger['balance'],
            'created_at' => Carbon::now(),
        ];
    }

    public function insertJournalLines(array $lines): void
    {
        if (empty($lines)) {
            return;
        }

        AccountJournalEntryDetail::insert($lines);
    }

    /**
     * @return array{debit: float, credit: float, balance: float}
     */
    public function updateLedgerTotals(int $accountId, string $type, float $amount): array
    {
        $ledger = AccountLedger::firstOrNew(['account_id' => $accountId]);
        $currentDebit = (float) ($ledger->debit ?? 0);
        $currentCredit = (float) ($ledger->credit ?? 0);

        if ($type === 'debit') {
            $currentDebit += $amount;
        } else {
            $currentCredit += $amount;
        }

        $ledger->debit = $currentDebit;
        $ledger->credit = $currentCredit;
        $ledger->balance = $currentDebit - $currentCredit;
        $ledger->save();

        return [
            'debit' => $currentDebit,
            'credit' => $currentCredit,
            'balance' => $ledger->balance,
        ];
    }

    /**
     * Écriture double : débit accountDebit / crédit accountCredit, même montant.
     */
    public function postBalancedEntry(
        $date,
        string $documentNo,
        string $libelle,
        int $debitAccountId,
        int $creditAccountId,
        float $amount
    ): void {
        $amount = round((float) $amount, 2);
        if ($amount <= 0) {
            return;
        }

        $this->postEntryLines($date, $documentNo, [
            [
                'account_id' => $debitAccountId,
                'debit' => $amount,
                'credit' => 0,
                'libelle' => $libelle,
            ],
            [
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $amount,
                'libelle' => $libelle,
            ],
        ]);
    }

    /**
     * Écriture multi-lignes équilibrée (somme débits = somme crédits).
     *
     * @param  array<int, array{account_id:int, debit?:float|int, credit?:float|int, libelle?:string}>  $lines
     */
    public function postEntryLines($date, string $documentNo, array $lines): void
    {
        $fiscalYear = AccountSetting::where('active', 1)->value('fiscal_year');
        AccountingPeriodGuard::check($fiscalYear !== null ? (string) $fiscalYear : null);

        $normalized = [];
        $sumDebit = 0.0;
        $sumCredit = 0.0;

        foreach ($lines as $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);
            if ($debit <= 0 && $credit <= 0) {
                continue;
            }
            if ($debit > 0 && $credit > 0) {
                throw new RuntimeException('Une ligne ne peut pas avoir débit et crédit simultanément.');
            }
            $sumDebit += $debit;
            $sumCredit += $credit;
            $normalized[] = [
                'account_id' => (int) $line['account_id'],
                'debit' => $debit,
                'credit' => $credit,
                'libelle' => (string) ($line['libelle'] ?? $documentNo),
            ];
        }

        if (empty($normalized)) {
            return;
        }

        if (abs($sumDebit - $sumCredit) > 0.01) {
            throw new RuntimeException(
                sprintf('Écriture non équilibrée (débit %s / crédit %s).', $sumDebit, $sumCredit)
            );
        }

        $now = Carbon::now();
        $rows = [];
        foreach ($normalized as $line) {
            $type = $line['debit'] > 0 ? 'debit' : 'credit';
            $amount = $line['debit'] > 0 ? $line['debit'] : $line['credit'];
            $state = $this->updateLedgerTotals($line['account_id'], $type, $amount);
            $rows[] = [
                'date' => $date,
                'document_no' => $documentNo,
                'description' => $line['libelle'],
                'account_id' => $line['account_id'],
                'libelle' => $line['libelle'],
                'debit' => $line['debit'],
                'credit' => $line['credit'],
                'balance' => $state['balance'],
                'fiscal_year' => $fiscalYear,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Clôture : bypass period lock (date 31/12 peut être sur période déjà soft-lock)
        app(JournalPostingService::class)->withoutLock(function () use ($rows) {
            app(JournalPostingService::class)->insert($rows);
        });
    }
}
