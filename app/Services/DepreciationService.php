<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialDepreciationPosting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Calcul d'amortissement linéaire selon la convention comptable française
 * des 360 jours (12 mois × 30 jours).
 *
 * Prépare aussi les écritures de clôture (dotation annuelle) :
 * {@see buildClosingCandidates()} / {@see markPostingDraft()}.
 */
class DepreciationService
{
    /**
     * Génère le plan d'amortissement linéaire d'un matériel.
     *
     * @return array{
     *     base_amortissable: float,
     *     taux: float,
     *     duration_years: int,
     *     service_start_date: string|null,
     *     amortissements_cumules: float,
     *     vnc: float,
     *     progression_pct: float,
     *     schedule: array<int, array<string, mixed>>
     * }
     */
    public function generateLinearSchedule(Material $material): array
    {
        if (!$material->is_amortizable) {
            return $this->emptyResult($material);
        }

        // Plan incomplet : pas encore de date / durée (souvent renseignées à l'approbation)
        if (empty($material->service_start_date) || empty($material->duration_years)) {
            return $this->emptyResult($material);
        }

        $depreciationBase = round((float) ($material->depreciation_base ?? 0), 2);
        $residualValue = round((float) ($material->residual_value ?? 0), 2);
        $baseAmortissable = round(max(0, $depreciationBase - $residualValue), 2);
        $durationYears = max(1, (int) $material->duration_years);
        $taux = round(100 / $durationYears, 6);

        if ($baseAmortissable <= 0) {
            return [
                'base_amortissable' => 0.0,
                'taux' => $taux,
                'duration_years' => $durationYears,
                'service_start_date' => optional($material->service_start_date)->format('Y-m-d'),
                'amortissements_cumules' => 0.0,
                'vnc' => 0.0,
                'progression_pct' => 100.0,
                'schedule' => [],
            ];
        }

        $start = $material->service_start_date instanceof Carbon
            ? $material->service_start_date->copy()->startOfDay()
            : Carbon::parse($material->service_start_date)->startOfDay();

        // Convention 360 jours : plafonner le jour de départ à 30
        $startDay = min(30, (int) $start->day);
        $startMonth = (int) $start->month;

        // Prorata année 1 : Jours = ((12 - Mois) * 30) + (30 - Jour + 1)
        $daysYear1 = ((12 - $startMonth) * 30) + (30 - $startDay + 1);
        $daysYear1 = min(360, max(0, $daysYear1));

        $totalDays = $durationYears * 360;
        $annualChargeFull = $baseAmortissable / $durationYears;

        $schedule = [];
        $cumulative = 0.0;
        $remainingDays = $totalDays;
        $year = (int) $start->year;
        $isFirstYear = true;

        while ($remainingDays > 0) {
            if ($isFirstYear) {
                $days = $daysYear1 > 0 ? $daysYear1 : min(360, $remainingDays);
                $isFirstYear = false;
            } else {
                $days = min(360, $remainingDays);
            }

            $remainingDays -= $days;
            $isLastYear = ($remainingDays <= 0);

            // Dernière année : solde par soustraction pour VNC = 0,00 (aucun écart de centimes)
            if ($isLastYear) {
                $dotation = round($baseAmortissable - $cumulative, 2);
            } else {
                $dotation = round($annualChargeFull * ($days / 360), 2);
            }

            $cumulative = round($cumulative + $dotation, 2);
            $vnc = $isLastYear ? 0.0 : round($baseAmortissable - $cumulative, 2);

            if ($isLastYear) {
                $cumulative = $baseAmortissable;
            }

            $schedule[] = [
                'year' => $year,
                'base_amortissable' => $baseAmortissable,
                'taux' => $taux,
                'days' => $days,
                'is_prorata' => ($days < 360),
                'dotation' => $dotation,
                'charge' => $dotation,
                'cumulative' => $cumulative,
                'vnc' => $vnc,
                'is_current_year' => ($year === (int) date('Y')),
            ];

            $year++;
        }

        $currentCumulative = $this->cumulativeAtCivilYear($schedule, (int) date('Y'));
        $currentVnc = round(max(0, $baseAmortissable - $currentCumulative), 2);
        $progression = $baseAmortissable > 0
            ? round(($currentCumulative / $baseAmortissable) * 100, 2)
            : 0.0;

        return [
            'base_amortissable' => $baseAmortissable,
            'taux' => $taux,
            'duration_years' => $durationYears,
            'service_start_date' => $start->format('Y-m-d'),
            'amortissements_cumules' => $currentCumulative,
            'vnc' => $currentVnc,
            'progression_pct' => min(100.0, $progression),
            'schedule' => $schedule,
        ];
    }

