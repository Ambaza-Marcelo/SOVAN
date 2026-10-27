<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountClosingRun;
use App\Models\Accounting\AccountExercise;
use App\Models\Accounting\AccountJournalEntry;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountPeriod;
use App\Models\Accounting\AccountSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Clôture comptable automatique d'un exercice.
 *
 * Étapes prévues :
 * 1. Préconditions (exercice ouvert, comptes résultat renseignés, périodes verrouillées)
 * 2. Calcul soldes classes 6 (charges) et 7 (produits) sur la période
 * 3. Génération pièce CLOSING → compte de résultat
 * 4. Marquage exercice clôturé + création exercice N+1 + soldes d'ouverture (hors 6/7)
 */
class YearClosingService
{
    /** @var PeriodLockService */
    protected $periodLock;

    public function __construct(PeriodLockService $periodLock)
    {
        $this->periodLock = $periodLock;
    }

    /**
     * Crée les 12 périodes mensuelles d'un exercice (si absentes).
     */
    public function ensureMonthlyPeriods(AccountExercise $exercise): void
    {
        if ($exercise->periods()->exists()) {
            return;
        }

        $start = Carbon::parse($exercise->date_start)->startOfMonth();
        $end = Carbon::parse($exercise->date_end);
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        $periodNo = 1;
        $cursor = $start->copy();
        while ($cursor->lte($end) && $periodNo <= 12) {
            $periodStart = $cursor->copy()->startOfMonth();
            $periodEnd = $cursor->copy()->endOfMonth();
            if ($periodStart->lt($exercise->date_start)) {
                $periodStart = Carbon::parse($exercise->date_start);
            }
            if ($periodEnd->gt($exercise->date_end)) {
                $periodEnd = Carbon::parse($exercise->date_end);
            }

            AccountPeriod::create([
                'exercise_id' => $exercise->id,
                'period_no' => $periodNo,
                'name' => $months[(int) $cursor->month] ?? ('P' . $periodNo),
                'date_start' => $periodStart->toDateString(),
                'date_end' => $periodEnd->toDateString(),
                'status' => AccountPeriod::STATUS_OPEN,
            ]);

            $cursor->addMonth();
            $periodNo++;
        }
    }

    /**
     * Verrouille une période mensuelle.
     */
    public function lockPeriod(AccountPeriod $period, string $by): AccountClosingRun
    {
        if ((int) $period->status !== AccountPeriod::STATUS_OPEN) {
            throw new RuntimeException('Cette période est déjà verrouillée ou clôturée.');
        }

        $period->status = AccountPeriod::STATUS_LOCKED;
        $period->locked_by = $by;
        $period->locked_at = now();
        $period->save();

        return AccountClosingRun::create([
            'exercise_id' => $period->exercise_id,
            'period_id' => $period->id,
            'type' => AccountClosingRun::TYPE_PERIOD_LOCK,
            'status' => AccountClosingRun::STATUS_DONE,
            'notes' => 'Verrouillage période ' . $period->name,
            'created_by' => $by,
        ]);
    }

    /**
     * Prévisualisation des soldes 6/7 avant clôture (sans écriture).
     */
    public function previewClosing(AccountExercise $exercise): array
    {
        $balances = $this->computeClass67Balances($exercise);
        $charges = 0.0;
        $produits = 0.0;

        foreach ($balances as $row) {
            if (strpos((string) $row['account_number'], '6') === 0) {
                $charges += $row['balance'];
            }
            if (strpos((string) $row['account_number'], '7') === 0) {
                $produits += $row['balance'];
            }
        }

        $resultat = $produits - $charges;

        return [
            'charges' => $charges,
            'produits' => $produits,
            'resultat' => $resultat,
            'lines' => $balances,
            'profit_account_id' => $this->resolveProfitAccountId($exercise),
            'loss_account_id' => $this->resolveLossAccountId($exercise),
        ];
    }

