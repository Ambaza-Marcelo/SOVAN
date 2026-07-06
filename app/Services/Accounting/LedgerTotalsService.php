<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountLedger;
use InvalidArgumentException;

class LedgerTotalsService
{
    /**
     * Increment debit or credit on an account ledger and persist the new balance.
     *
     * @return array{debit: float, credit: float, balance: float}
     */
    public function update(?int $accountId, string $type, float $amount): array
    {
        if (is_null($accountId)) {
            throw new InvalidArgumentException('Account ID cannot be null');
        }

        if (!in_array($type, ['debit', 'credit'], true)) {
            throw new InvalidArgumentException('Ledger update type must be debit or credit');
        }

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
            'balance' => (float) $ledger->balance,
        ];
    }
}
