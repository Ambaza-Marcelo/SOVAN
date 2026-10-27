<?php

namespace App\Services\Accounting;

use App\Exceptions\AccountingPeriodLockedException;
use App\Models\Accounting\AccountExercise;
use App\Models\Accounting\AccountPeriod;
use Carbon\Carbon;

/**
 * Garde-fou écritures : refuse une date dans une période verrouillée / exercice clôturé.
 */
class PeriodLockService
{
    public function resolveExercise($date): ?AccountExercise
    {
        $day = Carbon::parse($date)->startOfDay();

        return AccountExercise::whereDate('date_start', '<=', $day)
            ->whereDate('date_end', '>=', $day)
            ->orderByDesc('is_current')
            ->first();
    }

    public function resolvePeriod($date): ?AccountPeriod
    {
        $day = Carbon::parse($date)->startOfDay();

        return AccountPeriod::whereDate('date_start', '<=', $day)
            ->whereDate('date_end', '>=', $day)
            ->first();
    }

    /**
     * @throws AccountingPeriodLockedException
     */
    public function assertDateWritable($date): void
    {
        $exercise = $this->resolveExercise($date);

        if (!$exercise) {
            // Pas encore d'exercice configuré : laisser passer
            return;
        }

        if ($exercise->isLocked() || $exercise->isClosed()) {
            throw new AccountingPeriodLockedException(
                'Exercice verrouillé/clôturé (' . $exercise->fiscal_year . ') : écriture impossible au ' .
                Carbon::parse($date)->format('d/m/Y') . '.'
            );
        }

        $period = $this->resolvePeriod($date);
        if ($period && !$period->isWritable()) {
            throw new AccountingPeriodLockedException(
                'Période verrouillée (' . $period->name . ' ' . $exercise->fiscal_year .
                ') : écriture impossible au ' . Carbon::parse($date)->format('d/m/Y') . '.'
            );
        }
    }

    public function stampMeta($date): array
    {
        $exercise = $this->resolveExercise($date);
        $period = $this->resolvePeriod($date);

        return [
            'exercise_id' => $exercise ? $exercise->id : null,
            'fiscal_year' => $exercise ? $exercise->fiscal_year : Carbon::parse($date)->format('Y'),
            'period_id' => $period ? $period->id : null,
        ];
    }
}