    /**
     * Dotation théorique d'un exercice civil pour un article.
     */
    public function chargeForFiscalYear(Material $material, int $fiscalYear): ?array
    {
        try {
            $schedule = $this->generateLinearSchedule($material);
        } catch (InvalidArgumentException $e) {
            return null;
        }

        foreach ($schedule['schedule'] as $row) {
            if ((int) $row['year'] === (int) $fiscalYear) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Prépare les candidats de clôture annuelle (écritures 681x / 28x).
     *
     * @return Collection|array[]
     */
    public function buildClosingCandidates(int $fiscalYear, ?Carbon $postingDate = null): Collection
    {
        $postingDate = $postingDate ?: Carbon::create($fiscalYear, 12, 31)->startOfDay();

        return Material::query()
            ->with([
                'depreciationExpenseAccount:id,account_number,account_name',
                'depreciationAssetAccount:id,account_number,account_name',
            ])
            ->where('is_amortizable', true)
            ->whereNotNull('service_start_date')
            ->whereNotNull('depreciation_expense_account_id')
            ->whereNotNull('depreciation_asset_account_id')
            ->orderBy('id')
            ->get()
            ->map(function (Material $material) use ($fiscalYear, $postingDate) {
                $row = $this->chargeForFiscalYear($material, $fiscalYear);
                if (!$row) {
                    return null;
                }

                $amount = $this->money($row['dotation'] ?? $row['charge'] ?? 0);
                if ($amount <= 0) {
                    return null;
                }

                $alreadyPosted = MaterialDepreciationPosting::query()
                    ->where('material_id', $material->id)
                    ->where('fiscal_year', $fiscalYear)
                    ->where('status', 'posted')
                    ->exists();

                $expenseAcc = $material->depreciationExpenseAccount;
                $assetAcc = $material->depreciationAssetAccount;

                return [
                    'material_id' => $material->id,
                    'material_name' => $material->name,
                    'material_code' => $material->code,
                    'fiscal_year' => $fiscalYear,
                    'schedule_year' => (int) $row['year'],
                    'amount' => $amount,
                    'posting_date' => $postingDate->format('Y-m-d'),
                    'debit_account_id' => (int) $material->depreciation_expense_account_id,
                    'credit_account_id' => (int) $material->depreciation_asset_account_id,
                    'debit_account_label' => $expenseAcc
                        ? ($expenseAcc->account_number.' — '.$expenseAcc->account_name)
                        : (string) $material->depreciation_expense_account_id,
                    'credit_account_label' => $assetAcc
                        ? ($assetAcc->account_number.' — '.$assetAcc->account_name)
                        : (string) $material->depreciation_asset_account_id,
                    'libelle' => sprintf(
                        'Dotation amortissement %s — %s (%s)',
                        $fiscalYear,
                        $material->name,
                        $material->code
                    ),
                    'already_posted' => $alreadyPosted,
                    'journal_lines' => [
                        [
                            'account_id' => (int) $material->depreciation_expense_account_id,
                            'debit' => $amount,
                            'credit' => 0.0,
                        ],
                        [
                            'account_id' => (int) $material->depreciation_asset_account_id,
                            'debit' => 0.0,
                            'credit' => $amount,
                        ],
                    ],
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Enregistre un brouillon de dotation (anti-doublon) avant validation clôture.
     */
    public function markPostingDraft(array $candidate, string $createdBy = 'system'): MaterialDepreciationPosting
    {
        if (empty($candidate['material_id']) || empty($candidate['fiscal_year'])) {
            throw new InvalidArgumentException('Candidat de clôture incomplet.');
        }

        return MaterialDepreciationPosting::updateOrCreate(
            [
                'material_id' => $candidate['material_id'],
                'fiscal_year' => $candidate['fiscal_year'],
                'schedule_year' => $candidate['schedule_year'] ?? $candidate['fiscal_year'],
            ],
            [
                'amount' => $this->money($candidate['amount'] ?? 0),
                'posting_date' => $candidate['posting_date'] ?? Carbon::now()->format('Y-m-d'),
                'document_no' => $candidate['document_no'] ?? null,
                'status' => 'draft',
                'posted_by' => $createdBy,
                'posted_at' => null,
            ]
        );
    }

    protected function cumulativeAtCivilYear(array $schedule, int $civilYear): float
    {
        $last = 0.0;
        foreach ($schedule as $row) {
            $last = (float) $row['cumulative'];
            if ((int) $row['year'] >= $civilYear) {
                return $last;
            }
        }

        return $last;
    }

    protected function emptyResult(Material $material): array
    {
        return [
            'base_amortissable' => 0.0,
            'taux' => 0.0,
            'duration_years' => (int) ($material->duration_years ?? 0),
            'service_start_date' => optional($material->service_start_date)->format('Y-m-d'),
            'amortissements_cumules' => 0.0,
            'vnc' => 0.0,
            'progression_pct' => 0.0,
            'schedule' => [],
        ];
    }

    protected function money($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float) $value, 2);
    }
}
