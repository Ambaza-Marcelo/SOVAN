<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Accounting\ReturnFund;
use App\Models\Accounting\CashOutFlow;
use App\Models\Accounting\CashOutFlowDetail;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\HrEmploye;
use App\Models\Supplier;
use Carbon\Carbon;
use PDF;

class ReturnFundController extends Controller
{
    public $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.view')) {
            abort(403, 'Sorry!! you are Unauthorized to view return fund');
        }

        $query = ReturnFund::with(['employe', 'account']);
        
        // Si des dates sont fournies, filtrer par période
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('created_at', [$dateDebut, $dateFin]);
        } else {
            // Par défaut, afficher les 100 lignes les plus récentes
            $query->take(100);
        }
        
        $return_funds = $query->orderBy('id', 'desc')->get();

        return view('backend.pages.accounting.return_fund.index', compact('return_funds'));
    }

    public function create($cash_outflow_no)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.create')) {
            abort(403, 'Sorry!! you are Unauthorized to create return fund');
        }

        $data = CashOutFlow::where('cash_outflow_no', $cash_outflow_no)->first();
        
        if (!$data) {
            session()->flash('error', 'Cash outflow not found');
            return redirect()->back();
        }

        $total_amount_picked = DB::table('cash_outflow_details')
            ->where('cash_outflow_no', '=', $cash_outflow_no)
            ->sum('cost_wvat');

        $employes = HrEmploye::where('etat', 1)->get();
        $accounts = AccountAccount::where('active', 1)->get();

        return view('backend.pages.accounting.return_fund.create', compact('data', 'total_amount_picked', 'employes', 'accounts', 'cash_outflow_no'));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.create')) {
            abort(403, 'Sorry!! you are Unauthorized to create return fund');
        }

        $request->validate([
            'cash_outflow_no' => 'required|exists:cash_outflows,cash_outflow_no',
            'total_amount_picked' => 'required|numeric|min:0',
            'total_amount_returned' => 'required|numeric|min:0|lte:total_amount_picked',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ], [
            'total_amount_returned.lte' => 'Le montant retourné ne peut pas être supérieur au montant sorti.',
        ]);

        DB::beginTransaction();
        try {
            $data = CashOutFlow::where('cash_outflow_no', $request->cash_outflow_no)->first();
            
            if (!$data) {
                session()->flash('error', 'Cash outflow not found');
                return redirect()->back();
            }

            // Generate return_no
            $latest = ReturnFund::latest()->first();
            $dateFormatted = Carbon::parse($request->date)->format('Ymd');
            
            if ($latest) {
                $return_no = 'RF' . str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT) . '/' . $dateFormatted;
            } else {
                $return_no = 'RF' . str_pad(1, 4, '0', STR_PAD_LEFT) . '/' . $dateFormatted;
            }

            // Generate return_signature
            $return_signature = config('app.tin_number_company') . Carbon::parse(Carbon::now())->format('YmdHis') . "/" . $return_no;

            $returnFund = new ReturnFund();
            $returnFund->date = $request->date;
            $returnFund->return_no = $return_no;
            $returnFund->cash_outflow_no = $request->cash_outflow_no;
            $returnFund->description = $request->description ?? 'Retour de fonds';
            $returnFund->total_amount_picked = $request->total_amount_picked;
            $returnFund->total_amount_returned = $request->total_amount_returned;
            $returnFund->return_signature = $return_signature;
            $returnFund->employe_id = $data->employe_id;
            $returnFund->account_id = $data->account_id;
            $returnFund->status = '0';
            $returnFund->created_by = $this->user->name;
            $returnFund->save();

            CashOutFlow::where('cash_outflow_no', '=', $request->cash_outflow_no)
                ->update(['status' => 5]);
            CashOutFlowDetail::where('cash_outflow_no', '=', $request->cash_outflow_no)
                ->update(['status' => 5]);


            DB::commit();
            session()->flash('success', 'Return fund created successfully');
            return redirect()->route('admin.accounting-return-fund.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error creating return fund: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function show($id)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.view')) {
            abort(403, 'Sorry!! you are Unauthorized to view return fund');
        }

        $return_fund = ReturnFund::with(['employe', 'account'])->findOrFail($id);

        return view('backend.pages.accounting.return_fund.show', compact('return_fund'));
    }

    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.edit')) {
            abort(403, 'Sorry!! you are Unauthorized to edit return fund');
        }

        $return_fund = ReturnFund::findOrFail($id);

        // Only allow editing if status is not 3 (approved)
        if ($return_fund->status == '3') {
            session()->flash('error', 'Cannot edit return fund that has been approved');
            return redirect()->route('admin.accounting-return-fund.index');
        }

        $data = CashOutFlow::where('cash_outflow_no', $return_fund->cash_outflow_no)->first();
        $employes = HrEmploye::where('etat', 1)->get();
        $accounts = AccountAccount::where('active', 1)->get();

        return view('backend.pages.accounting.return_fund.edit', compact('return_fund', 'data', 'employes', 'accounts'));
    }

    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.edit')) {
            abort(403, 'Sorry!! you are Unauthorized to edit return fund');
        }

        $return_fund = ReturnFund::findOrFail($id);

        // Only allow updating if status is not 3 (approved)
        if ($return_fund->status == '3') {
            session()->flash('error', 'Cannot update return fund that has been approved');
            return redirect()->route('admin.accounting-return-fund.index');
        }

        $request->validate([
            'total_amount_picked' => 'required|numeric|min:0',
            'total_amount_returned' => 'required|numeric|min:0|lte:total_amount_picked',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ], [
            'total_amount_returned.lte' => 'Le montant retourné ne peut pas être supérieur au montant sorti.',
        ]);

        DB::beginTransaction();
        try {
            $return_fund->date = $request->date;
            $return_fund->description = $request->description ?? 'Retour de fonds';
            $return_fund->total_amount_picked = $request->total_amount_picked;
            $return_fund->total_amount_returned = $request->total_amount_returned;
            $return_fund->updated_by = $this->user->name;
            $return_fund->save();

            DB::commit();
            session()->flash('success', 'Return fund updated successfully');
            return redirect()->route('admin.accounting-return-fund.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error updating return fund: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.delete')) {
            abort(403, 'Sorry!! you are Unauthorized to delete return fund');
        }

        $return_fund = ReturnFund::findOrFail($id);

        // Only allow deletion if status is not 3 (approved)
        if ($return_fund->status == '3') {
            session()->flash('error', 'Cannot delete return fund that has been approved');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $return_fund->delete();

            DB::commit();
            session()->flash('success', 'Return fund deleted successfully');
            return redirect()->route('admin.accounting-return-fund.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error deleting return fund: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function validateReturnFund($id)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.validate')) {
            abort(403, 'Sorry!! you are Unauthorized to validate return fund');
        }

        $return_fund = ReturnFund::findOrFail($id);

        // Only allow validation if status is 0 (draft)
        if ($return_fund->status != '0') {
            session()->flash('error', 'Return fund has already been validated');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $return_fund->status = '1';
            $return_fund->validated_by = $this->user->name;
            $return_fund->save();

            DB::commit();
            session()->flash('success', 'Return fund validated successfully');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error validating return fund: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function confirm($id)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.confirm')) {
            abort(403, 'Sorry!! you are Unauthorized to confirm return fund');
        }

        $return_fund = ReturnFund::findOrFail($id);

        // Only allow confirmation if status is 1 (validated)
        if ($return_fund->status != '1') {
            session()->flash('error', 'Return fund must be validated before confirmation');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $return_fund->status = '2';
            $return_fund->confirmed_by = $this->user->name;
            $return_fund->save();

            DB::commit();
            session()->flash('success', 'Return fund confirmed successfully');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error confirming return fund: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function approuve($id)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.approuve')) {
            abort(403, 'Sorry!! you are Unauthorized to approve return fund');
        }

        $return_fund = ReturnFund::findOrFail($id);

        // Only allow approval if status is 2 (confirmed)
        if ($return_fund->status != '2') {
            session()->flash('error', 'Return fund must be confirmed before approval');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $cashOutFlow = CashOutFlow::with(['employe', 'supplier'])
                ->where('cash_outflow_no', $return_fund->cash_outflow_no)
                ->first();

            if (!$cashOutFlow) {
                throw new \Exception('Sortie de fonds liée introuvable.');
            }

            $cashOutDetail = CashOutFlowDetail::with(['employe', 'supplier', 'expense'])
                ->where('cash_outflow_no', $return_fund->cash_outflow_no)
                ->first();

            if (!$cashOutDetail) {
                throw new \Exception('Détail de sortie de fonds introuvable.');
            }

            $cash_account_id = (int) $return_fund->account_id;
            $amount = (float) $return_fund->total_amount_returned;

            if ($cash_account_id <= 0) {
                throw new \Exception('Compte caisse/banque introuvable sur le retour de fonds.');
            }

            if ($amount <= 0) {
                throw new \Exception('Le montant retourné doit être supérieur à zéro.');
            }

            $creditPlan = $this->buildReturnFundCreditPlan($cashOutFlow, $cashOutDetail, $amount);

            // Débit caisse / banque
            $cashLedgerState = $this->updateLedgerTotals($cash_account_id, 'debit', $amount);

            AccountJournalEntryDetail::insert([[
                'date' => $return_fund->date,
                'document_no' => $return_fund->return_no,
                'description' => $return_fund->description ?? 'Retour de fonds',
                'account_id' => $cash_account_id,
                'libelle' => $return_fund->description ?? 'Retour de fonds',
                'debit' => $amount,
                'credit' => 0,
                'balance' => $cashLedgerState['balance'],
                'active' => 1,
                'created_at' => Carbon::now(),
            ]]);

            // Crédit du ou des comptes contrepartie (inverse de la sortie de fonds)
            $creditJournalRows = [];
            foreach ($creditPlan as $creditLine) {
                $creditAccountId = (int) $creditLine['account_id'];
                $creditAmount = (float) $creditLine['amount'];

                if ($creditAccountId <= 0 || $creditAmount <= 0) {
                    continue;
                }

                $creditLedgerState = $this->updateLedgerTotals($creditAccountId, 'credit', $creditAmount);

                $creditJournalRows[] = [
                    'date' => $return_fund->date,
                    'document_no' => $return_fund->return_no,
                    'description' => $return_fund->description ?? 'Retour de fonds',
                    'account_id' => $creditAccountId,
                    'libelle' => $return_fund->description ?? 'Retour de fonds',
                    'debit' => 0,
                    'credit' => $creditAmount,
                    'balance' => $creditLedgerState['balance'],
                    'active' => 1,
                    'created_at' => Carbon::now(),
                ];
            }

            if (empty($creditJournalRows)) {
                throw new \Exception('Aucune écriture de crédit n\'a pu être générée.');
            }

            AccountJournalEntryDetail::insert($creditJournalRows);

            // Update return fund status
            $return_fund->status = '3';
            $return_fund->approuved_by = $this->user->name;
            $return_fund->save();

            CashOutFlow::where('cash_outflow_no', '=', $return_fund->cash_outflow_no)
                ->update(['status' => 6]);
            CashOutFlowDetail::where('cash_outflow_no', '=', $return_fund->cash_outflow_no)
                ->update(['status' => 6]);

            DB::commit();
            session()->flash('success', 'Return fund approved successfully');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error approving return fund: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function print($id)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.view')) {
            abort(403, 'Sorry!! you are Unauthorized to print return fund');
        }

        $return_fund = ReturnFund::with(['employe', 'account'])->findOrFail($id);
        $setting = DB::table('settings')->orderBy('created_at', 'desc')->first();

        // Convert amount to words in French
        $amountInWords = $this->numberToWordsFrench($return_fund->total_amount_returned);

        $pdf = PDF::setOptions([
                'defaultFont' => 'dejavu sans',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ])->loadView('backend.pages.accounting.return_fund.print', compact('return_fund', 'setting', 'amountInWords'));

        Storage::put('public/pdf/accounting/return_fund/' . 'RETOUR DE FONDS ' . $return_fund->return_no . '.pdf', $pdf->output());

        return $pdf->download('RETOUR DE FONDS ' . $return_fund->return_no . '.pdf');
    }

    /**
     * Détermine le(s) compte(s) à créditer lors d'un retour de fonds
     * (inverse des écritures d'approbation de sortie de fonds).
     *
     * @return array<int, array{account_id: int, amount: float}>
     */
    protected function buildReturnFundCreditPlan(CashOutFlow $cashOutFlow, CashOutFlowDetail $detail, float $amount): array
    {
        $loanId = $cashOutFlow->loan_id ?? $detail->loan_id;
        $supplierId = $cashOutFlow->supplier_id ?? $detail->supplier_id;
        $employeId = $cashOutFlow->employe_id ?? $detail->employe_id;
        $flag2 = (string) ($cashOutFlow->flag2 ?? $detail->flag2 ?? '0');

        $employe = $cashOutFlow->employe ?? $detail->employe;
        if (!$employe && $employeId) {
            $employe = HrEmploye::find($employeId);
        }

        $supplier = $cashOutFlow->supplier ?? $detail->supplier;
        if (!$supplier && $supplierId) {
            $supplier = Supplier::find($supplierId);
        }

        // 1) Avance sur salaire → crédit employe->account_id
        if (!empty($loanId) && !empty($employeId)) {
            if (!$employe || empty($employe->account_id)) {
                throw new \Exception('Compte avance sur salaire introuvable. Configurez account_id sur la fiche employé.');
            }

            return [
                ['account_id' => (int) $employe->account_id, 'amount' => $amount],
            ];
        }

        // 2) Achat en espèces → crédit employe->cash_out_account_id
        if (!empty($employeId) && $flag2 === '1' && empty($loanId)) {
            if (!$employe || empty($employe->cash_out_account_id)) {
                throw new \Exception('Compte sortie de fonds introuvable. Configurez cash_out_account_id sur la fiche employé.');
            }

            return [
                ['account_id' => (int) $employe->cash_out_account_id, 'amount' => $amount],
            ];
        }

        // 3) Achat au crédit → crédit supplier->account_id
        if (!empty($supplierId)) {
            if (!$supplier || empty($supplier->account_id)) {
                throw new \Exception('Compte fournisseur introuvable. Configurez account_id sur la fiche fournisseur.');
            }

            return [
                ['account_id' => (int) $supplier->account_id, 'amount' => $amount],
            ];
        }

        // 4) Dépenses générales → crédit expense->default_account_id (répartition proportionnelle)
        if (!empty($employeId) && $flag2 === '0' && empty($loanId)) {
            $expenseDetails = CashOutFlowDetail::with('expense')
                ->where('cash_outflow_no', $cashOutFlow->cash_outflow_no)
                ->whereNotNull('expense_id')
                ->get();

            if ($expenseDetails->isEmpty()) {
                throw new \Exception('Aucune ligne de dépense trouvée pour ce retour de fonds.');
            }

            $totalPicked = (float) $expenseDetails->sum('cost_wvat');
            if ($totalPicked <= 0) {
                throw new \Exception('Montant total de la sortie de fonds invalide pour la répartition des charges.');
            }

            $creditsByAccount = [];
            $allocated = 0.0;
            $lastKey = $expenseDetails->count() - 1;

            foreach ($expenseDetails as $index => $line) {
                if (empty($line->expense) || empty($line->expense->default_account_id)) {
                    throw new \Exception('Compte de charge introuvable. Configurez default_account_id sur le type de dépense.');
                }

                $accountId = (int) $line->expense->default_account_id;

                if ($index === $lastKey) {
                    $lineAmount = round($amount - $allocated, 2);
                } else {
                    $lineAmount = round(((float) $line->cost_wvat / $totalPicked) * $amount, 2);
                    $allocated += $lineAmount;
                }

                if ($lineAmount <= 0) {
                    continue;
                }

                if (!isset($creditsByAccount[$accountId])) {
                    $creditsByAccount[$accountId] = 0.0;
                }
                $creditsByAccount[$accountId] += $lineAmount;
            }

            if ($creditsByAccount === []) {
                throw new \Exception('Impossible de calculer les crédits sur les comptes de charges.');
            }

            $plan = [];
            foreach ($creditsByAccount as $accountId => $creditAmount) {
                $plan[] = [
                    'account_id' => (int) $accountId,
                    'amount' => round($creditAmount, 2),
                ];
            }

            return $plan;
        }

        throw new \Exception('Type de sortie de fonds non reconnu pour le retour de fonds.');
    }

    protected function updateLedgerTotals(int $accountId, string $type, float $amount): array
    {
        // Récupère ou crée la ligne de grand livre pour ce compte
        $ledger = AccountLedger::firstOrNew(['account_id' => $accountId]);

        // Valeurs actuelles (0 si null)
        $currentDebit = (float) ($ledger->debit ?? 0);
        $currentCredit = (float) ($ledger->credit ?? 0);

        // Incrément du débit ou du crédit
        if ($type === 'debit') {
            $currentDebit += $amount;
        } else {
            $currentCredit += $amount;
        }

        // Sauvegarde des nouveaux totaux
        $ledger->date = Carbon::now();
        $ledger->debit = $currentDebit;
        $ledger->credit = $currentCredit;
        $ledger->balance = $currentDebit - $currentCredit;
        $ledger->save();

        return [
            'debit' => $currentDebit,
            'credit' => $currentCredit,
            'balance' => $ledger->balance,
        ];
    }

    protected function numberToWordsFrench($number)
    {
        $number = (int) $number;
        
        if ($number == 0) {
            return 'zéro';
        }
        
        $ones = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 
                 'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
        $tens = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'];
        
        $result = '';
        
        // Millions
        if ($number >= 1000000) {
            $millions = (int) ($number / 1000000);
            if ($millions == 1) {
                $result .= 'un million ';
            } else {
                $result .= $this->convertHundreds($millions, $ones, $tens) . ' millions ';
            }
            $number = $number % 1000000;
        }
        
        // Thousands
        if ($number >= 1000) {
            $thousands = (int) ($number / 1000);
            if ($thousands == 1) {
                $result .= 'mille ';
            } else {
                $result .= $this->convertHundreds($thousands, $ones, $tens) . ' mille ';
            }
            $number = $number % 1000;
        }
        
        // Hundreds, tens, ones
        if ($number > 0) {
            $result .= $this->convertHundreds($number, $ones, $tens);
        }
        
        return trim($result);
    }
    
    protected function convertHundreds($number, $ones, $tens)
    {
        $result = '';
        
        // Hundreds
        if ($number >= 100) {
            $hundred = (int) ($number / 100);
            if ($hundred == 1) {
                $result .= 'cent';
            } else {
                $result .= $ones[$hundred] . ' cent';
            }
            $number = $number % 100;
            if ($number > 0) {
                $result .= ' ';
            }
        }
        
        // Tens and ones
        if ($number >= 20) {
            $ten = (int) ($number / 10);
            $one = $number % 10;
            
            if ($ten == 7) {
                $result .= 'soixante';
                if ($one > 0) {
                    $result .= '-' . $ones[10 + $one];
                } else {
                    $result .= '-dix';
                }
            } elseif ($ten == 8) {
                $result .= 'quatre-vingt';
                if ($one > 0) {
                    $result .= '-' . $ones[$one];
                } else {
                    $result .= 's';
                }
            } elseif ($ten == 9) {
                $result .= 'quatre-vingt';
                if ($one > 0) {
                    $result .= '-' . $ones[10 + $one];
                } else {
                    $result .= '-dix';
                }
            } else {
                $result .= $tens[$ten];
                if ($one > 0) {
                    if ($ten == 8 && $one == 1) {
                        $result .= '-et-un';
                    } else {
                        $result .= '-' . $ones[$one];
                    }
                }
            }
        } elseif ($number > 0) {
            $result .= $ones[$number];
        }
        
        return trim($result);
    }
}
