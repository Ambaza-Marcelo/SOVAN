<?php

namespace App\Http\Controllers\Backend\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HrNoteInterne;
use App\Models\HrDepartement;

class NoteInterneController extends Controller
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
        if (is_null($this->user) || !$this->user->can('hr_note_interne.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les notes internes! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $note_internes = HrNoteInterne::with('departement')->orderBy('created_at','desc')->get();

        return view('backend.pages.hr.note_interne.index',compact('note_internes'));
    }

    public function create()
    {
        if (is_null($this->user) || !$this->user->can('hr_note_interne.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer une note interne! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $departements = HrDepartement::all();

        return view('backend.pages.hr.note_interne.create',compact('departements'));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_note_interne.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer une note interne! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'date' => 'required',
            'title' => 'required',
            'message' => 'required',
        ]);

        $note_interne = new HrNoteInterne();
        $note_interne->date = $request->date;
        $note_interne->title = $request->title;
        $note_interne->message = $request->message;
        $note_interne->signe_par = $request->signe_par ?? $this->user->name;
        $note_interne->confirme_par = $request->confirme_par;
        $note_interne->departement_id = $request->departement_id;
        $note_interne->save();

        session()->flash('success', 'Note interne est créée !!');

        return redirect()->route('admin.hr-note-interne.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\HrNoteInterne  $note_interne
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_note_interne.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier une note interne! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $note_interne = HrNoteInterne::findOrFail($id);
        $departements = HrDepartement::all();
        return view('backend.pages.hr.note_interne.edit', compact('note_interne','departements'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\HrNoteInterne  $note_interne
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('hr_note_interne.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier une note interne! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'date' => 'required',
            'title' => 'required',
            'message' => 'required',
        ]);

        $note_interne = HrNoteInterne::findOrFail($id);
        $note_interne->date = $request->date;
        $note_interne->title = $request->title;
        $note_interne->message = $request->message;
        $note_interne->signe_par = $request->signe_par;
        $note_interne->confirme_par = $request->confirme_par;
        $note_interne->departement_id = $request->departement_id;
        $note_interne->save();
        session()->flash('success', 'Note interne est modifiée !!');
        return redirect()->route('admin.hr-note-interne.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\HrNoteInterne  $note_interne
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_note_interne.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer une note interne! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $note_interne = HrNoteInterne::findOrFail($id);
        $note_interne->delete();
        session()->flash('success', 'Note interne est supprimée !!');
        return redirect()->back();
    }
}
