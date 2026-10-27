<?php

namespace App\Services\Stock;

use App\Exceptions\AccountingPeriodLockedException;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountSetting;
use App\Models\FinishedGoodsStockout;
use App\Models\FinishedGoodsStockoutDetail;
use App\Models\GoodsStockout;
use App\Models\GoodsStockoutDetail;
use App\Models\Item;
use App\Models\Material;
use App\Models\MaterialStockout;
use App\Models\MaterialStockoutDetail;
use App\Models\RawMaterial;
use App\Models\RawMaterialStockout;
use App\Models\RawMaterialStockoutDetail;
use App\Services\Accounting\LedgerTotalsService;
use App\Services\Accounting\PeriodLockService;
use App\Services\Material\MaterialFlowOrchestrator;
use Illuminate\Support\Facades\DB;

/**
 * Rectification en place des sorties directes (SAU / SN) approuvées qui ont débité
 * un compte d'ajustement global (m_saj, f_saj, raw_mat_saj).
 *
 * Aucune extourne ni nouvelle pièce : la ligne de débit existante est réimputée
 * sur le compte de la fiche article. Si le bon couvre plusieurs comptes, la ligne
 * est ventilée en plusieurs lignes de la même pièce (même date, même n° de document),
 * pour un total strictement identique. Le crédit stock n'est jamais modifié.
 */
class SauJournalRectificationService
{
    /** Marque des lignes issues d'une ventilation : "RECTIF_SAU:<id ligne d'origine>". */
    public const SPLIT_TAG = 'RECTIF_SAU';

    public const STATUS_READY = 'ready';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_LOCKED = 'locked';

    /** @var SauStockoutJournalService */
    protected $sau;

    /** @var LedgerTotalsService */
    protected $ledger;

    /** @var PeriodLockService */
    protected $periodLock;

    public function __construct(SauStockoutJournalService $sau, LedgerTotalsService $ledger, PeriodLockService $periodLock)
    {
        $this->sau = $sau;
        $this->ledger = $ledger;
        $this->periodLock = $periodLock;
    }

    protected function modules(): array
    {
        return [
            'material' => [
                'label' => 'Matériel',
                'header' => MaterialStockout::class,
                'detail' => MaterialStockoutDetail::class,
                'item_key' => 'material_id',
                'item_model' => Material::class,
                'weight' => 'total_purchase_value',
            ],
            'raw_material' => [
                'label' => 'Matière première',
                'header' => RawMaterialStockout::class,
                'detail' => RawMaterialStockoutDetail::class,
                'item_key' => 'r_material_id',
                'item_model' => RawMaterial::class,
                'weight' => 'total_purchase_value',
            ],
            'goods' => [
                'label' => 'Marchandise',
                'header' => GoodsStockout::class,
                'detail' => GoodsStockoutDetail::class,
                'item_key' => 'item_id',
                'item_model' => Item::class,
                'weight' => 'total_cost_value',
            ],
            'finished_goods' => [
                'label' => 'Produit fini',
                'header' => FinishedGoodsStockout::class,
                'detail' => FinishedGoodsStockoutDetail::class,
                'item_key' => 'item_id',
                'item_model' => Item::class,
                'weight' => 'total_cost_value',
            ],
        ];
    }

