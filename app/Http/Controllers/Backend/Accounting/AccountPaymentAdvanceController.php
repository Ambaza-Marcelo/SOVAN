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
use App\Models\EGRClient;
use App\Models\Accounting\AccountPaymentAdvance;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountSetting;
use Carbon\Carbon;
use PDF;

class AccountPaymentAdvanceController extends Controller
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
        if (is_null($this->user) || !$this->user->can('advance_payment.view')) {
            abort(403, 'Sorry!! you are Unauthorized to view advance payment');
        }

        $query = AccountPaymentAdvance::with(['client', 'account', 'advanceAccount']);
        
        // Si des dates sont fournies, filtrer par période
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('created_at', [$dateDebut, $dateFin]);
        } else {
            // Par défaut, afficher les 100 lignes les plus récentes
            $query->take(100);
        }
        
        $advances = $query->orderBy('id', 'desc')->get();

        return view('backend.pages.accounting.payment_advance.index',compact('advances'));
    }


    public function create()
    {
        if (is_null($this->user) || !$this->user->can('advance_payment.create')) {
            abort(403, 'Sorry!! you are Unauthorized to make advance payment');
        }

        // Get customers (EGRClient)
        $customers = EGRClient::orderBy('customer_name', 'asc')->get();

        // Get accounts for INTERNAL (type 4) and EXTERNAL (type 3 or 4)
        $internalAccounts = AccountAccount::where('account_type', 4)->where('active', 1)->get();
        $externalAccounts = AccountAccount::whereIn('account_type', [3, 4, 6, 7])->where('active', 1)->get();

        // Comptes avance (account_number commence par 418)
        $advanceAccounts = AccountAccount::where('account_number', 'like', '418%')->orderBy('account_number')->get();

        return view('backend.pages.accounting.payment_advance.create', compact('customers', 'internalAccounts', 'externalAccounts', 'advanceAccounts'));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('advance_payment.create')) {
            abort(403, 'Sorry!! you are Unauthorized to store advance payment');
        }

        $request->validate([
            'date' => 'required|date',
            'payment_type' => 'required|in:INTERNAL,EXTERNAL',
            'contact_name' =>'required',
            'contact_tel' =>'required',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'account_id' => 'required|exists:account_accounts,id',
            'advance_account_id' => 'required|exists:account_accounts,id',
        ]);

        DB::beginTransaction();
        try {

            $latest = AccountPaymentAdvance::latest()->first();

            $dateFormatted = Carbon::parse($request->date)->format('Ymd');

            if ($latest) {
               $code = "ADV".(str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)).'/'.$dateFormatted; 
            }else{
               $code = "ADV".(str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT)).'/'.$dateFormatted;  
            }

            // Handle document upload
            $documentPath = null;
            if ($request->hasFile('document')) {
                $document = $request->file('document');
                $documentName = time() . '_' . $document->getClientOriginalName();
                $documentPath = $document->storeAs('public/payment_advance/documents', $documentName);
                $documentPath = str_replace('public/', '', $documentPath); // Store path without 'public/' prefix
            }

            // Create the advance payment first to get the ID
            $advance = new AccountPaymentAdvance();
            $advance->date = $request->date;
            $advance->code = $code;
            $advance->payment_type = $request->payment_type;
            $advance->amount = $request->amount;
            $advance->contact_name = $request->contact_name;
            $advance->contact_tel = $request->contact_tel;
            $advance->description = $request->description;
            $advance->client_id = $request->client_id;
            $advance->account_id = $request->account_id;
            $advance->advance_account_id = $request->advance_account_id;
            $advance->document = $documentPath;
            $advance->status = 0; // Draft status
            $advance->created_by = $this->user->name;
            $advance->save();

            DB::commit();
            session()->flash('success', 'advance payment created successfully');
            return redirect()->route('admin.accounting-advance-payment.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error creating advance payment: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }


    public function show($id)
    {
        if (is_null($this->user) || !$this->user->can('advance_payment.view')) {
            abort(403, 'Sorry!! you are Unauthorized to view advance payment');
        }

        $advance = AccountPaymentAdvance::with(['client', 'account', 'advanceAccount'])->findOrFail($id);

        return view('backend.pages.accounting.payment_advance.show', compact('advance'));
    }

    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('advance_payment.edit')) {
            abort(403, 'Sorry!! you are Unauthorized to edit advance payment');
        }

        $advance = AccountPaymentAdvance::findOrFail($id);

        // Only allow editing if status is 0 (draft)
        if ($advance->status != 0) {
            session()->flash('error', 'Cannot edit advance payment that has been validated');
            return redirect()->route('admin.accounting-advance-payment.index');
        }

        // Get customers (EGRClient)
        $customers = EGRClient::orderBy('customer_name', 'asc')->get();

        // Get accounts based on payment type
        if ($advance->payment_type == 'INTERNAL') {
            $internalAccounts = AccountAccount::where('account_type', 4)->where('active', 1)->get();
            $externalAccounts = AccountAccount::whereIn('account_type', [3, 4, 6, 7])->where('active', 1)->get();
        } else {
            $internalAccounts = AccountAccount::where('account_type', 4)->where('active', 1)->get();
            $externalAccounts = AccountAccount::whereIn('account_type', [3, 4, 6, 7])->where('active', 1)->get();
        }

        $advanceAccounts = AccountAccount::where('account_number', 'like', '418%')->orderBy('account_number')->get();

        return view('backend.pages.accounting.payment_advance.edit', compact('advance', 'customers', 'internalAccounts', 'externalAccounts', 'advanceAccounts'));
    }

    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('advance_payment.edit')) {
            abort(403, 'Sorry!! you are Unauthorized to edit advance payment');
        }

        $advance = AccountPaymentAdvance::findOrFail($id);

        // Only allow updating if status is 0 (draft)
        if ($advance->status != 0) {
            session()->flash('error', 'Cannot update advance payment that has been validated');
            return redirect()->route('admin.accounting-advance-payment.index');
        }

        $request->validate([
            'date' => 'required|date',
            'payment_type' => 'required|in:INTERNAL,EXTERNAL',
            'contact_name' =>'required',
            'contact_tel' =>'required',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'account_id' => 'required|exists:account_accounts,id',
            'advance_account_id' => 'required|exists:account_accounts,id',
            'document' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // Max 10MB
        ]);

        DB::beginTransaction();
        try {
            // Handle document upload
            if ($request->hasFile('document')) {
                // Delete old document if exists
                if ($advance->document) {
                    Storage::delete('public/' . $advance->document);
                }
                
                $document = $request->file('document');
                $documentName = time() . '_' . $document->getClientOriginalName();
                $documentPath = $document->storeAs('public/payment_advance/documents', $documentName);
                $documentPath = str_replace('public/', '', $documentPath);
                $advance->document = $documentPath;
            }

            $advance->date = $request->date;
            $advance->payment_type = $request->payment_type;
            $advance->amount = $request->amount;
            $advance->description = $request->description;
            $advance->contact_name = $request->contact_name;
            $advance->contact_tel = $request->contact_tel;
            $advance->client_id = $request->client_id;
            $advance->account_id = $request->account_id;
            $advance->advance_account_id = $request->advance_account_id;
            $advance->save();

            DB::commit();
            session()->flash('success', 'advance payment updated successfully');
            return redirect()->route('admin.accounting-advance-payment.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error updating advance payment: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function validateAccountPaymentAdvance($id)
    {
        if (is_null($this->user) || !$this->user->can('advance_payment.validate')) {
            abort(403, 'Sorry!! you are Unauthorized to validate advance payment');
        }

        $advance = AccountPaymentAdvance::findOrFail($id);

        // Only allow validation if status is 0 (draft)
        if ($advance->status != 0) {
            session()->flash('error', 'advance payment has already been validated');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $advance->status = 1;
            $advance->created_by = $this->user->name;
            $advance->save();

            DB::commit();
            session()->flash('success', 'advance payment validated successfully');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error validating advance payment: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function confirm($id)
    {
        if (is_null($this->user) || !$this->user->can('advance_payment.confirm')) {
            abort(403, 'Sorry!! you are Unauthorized to confirm advance payment');
        }

        $advance = AccountPaymentAdvance::findOrFail($id);

        // Only allow confirmation if status is 1 (validated)
        if ($advance->status != 1) {
            session()->flash('error', 'advance payment must be validated before confirmation');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $advance->status = 2;
            $advance->confirmed_by = $this->user->name;
            $advance->save();

            DB::commit();
            session()->flash('success', 'advance payment confirmed successfully');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error confirming advance payment: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function approuve($id)
    {
        if (is_null($this->user) || !$this->user->can('advance_payment.approuve')) {
            abort(403, 'Sorry!! you are Unauthorized to approuve advance payment');
        }

        $data = AccountPaymentAdvance::where('id',$id)->first();

        // Only allow approval if status is 2 (confirmed)
        if ($data->status != 2) {
            session()->flash('error', 'advance payment must be confirmed before approval');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
        //start credit operations
                // Utiliser le compte avance de la fiche (advance_account_id) au lieu du paramètre global
                $advance_account_id = $data->advance_account_id;
                if (!$advance_account_id) {
                    DB::rollBack();
                    session()->flash('error', 'Advance account (Compte avance) is required for this payment. Please edit the advance payment and select an advance account (account number starting with 418).');
                    return redirect()->back();
                }

                $advPaymentAccLedgerState = $this->updateLedgerTotals($advance_account_id, 'credit', $data->amount);

                $journalEntryCreditDataAdvPaymentAcc = array(
                    'date' => $data->date,
                    'document_no' => $data->code,
                    'description' => $data->description ?? 'Advance payment',
                    'account_id' => $advance_account_id,
                    'libelle' => $data->description ?? 'Advance payment',
                    'credit' => $data->amount,
                    'balance' => $advPaymentAccLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_dataAdvPaymentAcc[] = $journalEntryCreditDataAdvPaymentAcc;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_dataAdvPaymentAcc);

                //debit origin account
                $id_acc = AccountPaymentAdvance::where('id',$id)->value('account_id');

                $accLedgerState = $this->updateLedgerTotals($id_acc, 'debit', $data->amount);

                $journalEntryDebitdataAcc = array(
                    'date' => $data->date,
                    'document_no' => $data->code,
                    'description' => $data->description,
                    'account_id' => $id_acc,
                    'libelle' => $data->description,
                    'debit' => $data->amount,
                    'balance' => $accLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_debit_dataAcc[] = $journalEntryDebitdataAcc;
                
                AccountJournalEntryDetail::insert($insert_journal_debit_dataAcc);
            
                //end credit operation

        AccountPaymentAdvance::where('id', '=', $id)
             ->update(['status' => 3,'approuved_by' => $this->user->name]);

        DB::commit();
        session()->flash('success', 'advance payment approved successfully');
        return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error approving advance payment: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function print($id)
    {
        if (is_null($this->user) || !$this->user->can('advance_payment.view')) {
            abort(403, 'Sorry!! you are Unauthorized to print advance payment');
        }

        // Load advance payment with relationships
        $advance = AccountPaymentAdvance::with(['client', 'account', 'advanceAccount'])->findOrFail($id);
        
        // Load settings
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
    
        // Convert amount to words in French
        $amountInWords = $this->numberToWordsFrench($advance->amount ?? 0);

        // Format date for display
        $formattedDate = $advance->date ? \Carbon\Carbon::parse($advance->date)->format('d F Y') : 'N/A';

        $pdf = PDF::setOptions([
                'defaultFont' => 'dejavu sans',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ])->loadView('backend.pages.accounting.document.paiement_avance', compact('advance', 'setting', 'amountInWords', 'formattedDate'));

        Storage::put('public/pdf/accounting/FICHE PAIEMENT AVANCE/'.'FICHE PAIEMENT AVANCE '.$advance->code.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('FICHE PAIEMENT AVANCE  '.$advance->code.'.pdf'); 
    }

    public function downloadDocument($id)
    {
        if (is_null($this->user) || !$this->user->can('advance_payment.view')) {
            abort(403, 'Sorry!! you are Unauthorized to download document');
        }

        $advance = AccountPaymentAdvance::findOrFail($id);

        if (!$advance->document) {
            session()->flash('error', 'No document attached to this advance payment');
            return redirect()->back();
        }

        $filePath = storage_path('app/public/' . $advance->document);

        if (!file_exists($filePath)) {
            session()->flash('error', 'Document file not found');
            return redirect()->back();
        }

        return response()->download($filePath);
    }

    public function viewDocument($id)
    {
        if (is_null($this->user) || !$this->user->can('advance_payment.view')) {
            abort(403, 'Sorry!! you are Unauthorized to view document');
        }

        $advance = AccountPaymentAdvance::findOrFail($id);

        if (!$advance->document) {
            abort(404, 'Document not found');
        }

        $filePath = storage_path('app/public/' . $advance->document);

        if (!file_exists($filePath)) {
            abort(404, 'Document file not found');
        }

        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeType = mime_content_type($filePath);

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"'
        ]);
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
