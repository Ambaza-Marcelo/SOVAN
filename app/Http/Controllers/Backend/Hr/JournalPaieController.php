<?php

namespace App\Http\Controllers\Backend\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\HrEmploye;
use App\Models\HrJournalPaie;
use App\Models\HrJournalPaieDetail;
use App\Models\HrPaiement;
use App\Models\HrLoan;
use App\Models\HrCompany;
use App\Models\Accounting\AccountCostCenter;
use App\Models\Accounting\AccountJournalEntry;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountCurrency;
use App\Models\Accounting\AccountSetting;
use App\Exports\Hr\JournalPaieExport;
use Carbon\Carbon;
use Validator;
use Excel;
use PDF;

class JournalPaieController extends Controller
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

    public function index()
    {
        if (is_null($this->user) || !$this->user->can('hr_employe.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les employés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $journal_paies = HrJournalPaie::all();
        $journal_paie_encours = count(HrJournalPaie::where('etat', 0)->get());
        return view('backend.pages.hr.journal_paie.index',compact('journal_paies','journal_paie_encours'));
    }

    public function selectByCompany($code)
    {
        if (is_null($this->user) || !$this->user->can('hr_employe.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les employés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $companies = HrCompany::all();
        return view('backend.pages.hr.journal_paie.select_by_company',compact('companies','code'));
    }

    public function create()
    {
        if (is_null($this->user) || !$this->user->can('hr_journal_paie.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer les paies! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        return view('backend.pages.hr.journal_paie.create');
    }

     public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_journal_paie.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer les paies! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'date_debut' => 'required',
            'title' => 'required',
            'date_fin' => 'required|after:date_debut'

        ]);


            $latest = HrJournalPaie::latest()->first();
            if ($latest) {
               $code = (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $code = (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }



        $journal_paie = new HrJournalPaie();
        $journal_paie->date_debut = $request->date_debut;
        $journal_paie->title = $request->title;
        $journal_paie->date_fin = $request->date_fin;
        $journal_paie->etat = 0;
        $journal_paie->code = $code;
        $journal_paie->save();

        $journal_paie_detail = new HrJournalPaieDetail();
        $journal_paie_detail->date_debut = $request->date_debut;
        //$journal_paie_detail->title = $request->title;
        $journal_paie_detail->date_fin = $request->date_fin;
        $journal_paie_detail->etat = 0;
        $journal_paie_detail->code = $code;
        $journal_paie_detail->save();
        
        if($latest){
            $datas = HrPaiement::where('code',$latest->code)->get();
            foreach ($datas as $data) {
                $paiement = new HrPaiement();
                $paiement->employe_id = $data->employe_id;
                $paiement->code = $code;
                $paiement->date_debut = $request->date_debut;
                $paiement->date_fin = $request->date_fin;
                $paiement->etat = 0;
                $paiement->somme_salaire_base = $data->somme_salaire_base;
                $paiement->somme_salaire_brut_imposable = $data->somme_salaire_brut_imposable;
                $paiement->somme_salaire_brut_non_imposable = $data->somme_salaire_brut_non_imposable;
                $paiement->somme_salaire_net_imposable = $data->somme_salaire_net_imposable;
                $paiement->somme_salaire_net_non_imposable = $data->somme_salaire_net_non_imposable;
                $paiement->nbre_jours_ouvrables = $data->nbre_jours_ouvrables;
                $paiement->code_banque = $data->code_banque;
                $paiement->code_departement = $data->code_departement;
                $paiement->code_service = $data->code_service;
                $paiement->numero_compte = $data->numero_compte;
                $paiement->somme_impot = $data->somme_impot;
                $paiement->somme_cotisation_inss = $data->somme_cotisation_inss;
                $paiement->inss_employeur = $data->inss_employeur;
                $paiement->assurance_maladie_employe = $data->assurance_maladie_employe;
                $paiement->assurance_maladie_employeur = $data->assurance_maladie_employeur;
                $paiement->indemnite_deplacement = $data->indemnite_deplacement;
                $paiement->indemnite_logement = $data->indemnite_logement;
                $paiement->allocation_familiale = $data->allocation_familiale;
                $paiement->prime_fonction = $data->prime_fonction;
                $paiement->soins_medicaux = $data->soins_medicaux ?? 0;
                $paiement->autre_retenue = $data->autre_retenue ?? 0;
                // Intégration automatique des avances sur salaire pour le nouveau journal
                $paiement->retenue_pret = $this->computeLoanRetentionForPeriod($data->employe_id, $request->date_debut);
                list($paiement->somme_salaire_net_imposable, $paiement->somme_salaire_net_non_imposable) = $this->recalculateNetFromPaiementData($data, $paiement->retenue_pret);
                $paiement->company_id = $data->company_id;
                $paiement->created_by = $this->user->name;
                $paiement->save();
            }
        }
     
    
        session()->flash('success', 'Journal Paie est créé !!');

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($code,$company_id)
    {
        //
        if (is_null($this->user) || !$this->user->can('hr_journal_paie.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les paies! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        $data = HrJournalPaie::where('code',$code)->first();

        $journal_paies = HrPaiement::where('code',$code)->where('employe_id','!=','')->where('company_id',$company_id)->get();
        /*
        $journal_paies = HrPaiement::select(
                        DB::raw('employe_id,somme_salaire_base,somme_impot,somme_cotisation_inss,inss_employeur,assurance_maladie_employe,assurance_maladie_employeur,indemnite_deplacement,indemnite_logement,allocation_familiale,soins_medicaux,prime_fonction,retenue_pret,autre_retenue'))->where('code',$code)->groupBy('employe_id','somme_salaire_base')->get();
                        */
        return view('backend.pages.hr.journal_paie.show',compact('journal_paies','data'));
        
    }

    public function journalPaie($code)
    {
        if (is_null($this->user) || !$this->user->can('hr_journal_paie.create')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();

        $datas = HrJournalPaieDetail::select(
                        DB::raw('employe_id,somme_salaire_base,sum(somme_cotisation) as somme_cotisation,sum(somme_indemnite) as somme_indemnite,sum(somme_prime) as somme_prime,sum(somme_impot) as somme_impot,sum(avance_sur_salaire) as avance_sur_salaire,sum(autre_retenue) as autre_retenue'))->where('code',$code)->groupBy('employe_id','somme_salaire_base')->get();

        $journal_paie = HrJournalPaie::where('code', $code)->first();
        //$somme_indemnite = HrJournalPaieDetail::where('code',$code)->sum('somme_indemnite');
        $pdf = PDF::loadView('backend.pages.document.journal_paie',compact('datas','setting','journal_paie'))->setPaper('a4', 'landscape');

        $currentTime = Carbon::now();

        $dateT =  $currentTime->toDateTimeString();

        $dateTime = str_replace([' ',':'], '_', $dateT);

        Storage::put('public/journal-paie/'.$dateTime.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download($dateTime.'.pdf');
    }

    public function cloturer($code)
    {
       if (is_null($this->user) || !$this->user->can('hr_journal_paie.cloturer')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de clôturer le journal de paye! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        try {DB::beginTransaction();

            $basic_salary = DB::table('hr_paiements')->where('code', '=', $code)->sum('somme_salaire_base');
            $house_allowance = DB::table('hr_paiements')->where('code', '=', $code)->sum('indemnite_logement');
            $transport_allowance = DB::table('hr_paiements')->where('code', '=', $code)->sum('indemnite_deplacement');
            $other_prime = DB::table('hr_paiements')->where('code', '=', $code)->sum('prime_fonction');
            $cotisation_employer = DB::table('hr_paiements')->where('code', '=', $code)->sum('inss_employeur');
            $insurance_employer = DB::table('hr_paiements')->where('code', '=', $code)->sum('assurance_maladie_employeur');
            $net_salary = DB::table('hr_paiements')->where('code', '=', $code)->sum('somme_salaire_net_imposable');
            $tax = DB::table('hr_paiements')->where('code', '=', $code)->sum('somme_impot');
            $insurance_employe = DB::table('hr_paiements')->where('code', '=', $code)->sum('assurance_maladie_employe');
            $cotisation_employe = DB::table('hr_paiements')->where('code', '=', $code)->sum('somme_cotisation_inss');
            $retenue_pret = DB::table('hr_paiements')->where('code', '=', $code)->sum('retenue_pret');
            $autre_retenue = DB::table('hr_paiements')->where('code', '=', $code)->sum('autre_retenue');
            $soins_medicaux = DB::table('hr_paiements')->where('code', '=', $code)->sum('soins_medicaux');
            $insurance_employer_employe = $insurance_employer + $insurance_employe;
            $cotisation_employer_employe = $cotisation_employer + $cotisation_employe;

            $total_debit = $basic_salary+$house_allowance+$transport_allowance+$other_prime+$cotisation_employer+$insurance_employer;
            $total_credit = $net_salary+$tax+$insurance_employer_employe+$cotisation_employer_employe+$retenue_pret+$autre_retenue+$soins_medicaux;


            $data = HrJournalPaie::where('code',$code)->first();

            //start accounting
            //start basic salary debit operations
            $basic_salary_account_id = AccountSetting::where('active',1)->value('basic_salary_account_id');
            $basicSalaryLedgerState = $this->updateLedgerTotals($basic_salary_account_id, 'debit', $basic_salary);

            $journalEntryBasicSalarydata = array(
                    'date' => $data->date_fin,
                    'document_no' => $data->code,
                    'description' => "basic salary",
                    'account_id' => $basic_salary_account_id,
                    'libelle' => "basic salary",
                    'debit' => $basic_salary,
                    'balance' => $basicSalaryLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_basic_salary_data[] = $journalEntryBasicSalarydata;

            AccountJournalEntryDetail::insert($insert_journal_basic_salary_data);

            //end basic salary debit operations
            //start house_allowance debit operations
            $h_allowance_account_id = AccountSetting::where('active',1)->value('h_allowance_account_id');
            $houseAllowanceLedgerState = $this->updateLedgerTotals($h_allowance_account_id, 'debit', $house_allowance);

            $journalEntryBasicSalarydata = array(
                    'date' => $data->date_fin,
                    'document_no' => $data->code,
                    'description' => "house allowance",
                    'account_id' => $h_allowance_account_id,
                    'libelle' => "house allowance",
                    'debit' => $house_allowance,
                    'balance' => $houseAllowanceLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_house_allowance_data[] = $journalEntryBasicSalarydata;

            AccountJournalEntryDetail::insert($insert_journal_house_allowance_data);

            //end house allowance debit operations
            //start transport allowance debit operations
            $t_allowance_account_id = AccountSetting::where('active',1)->value('t_allowance_account_id');
            $transportAllowanceLedgerState = $this->updateLedgerTotals($t_allowance_account_id, 'debit', $transport_allowance);

            $journalEntryBasicSalarydata = array(
                    'date' => $data->date_fin,
                    'document_no' => $data->code,
                    'description' => "transport allowance",
                    'account_id' => $t_allowance_account_id,
                    'libelle' => "transport allowance",
                    'debit' => $transport_allowance,
                    'balance' => $transportAllowanceLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_transport_allowance_data[] = $journalEntryBasicSalarydata;

            AccountJournalEntryDetail::insert($insert_journal_transport_allowance_data);

            //end transport allowance debit operations
            //start cotisation employer debit operations
            $cot_employer_account_id = AccountSetting::where('active',1)->value('cot_employer_account_id');
            $cotEmployerLedgerState = $this->updateLedgerTotals($cot_employer_account_id, 'debit', $cotisation_employer);

            $journalEntryBasicSalarydata = array(
                    'date' => $data->date_fin,
                    'document_no' => $data->code,
                    'description' => "cotisation employer",
                    'account_id' => $cot_employer_account_id,
                    'libelle' => "cotisation employer",
                    'debit' => $cotisation_employer,
                    'balance' => $cotEmployerLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_cot_employer_data[] = $journalEntryBasicSalarydata;

            AccountJournalEntryDetail::insert($insert_journal_cot_employer_data);

            //end cotisation employer debit operations
            //start insurance employer debit operations
            $insurance_employer_account_id = AccountSetting::where('active',1)->value('insurance_employer_account_id');
            $insuranceEmployerLedgerState = $this->updateLedgerTotals($insurance_employer_account_id, 'debit', $insurance_employer);

            $journalEntryBasicSalarydata = array(
                    'date' => $data->date_fin,
                    'document_no' => $data->code,
                    'description' => "insurance employer",
                    'account_id' => $insurance_employer_account_id,
                    'libelle' => "insurance employer",
                    'debit' => $insurance_employer,
                    'balance' => $insuranceEmployerLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_insurance_employer_data[] = $journalEntryBasicSalarydata;

            AccountJournalEntryDetail::insert($insert_journal_insurance_employer_data);

            //end insurance employer debit operations

            //start net salary credit operations
            $net_salary_account_id = AccountSetting::where('active',1)->value('net_salary_account_id');
            $netSalaryLedgerState = $this->updateLedgerTotals($net_salary_account_id, 'credit', $net_salary);

            $journalEntryBasicSalaryCreditdata = array(
                    'date' => $data->date_fin,
                    'document_no' => $data->code,
                    'description' => "net salary",
                    'account_id' => $net_salary_account_id,
                    'libelle' => "net salary",
                    'credit' => $net_salary,
                    'balance' => $netSalaryLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_net_salary_credit_data[] = $journalEntryBasicSalaryCreditdata;
                
            AccountJournalEntryDetail::insert($insert_journal_net_salary_credit_data);

            //end credit operation
            //start tax salary credit operations
            $tax_salary_account_id = AccountSetting::where('active',1)->value('tax_salary_account_id');
            $taxSalaryLedgerState = $this->updateLedgerTotals($tax_salary_account_id, 'credit', $tax);

            $journalEntryTaxSalaryCreditdata = array(
                    'date' => $data->date_fin,
                    'document_no' => $data->code,
                    'description' => "tax salary",
                    'account_id' => $tax_salary_account_id,
                    'libelle' => "tax salary",
                    'credit' => $tax,
                    'balance' => $taxSalaryLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_tax_salary_credit_data[] = $journalEntryTaxSalaryCreditdata;
                
            AccountJournalEntryDetail::insert($insert_journal_tax_salary_credit_data);

            //end tax credit operation
            //start cotisation employer credit operations
            $cot_employer_account_id = AccountSetting::where('active',1)->value('cot_employer_account_id');
            $cotEmployerCreditLedgerState = $this->updateLedgerTotals($cot_employer_account_id, 'credit', $cotisation_employer);

            $journalEntryCotEmployerCreditdata = array(
                    'date' => $data->date_fin,
                    'document_no' => $data->code,
                    'description' => "cotisation employer",
                    'account_id' => $cot_employer_account_id,
                    'libelle' => "cotisation employer",
                    'credit' => $cotisation_employer,
                    'balance' => $cotEmployerCreditLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_cot_employer_credit_data[] = $journalEntryCotEmployerCreditdata;
                
            AccountJournalEntryDetail::insert($insert_journal_cot_employer_credit_data);

            //end cot employer credit operation
            //start cotisation employee credit operations
            $cot_employee_account_id = AccountSetting::where('active',1)->value('cot_employee_account_id');
            $cotEmployeeCreditLedgerState = $this->updateLedgerTotals($cot_employee_account_id, 'credit', $cotisation_employe);

            $journalEntryCotEmployeeCreditdata = array(
                    'date' => $data->date_fin,
                    'document_no' => $data->code,
                    'description' => "cotisation employee",
                    'account_id' => $cot_employee_account_id,
                    'libelle' => "cotisation employee",
                    'credit' => $cotisation_employe,
                    'balance' => $cotEmployeeCreditLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_cot_employee_credit_data[] = $journalEntryCotEmployeeCreditdata;
                
            AccountJournalEntryDetail::insert($insert_journal_cot_employee_credit_data);

            //end cot employee credit operation
            //start insurance employee credit operations
            $insurance_employee_account_id = AccountSetting::where('active',1)->value('insurance_employee_account_id');
            $insuranceEmployeeCreditLedgerState = $this->updateLedgerTotals($insurance_employee_account_id, 'credit', $insurance_employe);

            $journalEntryInsuranceEmployeeCreditdata = array(
                    'date' => $data->date_fin,
                    'document_no' => $data->code,
                    'description' => "insurance employee",
                    'account_id' => $insurance_employee_account_id,
                    'libelle' => "insurance employee",
                    'credit' => $insurance_employe,
                    'balance' => $insuranceEmployeeCreditLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_insurance_employee_credit_data[] = $journalEntryInsuranceEmployeeCreditdata;
                
            AccountJournalEntryDetail::insert($insert_journal_insurance_employee_credit_data);

            //end insurance employee credit operation
            //start insurance employer credit operations
            $insurance_employer_account_id = AccountSetting::where('active',1)->value('insurance_employer_account_id');
            $insuranceEmployerCreditLedgerState = $this->updateLedgerTotals($insurance_employer_account_id, 'credit', $insurance_employer);

            $journalEntryInsuranceEmployerCreditdata = array(
                    'date' => $data->date_fin,
                    'document_no' => $data->code,
                    'description' => "insurance employer",
                    'account_id' => $insurance_employer_account_id,
                    'libelle' => "insurance employer",
                    'credit' => $insurance_employer,
                    'balance' => $insuranceEmployerCreditLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_insurance_employer_credit_data[] = $journalEntryInsuranceEmployerCreditdata;
                
            AccountJournalEntryDetail::insert($insert_journal_insurance_employer_credit_data);

            //end insurance employer credit operation
            //start medecine care fees credit operations
            $med_care_salary_account_id = AccountSetting::where('active',1)->value('med_care_salary_account_id');
            $medCareCreditLedgerState = $this->updateLedgerTotals($med_care_salary_account_id, 'credit', $soins_medicaux);

            $journalEntryMedCareCreditdata = array(
                    'date' => $data->date_fin,
                    'document_no' => $data->code,
                    'description' => "medical care fees",
                    'account_id' => $med_care_salary_account_id,
                    'libelle' => "medical care fees",
                    'credit' => $soins_medicaux,
                    'balance' => $medCareCreditLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_med_care_credit_data[] = $journalEntryMedCareCreditdata;
                
            AccountJournalEntryDetail::insert($insert_journal_med_care_credit_data);

            //end medecine care fees credit operation
            //start loan salary credit operations
            // For each employee having an outstanding loan retention on salary (retenue_pret > 0 and etat = 0),
            // credit the employee's own advance-on-salary account ($employe->account_id).
            $insert_journal_loan_salary_credit_data = [];

            $loanSalaryPaiements = HrPaiement::where('code', $code)
                ->where('retenue_pret', '>', 0)
                ->where('etat', 0)
                ->get();

            foreach ($loanSalaryPaiements as $loanPaiement) {
                $employe = HrEmploye::find($loanPaiement->employe_id);

                // Skip if employee or its advance-on-salary account is not defined
                if (!$employe || !$employe->account_id) {
                    continue;
                }

                $loanSalaryCreditLedgerState = $this->updateLedgerTotals($employe->account_id, 'credit', $loanPaiement->retenue_pret);

                $journalEntryLoanSalaryCreditdata = [
                    'date'        => $data->date_fin,
                    'document_no' => $data->code,
                    'description' => 'loan on salary',
                    'account_id'  => $employe->account_id,
                    'libelle'     => 'loan on salary',
                    'credit'      => $loanPaiement->retenue_pret,
                    'balance'     => $loanSalaryCreditLedgerState['balance'],
                    'created_at'  => \Carbon\Carbon::now(),
                ];

                $insert_journal_loan_salary_credit_data[] = $journalEntryLoanSalaryCreditdata;
            }

            if (!empty($insert_journal_loan_salary_credit_data)) {
                AccountJournalEntryDetail::insert($insert_journal_loan_salary_credit_data);
            }

            //end loan on salary credit operation
            //start other deduction credit operations
            $ded1_salary_account_id = AccountSetting::where('active',1)->value('ded1_salary_account_id');
            $otherDeductionCreditLedgerState = $this->updateLedgerTotals($ded1_salary_account_id, 'credit', $autre_retenue);

            $journalEntryOtherDeductionCreditdata = array(
                    'date' => $data->date_fin,
                    'document_no' => $data->code,
                    'description' => "other deduction",
                    'account_id' => $ded1_salary_account_id,
                    'libelle' => "other deduction",
                    'credit' => $autre_retenue,
                    'balance' => $otherDeductionCreditLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_other_deduction_credit_data[] = $journalEntryOtherDeductionCreditdata;
                
            AccountJournalEntryDetail::insert($insert_journal_other_deduction_credit_data);

            //end other deduction credit operation
            //end accounting

            HrJournalPaie::where('code', '=', $code)
                ->update(['etat' => 1,'cloture_par' => $this->user->name]);
            HrJournalPaieDetail::where('code', '=', $code)
                ->update(['etat' => 1,'cloture_par' => $this->user->name]);

            HrPaiement::where('code', '=', $code)
                ->update(['etat' => 1,'cloture_par' => $this->user->name]);

            DB::commit();

            session()->flash('success', 'Journal Paie  est clôturé avec succés!!');
            return back();

        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\HrJournalPaie  $journal_paie
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_journal_paie.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le journal de paye! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $journal_paie = HrJournalPaie::findOrFail($id);
        return view('backend.pages.hr.journal_paie.edit', compact('journal_paie'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\HrJournalPaie  $journal_paie
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('hr_journal_paie.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le journal de paye! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $request->validate([
            'date_debut' => 'required',
            'date_fin' => 'required|after:date_debut',
            'title' => 'required|min:10|max:255',
        ]);

        $journal_paie = HrJournalPaie::findOrFail($id);

        // On ne modifie que le journal de paie en cours (non clôturé)
        if ((int) $journal_paie->etat !== 0) {
            session()->flash('error', 'Seul le journal de paie en cours peut être modifié. Les actions sont désactivées après clôture.');
            return back();
        }

        try {
            DB::beginTransaction();

            // Journal précédent (même logique que à la création : on reprend les données du mois précédent)
            $previous = HrJournalPaie::where('id', '<', $journal_paie->id)->orderBy('id', 'desc')->first();

            // Supprimer toutes les fiches de paie liées à ce journal (même code)
            HrPaiement::where('code', $journal_paie->code)->delete();

            // Mettre à jour les dates du détail du journal
            HrJournalPaieDetail::where('code', $journal_paie->code)->update([
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
            ]);

            // Recopier les paiements depuis le journal précédent et intégrer les avances
            if ($previous) {
                $datas = HrPaiement::where('code', $previous->code)->get();
                foreach ($datas as $data) {
                    $retenuePret = $this->computeLoanRetentionForPeriod($data->employe_id, $request->date_debut);
                    list($netImposable, $netNonImposable) = $this->recalculateNetFromPaiementData($data, $retenuePret);

                    $paiement = new HrPaiement();
                    $paiement->employe_id = $data->employe_id;
                    $paiement->code = $journal_paie->code;
                    $paiement->date_debut = $request->date_debut;
                    $paiement->date_fin = $request->date_fin;
                    $paiement->etat = 0;
                    $paiement->somme_salaire_base = $data->somme_salaire_base;
                    $paiement->somme_salaire_brut_imposable = $data->somme_salaire_brut_imposable;
                    $paiement->somme_salaire_brut_non_imposable = $data->somme_salaire_brut_non_imposable;
                    $paiement->somme_salaire_net_imposable = $netImposable;
                    $paiement->somme_salaire_net_non_imposable = $netNonImposable;
                    $paiement->nbre_jours_ouvrables = $data->nbre_jours_ouvrables;
                    $paiement->code_banque = $data->code_banque;
                    $paiement->code_departement = $data->code_departement;
                    $paiement->code_service = $data->code_service;
                    $paiement->numero_compte = $data->numero_compte;
                    $paiement->somme_impot = $data->somme_impot;
                    $paiement->somme_cotisation_inss = $data->somme_cotisation_inss;
                    $paiement->inss_employeur = $data->inss_employeur;
                    $paiement->assurance_maladie_employe = $data->assurance_maladie_employe;
                    $paiement->assurance_maladie_employeur = $data->assurance_maladie_employeur;
                    $paiement->indemnite_deplacement = $data->indemnite_deplacement;
                    $paiement->indemnite_logement = $data->indemnite_logement;
                    $paiement->allocation_familiale = $data->allocation_familiale;
                    $paiement->prime_fonction = $data->prime_fonction;
                    $paiement->soins_medicaux = $data->soins_medicaux ?? 0;
                    $paiement->autre_retenue = $data->autre_retenue ?? 0;
                    $paiement->retenue_pret = $retenuePret;
                    $paiement->company_id = $data->company_id;
                    $paiement->created_by = $this->user->name;
                    $paiement->save();
                }
            }

            $journal_paie->date_debut = $request->date_debut;
            $journal_paie->title = $request->title;
            $journal_paie->date_fin = $request->date_fin;
            $journal_paie->etat = 0;
            $journal_paie->save();

            DB::commit();
            session()->flash('success', 'Journal de paie modifié : fiches recopiées depuis le mois précédent et avances sur salaire intégrées.');
            return back();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', __('messages.error_journal_modification') . $e->getMessage());
            return back();
        }
    }


    public function exportToExcel(Request $request)
    {
        return Excel::download(new JournalPaieExport, 'journal_paie.xlsx');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\HrJournalPaie  $journal_paie
     * @return \Illuminate\Http\Response
     */
    public function destroy($code)
    {
        if (is_null($this->user) || !$this->user->can('hr_journal_paie.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer le journal de paye! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $journal_paie = HrJournalPaie::where('code', $code)->first();
        if (!$journal_paie) {
            session()->flash('error', 'Journal de paie introuvable.');
            return redirect()->back();
        }

        // Seul le journal en cours peut être supprimé ; après clôture les actions sont désactivées
        if ((int) $journal_paie->etat !== 0) {
            session()->flash('error', 'Impossible de supprimer un journal déjà clôturé.');
            return redirect()->back();
        }

        $journal_paie->delete();
        session()->flash('success', 'Journal Paie est supprimé !!');
        return redirect()->back();
    }

    /**
     * Increment debit/credit totals and keep AccountLedger balance in sync.
     */
    private function updateAccountLedgerSums($accountId, $debitIncrement = 0, $creditIncrement = 0)
    {
        $ledger = AccountLedger::where('account_id', $accountId)->lockForUpdate()->first();

        if (!$ledger) {
            $ledger = new AccountLedger();
            $ledger->account_id = $accountId;
            $ledger->debit = 0;
            $ledger->credit = 0;
            $ledger->balance = 0;
        }

        $ledger->debit = ($ledger->debit ?? 0) + $debitIncrement;
        $ledger->credit = ($ledger->credit ?? 0) + $creditIncrement;
        $ledger->balance = ($ledger->debit ?? 0) - ($ledger->credit ?? 0);
        $ledger->save();

        return $ledger;
    }

    /**
     * Update ledger totals using the same pattern as FactureController.
     */
    protected function updateLedgerTotals(int $accountId, string $type, float $amount): array
    {
        $ledger = AccountLedger::firstOrNew(['account_id' => $accountId]);

        $currentDebit = (float) ($ledger->debit ?? 0);
        $currentCredit = (float) ($ledger->credit ?? 0);
        $movement = (float) $amount;

        if ($movement === 0.0) {
            return [
                'debit' => $currentDebit,
                'credit' => $currentCredit,
                'balance' => $currentDebit - $currentCredit,
            ];
        }

        if ($type === 'debit') {
            $currentDebit += $movement;
        } else {
            $currentCredit += $movement;
        }

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

    /**
     * Recalcule le salaire net à partir des éléments de paie et de la retenue prêt.
     * Retourne [somme_salaire_net_imposable, somme_salaire_net_non_imposable].
     */
    protected function recalculateNetFromPaiementData($data, float $retenuePret): array
    {
        $salaireBrut = ($data->somme_salaire_base ?? 0)
            + ($data->allocation_familiale ?? 0)
            + ($data->indemnite_logement ?? 0)
            + ($data->indemnite_deplacement ?? 0)
            + ($data->prime_fonction ?? 0);
        $soinsMedicaux = (float) ($data->soins_medicaux ?? 0);
        $autreRetenue = (float) ($data->autre_retenue ?? 0);
        $totalDeductions = ($data->somme_cotisation_inss ?? 0)
            + ($data->assurance_maladie_employe ?? 0)
            + ($data->somme_impot ?? 0)
            + $retenuePret
            + $soinsMedicaux
            + $autreRetenue;
        $net = $salaireBrut - $totalDeductions;
        return [$net, $net];
    }

    /**
     * Calcule la retenue de prêt à appliquer pour un employé
     * sur la période de paie correspondant à $dateDebut.
     *
     * - Si l'employé n'a pas d'avance approuvée en attente => 0
     * - Si l'avance est à rembourser en 1, 2 ou 3 mois, on retourne
     *   le montant correspondant au mois (1er, 2ème, 3ème) en fonction
     *   de la date de l'avance et des flags de paiement.
     */
    protected function computeLoanRetentionForPeriod(int $employeId, string $dateDebut): float
    {
        if (empty($employeId) || empty($dateDebut)) {
            return 0.0;
        }

        $paymentDate = Carbon::parse($dateDebut);
        $paymentMonth = $paymentDate->month;
        $paymentYear = $paymentDate->year;

        // Avances approuvées (status = 3) ; on ignore les prêts déjà totalement payés (flag2 = '2')
        $loans = HrLoan::where('employe_id', $employeId)
            ->where('status', '3')
            ->where(function ($q) {
                $q->whereNull('flag2')->orWhere('flag2', '!=', '2');
            })
            ->get();

        foreach ($loans as $loan) {
            if (empty($loan->date)) {
                continue;
            }

            $loanDate = Carbon::parse($loan->date);
            $loanMonth = $loanDate->month;
            $loanYear = $loanDate->year;

            // Mois de paie par rapport au mois du prêt : 0 = 1er mois, 1 = 2e, 2 = 3e
            $monthDiff = (($paymentYear - $loanYear) * 12) + ($paymentMonth - $loanMonth);

            // 1er mois : retenue = amount_to_pay_1m
            if ($monthDiff === 0) {
                if ($loan->flag1 == '0' || $loan->flag1 == 0) {
                    return (float) ($loan->amount_to_pay_1m ?? 0);
                }
            }
            // 2e mois (mois suivant) : retenue = amount_to_pay_2m
            elseif ($monthDiff === 1) {
                if (($loan->flag1 == '1' || $loan->flag1 == 1) && ($loan->flag2 == '0' || $loan->flag2 == 0)) {
                    return (float) ($loan->amount_to_pay_2m ?? 0);
                }
            }
            // 3e mois : retenue = amount_to_pay_3m jusqu'à dette totalement payée
            elseif ($monthDiff === 2) {
                if (($loan->flag1 == '1' || $loan->flag1 == 1) && ($loan->flag2 == '1' || $loan->flag2 == 1)) {
                    return (float) ($loan->amount_to_pay_3m ?? 0);
                }
            }
        }

        return 0.0;
    }
}
