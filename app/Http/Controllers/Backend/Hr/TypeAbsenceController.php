<?php

namespace App\Http\Controllers\Backend\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HrTypeAbsence;

class TypeAbsenceController extends Controller
{
    public $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_absence.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer un motif d\'absence!');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
        ]);

        $typeAbsence = new HrTypeAbsence();
        $typeAbsence->name = $request->name;
        $typeAbsence->type = $request->type;
        $typeAbsence->created_by = $this->user->name ?? null;
        $typeAbsence->save();

        session()->flash('success', 'Motif d\'absence créé !!');

        return redirect()->back();
    }

    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_absence.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier un motif d\'absence!');
        }

        $type_absence = HrTypeAbsence::findOrFail($id);

        return view('backend.pages.hr.type_absence.edit', compact('type_absence'));
    }

    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('hr_absence.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier un motif d\'absence!');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
        ]);

        $typeAbsence = HrTypeAbsence::findOrFail($id);
        $typeAbsence->name = $request->name;
        $typeAbsence->type = $request->type;
        $typeAbsence->save();

        session()->flash('success', 'Motif d\'absence modifié !!');

        return redirect()->route('admin.hr-reglages.index');
    }

    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_absence.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer un motif d\'absence!');
        }

        $typeAbsence = HrTypeAbsence::findOrFail($id);
        $typeAbsence->delete();

        session()->flash('success', 'Motif d\'absence supprimé !!');

        return redirect()->back();
    }
}
