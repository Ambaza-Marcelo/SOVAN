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
use App\Models\HrJourFerie;

class JourFerieController extends Controller
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
        if (is_null($this->user) || !$this->user->can('hr_jour_ferie.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les jours fériés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $jour_feries = HrJourFerie::all();

        return view('backend.pages.hr.jour_ferie.index',compact('jour_feries'));
    }

    public function create()
    {
        if (is_null($this->user) || !$this->user->can('hr_jour_ferie.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer un jour férié! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        return view('backend.pages.hr.jour_ferie.create');
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_jour_ferie.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer un jour férié! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'name' => 'required',
            'date' => 'required',
        ]);

        $jour_ferie = new HrJourFerie();
        $jour_ferie->name = $request->name;
        $jour_ferie->date = $request->date;
        $jour_ferie->created_by = $this->user->name;
        $jour_ferie->save();

        session()->flash('success', 'Jour férié est créé !!');

        return redirect()->back();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\HrJourFerie  $jour_ferie
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_jour_ferie.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier un jour férié! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $jour_ferie = HrJourFerie::findOrFail($id);
        return view('backend.pages.hr.jour_ferie.edit', compact('jour_ferie'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\HrJourFerie  $jour_ferie
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('hr_jour_ferie.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier un jour férié! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'name' => 'required',
            'date' => 'required',
        ]);

        $jour_ferie = HrJourFerie::findOrFail($id);

        $jour_ferie->name = $request->name;
        $jour_ferie->date = $request->date;
        $jour_ferie->save();
        session()->flash('success', 'Jour férié est modifié !!');
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\HrJourFerie  $jour_ferie
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_jour_ferie.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer un jour férié! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $jour_ferie = HrJourFerie::findOrFail($id);
        $jour_ferie->delete();
        session()->flash('success', 'Jour férié est supprimé !!');
        return redirect()->back();
    }
}