    /**
     * @return int[]
     */
    public function genericAccountIds(): array
    {
        $setting = AccountSetting::where('active', 1)->first();
        if (!$setting) {
            return [];
        }

        $ids = [];
        foreach (['m_saj_account_id', 'f_saj_account_id', 'raw_mat_saj_account_id'] as $column) {
            $id = (int) ($setting->{$column} ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * Inventaire des pièces à rectifier (lecture seule).
     *
     * @return array<int, array>
     */
    public function plan(?string $documentNo = null): array
    {
        $generic = $this->genericAccountIds();
        if (empty($generic)) {
            return [];
        }

        $plans = [];
        $seenLineIds = [];

        foreach ($this->modules() as $moduleKey => $module) {
            $documentNos = $module['header']::where('status', 4)
                ->where(function ($q) {
                    $q->whereNull('item_movement_type')
                        ->orWhereIn('item_movement_type', ['', SauStockoutJournalService::SAU, SauStockoutJournalService::SN]);
                })
                ->when($documentNo, function ($q) use ($documentNo) {
                    $q->where('stockout_no', $documentNo);
                })
                ->pluck('stockout_no')
                ->all();

            if (empty($documentNos)) {
                continue;
            }

            $linesByDocument = AccountJournalEntryDetail::whereIn('document_no', $documentNos)
                ->whereIn('account_id', $generic)
                ->whereRaw('CAST(debit AS DECIMAL(20,4)) > 0')
                ->orderBy('id')
                ->get()
                ->groupBy('document_no');

            foreach ($linesByDocument as $docNo => $lines) {
                $lines = $lines->reject(function ($line) use ($seenLineIds) {
                    return isset($seenLineIds[$line->id]);
                });
                if ($lines->isEmpty()) {
                    continue;
                }
                foreach ($lines as $line) {
                    $seenLineIds[$line->id] = true;
                }

                $plans[] = $this->buildDocumentPlan($moduleKey, $module, (string) $docNo, $lines->all());
            }
        }

        return $plans;
    }

    protected function buildDocumentPlan(string $moduleKey, array $module, string $documentNo, array $lines): array
    {
        $plan = [
            'module' => $moduleKey,
            'module_label' => $module['label'],
            'document_no' => $documentNo,
            'status' => self::STATUS_READY,
            'reasons' => [],
            'lines' => [],
        ];

        $orchestrator = app(MaterialFlowOrchestrator::class);
        $accounts = [];

        $details = $module['detail']::where('stockout_no', $documentNo)->get();
        foreach ($details as $detail) {
            $itemId = $detail->{$module['item_key']};
            $item = $module['item_model']::find($itemId);

            if ($item instanceof Material && !$orchestrator->affectsPhysicalStock($item)) {
                continue;
            }

            $label = $item ? (string) $item->name : ('#' . $itemId);
            try {
                $accountId = $this->sau->resolveDebitAccountId($item, $label);
            } catch (SauStockoutException $e) {
                $plan['status'] = self::STATUS_BLOCKED;
                $plan['reasons'][] = $e->getMessage();
                continue;
            }

            $weight = (float) ($detail->{$module['weight']} ?? 0);
            if ($weight <= 0) {
                $weight = max((float) ($detail->quantity ?? 0), 0.0);
            }

            if (!isset($accounts[$accountId])) {
                $accounts[$accountId] = ['weight' => 0.0, 'labels' => []];
            }
            $accounts[$accountId]['weight'] += $weight;
            $accounts[$accountId]['labels'][$label] = $label;
        }

        if (empty($accounts) && $plan['status'] === self::STATUS_READY) {
            $plan['status'] = self::STATUS_BLOCKED;
            $plan['reasons'][] = 'Aucune ligne article exploitable sur le bon ' . $documentNo . '.';
        }

        foreach ($lines as $line) {
            if ($plan['status'] === self::STATUS_READY) {
                try {
                    $this->periodLock->assertDateWritable($line->date);
                } catch (AccountingPeriodLockedException $e) {
                    $plan['status'] = self::STATUS_LOCKED;
                    $plan['reasons'][] = $e->getMessage();
                }
            }

            $amount = (float) $line->debit;
            $plan['lines'][] = [
                'line_id' => (int) $line->id,
                'date' => $line->date,
                'generic_account_id' => (int) $line->account_id,
                'amount' => $amount,
                'allocations' => empty($accounts) ? [] : $this->allocate($amount, $accounts),
            ];
        }

        return $plan;
    }

    /**
     * Ventile le montant au prorata des valeurs de sortie ; l'écart d'arrondi
     * est porté sur la plus grosse part pour conserver le total exact.
     */
    protected function allocate(float $amount, array $accounts): array
    {
        $totalWeight = 0.0;
        foreach ($accounts as $data) {
            $totalWeight += $data['weight'];
        }

        $allocations = [];
        $allocated = 0.0;
        $count = count($accounts);
        foreach ($accounts as $accountId => $data) {
            $ratio = $totalWeight > 0 ? $data['weight'] / $totalWeight : 1 / $count;
            $share = round($amount * $ratio, 2);
            $allocated += $share;
            $allocations[] = [
                'account_id' => (int) $accountId,
                'amount' => $share,
                'labels' => array_values($data['labels']),
            ];
        }

        $residue = $amount - $allocated;
        if (abs($residue) > 0.000001) {
            $largest = 0;
            foreach ($allocations as $i => $allocation) {
                if ($allocation['amount'] > $allocations[$largest]['amount']) {
                    $largest = $i;
                }
            }
            $allocations[$largest]['amount'] += $residue;
        }

        return array_values(array_filter($allocations, function ($allocation) {
            return abs($allocation['amount']) > 0.000001;
        }));
    }

    /**
     * Applique les plans prêts. Chaque pièce est traitée dans sa propre transaction :
     * toute anomalie annule l'intégralité de la pièce.
     *
     * @return array{applied: int, skipped: int, lines: int, errors: string[]}
     */
    public function apply(?string $documentNo = null): array
    {
        $summary = ['applied' => 0, 'skipped' => 0, 'lines' => 0, 'errors' => []];

        foreach ($this->plan($documentNo) as $plan) {
            if ($plan['status'] !== self::STATUS_READY) {
                $summary['skipped']++;
                continue;
            }

            try {
                $summary['lines'] += DB::transaction(function () use ($plan) {
                    return $this->applyDocumentPlan($plan);
                });
                $summary['applied']++;
            } catch (\Throwable $e) {
                $summary['skipped']++;
                $summary['errors'][] = $plan['document_no'] . ' : ' . $e->getMessage();
            }
        }

        return $summary;
    }

    protected function applyDocumentPlan(array $plan): int
    {
        $affectedAccounts = [];
        $rectifiedLines = 0;

        foreach ($plan['lines'] as $planLine) {
            $line = AccountJournalEntryDetail::lockForUpdate()->find($planLine['line_id']);
            if (!$line
                || (int) $line->account_id !== $planLine['generic_account_id']
                || abs((float) $line->debit - $planLine['amount']) > 0.000001) {
                throw new SauStockoutException('La ligne de journal #' . $planLine['line_id'] . ' a changé depuis l\'analyse.');
            }

            $allocations = $planLine['allocations'];
            if (empty($allocations)) {
                throw new SauStockoutException('Aucune ventilation calculée pour la ligne #' . $line->id . '.');
            }
            if (count($allocations) === 1 && $allocations[0]['account_id'] === (int) $line->account_id) {
                continue;
            }

            $genericAccountId = (int) $line->account_id;
            $baseLibelle = (string) $line->libelle;
            $first = array_shift($allocations);

            $line->account_id = $first['account_id'];
            $line->debit = $this->formatAmount($first['amount']);
            $line->libelle = $this->rectifiedLibelle($baseLibelle, $first['labels']);
            $line->save();

            foreach ($allocations as $allocation) {
                $split = $line->replicate();
                $split->account_id = $allocation['account_id'];
                $split->debit = $this->formatAmount($allocation['amount']);
                $split->libelle = $this->rectifiedLibelle($baseLibelle, $allocation['labels']);
                $split->type = self::SPLIT_TAG . ':' . $line->id;
                $split->created_at = $line->created_at;
                $split->save();
            }

            $this->ledger->update($genericAccountId, 'debit', -$planLine['amount']);
            $affectedAccounts[$genericAccountId] = true;
            foreach (array_merge([$first], $allocations) as $allocation) {
                $this->ledger->update($allocation['account_id'], 'debit', $allocation['amount']);
                $affectedAccounts[$allocation['account_id']] = true;
            }

            $rectifiedLines++;
        }

        foreach (array_keys($affectedAccounts) as $accountId) {
            $this->recomputeRunningBalances((int) $accountId);
        }

        return $rectifiedLines;
    }

    /**
     * Recalcule le solde progressif (colonne balance) des lignes d'un compte, en
     * plaçant chaque ligne ventilée juste après sa ligne d'origine. Le solde final
     * est aligné sur le grand livre (account_ledgers).
     */
    public function recomputeRunningBalances(int $accountId): void
    {
        $rows = DB::table('account_journal_entry_details')
            ->where('account_id', $accountId)
            ->select('id', 'debit', 'credit', 'balance', 'type')
            ->get()
            ->all();

        if (empty($rows)) {
            return;
        }

        $prefix = self::SPLIT_TAG . ':';
        $sortKey = function ($row) use ($prefix) {
            $type = (string) ($row->type ?? '');
            if (strpos($type, $prefix) === 0) {
                return [(int) substr($type, strlen($prefix)), 1, (int) $row->id];
            }

            return [(int) $row->id, 0, (int) $row->id];
        };
        usort($rows, function ($a, $b) use ($sortKey) {
            return $sortKey($a) <=> $sortKey($b);
        });

        $movementTotal = 0.0;
        foreach ($rows as $row) {
            $movementTotal += (float) $row->debit - (float) $row->credit;
        }

        $ledger = AccountLedger::where('account_id', $accountId)->first();
        $running = $ledger ? (float) $ledger->balance - $movementTotal : 0.0;

        foreach ($rows as $row) {
            $running += (float) $row->debit - (float) $row->credit;
            $balance = round($running, 2);
            if ($row->balance === null || abs((float) $row->balance - $balance) > 0.004) {
                DB::table('account_journal_entry_details')
                    ->where('id', $row->id)
                    ->update(['balance' => $this->formatAmount($balance)]);
            }
        }
    }

    /**
     * @param  int[]  $accountIds
     * @return array<int, string>
     */
    public function accountLabels(array $accountIds): array
    {
        return AccountAccount::whereIn('id', array_unique($accountIds))
            ->get(['id', 'account_number', 'account_name'])
            ->mapWithKeys(function ($account) {
                return [$account->id => trim($account->account_number . ' - ' . $account->account_name, ' -')];
            })
            ->all();
    }

    protected function rectifiedLibelle(string $libelle, array $labels): string
    {
        return trim($libelle . ' - ' . implode(', ', $labels), ' -');
    }

    protected function formatAmount(float $amount): string
    {
        $formatted = rtrim(rtrim(sprintf('%.6F', $amount), '0'), '.');

        return $formatted === '-0' ? '0' : $formatted;
    }
}
