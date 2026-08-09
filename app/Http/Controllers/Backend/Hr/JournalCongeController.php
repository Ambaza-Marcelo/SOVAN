<?php

namespace App\Http\Controllers\Backend\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HrJournalConge;
use App\Models\HrEmploye;
use App\Models\HrTypeConge;
use App\Models\HrCompany;

class JournalCongeController extends Controller
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
        if (is_null($this->user) || !$this->user->can('hr_conge.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser le journal des congés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $journal_conges = HrJournalConge::with('employe','typeConge')->orderBy('created_at','desc')->get();

        return view('backend.pages.hr.journal_conge.index',compact('journal_conges'));
    }

    public function selectByCompany()
    {
        if (is_null($this->user) || !$this->user->can('hr_conge.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser le journal des congés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $companies = HrCompany::all();
        return view('backend.pages.hr.journal_conge.select_by_company',compact('companies'));
    }

    public function create()
    {
        if (is_null($this->user) || !$this->user->can('hr_conge.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer le journal des congés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $employes = HrEmploye::orderBy('firstname')->get();
        $type_conges = HrTypeConge::all();

        return view('backend.pages.hr.journal_conge.create',compact('employes','type_conges'));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_conge.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer le journal des congés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'employe_id' => 'required',
            'type_conge_id' => 'required',
            'date_heure_debut' => 'required',
            'date_heure_fin' => 'required',
        ]);

        $employe = HrEmploye::find($request->employe_id);

        $journal_conge = new HrJournalConge();
        $journal_conge->date_heure_debut = $request->date_heure_debut;
        $journal_conge->date_heure_fin = $request->date_heure_fin;
        $journal_conge->nbre_jours_conge_pris = $request->nbre_jours_conge_pris;
        $journal_conge->nbre_heures_conge_pris = $request->nbre_heures_conge_pris;
        $journal_conge->etat = $request->etat ?? 0;
        $journal_conge->code = "JC".date("y").substr(number_format(time() * mt_rand(), 0, '', ''), 0, 6);
        $journal_conge->auteur = $this->user->name;
        $journal_conge->employe_id = $request->employe_id;
        $journal_conge->type_conge_id = $request->type_conge_id;
        $journal_conge->company_id = $employe->company_id ?? $request->company_id;
        $journal_conge->save();

        session()->flash('success', 'Journal congé est créé !!');

        return redirect()->route('admin.hr-journal-conges.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\HrJournalConge  $journal_conge
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_conge.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le journal des congés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $journal_conge = HrJournalConge::findOrFail($id);
        $employes = HrEmploye::orderBy('firstname')->get();
        $type_conges = HrTypeConge::all();
        return view('backend.pages.hr.journal_conge.edit', compact('journal_conge','employes','type_conges'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\HrJournalConge  $journal_conge
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('hr_conge.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le journal des congés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'employe_id' => 'required',
            'type_conge_id' => 'required',
            'date_heure_debut' => 'required',
            'date_heure_fin' => 'required',
        ]);

        $journal_conge = HrJournalConge::findOrFail($id);
        $journal_conge->date_heure_debut = $request->date_heure_debut;
        $journal_conge->date_heure_fin = $request->date_heure_fin;
        $journal_conge->nbre_jours_conge_pris = $request->nbre_jours_conge_pris;
        $journal_conge->nbre_heures_conge_pris = $request->nbre_heures_conge_pris;
        $journal_conge->etat = $request->etat ?? $journal_conge->etat;
        $journal_conge->employe_id = $request->employe_id;
        $journal_conge->type_conge_id = $request->type_conge_id;
        $journal_conge->save();
        session()->flash('success', 'Journal congé est modifié !!');
        return redirect()->route('admin.hr-journal-conges.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\HrJournalConge  $journal_conge
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_conge.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer le journal des congés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $journal_conge = HrJournalConge::findOrFail($id);
        $journal_conge->delete();
        session()->flash('success', 'Journal congé est supprimé !!');
        return redirect()->back();
    }
}
