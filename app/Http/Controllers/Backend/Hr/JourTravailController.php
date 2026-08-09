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
use App\Models\HrJourTravail;

class JourTravailController extends Controller
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
        if (is_null($this->user) || !$this->user->can('hr_jour_travail.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les jours travaillés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $jour_travails = HrJourTravail::all();

        return view('backend.pages.hr.jour_travail.index',compact('jour_travails'));
    }

    public function create()
    {
        if (is_null($this->user) || !$this->user->can('hr_jour_travail.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer un jour travaillé! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        return view('backend.pages.hr.jour_travail.create');
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_jour_travail.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer un jour travaillé! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'name' => 'required',
        ]);

        $jour_travail = new HrJourTravail();
        $jour_travail->name = $request->name;
        $jour_travail->created_by = $this->user->name;
        $jour_travail->save();

        session()->flash('success', 'Jour travaillé est créé !!');

        return redirect()->back();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\HrJourTravail  $jour_travail
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_jour_travail.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier un jour travaillé! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $jour_travail = HrJourTravail::findOrFail($id);
        return view('backend.pages.hr.jour_travail.edit', compact('jour_travail'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\HrJourTravail  $jour_travail
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('hr_jour_travail.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier un jour travaillé! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'name' => 'required',
        ]);

        $jour_travail = HrJourTravail::findOrFail($id);

        $jour_travail->name = $request->name;
        $jour_travail->save();
        session()->flash('success', 'Jour travaillé est modifié !!');
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\HrJourTravail  $jour_travail
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_jour_travail.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer un jour travaillé! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $jour_travail = HrJourTravail::findOrFail($id);
        $jour_travail->delete();
        session()->flash('success', 'Jour travaillé est supprimé !!');
        return redirect()->back();
    }
}
