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
use App\Models\Accounting\FundTransfer;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountJournalEntryDetail;
use Carbon\Carbon;
use PDF;

class FundTransferController extends Controller
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

    public function index(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.view')) {
            abort(403, 'Sorry!! you are Unauthorized to view fund transfer');
        }

        $query = FundTransfer::with(['originAccount', 'DestinationAccount']);
        
        // Si des dates sont fournies, filtrer par période
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('created_at', [$dateDebut, $dateFin]);
        } else {
            // Par défaut, afficher les 100 lignes les plus récentes
            $query->take(100);
        }
        
        $fund_transfers = $query->orderBy('id', 'desc')->get();

        return view('backend.pages.accounting.fund_transfer.index',compact('fund_transfers'));
    }


    public function create()
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.create')) {
            abort(403, 'Sorry!! you are Unauthorized to make fund transfer');
        }

        // Get accounts for INTERNAL (type 4) and EXTERNAL (type 3 or 4)
        $internalAccounts = AccountAccount::whereIn('account_type', [3, 4, 5, 6, 7])->where('active', 1)->get();
        $externalAccounts = AccountAccount::whereIn('account_type', [3, 4, 5, 6, 7])->where('active', 1)->get();

        return view('backend.pages.accounting.fund_transfer.create', compact('internalAccounts', 'externalAccounts'));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.create')) {
            abort(403, 'Sorry!! you are Unauthorized to store fund transfer');
        }

        $request->validate([
            'date' => 'required|date',
            'transfer_type' => 'required|in:INTERNAL,EXTERNAL',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'origin_account_id' => 'required|exists:account_accounts,id',
            'destination_account_id' => 'required|exists:account_accounts,id|different:origin_account_id',
        ]);

        DB::beginTransaction();
        try {

            $latest = FundTransfer::latest()->first();

            $dateFormatted = Carbon::parse($request->date)->format('Ymd');

            if ($latest) {
               $code = (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)).'/'.$dateFormatted; 
            }else{
               $code = (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT)).'/'.$dateFormatted;  
            }

            // Create the fund transfer first to get the ID
            $fundTransfer = new FundTransfer();
            $fundTransfer->date = $request->date;
            $fundTransfer->code = $code;
            $fundTransfer->transfer_type = $request->transfer_type;
            $fundTransfer->amount = $request->amount;
            $fundTransfer->description = $request->description;
            $fundTransfer->origin_account_id = $request->origin_account_id;
            $fundTransfer->destination_account_id = $request->destination_account_id;
            $fundTransfer->status = 0; // Draft status
            $fundTransfer->created_by = $this->user->name;
            $fundTransfer->save();

            DB::commit();
            session()->flash('success', 'Fund transfer created successfully');
            return redirect()->route('admin.accounting-fund-transfer.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error creating fund transfer: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }


    public function show($id)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.view')) {
            abort(403, 'Sorry!! you are Unauthorized to view fund transfer');
        }

        $fund_transfer = FundTransfer::with(['originAccount', 'DestinationAccount'])->findOrFail($id);

        return view('backend.pages.accounting.fund_transfer.show', compact('fund_transfer'));
    }

    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.edit')) {
            abort(403, 'Sorry!! you are Unauthorized to edit fund transfer');
        }

        $fund_transfer = FundTransfer::findOrFail($id);

        // Only allow editing if status is 0 (draft)
        if ($fund_transfer->status != 0) {
            session()->flash('error', 'Cannot edit fund transfer that has been validated');
            return redirect()->route('admin.accounting-fund-transfer.index');
        }

        // Get accounts based on transfer type
        if ($fund_transfer->transfer_type == 'INTERNAL') {
            $accounts = AccountAccount::whereIn('account_type', [3, 4, 5, 6, 7])->where('active', 1)->get();
        } else {
            $accounts = AccountAccount::whereIn('account_type', [3, 4, 5, 6, 7])->where('active', 1)->get();
        }

        return view('backend.pages.accounting.fund_transfer.edit', compact('fund_transfer', 'accounts'));
    }

    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.edit')) {
            abort(403, 'Sorry!! you are Unauthorized to edit fund transfer');
        }

        $fund_transfer = FundTransfer::findOrFail($id);

        // Only allow updating if status is 0 (draft)
        if ($fund_transfer->status != 0) {
            session()->flash('error', 'Cannot update fund transfer that has been validated');
            return redirect()->route('admin.accounting-fund-transfer.index');
        }

        $request->validate([
            'date' => 'required|date',
            'transfer_type' => 'required|in:INTERNAL,EXTERNAL',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'origin_account_id' => 'required|exists:account_accounts,id',
            'destination_account_id' => 'required|exists:account_accounts,id|different:origin_account_id',
        ]);

        DB::beginTransaction();
        try {
            $fund_transfer->date = $request->date;
            $fund_transfer->transfer_type = $request->transfer_type;
            $fund_transfer->amount = $request->amount;
            $fund_transfer->description = $request->description;
            $fund_transfer->origin_account_id = $request->origin_account_id;
            $fund_transfer->destination_account_id = $request->destination_account_id;
            $fund_transfer->save();

            DB::commit();
            session()->flash('success', 'Fund transfer updated successfully');
            return redirect()->route('admin.accounting-fund-transfer.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error updating fund transfer: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function validateFundTransfer($id)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.validate')) {
            abort(403, 'Sorry!! you are Unauthorized to validate fund transfer');
        }

        $fund_transfer = FundTransfer::findOrFail($id);

        // Only allow validation if status is 0 (draft)
        if ($fund_transfer->status != 0) {
            session()->flash('error', 'Fund transfer has already been validated');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $fund_transfer->status = 1;
            $fund_transfer->created_by = $this->user->name;
            $fund_transfer->save();

            DB::commit();
            session()->flash('success', 'Fund transfer validated successfully');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error validating fund transfer: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function confirm($id)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.confirm')) {
            abort(403, 'Sorry!! you are Unauthorized to confirm fund transfer');
        }

        $fund_transfer = FundTransfer::findOrFail($id);

        // Only allow confirmation if status is 1 (validated)
        if ($fund_transfer->status != 1) {
            session()->flash('error', 'Fund transfer must be validated before confirmation');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $fund_transfer->status = 2;
            $fund_transfer->confirmed_by = $this->user->name;
            $fund_transfer->save();

            DB::commit();
            session()->flash('success', 'Fund transfer confirmed successfully');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error confirming fund transfer: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function approuve($id)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.approuve')) {
            abort(403, 'Sorry!! you are Unauthorized to approuve fund transfer');
        }

        $data = FundTransfer::where('id',$id)->first();

        // Only allow approval if status is 2 (confirmed)
        if ($data->status != 2) {
            session()->flash('error', 'Fund transfer must be confirmed before approval');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
        //start debit operations
                $id_acc_destination = FundTransfer::where('id',$data->id)->value('destination_account_id');

                $destinationAccLedgerState = $this->updateLedgerTotals($id_acc_destination, 'debit', $data->amount);

                $journalEntryDebitdataDestAcc = array(
                    'date' => $data->date,
                    'document_no' => $data->code,
                    'description' => $data->description,
                    'account_id' => $id_acc_destination,
                    'libelle' => $data->description,
                    'debit' => $data->amount,
                    'balance' => $destinationAccLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_debit_dataDestAcc[] = $journalEntryDebitdataDestAcc;
                
                AccountJournalEntryDetail::insert($insert_journal_debit_dataDestAcc);

                //credit origin account
                $id_acc_origin = FundTransfer::where('id',$id)->value('origin_account_id');

                $originAccLedgerState = $this->updateLedgerTotals($id_acc_origin, 'credit', $data->amount);

                $journalEntryCreditdataOrigAcc = array(
                    'date' => $data->date,
                    'document_no' => $data->code,
                    'description' => $data->description,
                    'account_id' => $id_acc_origin,
                    'libelle' => $data->description,
                    'credit' => $data->amount,
                    'balance' => $originAccLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_dataOrigAcc[] = $journalEntryCreditdataOrigAcc;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_dataOrigAcc);
            
                //end credit operation

        FundTransfer::where('id', '=', $id)
             ->update(['status' => 3,'approuved_by' => $this->user->name]);

        DB::commit();
        session()->flash('success', 'Fund transfer approved successfully');
        return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error approving fund transfer: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function print($id)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.view')) {
            abort(403, 'Sorry!! you are Unauthorized to print fund transfer');
        }

        $fund_transfer = FundTransfer::with(['originAccount', 'DestinationAccount'])->findOrFail($id);
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
    
        // Convert amount to words in French
        $amountInWords = $this->numberToWordsFrench($fund_transfer->amount);

        $pdf = PDF::setOptions([
                'defaultFont' => 'dejavu sans',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ])->loadView('backend.pages.accounting.document.fiche_transfert_fonds',compact('fund_transfer', 'setting', 'amountInWords'));

           Storage::put('public/pdf/accounting/fiche de transfert de fonds/'.'FICHE DE TRANSFERT DE FONDS '.$fund_transfer->code.'.pdf', $pdf->output());

           // download pdf file
           return $pdf->download('FICHE DE TRANSFERT DE FONDS  '.$fund_transfer->code.'.pdf'); 
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
}
