<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class AccountingLedgerBuilder
{
    public static function operationDateSortKey($entry): string
    {
        if (!empty($entry->date)) {
            return Carbon::parse($entry->date)->format('Ymd');
        }

        return '99999999';
    }

    public static function accountSortKey($entry): string
    {
        return trim((string) (optional($entry->account)->account_number ?? 'ZZZ'));
    }

    /**
     * Display order: account number, then operation date, then id.
     */
    public static function sortByAccountAndOperationDate(Collection $entries): Collection
    {
        return $entries->sortBy(function ($entry) {
            return sprintf(
                '%s|%s|%06d',
                self::accountSortKey($entry),
                self::operationDateSortKey($entry),
                $entry->id
            );
        })->values();
    }

    /**
     * @return array{
     *     ledgerRows: array<int, array<string, mixed>>,
     *     accountGroups: array<int, array<string, mixed>>,
     *     grandTotalDebit: float,
     *     grandTotalCredit: float,
     *     grandBalanceDebit: float,
     *     grandBalanceCredit: float
     * }
     */
    public static function buildFromEntries(Collection $entries, string $undefinedAccountLabel = 'Compte non défini'): array
    {
        if ($entries->isEmpty()) {
            return [
                'ledgerRows' => [],
                'accountGroups' => [],
                'grandTotalDebit' => 0,
                'grandTotalCredit' => 0,
                'grandBalanceDebit' => 0,
                'grandBalanceCredit' => 0,
            ];
        }

        $ledgerRows = [];
        $accountGroups = [];
        $grandTotalDebit = 0;
        $grandTotalCredit = 0;

        $groupedEntries = $entries->groupBy(function ($entry) use ($undefinedAccountLabel) {
            $accountId = $entry->account_id;
            $accountNumber = self::accountSortKey($entry);
            $accountName = optional($entry->account)->account_name ?? '';
            $accountLabel = trim($accountNumber . ' ' . $accountName) ?: $undefinedAccountLabel;

            return sprintf('%s|%s', $accountNumber ?: 'ZZZ', $accountId ?? 'null') . '||' . $accountLabel;
        })->sortKeys();

        foreach ($groupedEntries as $groupKey => $accountEntries) {
            [, $accountLabel] = array_pad(explode('||', (string) $groupKey, 2), 2, $undefinedAccountLabel);
            $sortedEntries = $accountEntries->sortBy(function ($entry) {
                return sprintf('%s|%06d', self::operationDateSortKey($entry), $entry->id);
            })->values();

            $pieceNumber = 0;
            $runningBalance = 0;
            $accountTotalDebit = 0;
            $accountTotalCredit = 0;
            $groupRows = [];

            foreach ($sortedEntries as $entry) {
                $pieceNumber++;
                $debit = (float) ($entry->debit ?? 0);
                $credit = (float) ($entry->credit ?? 0);
                $runningBalance += ($debit - $credit);
                $accountTotalDebit += $debit;
                $accountTotalCredit += $credit;
                $grandTotalDebit += $debit;
                $grandTotalCredit += $credit;

                $row = [
                    'account_label' => $accountLabel,
                    'account_id' => $entry->account_id,
                    'operation_date' => $entry->date ? Carbon::parse($entry->date)->format('d/m/Y') : '--/--/----',
                    'created_at' => $entry->created_at ? Carbon::parse($entry->created_at)->format('d/m/Y H:i') : '--/--/----',
                    'date' => $entry->date ? Carbon::parse($entry->date)->format('d/m/Y') : '--/--/----',
                    'cj' => $pieceNumber,
                    'piece' => $entry->document_no ?? '',
                    'document_no' => $entry->document_no ?? '',
                    'label' => $entry->libelle ?? $entry->description ?? '',
                    'let' => $entry->description ?? '',
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance_debit' => $runningBalance > 0 ? $runningBalance : 0,
                    'balance_credit' => $runningBalance < 0 ? abs($runningBalance) : 0,
                    'is_first_of_account' => $pieceNumber === 1,
                ];

                $groupRows[] = $row;
                $ledgerRows[] = $row;
            }

            $accountGroups[] = [
                'account_label' => $accountLabel,
                'account_id' => optional($sortedEntries->first())->account_id,
                'rows' => $groupRows,
                'total_debit' => $accountTotalDebit,
                'total_credit' => $accountTotalCredit,
                'balance_debit' => $runningBalance > 0 ? $runningBalance : 0,
                'balance_credit' => $runningBalance < 0 ? abs($runningBalance) : 0,
            ];
        }

        $grandBalance = $grandTotalDebit - $grandTotalCredit;

        return [
            'ledgerRows' => $ledgerRows,
            'accountGroups' => $accountGroups,
            'grandTotalDebit' => $grandTotalDebit,
            'grandTotalCredit' => $grandTotalCredit,
            'grandBalanceDebit' => $grandBalance > 0 ? $grandBalance : 0,
            'grandBalanceCredit' => $grandBalance < 0 ? abs($grandBalance) : 0,
        ];
    }
}
