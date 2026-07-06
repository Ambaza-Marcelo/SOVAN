<?php

namespace App\Support;

class LedgerPdfBuilder
{
    /**
     * Build continuous PDF rows (no forced page cuts).
     *
     * @param  array<int, array<string, mixed>>  $accountGroups
     * @return array<int, array<string, mixed>>
     */
    public static function buildRenderRows(array $accountGroups): array
    {
        $renderRows = [];

        foreach ($accountGroups as $group) {
            $accountParts = self::splitAccountLabel((string) ($group['account_label'] ?? ''));

            $renderRows[] = [
                'type' => 'account_header',
                'account_label' => $group['account_label'] ?? '',
                'account_number' => $accountParts['number'],
                'account_name' => $accountParts['name'],
            ];

            foreach ($group['rows'] as $entry) {
                $renderRows[] = [
                    'type' => 'entry',
                    'data' => $entry,
                ];
            }

            $renderRows[] = [
                'type' => 'account_total',
                'account_label' => $group['account_label'] ?? '',
                'account_number' => $accountParts['number'],
                'total_debit' => (float) ($group['total_debit'] ?? 0),
                'total_credit' => (float) ($group['total_credit'] ?? 0),
                'balance_debit' => (float) ($group['balance_debit'] ?? 0),
                'balance_credit' => (float) ($group['balance_credit'] ?? 0),
            ];
        }

        return $renderRows;
    }

    /**
     * @deprecated Use buildRenderRows() for a continuous table layout.
     *
     * @param  array<int, array<string, mixed>>  $accountGroups
     * @return array<int, array<string, mixed>>
     */
    public static function buildPaginatedRows(array $accountGroups, int $maxPageWeight = 26): array
    {
        return self::buildRenderRows($accountGroups);
    }

    /**
     * @return array{number: string, name: string}
     */
    public static function splitAccountLabel(string $label): array
    {
        $label = trim($label);
        if ($label === '') {
            return ['number' => '', 'name' => ''];
        }

        if (preg_match('/^(\S+)\s+(.+)$/u', $label, $matches)) {
            return [
                'number' => $matches[1],
                'name' => $matches[2],
            ];
        }

        return ['number' => '', 'name' => $label];
    }
}