    /**
     * Exécute la clôture automatique de l'exercice.
     */
    public function closeYear(AccountExercise $exercise, string $by, bool $createNext = true): AccountClosingRun
    {
        if ($exercise->isClosed()) {
            throw new RuntimeException('Cet exercice est déjà clôturé.');
        }

        $this->ensureMonthlyPeriods($exercise);

        $openPeriods = $exercise->periods()->where('status', AccountPeriod::STATUS_OPEN)->count();
        if ($openPeriods > 0) {
            // Auto-lock remaining periods before year close
            foreach ($exercise->periods()->where('status', AccountPeriod::STATUS_OPEN)->get() as $period) {
                $this->lockPeriod($period, $by);
            }
        }

        $profitAccountId = $this->resolveProfitAccountId($exercise);
        $lossAccountId = $this->resolveLossAccountId($exercise);

        if (!$profitAccountId && !$lossAccountId) {
            throw new RuntimeException(
                'Configurez les comptes de résultat (bénéfice / perte) sur l\'exercice ou dans les paramètres comptables.'
            );
        }

        $preview = $this->previewClosing($exercise);
        $run = AccountClosingRun::create([
            'exercise_id' => $exercise->id,
            'type' => AccountClosingRun::TYPE_YEAR_CLOSE,
            'status' => AccountClosingRun::STATUS_PENDING,
            'notes' => 'Clôture automatique exercice ' . $exercise->fiscal_year,
            'snapshot' => $preview,
            'created_by' => $by,
        ]);

        try {
            DB::beginTransaction();

            $documentNo = 'CLO' . $exercise->fiscal_year . '-' . str_pad((string) $run->id, 4, '0', STR_PAD_LEFT);
            $journalId = $this->postClosingJournal($exercise, $preview, $documentNo, $by, $profitAccountId, $lossAccountId);

            $ranAccountId = $this->resolveRetainedEarningsAccountId($exercise);
            if ($ranAccountId && abs((float) $preview['resultat']) >= 0.00001) {
                $this->postRetainedEarningsTransfer(
                    $exercise,
                    (float) $preview['resultat'],
                    $documentNo . '-RAN',
                    $by,
                    $profitAccountId,
                    $lossAccountId,
                    (int) $ranAccountId
                );
            }

            $exercise->periods()->update(['status' => AccountPeriod::STATUS_CLOSED]);
            $exercise->status = AccountExercise::STATUS_CLOSED;
            $exercise->is_current = false;
            $exercise->closed_by = $by;
            $exercise->closed_at = now();
            $exercise->save();

            if ($createNext) {
                $next = $this->createNextExercise($exercise, $by);
                $this->postOpeningBalances($exercise, $next, $by);
            }

            $run->status = AccountClosingRun::STATUS_DONE;
            $run->journal_entry_id = $journalId;
            $run->document_no = $documentNo;
            $run->save();

            DB::commit();
            return $run->fresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            $run->status = AccountClosingRun::STATUS_FAILED;
            $run->notes = ($run->notes ? $run->notes . ' | ' : '') . $e->getMessage();
            $run->save();
            throw $e;
        }
    }

    /**
     * Soldes nets des comptes 6* et 7* sur l'exercice (depuis les écritures).
     */
    public function computeClass67Balances(AccountExercise $exercise): array
    {
        $rows = AccountJournalEntryDetail::query()
            ->select(
                'account_journal_entry_details.account_id',
                'account_accounts.account_number',
                'account_accounts.account_name',
                DB::raw('COALESCE(SUM(account_journal_entry_details.debit),0) as total_debit'),
                DB::raw('COALESCE(SUM(account_journal_entry_details.credit),0) as total_credit')
            )
            ->join('account_accounts', 'account_accounts.id', '=', 'account_journal_entry_details.account_id')
            ->whereDate('account_journal_entry_details.date', '>=', $exercise->date_start)
            ->whereDate('account_journal_entry_details.date', '<=', $exercise->date_end)
            ->where(function ($q) {
                $q->where('account_accounts.account_number', 'like', '6%')
                    ->orWhere('account_accounts.account_number', 'like', '7%');
            })
            ->groupBy(
                'account_journal_entry_details.account_id',
                'account_accounts.account_number',
                'account_accounts.account_name'
            )
            ->orderBy('account_accounts.account_number')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $debit = (float) $row->total_debit;
            $credit = (float) $row->total_credit;
            $balance = $debit - $credit; // convention débit − crédit
            if (abs($balance) < 0.00001) {
                continue;
            }
            $out[] = [
                'account_id' => (int) $row->account_id,
                'account_number' => $row->account_number,
                'account_name' => $row->account_name,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
            ];
        }

