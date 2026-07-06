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
use Validator;
use PDF;
use App\Support\AccountingLedgerBuilder;
use App\Support\LedgerAccountFilter;
use App\Support\LedgerPdfBuilder;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Accounting\LedgerExport;

class AccountJournalEntryController extends Controller
{
    //
    public $user;

    /**
     * Parse optional piece / document filter (comma, semicolon or line breaks).
     *
     * @return array<int, string>
     */
    private static function parsePieceDocumentFilterTokens(?string $input): array
    {
        if ($input === null || trim($input) === '') {
            return [];
        }

        $parts = preg_split('/[\r\n,;]+/u', $input, -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        foreach ($parts as $part) {
            $t = trim($part);
            if ($t !== '') {
                $out[] = $t;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Resolve account IDs from request (parent account expands to its child accounts).
     * Returns null when no account filter is applied (all accounts).
     *
     * @return array<int>|null
     */
    private static function resolveLedgerAccountIds(Request $request): ?array
    {
        return LedgerAccountFilter::resolveAccountIds($request);
    }

    /**
     * @param  array<int>|null  $accountIds
     */
    private static function buildLedgerAccountLabel(Request $request, ?array $accountIds): string
    {
        $label = LedgerAccountFilter::buildLabel($request, $accountIds);

        if ($label !== 'Tous les comptes') {
            return $label;
        }

        $parentAccountId = $request->input('parent_account_id');
        if ($parentAccountId) {
            $parentAccount = AccountParentAccount::find($parentAccountId);
            if ($parentAccount) {
                $childCount = is_array($accountIds) ? count($accountIds) : 0;

                return trim($parentAccount->account_number . ' ' . $parentAccount->account_name)
                    . ' (' . $childCount . ' ' . ($childCount > 1 ? 'comptes enfants' : 'compte enfant') . ')';
            }
        }

        if (is_array($accountIds) && !empty($accountIds)) {
            if (count($accountIds) === 1) {
                $selectedAccount = AccountAccount::find($accountIds[0]);
                if ($selectedAccount) {
                    return $selectedAccount->account_number . ' ' . $selectedAccount->account_name;
                }
            }

            return 'Comptes sélectionnés';
        }

        return 'Tous les comptes';
    }

    /**
     * @param  array<int>|null  $accountIds
     * @return array{ledgerRows: array<int, array<string, mixed>>, accountGroups: array<int, array<string, mixed>>, grandTotalDebit: float, grandTotalCredit: float, grandBalanceDebit: float, grandBalanceCredit: float}
     */
    private static function buildLedgerRows(string $dateDebut, string $dateFin, ?array $accountIds, array $pieceTokens): array
    {
        if (is_array($accountIds) && $accountIds === []) {
            return [
                'ledgerRows' => [],
                'accountGroups' => [],
                'grandTotalDebit' => 0,
                'grandTotalCredit' => 0,
                'grandBalanceDebit' => 0,
                'grandBalanceCredit' => 0,
            ];
        }

        $query = AccountJournalEntryDetail::with('account')
            ->whereBetween('date', [$dateDebut, $dateFin]);

        if (!empty($accountIds)) {
            $query->whereIn('account_id', $accountIds);
        }

        if (!empty($pieceTokens)) {
            $query->wherePieceOrDocumentIn($pieceTokens);
        }

        $datas = $query->orderBy('date')->orderBy('id')->get();

        return AccountingLedgerBuilder::buildFromEntries(
            $datas,
            __('messages.account_undefined')
        );
    }

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * - By default shows the 100 most recent records
     * - When a period (date_debut & date_fin) is provided, shows all data in that range
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any journal entry !');
        }

        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        $query = AccountJournalEntryDetail::with('account')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if ($dateDebut && $dateFin) {
            // When a period is provided, show all data in that range (no 100‑row limit)
            $query->whereBetween('date', [$dateDebut, $dateFin]);
        } else {
            // By default, show only the 100 most recent records
            $query->limit(100);
        }

        $datas = $query->get();

        $ledgerData = AccountingLedgerBuilder::buildFromEntries(
            $datas,
            __('messages.Compte non défini')
        );

        $ledgerRows = $ledgerData['ledgerRows'];
        $accountGroups = $ledgerData['accountGroups'];
        $grandTotalDebit = $ledgerData['grandTotalDebit'];
        $grandTotalCredit = $ledgerData['grandTotalCredit'];
        $grandBalanceDebit = $ledgerData['grandBalanceDebit'];
        $grandBalanceCredit = $ledgerData['grandBalanceCredit'];

        $periodStart = $datas->whereNotNull('date')->min('date');
        $periodEnd = $datas->whereNotNull('date')->max('date');
        $periodStartFormatted = $periodStart ? Carbon::parse($periodStart)->format('d/m/Y') : '--/--/----';
        $periodEndFormatted = $periodEnd ? Carbon::parse($periodEnd)->format('d/m/Y') : '--/--/----';

        return view('backend.pages.accounting.journal_entry.index', compact(
            'datas',
            'ledgerRows',
            'accountGroups',
            'grandTotalDebit',
            'grandTotalCredit',
            'grandBalanceDebit',
            'grandBalanceCredit',
            'periodStartFormatted',
            'periodEndFormatted'
        ));
    }

    /**
     * Search parent and child accounts for the general ledger filter (Select2 AJAX).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchLedgerAccounts(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to search accounts !');
        }

        $term = trim((string) $request->get('q', ''));
        $scope = trim((string) $request->get('scope', ''));
        $results = [];

        if ($scope === '' || $scope === 'parent') {
            $parentQuery = AccountParentAccount::query()->orderBy('account_number');
            if ($term !== '') {
                $parentQuery->where(function ($query) use ($term) {
                    $query->where('account_name', 'LIKE', '%' . $term . '%')
                        ->orWhere('account_number', 'LIKE', '%' . $term . '%');
                });
            }

            foreach ($parentQuery->limit($scope === 'parent' ? 50 : 25)->get() as $parent) {
                $childCount = AccountAccount::where('acc_parent_id', $parent->id)->count();
                $results[] = [
                    'id' => 'parent-' . $parent->id,
                    'text' => '[Parent] ' . $parent->account_number . ' - ' . $parent->account_name
                        . ' (' . $childCount . ' ' . ($childCount > 1 ? 'enfants' : 'enfant') . ')',
                ];
            }
        }

        if ($scope === '' || $scope === 'child') {
            $childQuery = AccountAccount::with('accParent')->orderBy('account_number');
            if ($term !== '') {
                $childQuery->where(function ($query) use ($term) {
                    $query->where('account_name', 'LIKE', '%' . $term . '%')
                        ->orWhere('account_number', 'LIKE', '%' . $term . '%');
                });
            }

            foreach ($childQuery->limit($scope === 'child' ? 50 : 50)->get() as $account) {
                $parentLabel = $account->accParent
                    ? $account->accParent->account_number . ' - ' . $account->accParent->account_name
                    : '';

                $results[] = [
                    'id' => 'child-' . $account->id,
                    'text' => $account->account_number . ' - ' . $account->account_name
                        . ($parentLabel ? ' ← ' . $parentLabel : ''),
                ];
            }
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any journal entry !');
        }

        $currencies = Currency::orderBy('currency_name','asc')->get();
        $cost_centers = AccountCostCenter::orderBy('name','asc')->get();
        $accounts = AccountAccount::orderBy('account_number', 'asc')->get();

        return view('backend.pages.accounting.journal_entry.create', compact('currencies','cost_centers','accounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any journal entry !');
        }

        $rules = array(
                'date'  => 'required',
                'libelle.*'  => 'required',
                'document_no.*'  => 'required',
                //'cost_center_id'  => 'required',
                //'currency_id'  => 'required',
                'account_id.*' => 'required',
                'debit.*' => 'required',
                'credit.*' => 'required',
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $date = $request->date ? \Carbon\Carbon::parse($request->date) : \Carbon\Carbon::now();
            $libelle = $request->libelle;
            $document_no = $request->document_no;
            $currency_id = $request->currency_id;
            $created_by = $this->user->name;
            $active = 1;
            $account_id =$request->account_id;
            $debit = $request->debit;
            $credit = $request->credit;


            //create journal_entry
            $journal_entry = new AccountJournalEntry();
            $journal_entry->date = $date;
            $journal_entry->type = $request->type ?? 'NORMAL';
            //$journal_entry->currency_id = $currency_id;
            $journal_entry->active = $active;
            $journal_entry->save();
            $insert_data = [];
            $ledgerAdjustments = [];

            //insert details of journal_entry No.
            for( $count = 0; $count < count($account_id); $count++ ){

                $current_account_id = $account_id[$count];
                $current_debit = (float) ($debit[$count] ?? 0);
                $current_credit = (float) ($credit[$count] ?? 0);

                $data = array(
                    'date' => $date,
                    'libelle' => $libelle[$count],
                    'document_no' => $document_no[$count],
                    //'currency_id' => $currency_id,
                    'active' => $active,
                    'account_id' => $current_account_id,
                    'debit' => $current_debit,
                    'credit' => $current_credit,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;

                if (!isset($ledgerAdjustments[$current_account_id])) {
                    $ledgerAdjustments[$current_account_id] = [
                        'debit' => 0,
                        'credit' => 0,
                    ];
                }

                $ledgerAdjustments[$current_account_id]['debit'] += $current_debit;
                $ledgerAdjustments[$current_account_id]['credit'] += $current_credit;
            }

            AccountJournalEntryDetail::insert($insert_data);

            foreach ($ledgerAdjustments as $ledgerAccountId => $amounts) {
                $accountLedger = AccountLedger::firstOrNew(['account_id' => $ledgerAccountId]);

                $existingDebit = (float) ($accountLedger->debit ?? 0);
                $existingCredit = (float) ($accountLedger->credit ?? 0);

                $accountLedger->date = $date;
                $accountLedger->debit = $existingDebit + $amounts['debit'];
                $accountLedger->credit = $existingCredit + $amounts['credit'];
                $accountLedger->balance = $accountLedger->debit - $accountLedger->credit;
                $accountLedger->save();
            }

        DB::commit();
            session()->flash('success', 'cash out flow has been created !!');
            return redirect()->route('admin.journal-entry.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    /**
     * Get opening balance data for previous fiscal year
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getOpeningBalance(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.create')) {
            abort(403, 'Sorry !! You are Unauthorized to view opening balance !');
        }

        $currentYear = Carbon::now()->year;
        $previousYear = (string)($currentYear - 1);

        // Get all accounts with their opening balances from previous year
        $openingBalances = AccountLedger::where('fiscal_year', $previousYear)
            ->with('account')
            ->get()
            ->filter(function($ledger) {
                return $ledger->account !== null; // Filter out ledgers without accounts
            })
            ->groupBy('account_id')
            ->map(function ($ledgers) {
                // Get the latest ledger entry for each account (final balance)
                $latestLedger = $ledgers->sortByDesc('date')->first();
                if (!$latestLedger || !$latestLedger->account) {
                    return null;
                }
                return [
                    'account_id' => $latestLedger->account_id,
                    'debit' => (float)($latestLedger->debit ?? 0),
                    'credit' => (float)($latestLedger->credit ?? 0),
                    'balance' => (float)($latestLedger->balance ?? 0),
                    'account_number' => $latestLedger->account->account_number ?? '',
                    'account_name' => $latestLedger->account->account_name ?? '',
                ];
            })
            ->filter()
            ->values();

        // Get all accounts (in case no opening balance data exists), ordered by account_number
        $allAccounts = AccountAccount::select('id', 'account_number', 'account_name')->orderBy('account_number', 'asc')->get();

        // For OPENING type: exclude accounts whose account_number begins with 6 or 7
        $excludeOpening = function ($accountNumber) {
            $num = (string) ($accountNumber ?? '');
            if ($num === '') return true;
            $first = substr($num, 0, 1);
            return $first !== '6' && $first !== '7';
        };

        // If we have opening balances, use them; otherwise use all accounts with empty balances
        if ($openingBalances->count() > 0) {
            $accountsData = $openingBalances
                ->filter(function ($balance) use ($excludeOpening) {
                    return $excludeOpening($balance['account_number'] ?? '');
                })
                ->map(function ($balance) {
                    // Determine debit or credit based on balance
                    $debit = 0;
                    $credit = 0;
                    if ($balance['balance'] > 0) {
                        $debit = abs($balance['balance']);
                    } elseif ($balance['balance'] < 0) {
                        $credit = abs($balance['balance']);
                    }

                    return [
                        'account_id' => $balance['account_id'],
                        'account_number' => $balance['account_number'],
                        'account_name' => $balance['account_name'],
                        'account_display' => $balance['account_number'] . '-' . $balance['account_name'],
                        'debit' => $debit,
                        'credit' => $credit,
                    ];
                })
                ->sortBy('account_number')
                ->values();
        } else {
            // No opening balance data - return all accounts with empty values (excluding 6 and 7)
            $accountsData = $allAccounts
                ->filter(function ($account) use ($excludeOpening) {
                    return $excludeOpening($account->account_number ?? '');
                })
                ->map(function ($account) {
                    return [
                        'account_id' => $account->id,
                        'account_number' => $account->account_number,
                        'account_name' => $account->account_name,
                        'account_display' => $account->account_number . '-' . $account->account_name,
                        'debit' => 0,
                        'credit' => 0,
                    ];
                })
                ->values();
        }

        return response()->json([
            'success' => true,
            'data' => $accountsData,
            'previous_year' => $previousYear,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any journal entry !');
        }

        // Get journal entry details by date (grouped entries)
        $journalEntryDetail = AccountJournalEntryDetail::findOrFail($id);
        $date = $journalEntryDetail->date;
        
        // Get all details for this date
        $details = AccountJournalEntryDetail::where('date', $date)
            ->with('account')
            ->get();
        
        // Get the journal entry if exists
        $journalEntry = AccountJournalEntry::where('date', $date)->first();
        
        $currencies = Currency::orderBy('currency_name','asc')->get();
        $cost_centers = AccountCostCenter::orderBy('name','asc')->get();
        $accounts = AccountAccount::orderBy('account_number', 'asc')->get();

        return view('backend.pages.accounting.journal_entry.edit', compact('details', 'journalEntry', 'currencies', 'cost_centers', 'accounts', 'date'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to update any journal entry !');
        }

        $rules = array(
            'date'  => 'required',
            'libelle.*'  => 'required',
            'document_no.*'  => 'required',
            'account_id.*' => 'required',
            'debit.*' => 'required',
            'credit.*' => 'required',
        );

        $error = Validator::make($request->all(),$rules);

        if($error->fails()){
            return response()->json([
                'error' => $error->errors()->all(),
            ]);
        }

        try {
            DB::beginTransaction();

            $journalEntryDetail = AccountJournalEntryDetail::findOrFail($id);
            $oldDate = $journalEntryDetail->date;
            
            $date = $request->date ? \Carbon\Carbon::parse($request->date) : \Carbon\Carbon::now();
            $libelle = $request->libelle;
            $document_no = $request->document_no;
            $account_id = $request->account_id;
            $debit = $request->debit;
            $credit = $request->credit;

            // Delete old details for this date
            $oldDetails = AccountJournalEntryDetail::where('date', $oldDate)->get();
            $oldLedgerAdjustments = [];
            
            foreach ($oldDetails as $oldDetail) {
                $oldAccountId = $oldDetail->account_id;
                $oldDebit = (float)($oldDetail->debit ?? 0);
                $oldCredit = (float)($oldDetail->credit ?? 0);
                
                if (!isset($oldLedgerAdjustments[$oldAccountId])) {
                    $oldLedgerAdjustments[$oldAccountId] = [
                        'debit' => 0,
                        'credit' => 0,
                    ];
                }
                
                $oldLedgerAdjustments[$oldAccountId]['debit'] += $oldDebit;
                $oldLedgerAdjustments[$oldAccountId]['credit'] += $oldCredit;
            }
            
            // Reverse old ledger adjustments
            foreach ($oldLedgerAdjustments as $ledgerAccountId => $amounts) {
                $accountLedger = AccountLedger::where('account_id', $ledgerAccountId)->first();
                if ($accountLedger) {
                    $existingDebit = (float) ($accountLedger->debit ?? 0);
                    $existingCredit = (float) ($accountLedger->credit ?? 0);
                    
                    $accountLedger->debit = max(0, $existingDebit - $amounts['debit']);
                    $accountLedger->credit = max(0, $existingCredit - $amounts['credit']);
                    $accountLedger->balance = $accountLedger->debit - $accountLedger->credit;
                    $accountLedger->save();
                }
            }
            
            // Delete old details
            AccountJournalEntryDetail::where('date', $oldDate)->delete();
            
            // Delete old journal entry if exists
            AccountJournalEntry::where('date', $oldDate)->delete();

            // Create new journal entry
            $journal_entry = new AccountJournalEntry();
            $journal_entry->date = $date;
            $journal_entry->type = $request->type ?? 'NORMAL';
            $journal_entry->active = 1;
            $journal_entry->save();
            
            $insert_data = [];
            $ledgerAdjustments = [];

            // Insert new details
            for($count = 0; $count < count($account_id); $count++){
                $current_account_id = $account_id[$count];
                $current_debit = (float) ($debit[$count] ?? 0);
                $current_credit = (float) ($credit[$count] ?? 0);

                $data = array(
                    'date' => $date,
                    'libelle' => $libelle[$count],
                    'document_no' => $document_no[$count],
                    'active' => 1,
                    'account_id' => $current_account_id,
                    'debit' => $current_debit,
                    'credit' => $current_credit,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;

                if (!isset($ledgerAdjustments[$current_account_id])) {
                    $ledgerAdjustments[$current_account_id] = [
                        'debit' => 0,
                        'credit' => 0,
                    ];
                }

                $ledgerAdjustments[$current_account_id]['debit'] += $current_debit;
                $ledgerAdjustments[$current_account_id]['credit'] += $current_credit;
            }

            AccountJournalEntryDetail::insert($insert_data);

            // Update ledger with new adjustments
            foreach ($ledgerAdjustments as $ledgerAccountId => $amounts) {
                $accountLedger = AccountLedger::firstOrNew(['account_id' => $ledgerAccountId]);

                $existingDebit = (float) ($accountLedger->debit ?? 0);
                $existingCredit = (float) ($accountLedger->credit ?? 0);

                $accountLedger->date = $date;
                $accountLedger->debit = $existingDebit + $amounts['debit'];
                $accountLedger->credit = $existingCredit + $amounts['credit'];
                $accountLedger->balance = $accountLedger->debit - $accountLedger->credit;
                $accountLedger->save();
            }

            DB::commit();
            session()->flash('success', 'Journal entry has been updated !!');
            return redirect()->route('admin.journal-entry.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Print general ledger for a specific period and account
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function print(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to print general ledger !');
        }
        $date_debut = $request->input('date_debut');
        $date_fin = $request->input('date_fin');
        $account_ids = self::resolveLedgerAccountIds($request);
        $pieceTokens = self::parsePieceDocumentFilterTokens($request->input('piece_document_filter'));

        $ledgerData = self::buildLedgerRows($date_debut, $date_fin, $account_ids, $pieceTokens);
        $ledgerRows = $ledgerData['ledgerRows'];
        $accountGroups = $ledgerData['accountGroups'];
        $grandTotalDebit = $ledgerData['grandTotalDebit'];
        $grandTotalCredit = $ledgerData['grandTotalCredit'];
        $grandBalanceDebit = $ledgerData['grandBalanceDebit'];
        $grandBalanceCredit = $ledgerData['grandBalanceCredit'];

        $periodStartFormatted = $date_debut ? Carbon::parse($date_debut)->format('d/m/Y') : '--/--/----';
        $periodEndFormatted = $date_fin ? Carbon::parse($date_fin)->format('d/m/Y') : '--/--/----';
        $accountLabel = self::buildLedgerAccountLabel($request, $account_ids);

        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $pdfRenderRows = LedgerPdfBuilder::buildRenderRows($accountGroups);

        $pdf = PDF::loadView('backend.pages.accounting.journal_entry.print', compact(
            'ledgerRows', 
            'accountGroups',
            'pdfRenderRows',
            'grandTotalDebit', 
            'grandTotalCredit', 
            'grandBalanceDebit',
            'grandBalanceCredit',
            'periodStartFormatted', 
            'periodEndFormatted',
            'accountLabel',
            'setting'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('GENERAL_LEDGER_' . str_replace(' ', '_', $accountLabel) . '_' . $periodStartFormatted . '_' . $periodEndFormatted . '.pdf');
    }

    /**
     * Export general ledger to Excel for a specific period and (optionally) selected accounts
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export general ledger !');
        }

        $date_debut = $request->input('date_debut');
        $date_fin = $request->input('date_fin');
        $account_ids = self::resolveLedgerAccountIds($request);
        $accountLabel = str_replace(' ', '_', self::buildLedgerAccountLabel($request, $account_ids));

        $periodStartFormatted = $date_debut ? Carbon::parse($date_debut)->format('d_m_Y') : '--_--_----';
        $periodEndFormatted = $date_fin ? Carbon::parse($date_fin)->format('d_m_Y') : '--_--_----';

        $pieceTokens = self::parsePieceDocumentFilterTokens($request->input('piece_document_filter'));

        return Excel::download(
            new LedgerExport($date_debut, $date_fin, $account_ids, $pieceTokens),
            'GENERAL_LEDGER_' . str_replace(' ', '_', $accountLabel) . '_' . $periodStartFormatted . '_' . $periodEndFormatted . '.xlsx'
        );
    }
    
}
