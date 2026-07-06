<?php

namespace App\Http\Controllers\Backend\Concerns;

use App\Services\Accounting\LedgerTotalsService;

trait UpdatesLedgerTotals
{
    /**
     * @return array{debit: float, credit: float, balance: float}
     */
    protected function updateLedgerTotals(?int $accountId, string $type, float $amount): array
    {
        return app(LedgerTotalsService::class)->update($accountId, $type, $amount);
    }
}
