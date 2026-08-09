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
use App\Models\HrEmploye;
use App\Models\HrTakeCongePaye;
use App\Models\HrCongePaye;
use App\Models\HrTypeConge;
use App\Models\HrJournalCongePaye;
use App\Models\HrCompany;
use Carbon\Carbon;
use App\Exports\TakeCongePayeExport;
use Excel;
use PDF;

class TakeCongePayeController extends Controller
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

    public function index(Request $request, $company_id)
    {
        if (is_null($this->user) || !$this->user->can('hr_conge_paye.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les congés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $conge_payes = DB::table('hr_conge_payes')->orderBy('created_at','desc')->get();
        $employes = HrEmploye::where('company_id', $company_id)->orderBy('lastname')->get();
        $take_conge_payes = $this->buildCongePayeQuery($request, (int) $company_id)->get();
        $selectedFilterEmployeId = $request->get('employe_id');

        return view('backend.pages.hr.take_conge_paye.index', compact(
            'take_conge_payes',
            'conge_payes',
            'company_id',
            'employes',
            'selectedFilterEmployeId'
        ));
    }

    public function exportToExcel(Request $request, $company_id)
    {
        if (is_null($this->user) || !$this->user->can('hr_conge_paye.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation d\'exporter les congés!');
        }

        $records = $this->buildCongePayeQuery($request, (int) $company_id)->get();
        $fileName = $this->buildExportFileName('xlsx', $company_id, $request);

        return Excel::download(new TakeCongePayeExport($records), $fileName);
    }

    public function exportToPdf(Request $request, $company_id)
    {
        if (is_null($this->user) || !$this->user->can('hr_conge_paye.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation d\'exporter les congés!');
        }

        $records = $this->buildCongePayeQuery($request, (int) $company_id)
            ->where('etat', 4)
            ->get();
        $company = HrCompany::find($company_id);
        $setting = DB::table('settings')->orderBy('created_at', 'desc')->first();
        $employeLabel = $this->resolveExportEmployeLabel($request, (int) $company_id);
        $filters = [
            'date_debut' => $request->get('date_debut'),
            'date_fin' => $request->get('date_fin'),
        ];

        $pdf = PDF::loadView('backend.pages.hr.document.rapport_mouvements_conge_paye', compact(
            'records',
            'company',
            'setting',
            'employeLabel',
            'filters'
        ));

        return $pdf->download($this->buildExportFileName('pdf', $company_id, $request));
    }

    protected function buildCongePayeQuery(Request $request, int $companyId)
    {
        $query = HrTakeCongePaye::with('employe')
            ->where('company_id', $companyId)
            ->orderBy('date_heure_debut', 'desc');

        if ($request->filled('employe_id')) {
            $query->where('employe_id', $request->employe_id);
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('date_heure_debut', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('date_heure_fin', '<=', $request->date_fin);
        }

        return $query;
    }

    protected function resolveExportEmployeLabel(Request $request, int $companyId): string
    {
        if (!$request->filled('employe_id')) {
            return 'Tous les employés';
        }

        $employe = HrEmploye::where('company_id', $companyId)
            ->where('id', $request->employe_id)
            ->first();

        if (!$employe) {
            return 'Employé inconnu';
        }

        return $employe->display_label;
    }

    protected function buildExportFileName(string $extension, int $companyId, Request $request): string
    {
        $suffix = $request->filled('employe_id') ? 'employe_' . $request->employe_id : 'tous_employes';
        $from = $request->get('date_debut', 'debut');
        $to = $request->get('date_fin', 'fin');

        return 'mouvements_conges_payes_' . $companyId . '_' . $suffix . '_' . $from . '_au_' . $to . '.' . $extension;
    }

    public function selectByCompany()
    {
        if (is_null($this->user) || !$this->user->can('hr_conge_paye.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les congés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $companies = HrCompany::all();
        return view('backend.pages.hr.take_conge_paye.select_by_company',compact('companies'));
    }

    public function createByCompany()
    {
        if (is_null($this->user) || !$this->user->can('hr_conge_paye.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les congés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $companies = HrCompany::all();
        return view('backend.pages.hr.take_conge_paye.create_by_company',compact('companies'));
    }
    
    public function create($company_id)
    {
        if (is_null($this->user) || !$this->user->can('hr_conge_paye.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer les congés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        $employes = HrEmploye::where('company_id',$company_id)->get();

        return view('backend.pages.hr.take_conge_paye.create',compact('employes','company_id'));
    }

    public function fetch(Request $request)
    {
        $employeId = (int) $request->employe_id;
        $excludeId = $request->filled('exclude_id') ? (int) $request->exclude_id : null;
        $balance = $this->resolveCongeBalance($employeId, $excludeId);

        if ($balance === null) {
            return response()->json(['data' => []]);
        }

        // Format compatible avec le JS existant (tableau data[0])
        $data['data'] = [[
            'employe_id' => $employeId,
            'nbre_jours_conge_paye' => $balance['paye'],
            'nbre_jours_conge_pris' => $balance['base_pris'],
            'nbre_jours_conge_restant' => $balance['base_restant'],
            'available_restant' => $balance['base_restant'],
        ]];

        return response()->json($data);
    }

    /**
     * Retourne la date minimale pour date_heure_debut : le lendemain du dernier date_heure_fin
     * des congés déjà créés pour cet employé (congés non rejetés).
     * Si l'employé a des congés en cours, on ne peut attribuer un nouveau congé que si
     * la date de début est strictement supérieure à la date de fin du dernier congé.
     */
    public function fetchMinDateDebut(Request $request)
    {
        $employeId = $request->employe_id;
        $excludeId = $request->exclude_id; // pour l'édition : exclure le congé en cours

        if (!$employeId) {
            return response()->json(['min_date_debut' => null, 'last_date_fin' => null]);
        }

        $query = HrTakeCongePaye::where('employe_id', $employeId)
            ->where('etat', '!=', '-1');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $lastDateFin = $query->orderBy('date_heure_fin', 'desc')->value('date_heure_fin');

        if (!$lastDateFin) {
            return response()->json(['min_date_debut' => null, 'last_date_fin' => null]);
        }

        $minDateDebut = Carbon::parse($lastDateFin)->addDay()->format('Y-m-d');
        return response()->json([
            'min_date_debut' => $minDateDebut,
            'last_date_fin' => Carbon::parse($lastDateFin)->format('Y-m-d'),
        ]);
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_conge_paye.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer les congés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $currentDate = \Carbon\Carbon::now();
        $request->validate([
            'date_heure_debut' => 'required',
            'date_heure_fin' => 'required',
            'nbre_jours_conge_sollicite' => 'required',
            'nbre_jours_conge_restant' => 'required',
            'nbre_jours_conge_pris' => 'required',
            'employe_id' => 'required'

        ]);

        $minDateDebut = $this->getMinDateDebutForEmploye((int) $request->employe_id, null);
        if ($minDateDebut !== null) {
            $debutDate = Carbon::parse($request->date_heure_debut)->format('Y-m-d');
            if ($debutDate < $minDateDebut) {
                $minDay = Carbon::parse($minDateDebut)->format('d/m/Y');
                session()->flash('error', 'Cet employé possède déjà un congé en cours. La date de début du nouveau congé doit être strictement supérieure à la date de fin de son dernier congé. Veuillez choisir au moins le ' . $minDay . '.');
                return back()->withInput();
            }
        }

        try {DB::beginTransaction();

        $code = date("y").substr(number_format(time() * mt_rand(), 0, '', ''), 0, 6);

        $balance = $this->resolveCongeBalance((int) $request->employe_id, null);
        if ($balance === null) {
            session()->flash('error', 'Journal de congé introuvable pour cet employé.');
            return back()->withInput();
        }

        $sollicite = (int) $request->nbre_jours_conge_sollicite;
        if ($balance['base_restant'] >= $sollicite) {
            $amounts = $this->computeCongeAmounts($balance, $sollicite);

            $take_conge_paye = new HrTakeCongePaye();
            $take_conge_paye->date_heure_debut = $request->date_heure_debut;
            $take_conge_paye->date_heure_fin = $request->date_heure_fin;
            $take_conge_paye->nbre_jours_conge_paye = $amounts['paye'];
            $take_conge_paye->nbre_jours_conge_sollicite = $sollicite;
            $take_conge_paye->nbre_jours_conge_pris = $amounts['pris'];
            $take_conge_paye->nbre_jours_conge_restant = $amounts['restant'];
            $take_conge_paye->created_by = $this->user->name;
            $take_conge_paye->etat = 1;
            $take_conge_paye->code = $code;
            $take_conge_paye->employe_id = $request->employe_id;
            $take_conge_paye->company_id = $request->company_id;
            $take_conge_paye->save();
        } else {
            session()->flash('error', 'Les jours demandé sont déjà consommé !!');
            return back()->withInput();
        } 

        $employe = HrEmploye::where('id',$request->employe_id)->value('lastname');

        DB::commit();

        session()->flash('success', $employe.', Vous venez de solliciter le congé payé de !'.$request->nbre_jours_conge_sollicite.' jours');

        return redirect()->route('admin.hr-take-conge-payes.index',$take_conge_paye->company_id);


        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }


    public function validerConge($id)
    {
       if (is_null($this->user) || !$this->user->can('hr_conge_paye.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any conge !');
        }
            HrTakeCongePaye::where('id', '=', $id)
                ->update(['etat' => 2,'valide_par' => $this->user->name]);

        session()->flash('success', 'congé est validé avec succés!!');
        return back();
    }

    public function rejeterConge($id)
    {
       if (is_null($this->user) || !$this->user->can('hr_conge_paye.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any conge !');
        }

        try {DB::beginTransaction();

        $employe_id = HrTakeCongePaye::where('id',$id)->value('employe_id');

        $data = HrJournalCongePaye::where('employe_id', $employe_id)->first();

        $journal_conge_paye = HrJournalCongePaye::where('id',$data->id)->first();
        $journal_conge_paye->nbre_jours_conge_sollicite = 0;
        // pris stocké hors demande en cours : on ne le diminue pas à nouveau
        $journal_conge_paye->nbre_jours_conge_pris = (int) $data->nbre_jours_conge_pris;
        $journal_conge_paye->nbre_jours_conge_restant = (int) $data->nbre_jours_conge_restant + (int) $data->nbre_jours_conge_sollicite;
        $journal_conge_paye->etat = -1;
        $journal_conge_paye->employe_id = $data->employe_id;
        $journal_conge_paye->rejete_par = $this->user->name;
        $journal_conge_paye->save();
        

        $take_conge_paye = HrTakeCongePaye::where('code',$data->code)->first();
        $take_conge_paye->etat = -1;
        $take_conge_paye->employe_id = $data->employe_id;
        //$take_conge_paye->rejete_par = $this->user->name;
        $take_conge_paye->save();

        DB::commit();
        session()->flash('success', 'congé est rejeté !!');
        return back();

        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
    }

    public function annulerConge($id)
    {
       if (is_null($this->user) || !$this->user->can('hr_conge_paye.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any conge !');
        }

        $employe_id = HrTakeCongePaye::where('id',$id)->value('employe_id');

        $data = HrJournalCongePaye::where('employe_id', $employe_id)->first();


        HrJournalCongePaye::where('id', '=', $data->id)
                ->update(['etat' => 1,'annule_par' => $this->user->name]);

        session()->flash('success', 'congé est annulé!!');
        return back();
    }

    public function confirmerConge($id)
    {
       if (is_null($this->user) || !$this->user->can('hr_conge_paye.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any conge !');
        }

        HrTakeCongePaye::where('id', '=', $id)
                ->update(['etat' => 3,'confirme_par' => $this->user->name]);

        session()->flash('success', 'congé est confirmé!!');
        return back();
    }

    public function approuverConge($id)
    {
       if (is_null($this->user) || !$this->user->can('hr_conge_paye.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any conge !');
        }

        try {DB::beginTransaction();

        $data = HrTakeCongePaye::where('id',$id)->first();

        $journal_conge_paye = HrJournalCongePaye::where('employe_id',$data->employe_id)->first();
        $journal_conge_paye->date_heure_debut = $data->date_heure_debut;
        $journal_conge_paye->date_heure_fin = $data->date_heure_fin;
        $journal_conge_paye->nbre_jours_conge_paye = $data->nbre_jours_conge_paye;
        $journal_conge_paye->nbre_jours_conge_pris = $data->nbre_jours_conge_pris;
        $journal_conge_paye->nbre_jours_conge_sollicite = $data->nbre_jours_conge_sollicite;
        $journal_conge_paye->nbre_jours_conge_restant = $data->nbre_jours_conge_restant;
        $journal_conge_paye->created_by = $this->user->name;
        $journal_conge_paye->etat = 1;
        $journal_conge_paye->code = $data->code;
        $journal_conge_paye->employe_id = $data->employe_id;
        $journal_conge_paye->save(); 

        HrTakeCongePaye::where('id', '=', $id)
                ->update(['etat' => 4,'approuve_par' => $this->user->name]);

        DB::commit();
        session()->flash('success', 'Congé Payé est approuvé avec succés !!');
        return back();

        }catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
    }


    public function lettreDemandeConge($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_conge_paye.create')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();

        $employe_id = HrTakeCongePaye::where('id',$id)->value('employe_id');
        $data = HrTakeCongePaye::where('id', $id)->first();
        $take_conge_paye = HrTakeCongePaye::where('id', $id)->first();
        $nbre_jours_conge_paye = HrJournalCongePaye::where('employe_id',$employe_id)->value('nbre_jours_conge_paye');
        $pdf = PDF::loadView('backend.pages.hr.document.lettre_demande_conge',compact('data','take_conge_paye','setting','nbre_jours_conge_paye'));

        // download pdf file
        return $pdf->download('LETTRE DE DEMANDE CONGE'.'.pdf');
        
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\HrTakeCongePaye  $take_conge_paye
     * @return \Illuminate\Http\Response
     */
    public function show($id){

    }
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_conge_paye.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modfier le congé! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        $data = HrTakeCongePaye::with('employe')->findOrFail($id);

        if ($response = $this->guardCongePayeEditable($data)) {
            return $response;
        }

        $employes = HrEmploye::where('company_id', $data->company_id)->orderBy('lastname')->get();

        $balance = $this->resolveCongeBalance((int) $data->employe_id, (int) $data->id);
        $sollicite = (int) $data->nbre_jours_conge_sollicite;
        $amounts = $balance
            ? $this->computeCongeAmounts($balance, $sollicite)
            : [
                'paye' => (int) $data->nbre_jours_conge_paye,
                'pris' => (int) $data->nbre_jours_conge_pris,
                'restant' => (int) $data->nbre_jours_conge_restant,
                'sollicite' => $sollicite,
            ];

        return view('backend.pages.hr.take_conge_paye.edit', compact('data', 'employes', 'balance', 'amounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\HrTakeCongePaye  $take_conge_paye
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('hr_conge_paye.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modfier le congé! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $take_conge_paye = HrTakeCongePaye::findOrFail($id);

        if ($response = $this->guardCongePayeEditable($take_conge_paye)) {
            return $response;
        }

        //
        $currentDate = \Carbon\Carbon::now();
        $request->validate([
            'date_heure_debut' => 'required',
            'date_heure_fin' => 'required',
            'nbre_jours_conge_sollicite' => 'required',
            'nbre_jours_conge_restant' => 'required',
            'nbre_jours_conge_pris' => 'required',
            'employe_id' => 'required'

        ]);

        $minDateDebut = $this->getMinDateDebutForEmploye((int) $request->employe_id, (int) $id);
        if ($minDateDebut !== null) {
            $debutDate = Carbon::parse($request->date_heure_debut)->format('Y-m-d');
            if ($debutDate < $minDateDebut) {
                $minDay = Carbon::parse($minDateDebut)->format('d/m/Y');
                session()->flash('error', 'Cet employé possède déjà un autre congé en cours. La date de début doit être strictement supérieure à la date de fin de son dernier congé. Veuillez choisir au moins le ' . $minDay . '.');
                return back()->withInput();
            }
        }

        try {DB::beginTransaction();

        $balance = $this->resolveCongeBalance((int) $request->employe_id, (int) $id);
        if ($balance === null) {
            session()->flash('error', 'Journal de congé introuvable pour cet employé.');
            return back()->withInput();
        }

        $sollicite = (int) $request->nbre_jours_conge_sollicite;
        if ($balance['base_restant'] >= $sollicite) {
            $amounts = $this->computeCongeAmounts($balance, $sollicite);

            $take_conge_paye = HrTakeCongePaye::findOrFail($id);
            $take_conge_paye->date_heure_debut = $request->date_heure_debut;
            $take_conge_paye->date_heure_fin = $request->date_heure_fin;
            $take_conge_paye->nbre_jours_conge_paye = $amounts['paye'];
            $take_conge_paye->nbre_jours_conge_sollicite = $sollicite;
            $take_conge_paye->nbre_jours_conge_pris = $amounts['pris'];
            $take_conge_paye->nbre_jours_conge_restant = $amounts['restant'];
            $take_conge_paye->created_by = $this->user->name;
            $take_conge_paye->etat = 1;
            $take_conge_paye->employe_id = $request->employe_id;
            $take_conge_paye->save();
        } else {
            session()->flash('error', 'Les jours demandé sont déjà consommé !!');
            return back()->withInput();
        } 

        $employe = HrEmploye::where('id',$request->employe_id)->value('lastname');

        DB::commit();

        return redirect()->route('admin.hr-take-conge-payes.index',$take_conge_paye->company_id);


        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    /**
     * Retourne la première date autorisée pour date_heure_debut (lendemain du dernier date_heure_fin)
     * ou null si l'employé n'a pas de congé non rejeté.
     */
    protected function getMinDateDebutForEmploye(int $employeId, ?int $excludeTakeCongeId = null): ?string
    {
        $query = HrTakeCongePaye::where('employe_id', $employeId)
            ->where('etat', '!=', '-1');
        if ($excludeTakeCongeId !== null) {
            $query->where('id', '!=', $excludeTakeCongeId);
        }
        $lastDateFin = $query->orderBy('date_heure_fin', 'desc')->value('date_heure_fin');
        if (!$lastDateFin) {
            return null;
        }
        return Carbon::parse($lastDateFin)->addDay()->format('Y-m-d');
    }

    /**
     * Solde de référence AVANT le congé en cours (création ou édition).
     * - paye : droit annuel
     * - base_pris : jours déjà pris (hors demande en cours)
     * - base_restant : jours encore disponibles pour cette demande
     *
     * Les congés non rejetés / non encore approuvés (etat 1..3) consomment aussi
     * le stock disponible (hors congé exclu en édition).
     */
    protected function resolveCongeBalance(int $employeId, ?int $excludeTakeCongeId = null): ?array
    {
        $journal = HrJournalCongePaye::where('employe_id', $employeId)
            ->orderByDesc('id')
            ->first();

        if (!$journal) {
            return null;
        }

        $paye = (int) ($journal->nbre_jours_conge_paye ?? 0);
        $basePris = (int) ($journal->nbre_jours_conge_pris ?? 0);
        $baseRestant = (int) ($journal->nbre_jours_conge_restant ?? 0);

        // Congé déjà écrit dans le journal (après approbation) : restituer ses jours
        // pour recalculer proprement lors d'une édition (cas limite / données historiques).
        if ($excludeTakeCongeId !== null) {
            $current = HrTakeCongePaye::find($excludeTakeCongeId);
            if (
                $current
                && (string) $journal->code === (string) $current->code
                && (int) $current->etat === 4
            ) {
                $currentSollicite = (int) $current->nbre_jours_conge_sollicite;
                $basePris = max(0, $basePris - $currentSollicite);
                $baseRestant += $currentSollicite;
            }
        }

        // Autres demandes encore ouvertes (pas encore dans le journal) réduisent le disponible.
        $pendingSollicite = (int) HrTakeCongePaye::where('employe_id', $employeId)
            ->where('etat', '!=', -1)
            ->where('etat', '!=', 4)
            ->when($excludeTakeCongeId !== null, function ($query) use ($excludeTakeCongeId) {
                $query->where('id', '!=', $excludeTakeCongeId);
            })
            ->sum('nbre_jours_conge_sollicite');

        $baseRestant = max(0, $baseRestant - $pendingSollicite);

        return [
            'paye' => $paye,
            'base_pris' => $basePris,
            'base_restant' => $baseRestant,
        ];
    }

    /**
     * Calcul fiable :
     * - pris = jours déjà pris avant cette demande
     * - restant = disponible après cette demande
     * Identité : paye = pris + sollicite + restant
     */
    protected function computeCongeAmounts(array $balance, int $sollicite): array
    {
        $paye = (int) $balance['paye'];
        $basePris = (int) $balance['base_pris'];
        $baseRestant = (int) $balance['base_restant'];
        $sollicite = max(0, $sollicite);

        $restant = max(0, $baseRestant - $sollicite);
        $pris = $basePris;

        return [
            'paye' => $paye,
            'pris' => $pris,
            'restant' => $restant,
            'sollicite' => $sollicite,
        ];
    }

    protected function guardCongePayeEditable(HrTakeCongePaye $takeCongePaye)
    {
        if ((int) $takeCongePaye->etat === 4) {
            session()->flash('error', 'Ce congé est déjà approuvé : modification impossible.');
            return redirect()->route('admin.hr-take-conge-payes.index', $takeCongePaye->company_id);
        }

        return null;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\HrTakeCongePaye  $take_conge_paye
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_conge_paye.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer le congé! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $take_conge_paye = HrTakeCongePaye::findOrFail($id);
        $take_conge_paye->delete();
        session()->flash('success', 'congé est supprimé !!');
        return redirect()->back();
    }
}
