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

    public function index($company_id)
    {
        if (is_null($this->user) || !$this->user->can('hr_conge_paye.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les congés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $conge_payes = DB::table('hr_conge_payes')->orderBy('created_at','desc')->get();
        $take_conge_payes = HrTakeCongePaye::with('employe')->where('company_id',$company_id)->get();
        return view('backend.pages.hr.take_conge_paye.index',compact('take_conge_payes','conge_payes','company_id'));
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
        $data['data'] = HrJournalCongePaye::where("employe_id", $request->employe_id)->get(["nbre_jours_conge_restant","nbre_jours_conge_paye","nbre_jours_conge_pris", "employe_id"]);
  
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

        $nbre_jours_conge_restant = HrJournalCongePaye::where('employe_id',$request->employe_id)->orderBy('id','desc')->value('nbre_jours_conge_restant');

        if ($nbre_jours_conge_restant >= $request->nbre_jours_conge_sollicite) {

        $take_conge_paye = new HrTakeCongePaye();
        $take_conge_paye->date_heure_debut = $request->date_heure_debut;
        $take_conge_paye->date_heure_fin = $request->date_heure_fin;
        $take_conge_paye->nbre_jours_conge_paye = $request->nbre_jours_conge_paye;
        $take_conge_paye->nbre_jours_conge_sollicite = $request->nbre_jours_conge_sollicite;
        $take_conge_paye->nbre_jours_conge_pris = $request->nbre_jours_conge_pris;
        $take_conge_paye->nbre_jours_conge_restant = $request->nbre_jours_conge_restant;
        $take_conge_paye->created_by = $this->user->name;
        $take_conge_paye->etat = 1;
        $take_conge_paye->code = $code;
        $take_conge_paye->employe_id = $request->employe_id;
        $take_conge_paye->company_id = $request->company_id;
        $take_conge_paye->save();

        }else{
            session()->flash('error', 'Les jours demandé sont déjà consommé !!');
            return back();
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
        $journal_conge_paye->nbre_jours_conge_pris = $data->nbre_jours_conge_pris - $data->nbre_jours_conge_sollicite;
        
        $journal_conge_paye->nbre_jours_conge_restant = $data->nbre_jours_conge_restant + $data->nbre_jours_conge_sollicite;
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
        $employes = HrEmploye::all();
        $type_conges = HrTypeConge::all();
        $conge_payes = HrCongePaye::all();
        //
        $data = HrTakeCongePaye::findOrFail($id);
        return view('backend.pages.hr.take_conge_paye.edit', compact('data','employes','type_conges','conge_payes'));
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

        $nbre_jours_conge_restant = HrJournalCongePaye::where('employe_id',$request->employe_id)->orderBy('id','desc')->value('nbre_jours_conge_restant');
        
        if ($nbre_jours_conge_restant >= $request->nbre_jours_conge_sollicite) {

        $take_conge_paye = HrTakeCongePaye::findOrFail($id);
        $take_conge_paye->date_heure_debut = $request->date_heure_debut;
        $take_conge_paye->date_heure_fin = $request->date_heure_fin;
        $take_conge_paye->nbre_jours_conge_paye = $request->nbre_jours_conge_paye;
        $take_conge_paye->nbre_jours_conge_sollicite = $request->nbre_jours_conge_sollicite;
        $take_conge_paye->nbre_jours_conge_pris = $request->nbre_jours_conge_pris;
        $take_conge_paye->nbre_jours_conge_restant = $request->nbre_jours_conge_restant;
        $take_conge_paye->created_by = $this->user->name;
        $take_conge_paye->etat = 1;
        $take_conge_paye->employe_id = $request->employe_id;
        $take_conge_paye->save();

        }else{
            session()->flash('error', 'Les jours demandé sont déjà consommé !!');
            return back();
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
