<?php

namespace App\Http\Controllers\Backend\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Storage;
use App\Models\HrEmploye;
use App\Models\HrPaiement;
use App\Models\HrJournalPaie;
use App\Models\HrLoan;
use App\Models\HrDepartement;
use App\Models\HrService;
use App\Models\HrBanque;
use App\Models\HrReglage;
use App\Models\HrCompany;
use App\Models\Accounting\AccountCostCenter;
use App\Models\Accounting\AccountJournalEntry;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountCurrency;
use App\Models\Accounting\AccountSetting;
use Carbon\Carbon;
use Validator;
use PDF;

class PaiementController extends Controller
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

    public function index($company_id)
    {
        if (is_null($this->user) || !$this->user->can('hr_paiement.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les employés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $paiements = HrPaiement::where('employe_id','!=','')->where('company_id',$company_id)->get();
        return view('backend.pages.hr.paiement.index',compact('paiements'));
    }

    public function selectByCompany()
    {
        if (is_null($this->user) || !$this->user->can('hr_paiement.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les employés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $companies = HrCompany::all();
        return view('backend.pages.hr.paiement.select_by_company',compact('companies'));
    }

    public function createByCompany()
    {
        if (is_null($this->user) || !$this->user->can('hr_paiement.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les employés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $companies = HrCompany::all();
        return view('backend.pages.hr.paiement.create_by_company',compact('companies'));
    }

    public function create($company_id)
    {
        if (is_null($this->user) || !$this->user->can('hr_paiement.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer les employés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $journal_paie = HrJournalPaie::where('etat', 0)->first();

        $employes = HrEmploye::where('company_id',$company_id)->whereNotIn('id', function($q){
        $q->select('employe_id')->where('code','0001')->where('employe_id','!=','')->from('hr_paiements');
        })->orderBy('firstname')->get();
        

        return view('backend.pages.hr.paiement.create',compact('employes','company_id','journal_paie'));
    }

    public function fetch(Request $request)
    {
        $data['data'] = HrEmploye::where("id", $request->employe_id)->get(["id","somme_salaire_base","numero_compte","indemnite_deplacement","indemnite_logement","prime_fonction","matricule_no","cni","code_departement","code_service","code_banque"]);
  
        return response()->json($data);
    }

    public function fetchLoan(Request $request)
    {
        $employe_id = $request->employe_id;
        $date_debut = $request->date_debut; // Format: Y-m-d H:i:s or Y-m-d
        
        if (!$employe_id || !$date_debut) {
            return response()->json(['retenue_pret' => 0]);
        }

        // Get the month from date_debut
        $paymentDate = Carbon::parse($date_debut);
        $paymentMonth = $paymentDate->month;
        $paymentYear = $paymentDate->year;

        // Avances approuvées (status = 3) ; on ignore les prêts déjà totalement payés (flag2 = '2')
        $loans = HrLoan::where('employe_id', $employe_id)
            ->where('status', '3')
            ->where(function ($q) {
                $q->whereNull('flag2')->orWhere('flag2', '!=', '2');
            })
            ->get();

        $retenue_pret = 0;
        $loanId = null;

        foreach ($loans as $loan) {
            if (!$loan->date) {
                continue;
            }

            $loanDate = Carbon::parse($loan->date);
            $loanMonth = $loanDate->month;
            $loanYear = $loanDate->year;

            // Mois de paie par rapport au mois du prêt : 0 = 1er mois, 1 = 2e mois, 2 = 3e mois
            $monthDiff = (($paymentYear - $loanYear) * 12) + ($paymentMonth - $loanMonth);

            if ($monthDiff == 0) {
                // 1er mois : déduction amount_to_pay_1m (engagement 1, 2 ou 3 mois)
                if ($loan->flag1 == '0' || $loan->flag1 == 0) {
                    $retenue_pret = $loan->amount_to_pay_1m ?? 0;
                    $loanId = $loan->id;
                    break;
                }
            } elseif ($monthDiff == 1) {
                // 2e mois : déduction amount_to_pay_2m (après que le 1er soit payé)
                if (($loan->flag1 == '1' || $loan->flag1 == 1) && ($loan->flag2 == '0' || $loan->flag2 == 0)) {
                    $retenue_pret = $loan->amount_to_pay_2m ?? 0;
                    $loanId = $loan->id;
                    break;
                }
            } elseif ($monthDiff == 2) {
                // 3e mois : déduction amount_to_pay_3m (après que les 2 premiers soient payés)
                if (($loan->flag1 == '1' || $loan->flag1 == 1) && ($loan->flag2 == '1' || $loan->flag2 == 1)) {
                    $retenue_pret = $loan->amount_to_pay_3m ?? 0;
                    $loanId = $loan->id;
                    break;
                }
            }
            // Si monthDiff > 2 ou dette déjà totalement payée (flag2 = '2'), on ne déduit plus
        }

        return response()->json([
            'retenue_pret' => $retenue_pret,
            'loan_id' => $loanId
        ]);
    }

     public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_paiement.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer les employés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $rules = array(
                'date_debut' => 'required',
                'date_fin' => 'required|after:date_debut',
                'somme_salaire_base' => 'required',
                'employe_id' => 'required',
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

        try {DB::beginTransaction();

        $codeJournalPaieEncours = HrJournalPaie::where('etat', 0)->value('code');

        $plafond_cotisation = 450000;

        $employe_id = $request->employe_id;
        $date_debut = $request->date_debut;
        $date_fin = $request->date_fin;
        $indemnite_id = $request->indemnite_id;
        $cotisation_id = $request->cotisation_id;
        $impot_id = $request->impot_id;
        $etat = 0;
        $somme_salaire_base = $request->somme_salaire_base;
        $somme_salaire_net = $request->somme_salaire_net;
        $nbre_jours_ouvrables = $request->nbre_jours_ouvrables;
        $nbre_jours_conges = $request->nbre_jours_conges;
        $nbre_jours_conges_consomes = $request->nbre_jours_conges_consomes;
        $code_banque = $request->code_banque;
        $code_departement = $request->code_departement;
        $code_service = $request->code_service;
        $numero_compte = $request->numero_compte;
        $allocation_familiale = $request->allocation_familiale;
        $montant_ind_imposable = $request->montant_ind_imposable;
        $montant_ind_non_imposable = $request->montant_ind_non_imposable;
        $designation_prime_non_imposable = $request->designation_prime_non_imposable;
        $montant_prime_non_imposable = $request->montant_prime_non_imposable;
        $soins_medicaux = $request->soins_medicaux;
        $retenue_pret = $request->retenue_pret;
        $autre_retenue = $request->autre_retenue;
        $company_id = $request->company_id;
        $created_by = $this->user->name;
            

      $indemnite_deplacement = HrEmploye::where('id',$employe_id)->value('indemnite_deplacement');
      $indemnite_logement = HrEmploye::where('id',$employe_id)->value('indemnite_logement');
      $prime_fonction = HrEmploye::where('id',$employe_id)->value('prime_fonction');

      $salaire_brut = $somme_salaire_base + $allocation_familiale + 
       $indemnite_logement + $indemnite_deplacement + $prime_fonction;


            if ($salaire_brut < 450000) {
                $inss = ($salaire_brut * 4)/100;
                $somme_cotisation_inss = ($salaire_brut * 4)/100;
                $inss_employeur = ($salaire_brut * 6)/100;
            }else{
                $inss = (450000 * 4)/100;
                $somme_cotisation_inss = ($plafond_cotisation * 4)/100;
                $inss_employeur = ($plafond_cotisation * 6)/100;
            }

            if ($salaire_brut >= 2000000) {
                $base_assurance_maladie = 2000000;
                $assurance_maladie_employe = $base_assurance_maladie * 4 /100;
                $assurance_maladie_employeur = $base_assurance_maladie * 6 /100;
            }elseif ($salaire_brut >= 1000000 && $salaire_brut < 2000000) {
                $base_assurance_maladie = 1000000;
                $assurance_maladie_employe = $base_assurance_maladie * 4 /100;
                $assurance_maladie_employeur = $base_assurance_maladie * 6 /100;
            }elseif ($salaire_brut >= 500000 && $salaire_brut < 1000000) {
                $base_assurance_maladie = 500000;
                $assurance_maladie_employe = $base_assurance_maladie * 4 /100;
                $assurance_maladie_employeur = $base_assurance_maladie * 6 /100;
            }elseif ($salaire_brut >= 400000 && $salaire_brut < 500000) {
                $base_assurance_maladie = 400000;
                $assurance_maladie_employe = $base_assurance_maladie * 4 /100;
                $assurance_maladie_employeur = $base_assurance_maladie * 6 /100;
            }elseif ($salaire_brut >= 300000 && $salaire_brut < 400000) {
                $base_assurance_maladie = 300000;
                $assurance_maladie_employe = $base_assurance_maladie * 4 /100;
                $assurance_maladie_employeur = $base_assurance_maladie * 6 /100;
            }elseif ($salaire_brut >= 250000 && $salaire_brut < 300000) {
                $base_assurance_maladie = 250000;
                $assurance_maladie_employe = $base_assurance_maladie * 4 /100;
                $assurance_maladie_employeur = $base_assurance_maladie * 6 /100;
            }else{
                $base_assurance_maladie = 200000;
                $assurance_maladie_employe = $base_assurance_maladie * 4 /100;
                $assurance_maladie_employeur = $base_assurance_maladie * 6 /100;
            }


        $base_imposable = $salaire_brut - $indemnite_logement - $indemnite_deplacement - $inss - $assurance_maladie_employe;
      //les impots

            if ($base_imposable >= 0 && $base_imposable <= 150000) {
                $somme_impot = 0;
            }elseif ($base_imposable > 150000 && $base_imposable <= 300000) {
                $somme_impot = (($base_imposable - 150000) * 20)/100;
            }elseif ($base_imposable > 300000) {
                $somme_impot = 30000 + (($base_imposable - 300000) * 30)/100;    
            }

        $somme_salaire_brut_imposable = $salaire_brut;
        $somme_salaire_brut_non_imposable = $salaire_brut;
        $total_deductions = $somme_cotisation_inss + $assurance_maladie_employe + $somme_impot + $retenue_pret + $soins_medicaux + $autre_retenue;
        $somme_salaire_net_imposable = $salaire_brut - $total_deductions;
        $somme_salaire_net_non_imposable = $salaire_brut - $total_deductions;

        $paiement = new HrPaiement();
        $paiement->employe_id = $employe_id;
        $paiement->code = $codeJournalPaieEncours;
        $paiement->date_debut = $request->date_debut;
        $paiement->date_fin = $request->date_fin;
        $paiement->etat = 0;
        $paiement->somme_salaire_base = $somme_salaire_base;
        $paiement->somme_salaire_brut_imposable = $somme_salaire_brut_imposable;
        $paiement->somme_salaire_brut_non_imposable = $somme_salaire_brut_non_imposable;
        $paiement->somme_salaire_net_imposable = $somme_salaire_net_imposable;
        $paiement->somme_salaire_net_non_imposable = $somme_salaire_net_non_imposable;
        $paiement->nbre_jours_ouvrables = $request->nbre_jours_ouvrables;
        $paiement->code_banque = $code_banque;
        $paiement->code_departement = $code_departement;
        $paiement->code_service = $code_service;
        $paiement->numero_compte = $request->numero_compte;
        $paiement->somme_impot = $somme_impot;
        $paiement->somme_cotisation_inss = $somme_cotisation_inss;
        $paiement->inss_employeur = $inss_employeur;
        $paiement->assurance_maladie_employe = $assurance_maladie_employe;
        $paiement->assurance_maladie_employeur = $assurance_maladie_employeur;
        $paiement->indemnite_deplacement = $indemnite_deplacement;
        $paiement->indemnite_logement = $indemnite_logement;
        $paiement->allocation_familiale = $allocation_familiale;
        $paiement->soins_medicaux = $soins_medicaux;
        $paiement->prime_fonction = $prime_fonction;
        $paiement->retenue_pret = $request->retenue_pret;
        $paiement->autre_retenue = $request->autre_retenue;
        $paiement->company_id = $request->company_id;
        $paiement->created_by = $this->user->name;
        $paiement->save();

        // Update loan flags if retenue_pret > 0
        if ($request->retenue_pret > 0) {
            $paymentDate = Carbon::parse($date_debut);
            $paymentMonth = $paymentDate->month;
            $paymentYear = $paymentDate->year;

            $loans = HrLoan::where('employe_id', $employe_id)
                ->where('status', '3')
                ->get();

            foreach ($loans as $loan) {
                if (!$loan->date) {
                    continue;
                }

                $loanDate = Carbon::parse($loan->date);
                $loanMonth = $loanDate->month;
                $loanYear = $loanDate->year;

                $monthDiff = (($paymentYear - $loanYear) * 12) + ($paymentMonth - $loanMonth);

                if ($monthDiff == 0 && ($loan->flag1 == '0' || $loan->flag1 == 0)) {
                    // First payment - set flag1 = 1
                    $loan->flag1 = '1';
                    $loan->save();
                    break;
                } elseif ($monthDiff == 1 && ($loan->flag1 == '1' || $loan->flag1 == 1) && ($loan->flag2 == '0' || $loan->flag2 == 0)) {
                    // Second payment - set flag2 = 1
                    $loan->flag2 = '1';
                    $loan->save();
                    break;
                } elseif ($monthDiff == 2 && ($loan->flag1 == '1' || $loan->flag1 == 1) && ($loan->flag2 == '1' || $loan->flag2 == 1)) {
                    // Third payment - set flag2 = 2 (fully paid)
                    $loan->flag2 = '2';
                    $loan->save();
                    break;
                }
            }
        }

        DB::commit();

            session()->flash('success', $this->user->name.', vous avez créé une fiche de paie avec succés !!');
            return redirect()->back();

            } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
        //return redirect()->route('admin.hr-paiements.index',$company_id);
    }

    public function show($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_paiement.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de voir les employés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }


        $employe_id = HrPaiement::where('id',$id)->where('employe_id','!=','')->value('employe_id');
        $code = HrPaiement::where('id',$id)->where('employe_id','!=','')->value('code');
        $data = HrPaiement::where('code',$code)->where('employe_id','!=','')->where('employe_id',$employe_id)->first();

        $setting = DB::table('settings')->orderBy('created_at','desc')->first();

        return view('backend.pages.hr.paiement.show',compact('data'));
    }


     public function ficheSalaire($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_paiement.create')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }
        

        $employe_id = HrPaiement::where('id',$id)->where('employe_id','!=','')->value('employe_id');
        $company_id = HrPaiement::where('id',$id)->where('employe_id','!=','')->value('company_id');
        $company = HrCompany::where('id',$company_id)->first();
        $code = HrPaiement::where('id',$id)->where('employe_id','!=','')->value('code');
        $matricule_no = HrEmploye::where('id',$employe_id)->value('matricule_no');
        $data = HrPaiement::where('code',$code)->where('employe_id','!=','')->where('employe_id',$employe_id)->first();

        $setting = DB::table('settings')->orderBy('created_at','desc')->first();

        $currentTime = Carbon::now();
        $dateT =  $currentTime->toDateTimeString();
        $dateTime = str_replace([' ',':'], '_', $dateT);

        $pdf = PDF::loadView('backend.pages.hr.document.fiche_salaire',compact('data','setting','company'));

        Storage::put('public/'.$company->name.'/hr/bulletin-salaire/'.$matricule_no.'_'.$dateTime.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('BULLETIN DE PAIE '.$data->employe->firstname.' '.$data->employe->lastname.'.pdf');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\HrEmploye  $employe
     * @return \Illuminate\Http\Response
     */
    public function edit($id,$company_id)
    {
        if (is_null($this->user) || !$this->user->can('hr_paiement.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier l\'employé! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $employe_id = HrPaiement::where('id',$id)->where('company_id',$company_id)->value('employe_id');
        $code = HrPaiement::where('id',$id)->where('company_id',$company_id)->where('employe_id','!=','')->value('code');
        $data = HrPaiement::where('code',$code)->where('company_id',$company_id)->where('employe_id',$employe_id)->first();

        $employes = HrEmploye::orderBy('firstname','asc')->get();

        return view('backend.pages.hr.paiement.edit',compact('data','employes','company_id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\HrEmploye  $employe
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('hr_paiement.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier l\'employé! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $rules = array(
                'date_debut' => 'required',
                'date_fin' => 'required|after:date_debut',
                'somme_salaire_base' => 'required',
                'employe_id' => 'required',
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

        try {DB::beginTransaction();

        $codeJournalPaieEncours = HrJournalPaie::where('etat', 0)->value('code');

        $plafond_cotisation = 450000;

        $employe_id = $request->employe_id;
        $date_debut = $request->date_debut;
        $date_fin = $request->date_fin;
        $indemnite_id = $request->indemnite_id;
        $cotisation_id = $request->cotisation_id;
        $impot_id = $request->impot_id;
        $etat = 0;
        $somme_salaire_base = $request->somme_salaire_base;
        $somme_salaire_net = $request->somme_salaire_net;
        $nbre_jours_ouvrables = $request->nbre_jours_ouvrables;
        $nbre_jours_conges = $request->nbre_jours_conges;
        $nbre_jours_conges_consomes = $request->nbre_jours_conges_consomes;
        $code_banque = $request->code_banque;
        $code_departement = $request->code_departement;
        $code_service = $request->code_service;
        $numero_compte = $request->numero_compte;
        $allocation_familiale = $request->allocation_familiale;
        $montant_ind_imposable = $request->montant_ind_imposable;
        $montant_ind_non_imposable = $request->montant_ind_non_imposable;
        $designation_prime_non_imposable = $request->designation_prime_non_imposable;
        $montant_prime_non_imposable = $request->montant_prime_non_imposable;
        $soins_medicaux = $request->soins_medicaux;
        $retenue_pret = $request->retenue_pret;
        $autre_retenue = $request->autre_retenue;
        $company_id = $request->company_id;
        $created_by = $this->user->name;
            

      $indemnite_deplacement = HrEmploye::where('id',$employe_id)->value('indemnite_deplacement');
      $indemnite_logement = HrEmploye::where('id',$employe_id)->value('indemnite_logement');
      $prime_fonction = HrEmploye::where('id',$employe_id)->value('prime_fonction');

      $salaire_brut = $somme_salaire_base + $allocation_familiale + 
       $indemnite_logement + $indemnite_deplacement + $prime_fonction;


            if ($salaire_brut < 450000) {
                $inss = ($salaire_brut * 4)/100;
                $somme_cotisation_inss = ($salaire_brut * 4)/100;
                $inss_employeur = ($salaire_brut * 6)/100;
            }else{
                $inss = (450000 * 4)/100;
                $somme_cotisation_inss = ($plafond_cotisation * 4)/100;
                $inss_employeur = ($plafond_cotisation * 6)/100;
            }

            if ($salaire_brut >= 2000000) {
                $base_assurance_maladie = 2000000;
                $assurance_maladie_employe = $base_assurance_maladie * 4 /100;
                $assurance_maladie_employeur = $base_assurance_maladie * 6 /100;
            }elseif ($salaire_brut >= 1000000 && $salaire_brut < 2000000) {
                $base_assurance_maladie = 1000000;
                $assurance_maladie_employe = $base_assurance_maladie * 4 /100;
                $assurance_maladie_employeur = $base_assurance_maladie * 6 /100;
            }elseif ($salaire_brut >= 500000 && $salaire_brut < 1000000) {
                $base_assurance_maladie = 500000;
                $assurance_maladie_employe = $base_assurance_maladie * 4 /100;
                $assurance_maladie_employeur = $base_assurance_maladie * 6 /100;
            }elseif ($salaire_brut >= 400000 && $salaire_brut < 500000) {
                $base_assurance_maladie = 400000;
                $assurance_maladie_employe = $base_assurance_maladie * 4 /100;
                $assurance_maladie_employeur = $base_assurance_maladie * 6 /100;
            }elseif ($salaire_brut >= 300000 && $salaire_brut < 400000) {
                $base_assurance_maladie = 300000;
                $assurance_maladie_employe = $base_assurance_maladie * 4 /100;
                $assurance_maladie_employeur = $base_assurance_maladie * 6 /100;
            }elseif ($salaire_brut >= 250000 && $salaire_brut < 300000) {
                $base_assurance_maladie = 250000;
                $assurance_maladie_employe = $base_assurance_maladie * 4 /100;
                $assurance_maladie_employeur = $base_assurance_maladie * 6 /100;
            }else{
                $base_assurance_maladie = 200000;
                $assurance_maladie_employe = $base_assurance_maladie * 4 /100;
                $assurance_maladie_employeur = $base_assurance_maladie * 6 /100;
            }


        $base_imposable = $salaire_brut - $indemnite_logement - $indemnite_deplacement - $inss - $assurance_maladie_employe;
      //les impots

            if ($base_imposable >= 0 && $base_imposable <= 150000) {
                $somme_impot = 0;
            }elseif ($base_imposable > 150000 && $base_imposable <= 300000) {
                $somme_impot = (($base_imposable - 150000) * 20)/100;
            }elseif ($base_imposable > 300000) {
                $somme_impot = 30000 + (($base_imposable - 300000) * 30)/100;    
            }

        $somme_salaire_brut_imposable = $salaire_brut;
        $somme_salaire_brut_non_imposable = $salaire_brut;
        $total_deductions = $somme_cotisation_inss + $assurance_maladie_employe + $somme_impot + $retenue_pret + $soins_medicaux + $autre_retenue;
        $somme_salaire_net_imposable = $salaire_brut - $total_deductions;
        $somme_salaire_net_non_imposable = $salaire_brut - $total_deductions;


        $employe_id = HrPaiement::where('id',$id)->where('company_id',$company_id)->where('employe_id','!=','')->value('employe_id');
        $code = HrPaiement::where('id',$id)->where('employe_id','!=','')->value('code');
        $paiement = HrPaiement::where('code',$code)->where('company_id',$company_id)->where('employe_id','!=','')->where('employe_id',$employe_id)->first();
        $paiement->employe_id = $employe_id;
        $paiement->code = $codeJournalPaieEncours;
        $paiement->date_debut = $request->date_debut;
        $paiement->date_fin = $request->date_fin;
        $paiement->etat = 0;
        $paiement->somme_salaire_base = $somme_salaire_base;
        $paiement->somme_salaire_brut_imposable = $somme_salaire_brut_imposable;
        $paiement->somme_salaire_brut_non_imposable = $somme_salaire_brut_non_imposable;
        $paiement->somme_salaire_net_imposable = $somme_salaire_net_imposable;
        $paiement->somme_salaire_net_non_imposable = $somme_salaire_net_non_imposable;
        $paiement->nbre_jours_ouvrables = $request->nbre_jours_ouvrables;
        $paiement->code_banque = $code_banque;
        $paiement->code_departement = $code_departement;
        $paiement->code_service = $code_service;
        $paiement->numero_compte = $request->numero_compte;
        $paiement->somme_impot = $somme_impot;
        $paiement->somme_cotisation_inss = $somme_cotisation_inss;
        $paiement->inss_employeur = $inss_employeur;
        $paiement->assurance_maladie_employe = $assurance_maladie_employe;
        $paiement->assurance_maladie_employeur = $assurance_maladie_employeur;
        $paiement->indemnite_deplacement = $indemnite_deplacement;
        $paiement->indemnite_logement = $indemnite_logement;
        $paiement->allocation_familiale = $allocation_familiale;
        $paiement->soins_medicaux = $soins_medicaux;
        $paiement->prime_fonction = $prime_fonction;
        $paiement->retenue_pret = $request->retenue_pret;
        $paiement->autre_retenue = $request->autre_retenue;
        $paiement->company_id = $request->company_id;
        $paiement->created_by = $this->user->name;
        $paiement->save();

        // Update loan flags if retenue_pret > 0
        if ($request->retenue_pret > 0) {
            $paymentDate = Carbon::parse($date_debut);
            $paymentMonth = $paymentDate->month;
            $paymentYear = $paymentDate->year;

            $loans = HrLoan::where('employe_id', $employe_id)
                ->where('status', '3')
                ->get();

            foreach ($loans as $loan) {
                if (!$loan->date) {
                    continue;
                }

                $loanDate = Carbon::parse($loan->date);
                $loanMonth = $loanDate->month;
                $loanYear = $loanDate->year;

                $monthDiff = (($paymentYear - $loanYear) * 12) + ($paymentMonth - $loanMonth);

                if ($monthDiff == 0 && ($loan->flag1 == '0' || $loan->flag1 == 0)) {
                    // First payment - set flag1 = 1
                    $loan->flag1 = '1';
                    $loan->save();
                    break;
                } elseif ($monthDiff == 1 && ($loan->flag1 == '1' || $loan->flag1 == 1) && ($loan->flag2 == '0' || $loan->flag2 == 0)) {
                    // Second payment - set flag2 = 1
                    $loan->flag2 = '1';
                    $loan->save();
                    break;
                } elseif ($monthDiff == 2 && ($loan->flag1 == '1' || $loan->flag1 == 1) && ($loan->flag2 == '1' || $loan->flag2 == 1)) {
                    // Third payment - set flag2 = 2 (fully paid)
                    $loan->flag2 = '2';
                    $loan->save();
                    break;
                }
            }
        }

        $remuneration_brute = $salaire_brut;
        $total_deductions = $paiement->somme_cotisation_inss + $paiement->assurance_maladie_employe + $paiement->somme_impot + $paiement->retenue_pret + $paiement->soins_medicaux + $paiement->autre_retenue;
        $net_a_payer = $remuneration_brute - $total_deductions;

        $employe = HrEmploye::where('company_id',$company_id)->where('id',$employe_id)->first();
        $employe->somme_salaire_base = $paiement->somme_salaire_base;
        $employe->somme_salaire_net = $net_a_payer;
        $employe->indemnite_deplacement = ($paiement->somme_salaire_base * 15)/100;
        $employe->indemnite_logement = ($paiement->somme_salaire_base*60)/100;
        $employe->prime_fonction = $paiement->prime_fonction;
        $employe->save();

        DB::commit();

            session()->flash('success', $this->user->name.', vous avez modifié le bulletin de paie!!');
            return redirect()->back();

            } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\HrPaiement  $paiement
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_paiement.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer l\'employé! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $paiement = HrPaiement::findOrFail($id);
        $paiement->delete();
        session()->flash('success', 'le bulletin de paie est supprimé !!');
        return redirect()->back();
    }
}
