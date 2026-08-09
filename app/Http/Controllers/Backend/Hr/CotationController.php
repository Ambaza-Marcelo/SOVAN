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
use App\Models\HrCotation;
use App\Models\HrEmploye;

class CotationController extends Controller
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
        if (is_null($this->user) || !$this->user->can('hr_cotation.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les cotations! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $cotations = HrCotation::with('employe')->orderBy('created_at','desc')->get();

        return view('backend.pages.hr.cotation.index',compact('cotations'));
    }

    public function create()
    {
        if (is_null($this->user) || !$this->user->can('hr_cotation.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer la cotation! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $employes = HrEmploye::orderBy('firstname')->get();

        return view('backend.pages.hr.cotation.create',compact('employes'));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_cotation.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer la cotation! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'employe_id' => 'required',
            'somme_note_obtenu' => 'required|numeric',
            'somme_note_total' => 'required|numeric',
        ]);

        $pourcentage = $request->somme_note_total > 0
            ? round(($request->somme_note_obtenu / $request->somme_note_total) * 100, 2)
            : 0;

        $cotation = new HrCotation();
        $cotation->employe_id = $request->employe_id;
        $cotation->somme_note_obtenu = $request->somme_note_obtenu;
        $cotation->somme_note_total = $request->somme_note_total;
        $cotation->pourcentage = $pourcentage;
        $cotation->mention = $this->getMention($pourcentage);
        $cotation->created_by = $this->user->name;
        $cotation->save();

        session()->flash('success', 'Cotation est créée !!');

        return redirect()->route('admin.hr-cotations.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\HrCotation  $cotation
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_cotation.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier la cotation! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $cotation = HrCotation::findOrFail($id);
        $employes = HrEmploye::orderBy('firstname')->get();
        return view('backend.pages.hr.cotation.edit', compact('cotation','employes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\HrCotation  $cotation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('hr_cotation.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier la cotation! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'employe_id' => 'required',
            'somme_note_obtenu' => 'required|numeric',
            'somme_note_total' => 'required|numeric',
        ]);

        $pourcentage = $request->somme_note_total > 0
            ? round(($request->somme_note_obtenu / $request->somme_note_total) * 100, 2)
            : 0;

        $cotation = HrCotation::findOrFail($id);
        $cotation->employe_id = $request->employe_id;
        $cotation->somme_note_obtenu = $request->somme_note_obtenu;
        $cotation->somme_note_total = $request->somme_note_total;
        $cotation->pourcentage = $pourcentage;
        $cotation->mention = $this->getMention($pourcentage);
        $cotation->save();
        session()->flash('success', 'Cotation est modifiée !!');
        return redirect()->route('admin.hr-cotations.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\HrCotation  $cotation
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_cotation.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer la cotation! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $cotation = HrCotation::findOrFail($id);
        $cotation->delete();
        session()->flash('success', 'Cotation est supprimée !!');
        return redirect()->back();
    }

    private function getMention($pourcentage)
    {
        if ($pourcentage >= 80) {
            return 'Excellent';
        } elseif ($pourcentage >= 70) {
            return 'Très Bien';
        } elseif ($pourcentage >= 60) {
            return 'Bien';
        } elseif ($pourcentage >= 50) {
            return 'Assez Bien';
        }

        return 'Insuffisant';
    }
}
