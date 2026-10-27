<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountExercise;
use App\Models\Accounting\AccountSetting;
use App\Models\MaterialDepreciationPosting;
use App\Services\DepreciationService;
use App\Services\Material\MaterialFlowOrchestrator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Clôture comptable annuelle — étape 1 : dotations d'amortissement Material.
 *
 * Flux :
 * 1. preview($year) — candidats 681 → 28 non encore postés
 * 2. executeDepreciationClosing(...) — transaction atomique :
 *    drafts → écritures journal → mark posted → enregistre AccountExercise
 */
class DepreciationClosingBridge
{
    /** @var DepreciationService */
    protected $depreciation;

    /** @var MaterialFlowOrchestrator */
    protected $flow;

    /** @var YearEndClosingService */
    protected $yearEnd;

    public function __construct(
        DepreciationService $depreciation,
        MaterialFlowOrchestrator $flow,
        YearEndClosingService $yearEnd
    ) {
        $this->depreciation = $depreciation;
        $this->flow = $flow;
        $this->yearEnd = $yearEnd;
    }

    /**
     * @return Collection
     */
    public function preview(int $fiscalYear, ?Carbon $postingDate = null): Collection
    {
        return $this->depreciation->buildClosingCandidates($fiscalYear, $postingDate)
            ->reject(function ($candidate) {
                return !empty($candidate['already_posted']);
            })
            ->values();
    }

    /**
     * Inclut aussi les lignes déjà postées (pour l'écran de suivi).
     *
     * @return Collection
     */
    public function previewAll(int $fiscalYear, ?Carbon $postingDate = null): Collection
    {
        return $this->depreciation->buildClosingCandidates($fiscalYear, $postingDate)->values();
    }

    public function totalCharge(int $fiscalYear): float
    {
        return round((float) $this->preview($fiscalYear)->sum('amount'), 2);
    }

    /**
     * @return Collection|MaterialDepreciationPosting[]
     */
    public function prepareDrafts(int $fiscalYear, string $postedBy = 'system', ?Carbon $postingDate = null): Collection
    {
        return $this->preview($fiscalYear, $postingDate)->map(function ($candidate) use ($postedBy) {
            return $this->depreciation->markPostingDraft($candidate, $postedBy);
        });
    }

    /**
     * Exécute la clôture des dotations pour un exercice.
     *
     * @return array{
     *   exercise: AccountExercise,
     *   posted_count: int,
     *   total: float,
     *   document_no: string,
     *   candidates: Collection
     * }
     */
    public function executeDepreciationClosing(
        int $fiscalYear,
        string $closedBy,
        ?Carbon $postingDate = null,
        ?string $notes = null
    ): array {
        $postingDate = $postingDate ?: Carbon::create($fiscalYear, 12, 31)->startOfDay();

        $existing = AccountExercise::where('fiscal_year', (string) $fiscalYear)->first();
        if ($existing && $existing->isLocked()) {
            throw new RuntimeException('L\'exercice '.$fiscalYear.' est verrouillé.');
        }
        if ($existing && $existing->hasResultDone()) {
            throw new RuntimeException(
                'Le résultat de '.$fiscalYear.' est déjà clôturé : impossible d\'ajouter des amortissements.'
            );
        }
        if ($existing && $existing->hasDepreciationDone() && $this->preview($fiscalYear, $postingDate)->isEmpty()) {
            throw new RuntimeException(
                'Les amortissements de '.$fiscalYear.' sont déjà passés (aucune nouvelle dotation).'
            );
        }

        $candidates = $this->preview($fiscalYear, $postingDate);
        if ($candidates->isEmpty()) {
            throw new RuntimeException(
                'Aucune dotation d\'amortissement à passer pour '.$fiscalYear
                .' (articles amortissables sans ligne, ou déjà comptabilisées).'
            );
        }

        $documentNo = 'CLOT-AMO-'.$fiscalYear.'-'.Carbon::now()->format('YmdHis');

        DB::beginTransaction();
        try {
            $postedCount = 0;
            $total = 0.0;

            foreach ($candidates as $candidate) {
                $draft = $this->depreciation->markPostingDraft(array_merge($candidate, [
                    'document_no' => $documentNo,
                    'posting_date' => $postingDate->format('Y-m-d'),
                ]), $closedBy);

                $this->flow->postBalancedEntry(
                    $postingDate->format('Y-m-d H:i:s'),
                    $documentNo,
                    $candidate['libelle'],
                    (int) $candidate['debit_account_id'],
                    (int) $candidate['credit_account_id'],
                    (float) $candidate['amount']
                );

                $draft->status = 'posted';
                $draft->document_no = $documentNo;
                $draft->posting_date = $postingDate->format('Y-m-d');
                $draft->posted_by = $closedBy;
                $draft->posted_at = Carbon::now();
                $draft->save();

                $postedCount++;
                $total = round($total + (float) $candidate['amount'], 2);
            }

            $exercise = $this->yearEnd->ensureExercise($fiscalYear, $closedBy);
            $exercise->label = 'Exercice '.$fiscalYear;
            if (!$exercise->name) {
                $exercise->name = 'Exercice '.$fiscalYear;
            }
            if (!$exercise->hasResultDone()) {
                $exercise->closing_status = AccountExercise::CLOSING_DEPRECIATION_DONE;
            }
            $exercise->posting_date = $postingDate->format('Y-m-d');
            $exercise->document_no = $documentNo;
            $exercise->depreciation_total = round((float) ($exercise->depreciation_total ?? 0) + $total, 2);
            $exercise->depreciation_lines = (int) ($exercise->depreciation_lines ?? 0) + $postedCount;
            $exercise->notes = $notes;
            $exercise->closed_by = $closedBy;
            $exercise->closed_at = Carbon::now();
            $exercise->save();

            DB::commit();

            return [
                'exercise' => $exercise,
                'posted_count' => $postedCount,
                'total' => $total,
                'document_no' => $documentNo,
                'candidates' => $candidates,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Année fiscale active (AccountSetting) ou année civile.
     */
    public function resolveActiveFiscalYear(): int
    {
        $year = AccountSetting::where('active', 1)->value('fiscal_year');
        if ($year && preg_match('/^\d{4}$/', trim((string) $year))) {
            return (int) $year;
        }

        $current = AccountExercise::current();
        if ($current && preg_match('/^\d{4}$/', trim((string) $current->fiscal_year))) {
            return (int) $current->fiscal_year;
        }

        return (int) date('Y');
    }
}
