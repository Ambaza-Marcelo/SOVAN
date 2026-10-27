<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountExercise;
use App\Models\Accounting\AccountExerciseDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountPeriod;
use App\Models\Accounting\AccountSetting;
use App\Services\Material\MaterialFlowOrchestrator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Clôture d'exercice — étapes 2 à 4 :
 * 2. Clôture charges (6) / produits (7) vers compte de résultat (12)
 * 3. Report à nouveau (12 → 120 bénéfice / 129 perte)
 * 4. Verrouillage de période (+ option avance exercice actif)
 */
class YearEndClosingService
{
    /** @var MaterialFlowOrchestrator */
    protected $flow;

    /** @var AccountingPeriodGuard */
    protected $guard;

    /** @var YearClosingService|null */
    protected $legacyClosing;

    public function __construct(
        MaterialFlowOrchestrator $flow,
        AccountingPeriodGuard $guard,
        YearClosingService $legacyClosing
    ) {
        $this->flow = $flow;
        $this->guard = $guard;
        $this->legacyClosing = $legacyClosing;
    }

    public function activeSettings(): ?AccountSetting
    {
        return AccountSetting::where('active', 1)->first();
    }

    /**
     * @throws RuntimeException
     */
    public function requireClosingAccounts(): AccountSetting
    {
        $settings = $this->activeSettings();
        if (!$settings) {
            throw new RuntimeException('Aucun paramètre comptable actif.');
        }
        if (!$settings->resolvedResultAccountId()) {
            throw new RuntimeException(
                'Compte de résultat (12) non paramétré. Allez dans Paramètres comptes.'
            );
        }
        if (!$settings->resolvedRanProfitAccountId()) {
            throw new RuntimeException(
                'Compte report à nouveau bénéfice (120) non paramétré. Allez dans Paramètres comptes.'
            );
        }

        return $settings;
    }

    /**
     * @return array{
     *   charges: Collection,
     *   products: Collection,
     *   charges_total: float,
     *   products_total: float,
     *   net: float,
     *   is_profit: bool,
     *   result_account: ?AccountAccount,
     *   lines: Collection
     * }
     */
    public function previewResult(int $fiscalYear): array
    {
        $settings = $this->activeSettings();
        $excludeIds = array_filter([
            $settings ? $settings->resolvedResultAccountId() : null,
            $settings ? $settings->resolvedRanProfitAccountId() : null,
            $settings ? $settings->resolvedRanLossAccountId() : null,
        ]);

        $charges = $this->balancesForClassPrefix('6', $excludeIds);
        $products = $this->balancesForClassPrefix('7', $excludeIds);

        $chargeLines = $charges->map(function ($row) {
            $balance = (float) $row['balance'];
            $amount = abs($balance);
            if ($amount < 0.005) {
                return null;
            }

            return [
                'account_id' => $row['account_id'],
                'account_number' => $row['account_number'],
                'account_name' => $row['account_name'],
                'balance' => $balance,
                'amount' => round($amount, 2),
                'side' => 'charge',
                'close_debit_result' => $balance > 0,
            ];
        })->filter()->values();

        $productLines = $products->map(function ($row) {
            $balance = (float) $row['balance'];
            $amount = abs($balance);
            if ($amount < 0.005) {
                return null;
            }

            return [
                'account_id' => $row['account_id'],
                'account_number' => $row['account_number'],
                'account_name' => $row['account_name'],
                'balance' => $balance,
                'amount' => round($amount, 2),
                'side' => 'product',
                'close_debit_result' => $balance > 0,
            ];
        })->filter()->values();

        $chargesTotal = round((float) $chargeLines->sum('amount'), 2);
        $productsTotal = round((float) $productLines->sum('amount'), 2);

        $debitToResult = 0.0;
        $creditToResult = 0.0;
        foreach ($chargeLines as $line) {
            if ($line['close_debit_result']) {
                $debitToResult += $line['amount'];
            } else {
                $creditToResult += $line['amount'];
            }
        }
        foreach ($productLines as $line) {
            if ($line['close_debit_result']) {
                $debitToResult += $line['amount'];
            } else {
                $creditToResult += $line['amount'];
            }
        }

        $resultBalance = round($debitToResult - $creditToResult, 2);
        $isProfit = $resultBalance <= 0;
        $net = abs($resultBalance);

        $resultAccount = null;
        if ($settings && $settings->resolvedResultAccountId()) {
            $resultAccount = AccountAccount::find($settings->resolvedResultAccountId());
        }

        $lines = $chargeLines->concat($productLines)->values();

        return [
            'charges' => $chargeLines,
            'products' => $productLines,
            'charges_total' => $chargesTotal,
            'products_total' => $productsTotal,
            'net' => $net,
            'result_balance' => $resultBalance,
            'is_profit' => $isProfit,
            'result_account' => $resultAccount,
            'lines' => $lines,
            'fiscal_year' => $fiscalYear,
        ];
    }

