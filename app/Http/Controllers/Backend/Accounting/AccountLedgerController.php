<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountParentAccount;
use App\Models\Accounting\AccountJournalEntry;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\Currency;
use App\Models\Accounting\AccountCostCenter;
use App\Exports\Accounting\BalanceExport;
use App\Support\LedgerAccountFilter;
use Validator;
use PDF;
use Excel;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AccountLedgerController extends Controller
{
    //
    public $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }

    /**
     * Calculate balance data (refactored to avoid duplication)
     *
     * @param  array<int>|null  $accountIds
     */
    private function calculateBalanceData(?array $accountIds = null)
    {
        $ledgers = AccountLedger::with('account')->orderBy('account_id')->get();

        if (is_array($accountIds) && !empty($accountIds)) {
            $ledgers = $ledgers->whereIn('account_id', $accountIds);
        }

        $accountsData = $ledgers->groupBy('account_id')->map(function ($entries) {
            $firstEntry = $entries->first();
            $account = optional($firstEntry)->account;

            $accountNumber = trim(optional($account)->account_number ?? '');
            $accountName = trim(optional($account)->account_name ?? '');

            if ($accountNumber === '') {
                $accountNumber = __('messages.account_undefined');
            }

            $totalDebit = $entries->sum(function ($entry) {
                return (float) ($entry->debit ?? 0);
            });

            $totalCredit = $entries->sum(function ($entry) {
                return (float) ($entry->credit ?? 0);
            });

            $netBalance = $totalDebit - $totalCredit;
            $balanceDebit = $netBalance >= 0 ? $netBalance : 0;
            $balanceCredit = $netBalance < 0 ? abs($netBalance) : 0;

            return [
                'account_number' => $accountNumber,
                'account_name' => $accountName,
                'movement_debit' => $totalDebit,
                'movement_credit' => $totalCredit,
                'balance_debit' => $balanceDebit,
                'balance_credit' => $balanceCredit,
            ];
        })->sortBy(function ($row) {
            return sprintf('%s|%s', $row['account_number'], $row['account_name']);
        })->values();

        $grandTotals = [
            'movement_debit' => $accountsData->sum(function ($row) {
                return $row['movement_debit'];
            }),
            'movement_credit' => $accountsData->sum(function ($row) {
                return $row['movement_credit'];
            }),
            'balance_debit' => $accountsData->sum(function ($row) {
                return $row['balance_debit'];
            }),
            'balance_credit' => $accountsData->sum(function ($row) {
                return $row['balance_credit'];
            }),
        ];

        return [
            'accountsData' => $accountsData,
            'grandTotals' => $grandTotals
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function balance(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any balance !');
        }

        $ledgerAccountKeys = $request->input('ledger_account_keys', []);
        if (!is_array($ledgerAccountKeys)) {
            $ledgerAccountKeys = $ledgerAccountKeys ? [$ledgerAccountKeys] : [];
        }

        $ledgerAccountMode = (string) $request->input('ledger_account_mode', '');
        if ($ledgerAccountMode === '' && !empty($ledgerAccountKeys)) {
            $ledgerAccountMode = LedgerAccountFilter::detectMode($ledgerAccountKeys);
        }

        $accountIds = LedgerAccountFilter::resolveAccountIds($request);
        $balanceData = $this->calculateBalanceData($accountIds);
        $accountsData = $balanceData['accountsData'];
        $grandTotals = $balanceData['grandTotals'];

        $ledgers = AccountLedger::with('account')->orderBy('account_id')->get();
        $periodEnd = $ledgers->whereNotNull('date')->max('date');
        $fiscalYear = $periodEnd ? Carbon::parse($periodEnd)->format('Y') : Carbon::now()->format('Y');

        $reportGeneratedAt = now()->format('d/m/Y H:i');
        $companyName = config('app.name', 'Entreprise');
        $selectedLedgerAccounts = LedgerAccountFilter::buildSelectedOptions($ledgerAccountKeys);
        $accountFilterLabel = LedgerAccountFilter::buildLabel($request, $accountIds);

        return view('backend.pages.accounting.balance.index', compact(
            'accountsData',
            'grandTotals',
            'reportGeneratedAt',
            'companyName',
            'fiscalYear',
            'ledgerAccountMode',
            'ledgerAccountKeys',
            'selectedLedgerAccounts',
            'accountFilterLabel'
        ));
    }

    /**
     * Export balance to PDF
     */
    public function exportPDF(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export balance !');
        }

        $accountIds = LedgerAccountFilter::resolveAccountIds($request);
        $balanceData = $this->calculateBalanceData($accountIds);
        $accountsData = $balanceData['accountsData'];
        $grandTotals = $balanceData['grandTotals'];

        $ledgers = AccountLedger::with('account')->orderBy('account_id')->get();
        $periodEnd = $ledgers->whereNotNull('date')->max('date');
        $fiscalYear = $periodEnd ? Carbon::parse($periodEnd)->format('Y') : Carbon::now()->format('Y');

        $reportGeneratedAt = now()->format('d/m/Y H:i');
        $companyName = config('app.name', 'Entreprise');
        $accountFilterLabel = LedgerAccountFilter::buildLabel($request, $accountIds);
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();

        $pdf = PDF::setOptions([
                'defaultFont' => 'dejavu sans',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ])->loadView('backend.pages.accounting.document.balance', compact(
            'accountsData',
            'grandTotals',
            'reportGeneratedAt',
            'companyName',
            'fiscalYear',
            'accountFilterLabel',
            'setting'
        ))->setPaper('a4', 'portrait');

        $fileName = 'BALANCE_COMPTES_' . str_replace(' ', '_', $accountFilterLabel) . '_' . $fiscalYear . '_' . now()->format('YmdHis') . '.pdf';
        Storage::put('public/pdf/accounting/balance/' . $fileName, $pdf->output());

        return $pdf->download($fileName);
    }

    /**
     * Export balance to Excel
     */
    public function exportExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export balance !');
        }

        $accountIds = LedgerAccountFilter::resolveAccountIds($request);

        $ledgers = AccountLedger::with('account')->orderBy('account_id')->get();
        $periodEnd = $ledgers->whereNotNull('date')->max('date');
        $fiscalYear = $periodEnd ? Carbon::parse($periodEnd)->format('Y') : Carbon::now()->format('Y');

        $accountFilterLabel = LedgerAccountFilter::buildLabel($request, $accountIds);

        return Excel::download(
            new BalanceExport($accountIds),
            'BALANCE_COMPTES_' . str_replace(' ', '_', $accountFilterLabel) . '_' . $fiscalYear . '.xlsx'
        );
    }
}
