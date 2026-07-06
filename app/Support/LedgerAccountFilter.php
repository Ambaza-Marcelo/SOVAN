<?php

namespace App\Support;

use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountParentAccount;
use Illuminate\Http\Request;

class LedgerAccountFilter
{
    /**
     * Resolve child account IDs from ledger_account_keys (parent-X / child-X).
     * Returns null when no filter is applied (all accounts).
     *
     * @return array<int>|null
     */
    public static function resolveAccountIds(Request $request): ?array
    {
        $mode = (string) $request->input('ledger_account_mode', '');
        $keys = $request->input('ledger_account_keys', []);
        if (!is_array($keys)) {
            $keys = $keys ? [$keys] : [];
        }

        if ($mode === '' || $mode === 'all') {
            if (empty($keys)) {
                return null;
            }
        }

        if (!empty($keys)) {
            $accountIds = [];

            foreach ($keys as $key) {
                if (str_starts_with((string) $key, 'parent-')) {
                    $parentId = (int) substr($key, 7);
                    $children = AccountAccount::where('acc_parent_id', $parentId)->pluck('id')->toArray();
                    $accountIds = array_merge($accountIds, $children);
                } elseif (str_starts_with((string) $key, 'child-')) {
                    $accountIds[] = (int) substr($key, 6);
                }
            }

            return !empty($accountIds) ? array_values(array_unique(array_filter($accountIds))) : null;
        }

        return null;
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<int, array{id: string, text: string}>
     */
    public static function buildSelectedOptions(array $keys): array
    {
        $options = [];

        foreach ($keys as $key) {
            $key = (string) $key;

            if (str_starts_with($key, 'parent-')) {
                $parent = AccountParentAccount::find((int) substr($key, 7));
                if (!$parent) {
                    continue;
                }

                $childCount = AccountAccount::where('acc_parent_id', $parent->id)->count();
                $options[] = [
                    'id' => $key,
                    'text' => '[Parent] ' . $parent->account_number . ' - ' . $parent->account_name
                        . ' (' . $childCount . ' ' . ($childCount > 1 ? 'enfants' : 'enfant') . ')',
                ];
            } elseif (str_starts_with($key, 'child-')) {
                $account = AccountAccount::with('accParent')->find((int) substr($key, 6));
                if (!$account) {
                    continue;
                }

                $parentLabel = $account->accParent
                    ? $account->accParent->account_number . ' - ' . $account->accParent->account_name
                    : '';

                $options[] = [
                    'id' => $key,
                    'text' => $account->account_number . ' - ' . $account->account_name
                        . ($parentLabel ? ' ← ' . $parentLabel : ''),
                ];
            }
        }

        return $options;
    }

    /**
     * @param  array<int, string>  $keys
     */
    public static function detectMode(array $keys): string
    {
        if (empty($keys)) {
            return '';
        }

        $hasParent = false;
        $hasChild = false;

        foreach ($keys as $key) {
            if (str_starts_with((string) $key, 'parent-')) {
                $hasParent = true;
            }
            if (str_starts_with((string) $key, 'child-')) {
                $hasChild = true;
            }
        }

        if ($hasParent && !$hasChild) {
            return 'parent';
        }

        if ($hasChild && !$hasParent) {
            return 'child';
        }

        return '';
    }

    /**
     * @param  array<int>|null  $accountIds
     */
    public static function buildLabel(Request $request, ?array $accountIds): string
    {
        $keys = $request->input('ledger_account_keys', []);
        if (!is_array($keys)) {
            $keys = $keys ? [$keys] : [];
        }

        if (!empty($keys)) {
            $parentKeys = array_values(array_filter($keys, fn ($key) => str_starts_with((string) $key, 'parent-')));
            $childKeys = array_values(array_filter($keys, fn ($key) => str_starts_with((string) $key, 'child-')));

            if (count($parentKeys) > 0 && count($childKeys) === 0) {
                if (count($parentKeys) === 1) {
                    $parentAccount = AccountParentAccount::find((int) substr($parentKeys[0], 7));
                    if ($parentAccount) {
                        $childCount = is_array($accountIds) ? count($accountIds) : 0;

                        return trim($parentAccount->account_number . ' ' . $parentAccount->account_name)
                            . ' (' . $childCount . ' ' . ($childCount > 1 ? 'comptes enfants' : 'compte enfant') . ')';
                    }
                }

                $parentNumbers = [];
                foreach ($parentKeys as $parentKey) {
                    $parentAccount = AccountParentAccount::find((int) substr($parentKey, 7));
                    if ($parentAccount) {
                        $parentNumbers[] = $parentAccount->account_number;
                    }
                }

                $preview = implode(', ', array_slice($parentNumbers, 0, 4));
                if (count($parentNumbers) > 4) {
                    $preview .= '…';
                }

                return count($parentKeys) . ' comptes parents'
                    . ($preview !== '' ? ' (' . $preview . ')' : '');
            }

            if (count($childKeys) > 0 && count($parentKeys) === 0) {
                if (count($childKeys) === 1 && is_array($accountIds) && count($accountIds) === 1) {
                    $selectedAccount = AccountAccount::find($accountIds[0]);
                    if ($selectedAccount) {
                        return $selectedAccount->account_number . ' ' . $selectedAccount->account_name;
                    }
                }

                $childNumbers = [];
                foreach ($childKeys as $childKey) {
                    $childAccount = AccountAccount::find((int) substr($childKey, 6));
                    if ($childAccount) {
                        $childNumbers[] = $childAccount->account_number;
                    }
                }

                $preview = implode(', ', array_slice($childNumbers, 0, 4));
                if (count($childNumbers) > 4) {
                    $preview .= '…';
                }

                return count($childKeys) . ' comptes enfants'
                    . ($preview !== '' ? ' (' . $preview . ')' : '');
            }

            return 'Comptes sélectionnés';
        }

        return 'Tous les comptes';
    }
}