    /**
     * @return array{exercise: AccountExercise, document_no: string, net: float, is_profit: bool, lines_count: int}
     */
    public function executeResultClosing(
        int $fiscalYear,
        string $closedBy,
        ?Carbon $postingDate = null,
        ?string $notes = null
    ): array {
        $this->guard->assertWritable((string) $fiscalYear);
        $settings = $this->requireClosingAccounts();
        $exercise = $this->requireExercise($fiscalYear);

        if ($exercise->hasResultDone()) {
            throw new RuntimeException('La clôture du résultat est déjà effectuée pour '.$fiscalYear.'.');
        }
        if ($exercise->isLocked()) {
            throw new RuntimeException('Exercice '.$fiscalYear.' verrouillé.');
        }

        $preview = $this->previewResult($fiscalYear);
        if ($preview['lines']->isEmpty()) {
            throw new RuntimeException(
                'Aucun solde de charges/produits à clôturer pour '.$fiscalYear.'.'
            );
        }

        $postingDate = $postingDate ?: Carbon::create($fiscalYear, 12, 31)->startOfDay();
        $documentNo = 'CLOT-RES-'.$fiscalYear.'-'.Carbon::now()->format('YmdHis');
        $resultId = (int) $settings->resolvedResultAccountId();
        $dateStr = $postingDate->format('Y-m-d H:i:s');

        DB::beginTransaction();
        try {
            $postedPairs = 0;
            foreach ($preview['lines'] as $line) {
                $amount = (float) $line['amount'];
                $libelle = sprintf(
                    'Clôture %s — %s %s',
                    $fiscalYear,
                    $line['account_number'],
                    $line['account_name']
                );

                if (!empty($line['close_debit_result'])) {
                    $this->flow->postBalancedEntry(
                        $dateStr,
                        $documentNo,
                        $libelle,
                        $resultId,
                        (int) $line['account_id'],
                        $amount
                    );
                    $this->auditDetail($exercise->id, 'result', $resultId, $documentNo, $libelle, $amount, 0, $fiscalYear);
                    $this->auditDetail($exercise->id, 'result', (int) $line['account_id'], $documentNo, $libelle, 0, $amount, $fiscalYear);
                } else {
                    $this->flow->postBalancedEntry(
                        $dateStr,
                        $documentNo,
                        $libelle,
                        (int) $line['account_id'],
                        $resultId,
                        $amount
                    );
                    $this->auditDetail($exercise->id, 'result', (int) $line['account_id'], $documentNo, $libelle, $amount, 0, $fiscalYear);
                    $this->auditDetail($exercise->id, 'result', $resultId, $documentNo, $libelle, 0, $amount, $fiscalYear);
                }
                $postedPairs++;
            }

            $exercise->closing_status = AccountExercise::CLOSING_RESULT_DONE;
            $exercise->result_document_no = $documentNo;
            $exercise->result_lines = $postedPairs;
            $exercise->result_total = $preview['net'];
            $exercise->result_is_profit = $preview['is_profit'];
            $exercise->posting_date = $postingDate->format('Y-m-d');
            if ($notes) {
                $exercise->notes = trim(($exercise->notes ? $exercise->notes."\n" : '').$notes);
            }
            $exercise->closed_by = $closedBy;
            $exercise->closed_at = Carbon::now();
            $exercise->save();

            DB::commit();

            return [
                'exercise' => $exercise->fresh(),
                'document_no' => $documentNo,
                'net' => $preview['net'],
                'is_profit' => $preview['is_profit'],
                'lines_count' => $postedPairs,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @return array{
     *   amount: float,
     *   is_profit: bool,
     *   result_balance: float,
     *   result_account: ?AccountAccount,
     *   ran_account: ?AccountAccount
     * }
     */
    public function previewRan(int $fiscalYear): array
    {
        $settings = $this->requireClosingAccounts();
        $resultId = (int) $settings->resolvedResultAccountId();
        $balance = $this->ledgerBalance($resultId);
        $isProfit = $balance <= 0;
        $amount = abs($balance);

        $ranId = $isProfit
            ? (int) $settings->resolvedRanProfitAccountId()
            : (int) $settings->resolvedRanLossAccountId();

        return [
            'amount' => round($amount, 2),
            'is_profit' => $isProfit,
            'result_balance' => round($balance, 2),
            'result_account' => AccountAccount::find($resultId),
            'ran_account' => AccountAccount::find($ranId),
            'fiscal_year' => $fiscalYear,
        ];
    }

    /**
     * @return array{exercise: AccountExercise, document_no: string, amount: float, is_profit: bool}
     */
    public function executeRanClosing(
        int $fiscalYear,
        string $closedBy,
        ?Carbon $postingDate = null,
        ?string $notes = null
    ): array {
        $this->guard->assertWritable((string) $fiscalYear);
        $settings = $this->requireClosingAccounts();
        $exercise = $this->requireExercise($fiscalYear);

        if (!$exercise->hasResultDone()) {
            throw new RuntimeException('Clôturez d\'abord le résultat (charges/produits) pour '.$fiscalYear.'.');
        }
        if ($exercise->hasRanDone()) {
            throw new RuntimeException('Le report à nouveau est déjà effectué pour '.$fiscalYear.'.');
        }
        if ($exercise->isLocked()) {
            throw new RuntimeException('Exercice '.$fiscalYear.' verrouillé.');
        }

        $preview = $this->previewRan($fiscalYear);
        if ($preview['amount'] < 0.005) {
            $exercise->closing_status = AccountExercise::CLOSING_RAN_DONE;
            $exercise->ran_total = 0;
            $exercise->ran_document_no = 'CLOT-RAN-'.$fiscalYear.'-ZERO';
            $exercise->save();

            return [
                'exercise' => $exercise->fresh(),
                'document_no' => $exercise->ran_document_no,
                'amount' => 0.0,
                'is_profit' => true,
            ];
        }

        $postingDate = $postingDate ?: Carbon::create($fiscalYear, 12, 31)->startOfDay();
        $documentNo = 'CLOT-RAN-'.$fiscalYear.'-'.Carbon::now()->format('YmdHis');
        $resultId = (int) $settings->resolvedResultAccountId();
        $ranId = $preview['is_profit']
            ? (int) $settings->resolvedRanProfitAccountId()
            : (int) $settings->resolvedRanLossAccountId();
        $amount = (float) $preview['amount'];
        $dateStr = $postingDate->format('Y-m-d H:i:s');

        $libelle = $preview['is_profit']
            ? sprintf('Report à nouveau (bénéfice) %s', $fiscalYear)
            : sprintf('Report à nouveau (perte) %s', $fiscalYear);

        DB::beginTransaction();
        try {
            if ($preview['is_profit']) {
                $this->flow->postBalancedEntry($dateStr, $documentNo, $libelle, $resultId, $ranId, $amount);
                $this->auditDetail($exercise->id, 'ran', $resultId, $documentNo, $libelle, $amount, 0, $fiscalYear);
                $this->auditDetail($exercise->id, 'ran', $ranId, $documentNo, $libelle, 0, $amount, $fiscalYear);
            } else {
                $this->flow->postBalancedEntry($dateStr, $documentNo, $libelle, $ranId, $resultId, $amount);
                $this->auditDetail($exercise->id, 'ran', $ranId, $documentNo, $libelle, $amount, 0, $fiscalYear);
                $this->auditDetail($exercise->id, 'ran', $resultId, $documentNo, $libelle, 0, $amount, $fiscalYear);
            }

            $exercise->closing_status = AccountExercise::CLOSING_RAN_DONE;
            $exercise->ran_document_no = $documentNo;
            $exercise->ran_total = $amount;
            $exercise->result_is_profit = $preview['is_profit'];
            $exercise->posting_date = $postingDate->format('Y-m-d');
            if ($notes) {
                $exercise->notes = trim(($exercise->notes ? $exercise->notes."\n" : '').$notes);
            }
            $exercise->closed_by = $closedBy;
            $exercise->closed_at = Carbon::now();
            $exercise->save();

            DB::commit();

            return [
                'exercise' => $exercise->fresh(),
                'document_no' => $documentNo,
                'amount' => $amount,
                'is_profit' => $preview['is_profit'],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Verrouille l'exercice et optionnellement avance l'année fiscale active.
     * Intègre aussi le verrouillage des périodes mensuelles MUSUMBA.
     *
     * @return array{exercise: AccountExercise, next_year: ?string}
     */
    public function lockPeriod(
        int $fiscalYear,
        string $lockedBy,
        bool $advanceFiscalYear = true,
        ?string $notes = null
    ): array {
        $exercise = $this->requireExercise($fiscalYear);

        if ($exercise->isLocked()) {
            throw new RuntimeException('Exercice '.$fiscalYear.' est déjà verrouillé.');
        }
        if (!$exercise->hasRanDone()) {
            throw new RuntimeException(
                'Effectuez le report à nouveau avant de verrouiller l\'exercice '.$fiscalYear.'.'
            );
        }

        DB::beginTransaction();
        try {
            $nextYear = (string) ($fiscalYear + 1);
            $exercise->closing_status = AccountExercise::CLOSING_LOCKED;
            $exercise->status = AccountExercise::STATUS_CLOSED;
            $exercise->is_current = false;
            $exercise->locked_at = Carbon::now();
            $exercise->locked_by = $lockedBy;
            $exercise->closed_by = $lockedBy;
            $exercise->closed_at = Carbon::now();
            $exercise->next_fiscal_year = $advanceFiscalYear ? $nextYear : null;
            if ($notes) {
                $exercise->notes = trim(($exercise->notes ? $exercise->notes."\n" : '').$notes);
            }
            $exercise->save();

            // Verrouille toutes les périodes mensuelles
            $exercise->periods()->update([
                'status' => AccountPeriod::STATUS_CLOSED,
                'locked_by' => $lockedBy,
                'locked_at' => Carbon::now(),
            ]);

            $settings = $this->activeSettings();
            if ($settings) {
                $settings->exercise_id = $exercise->id;
                if ($advanceFiscalYear) {
                    $settings->fiscal_year = $nextYear;
                    $next = AccountExercise::where('fiscal_year', $nextYear)->first();
                    if ($next) {
                        $next->is_current = true;
                        if ((int) $next->status === AccountExercise::STATUS_DRAFT) {
                            $next->status = AccountExercise::STATUS_OPEN;
                        }
                        if (empty($next->closing_status)) {
                            $next->closing_status = AccountExercise::CLOSING_OPEN;
                        }
                        $next->save();
                        $settings->exercise_id = $next->id;
                        $this->legacyClosing->ensureMonthlyPeriods($next);
                    }
                }
                $settings->save();
            }

            DB::commit();

            return [
                'exercise' => $exercise->fresh(),
                'next_year' => $advanceFiscalYear ? $nextYear : null,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assure qu'un enregistrement exercice existe (crée si besoin).
     */
    public function ensureExercise(int $fiscalYear, string $createdBy = 'system'): AccountExercise
    {
        $exercise = AccountExercise::firstOrNew(['fiscal_year' => (string) $fiscalYear]);
        if (!$exercise->exists) {
            $exercise->code = 'EX'.$fiscalYear;
            $exercise->name = 'Exercice '.$fiscalYear;
            $exercise->label = 'Exercice '.$fiscalYear;
            $exercise->date_start = $fiscalYear.'-01-01';
            $exercise->date_end = $fiscalYear.'-12-31';
            $exercise->status = AccountExercise::STATUS_OPEN;
            $exercise->closing_status = AccountExercise::CLOSING_OPEN;
            $exercise->created_by = $createdBy;
            $exercise->save();
            $this->legacyClosing->ensureMonthlyPeriods($exercise);
        } elseif (empty($exercise->closing_status)) {
            $exercise->closing_status = AccountExercise::CLOSING_OPEN;
            $exercise->save();
        }

        return $exercise;
    }

    protected function requireExercise(int $fiscalYear): AccountExercise
    {
        $exercise = AccountExercise::where('fiscal_year', (string) $fiscalYear)->first();
        if (!$exercise) {
            throw new RuntimeException(
                'Aucun exercice '.$fiscalYear.' : créez-le via Créer un exercice ou lancez d\'abord les amortissements.'
            );
        }

        return $exercise;
    }

    /**
     * @param  int[]  $excludeIds
     * @return Collection|array[]
     */
    protected function balancesForClassPrefix(string $prefix, array $excludeIds = []): Collection
    {
        $query = AccountAccount::query()
            ->where('account_number', 'like', $prefix.'%');

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->orderBy('account_number')->get()->map(function (AccountAccount $account) {
            $balance = $this->ledgerBalance((int) $account->id);
            if (abs($balance) < 0.005) {
                return null;
            }

            return [
                'account_id' => (int) $account->id,
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'balance' => round($balance, 2),
            ];
        })->filter()->values();
    }

    protected function ledgerBalance(int $accountId): float
    {
        $ledger = AccountLedger::where('account_id', $accountId)
            ->orderByDesc('id')
            ->first();

        if (!$ledger) {
            return 0.0;
        }

        return round((float) $ledger->balance, 2);
    }

    protected function auditDetail(
        int $exerciseId,
        string $step,
        int $accountId,
        string $documentNo,
        string $libelle,
        float $debit,
        float $credit,
        int $fiscalYear
    ): void {
        AccountExerciseDetail::create([
            'exercise_id' => $exerciseId,
            'step' => $step,
            'account_id' => $accountId,
            'document_no' => $documentNo,
            'libelle' => $libelle,
            'debit' => $debit,
            'credit' => $credit,
            'fiscal_year' => (string) $fiscalYear,
        ]);
    }
}
