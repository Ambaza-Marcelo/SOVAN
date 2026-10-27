<?php

namespace App\Http\Controllers\Backend\Concerns;

use App\Exceptions\AccountingPeriodLockedException;
use App\Services\Accounting\PeriodLockService;
use Illuminate\Support\Facades\DB;

/**
 * Contrôle explicite avant un gros traitement (approbation, clôture paie…).
 */
trait GuardsAccountingPeriod
{
    /**
     * @param  mixed  $date
     * @return bool true si bloqué (flash déjà posé)
     */
    protected function abortIfAccountingPeriodLocked($date, ?string $message = null): bool
    {
        try {
            app(PeriodLockService::class)->assertDateWritable($date);
            return false;
        } catch (\RuntimeException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            session()->flash('error', $message ?: $e->getMessage());
            return true;
        }
    }

    /**
     * Convertit une exception de période en flash + redirect back.
     */
    protected function redirectWhenPeriodLocked(AccountingPeriodLockedException $e)
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        session()->flash('error', $e->getMessage());
        return redirect()->back();
    }
}