        return $out;
    }

    protected function postClosingJournal(
        AccountExercise $exercise,
        array $preview,
        string $documentNo,
        string $by,
        $profitAccountId,
        $lossAccountId
    ): ?int {
        $resultat = (float) $preview['resultat'];
        $lines = $preview['lines'];
        if (empty($lines) && abs($resultat) < 0.00001) {
            return null;
        }

        $header = new AccountJournalEntry();
        $header->date = $exercise->date_end;
        $header->type = 'CLOSING';
        $header->fiscal_year = $exercise->fiscal_year;
        $header->active = 1;
        $header->profit_account_id = $profitAccountId;
        $header->loss_account_id = $lossAccountId;
        if (\Illuminate\Support\Facades\Schema::hasColumn($header->getTable(), 'exercise_id')) {
            $header->exercise_id = $exercise->id;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn($header->getTable(), 'created_by')) {
            $header->created_by = $by;
        }
        $header->save();

        $details = [];
        $now = now();

        foreach ($lines as $line) {
            $balance = (float) $line['balance'];
            $isCharge = strpos((string) $line['account_number'], '6') === 0;

            // Soldes débiteurs (charges) → crédit pour soldage
            // Soldes créditeurs (produits, balance négative) → débit pour soldage
            if ($balance > 0) {
                $details[] = [
                    'date' => $exercise->date_end->format('Y-m-d'),
                    'document_no' => $documentNo,
                    'description' => 'Clôture exercice ' . $exercise->fiscal_year,
                    'account_id' => $line['account_id'],
                    'libelle' => 'Soldage ' . $line['account_number'] . ' - ' . $line['account_name'],
                    'debit' => 0,
                    'credit' => $balance,
                    'balance' => 0,
                    'fiscal_year' => $exercise->fiscal_year,
                    'exercise_id' => $exercise->id,
                    'created_at' => $now,
                ];
            } else {
                $details[] = [
                    'date' => $exercise->date_end->format('Y-m-d'),
                    'document_no' => $documentNo,
                    'description' => 'Clôture exercice ' . $exercise->fiscal_year,
                    'account_id' => $line['account_id'],
                    'libelle' => 'Soldage ' . $line['account_number'] . ' - ' . $line['account_name'],
                    'debit' => abs($balance),
                    'credit' => 0,
                    'balance' => 0,
                    'fiscal_year' => $exercise->fiscal_year,
                    'exercise_id' => $exercise->id,
                    'created_at' => $now,
                ];
            }

            $this->updateLedgerTotals((int) $line['account_id'], $balance > 0 ? 'credit' : 'debit', abs($balance));
            unset($isCharge);
        }

        // Contrepartie résultat
        if ($resultat >= 0 && $profitAccountId) {
            // Bénéfice : crédit compte résultat
            $details[] = [
                'date' => $exercise->date_end->format('Y-m-d'),
                'document_no' => $documentNo,
                'description' => 'Clôture exercice ' . $exercise->fiscal_year,
                'account_id' => $profitAccountId,
                'libelle' => 'Résultat bénéficiaire ' . $exercise->fiscal_year,
                'debit' => 0,
                'credit' => abs($resultat),
                'balance' => 0,
                'fiscal_year' => $exercise->fiscal_year,
                'exercise_id' => $exercise->id,
                'created_at' => $now,
            ];
            $this->updateLedgerTotals((int) $profitAccountId, 'credit', abs($resultat));
        } elseif ($resultat < 0 && $lossAccountId) {
            $details[] = [
                'date' => $exercise->date_end->format('Y-m-d'),
                'document_no' => $documentNo,
                'description' => 'Clôture exercice ' . $exercise->fiscal_year,
                'account_id' => $lossAccountId,
                'libelle' => 'Résultat déficitaire ' . $exercise->fiscal_year,
                'debit' => abs($resultat),
                'credit' => 0,
                'balance' => 0,
                'fiscal_year' => $exercise->fiscal_year,
                'exercise_id' => $exercise->id,
                'created_at' => $now,
            ];
            $this->updateLedgerTotals((int) $lossAccountId, 'debit', abs($resultat));
        } elseif ($resultat < 0 && $profitAccountId) {
            // Fallback : même compte résultat en débit
            $details[] = [
                'date' => $exercise->date_end->format('Y-m-d'),
                'document_no' => $documentNo,
                'description' => 'Clôture exercice ' . $exercise->fiscal_year,
                'account_id' => $profitAccountId,
                'libelle' => 'Résultat déficitaire ' . $exercise->fiscal_year,
                'debit' => abs($resultat),
                'credit' => 0,
                'balance' => 0,
                'fiscal_year' => $exercise->fiscal_year,
                'exercise_id' => $exercise->id,
                'created_at' => $now,
            ];
            $this->updateLedgerTotals((int) $profitAccountId, 'debit', abs($resultat));
        }

        if (!empty($details)) {
            app(JournalPostingService::class)->withoutLock(function () use ($details) {
                AccountJournalEntryDetail::insert($details);
            });
        }

        return $header->id;
    }

    protected function createNextExercise(AccountExercise $closed, string $by): AccountExercise
    {
        $nextYear = (string) ((int) $closed->fiscal_year + 1);
        $existing = AccountExercise::where('fiscal_year', $nextYear)->first();
        if ($existing) {
            $existing->is_current = true;
            if ($existing->status === AccountExercise::STATUS_DRAFT) {
                $existing->status = AccountExercise::STATUS_OPEN;
            }
            $existing->save();
            $this->ensureMonthlyPeriods($existing);
            return $existing;
        }

        $next = AccountExercise::create([
            'code' => 'EX' . $nextYear,
            'name' => 'Exercice ' . $nextYear,
            'fiscal_year' => $nextYear,
            'date_start' => Carbon::parse($closed->date_end)->addDay()->toDateString(),
            'date_end' => Carbon::parse($closed->date_end)->addYear()->toDateString(),
            'status' => AccountExercise::STATUS_OPEN,
            'is_current' => true,
            'profit_account_id' => $closed->profit_account_id,
            'loss_account_id' => $closed->loss_account_id,
            'retained_earnings_account_id' => $closed->retained_earnings_account_id,
            'created_by' => $by,
        ]);

        $this->ensureMonthlyPeriods($next);

        $setting = AccountSetting::where('active', 1)->first();
        if ($setting) {
            $setting->fiscal_year = $nextYear;
            $setting->exercise_id = $next->id;
            $setting->save();
        }

        return $next;
    }

    protected function resolveProfitAccountId(AccountExercise $exercise)
    {
        if ($exercise->profit_account_id) {
            return $exercise->profit_account_id;
        }
        $setting = AccountSetting::where('active', 1)->first();
        return $setting ? $setting->profit_account_id : null;
    }

    protected function resolveLossAccountId(AccountExercise $exercise)
    {
        if ($exercise->loss_account_id) {
            return $exercise->loss_account_id;
        }
        $setting = AccountSetting::where('active', 1)->first();
        return $setting ? $setting->loss_account_id : null;
    }

    protected function resolveRetainedEarningsAccountId(AccountExercise $exercise)
    {
        if ($exercise->retained_earnings_account_id) {
            return $exercise->retained_earnings_account_id;
        }
        $setting = AccountSetting::where('active', 1)->first();
        return $setting ? ($setting->retained_earnings_account_id ?? null) : null;
    }

    /**
     * Affectation du résultat au report à nouveau (RAN).
     * Bénéfice : Dr compte résultat / Cr RAN.
     * Perte : Dr RAN / Cr compte résultat (ou perte).
     */
    protected function postRetainedEarningsTransfer(
        AccountExercise $exercise,
        float $resultat,
        string $documentNo,
        string $by,
        $profitAccountId,
        $lossAccountId,
        int $ranAccountId
    ): void {
        $resultAccountId = $resultat >= 0
            ? ($profitAccountId ?: $lossAccountId)
            : ($lossAccountId ?: $profitAccountId);

        if (!$resultAccountId) {
            return;
        }

        $amount = abs($resultat);
        $now = now();
        $details = [];

        if ($resultat >= 0) {
            $details[] = [
                'date' => $exercise->date_end->format('Y-m-d'),
                'document_no' => $documentNo,
                'description' => 'Report à nouveau ' . $exercise->fiscal_year,
                'account_id' => $resultAccountId,
                'libelle' => 'Affectation résultat bénéficiaire → RAN',
                'debit' => $amount,
                'credit' => 0,
                'balance' => 0,
                'fiscal_year' => $exercise->fiscal_year,
                'exercise_id' => $exercise->id,
                'created_at' => $now,
            ];
            $details[] = [
                'date' => $exercise->date_end->format('Y-m-d'),
                'document_no' => $documentNo,
                'description' => 'Report à nouveau ' . $exercise->fiscal_year,
                'account_id' => $ranAccountId,
                'libelle' => 'Report à nouveau ' . $exercise->fiscal_year,
                'debit' => 0,
                'credit' => $amount,
                'balance' => 0,
                'fiscal_year' => $exercise->fiscal_year,
                'exercise_id' => $exercise->id,
                'created_at' => $now,
            ];
            $this->updateLedgerTotals((int) $resultAccountId, 'debit', $amount);
            $this->updateLedgerTotals($ranAccountId, 'credit', $amount);
        } else {
            $details[] = [
                'date' => $exercise->date_end->format('Y-m-d'),
                'document_no' => $documentNo,
                'description' => 'Report à nouveau ' . $exercise->fiscal_year,
                'account_id' => $ranAccountId,
                'libelle' => 'Report à nouveau (perte) ' . $exercise->fiscal_year,
                'debit' => $amount,
                'credit' => 0,
                'balance' => 0,
                'fiscal_year' => $exercise->fiscal_year,
                'exercise_id' => $exercise->id,
                'created_at' => $now,
            ];
            $details[] = [
                'date' => $exercise->date_end->format('Y-m-d'),
                'document_no' => $documentNo,
                'description' => 'Report à nouveau ' . $exercise->fiscal_year,
                'account_id' => $resultAccountId,
                'libelle' => 'Affectation résultat déficitaire → RAN',
                'debit' => 0,
                'credit' => $amount,
                'balance' => 0,
                'fiscal_year' => $exercise->fiscal_year,
                'exercise_id' => $exercise->id,
                'created_at' => $now,
            ];
            $this->updateLedgerTotals($ranAccountId, 'debit', $amount);
            $this->updateLedgerTotals((int) $resultAccountId, 'credit', $amount);
        }

        app(JournalPostingService::class)->withoutLock(function () use ($details) {
            AccountJournalEntryDetail::insert($details);
        });

        AccountClosingRun::create([
            'exercise_id' => $exercise->id,
            'type' => AccountClosingRun::TYPE_YEAR_CLOSE,
            'status' => AccountClosingRun::STATUS_DONE,
            'document_no' => $documentNo,
            'notes' => 'Affectation résultat → report à nouveau',
            'created_by' => $by,
        ]);
    }

    /**
     * Soldes d'ouverture N+1 : reprise des comptes hors classes 6/7
     * à la date de début de l'exercice suivant.
     */
    protected function postOpeningBalances(AccountExercise $closed, AccountExercise $next, string $by): void
    {
        $rows = AccountJournalEntryDetail::query()
            ->select(
                'account_journal_entry_details.account_id',
                'account_accounts.account_number',
                'account_accounts.account_name',
                DB::raw('COALESCE(SUM(account_journal_entry_details.debit),0) as total_debit'),
                DB::raw('COALESCE(SUM(account_journal_entry_details.credit),0) as total_credit')
            )
            ->join('account_accounts', 'account_accounts.id', '=', 'account_journal_entry_details.account_id')
            ->whereDate('account_journal_entry_details.date', '>=', $closed->date_start)
            ->whereDate('account_journal_entry_details.date', '<=', $closed->date_end)
            ->where('account_accounts.account_number', 'not like', '6%')
            ->where('account_accounts.account_number', 'not like', '7%')
            ->groupBy(
                'account_journal_entry_details.account_id',
                'account_accounts.account_number',
                'account_accounts.account_name'
            )
            ->get();

        $details = [];
        $now = now();
        $documentNo = 'ANO' . $next->fiscal_year;
        $openingDate = Carbon::parse($next->date_start)->format('Y-m-d');
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($rows as $row) {
            $balance = (float) $row->total_debit - (float) $row->total_credit;
            if (abs($balance) < 0.00001) {
                continue;
            }

            if ($balance > 0) {
                $details[] = [
                    'date' => $openingDate,
                    'document_no' => $documentNo,
                    'description' => 'À-nouveaux ' . $next->fiscal_year,
                    'account_id' => (int) $row->account_id,
                    'libelle' => 'AN ' . $row->account_number . ' - ' . $row->account_name,
                    'debit' => abs($balance),
                    'credit' => 0,
                    'balance' => 0,
                    'fiscal_year' => $next->fiscal_year,
                    'exercise_id' => $next->id,
                    'created_at' => $now,
                ];
                $totalDebit += abs($balance);
                $this->updateLedgerTotals((int) $row->account_id, 'debit', abs($balance));
            } else {
                $details[] = [
                    'date' => $openingDate,
                    'document_no' => $documentNo,
                    'description' => 'À-nouveaux ' . $next->fiscal_year,
                    'account_id' => (int) $row->account_id,
                    'libelle' => 'AN ' . $row->account_number . ' - ' . $row->account_name,
                    'debit' => 0,
                    'credit' => abs($balance),
                    'balance' => 0,
                    'fiscal_year' => $next->fiscal_year,
                    'exercise_id' => $next->id,
                    'created_at' => $now,
                ];
                $totalCredit += abs($balance);
                $this->updateLedgerTotals((int) $row->account_id, 'credit', abs($balance));
            }
        }

        if (empty($details)) {
            return;
        }

        // Équilibrage éventuel sur RAN si écart d'arrondi
        $gap = round($totalDebit - $totalCredit, 2);
        $ranId = $this->resolveRetainedEarningsAccountId($closed);
        if (abs($gap) >= 0.01 && $ranId) {
            if ($gap > 0) {
                $details[] = [
                    'date' => $openingDate,
                    'document_no' => $documentNo,
                    'description' => 'À-nouveaux ' . $next->fiscal_year,
                    'account_id' => (int) $ranId,
                    'libelle' => 'Équilibrage à-nouveaux',
                    'debit' => 0,
                    'credit' => abs($gap),
                    'balance' => 0,
                    'fiscal_year' => $next->fiscal_year,
                    'exercise_id' => $next->id,
                    'created_at' => $now,
                ];
                $this->updateLedgerTotals((int) $ranId, 'credit', abs($gap));
            } else {
                $details[] = [
                    'date' => $openingDate,
                    'document_no' => $documentNo,
                    'description' => 'À-nouveaux ' . $next->fiscal_year,
                    'account_id' => (int) $ranId,
                    'libelle' => 'Équilibrage à-nouveaux',
                    'debit' => abs($gap),
                    'credit' => 0,
                    'balance' => 0,
                    'fiscal_year' => $next->fiscal_year,
                    'exercise_id' => $next->id,
                    'created_at' => $now,
                ];
                $this->updateLedgerTotals((int) $ranId, 'debit', abs($gap));
            }
        }

        $header = new AccountJournalEntry();
        $header->date = $next->date_start;
        $header->type = 'OPENING';
        $header->fiscal_year = $next->fiscal_year;
        $header->active = 1;
        if (\Illuminate\Support\Facades\Schema::hasColumn($header->getTable(), 'exercise_id')) {
            $header->exercise_id = $next->id;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn($header->getTable(), 'created_by')) {
            $header->created_by = $by;
        }
        $header->save();

        app(JournalPostingService::class)->withoutLock(function () use ($details) {
            AccountJournalEntryDetail::insert($details);
        });

        AccountClosingRun::create([
            'exercise_id' => $next->id,
            'type' => AccountClosingRun::TYPE_OPENING_BALANCE,
            'status' => AccountClosingRun::STATUS_DONE,
            'journal_entry_id' => $header->id,
            'document_no' => $documentNo,
            'notes' => 'À-nouveaux depuis exercice ' . $closed->fiscal_year,
            'created_by' => $by,
        ]);
    }

    protected function updateLedgerTotals(int $accountId, string $type, float $amount): void
    {
        $ledger = AccountLedger::firstOrNew(['account_id' => $accountId]);
        $debit = (float) ($ledger->debit ?? 0);
        $credit = (float) ($ledger->credit ?? 0);

        if ($type === 'debit') {
            $debit += $amount;
        } else {
            $credit += $amount;
        }

        $ledger->debit = $debit;
        $ledger->credit = $credit;
        $ledger->balance = $debit - $credit;
        $ledger->save();
    }
}
