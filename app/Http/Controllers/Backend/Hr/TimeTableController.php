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
use App\Models\HrDepartement;
use App\Models\HrEmploye;
use App\Models\HrTimeTable;
use Carbon\Carbon;

class TimeTableController extends Controller
{
    public $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_departement.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de voir le time table! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $departements = HrDepartement::all();
        $selectedWeek = $request->get('week', date('Y-\WW'));
        $selectedDepartement = $request->get('departement_id');

        // Parse la semaine
        $weekParts = explode('-W', $selectedWeek);
        $year = $weekParts[0];
        $week = str_replace('W', '', $weekParts[1]);
        
        // Obtenir les dates de la semaine (Lundi à Dimanche)
        $date = new Carbon();
        $date->setISODate($year, $week);
        $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
        
        $weekDates = [];
        for ($i = 0; $i < 7; $i++) {
            $weekDates[] = $startOfWeek->copy()->addDays($i);
        }

        $timeTables = collect();
        if ($selectedDepartement) {
            $timeTables = HrTimeTable::whereHas('employe', function($query) use ($selectedDepartement) {
                $query->where('departement_id', $selectedDepartement);
            })
            ->whereBetween('date', [$weekDates[0]->format('Y-m-d'), $weekDates[6]->format('Y-m-d')])
            ->with('employe')
            ->get()
            ->groupBy('employe_id')
            ->map(function($items) {
                return $items->keyBy(function($item) {
                    return Carbon::parse($item->date)->format('Y-m-d');
                });
            });
        }

        return view('backend.pages.hr.time_table.index', compact('departements', 'selectedWeek', 'selectedDepartement', 'weekDates', 'timeTables'));
    }

    public function create(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_departement.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer le time table! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $departements = HrDepartement::all();
        $selectedWeek = $request->get('week', date('Y-\WW'));
        $selectedDepartement = $request->get('departement_id');
        $selectedEmploye = $request->get('employe_id');

        // Parse la semaine
        $weekParts = explode('-W', $selectedWeek);
        $year = $weekParts[0];
        $week = str_replace('W', '', $weekParts[1]);
        
        $date = new Carbon();
        $date->setISODate($year, $week);
        $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
        
        $weekDates = [];
        for ($i = 0; $i < 7; $i++) {
            $weekDates[] = $startOfWeek->copy()->addDays($i);
        }

        $employes = collect();
        if ($selectedDepartement) {
            $employes = HrEmploye::where('departement_id', $selectedDepartement)
                ->get();
        }

        // Récupérer les horaires existants pour la semaine sélectionnée
        $existingTimeTables = collect();
        if ($selectedEmploye) {
            $existingTimeTables = HrTimeTable::where('employe_id', $selectedEmploye)
                ->whereBetween('date', [$weekDates[0]->format('Y-m-d'), $weekDates[6]->format('Y-m-d')])
                ->get()
                ->keyBy(function($item) {
                    return Carbon::parse($item->date)->format('Y-m-d');
                });
        }

        return view('backend.pages.hr.time_table.create', compact(
            'departements', 
            'employes', 
            'selectedWeek', 
            'selectedDepartement', 
            'selectedEmploye',
            'weekDates',
            'existingTimeTables'
        ));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_departement.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer le time table! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $request->validate([
            'employe_id' => 'required|exists:hr_employes,id',
            'week' => 'required',
            'schedules' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $employeId = $request->employe_id;
            $week = $request->week;
            
            // Parse la semaine
            $weekParts = explode('-W', $week);
            $year = $weekParts[0];
            $weekNum = str_replace('W', '', $weekParts[1]);
            
            $date = new Carbon();
            $date->setISODate($year, $weekNum);
            $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
            
            $employe = HrEmploye::findOrFail($employeId);
            $month = $startOfWeek->format('F');
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            
            // Supprimer les horaires existants pour cette semaine et cet employé
            HrTimeTable::where('employe_id', $employeId)
                ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $startOfWeek->copy()->addDays(6)->format('Y-m-d')])
                ->delete();

            // Créer les nouveaux horaires
            foreach ($request->schedules as $index => $shift) {
                if ($shift && $shift !== 'OFF') {
                    $currentDate = $startOfWeek->copy()->addDays($index);
                    $dayName = $days[$index];
                    
                    HrTimeTable::create([
                        'employe_id' => $employeId,
                        'day' => $dayName,
                        'week' => $week,
                        'month' => $month,
                        'date' => $currentDate->format('Y-m-d'),
                        'shift_name' => $shift,
                    ]);
                }
            }

            DB::commit();
            session()->flash('success', 'Horaire créé avec succès !!');
            return redirect()->route('admin.hr-time-tables.index', [
                'week' => $week,
                'departement_id' => $employe->departement_id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Erreur lors de la création de l\'horaire: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function edit(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('hr_departement.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le time table! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        // Récupérer l'employé
        $employe = HrEmploye::findOrFail($id);
        $selectedWeek = $request->get('week', date('Y-\WW'));

        // Parse la semaine
        $weekParts = explode('-W', $selectedWeek);
        $year = $weekParts[0];
        $week = str_replace('W', '', $weekParts[1]);
        
        $date = new Carbon();
        $date->setISODate($year, $week);
        $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
        
        $weekDates = [];
        for ($i = 0; $i < 7; $i++) {
            $weekDates[] = $startOfWeek->copy()->addDays($i);
        }

        // Récupérer les horaires existants
        $existingTimeTables = HrTimeTable::where('employe_id', $id)
            ->whereBetween('date', [$weekDates[0]->format('Y-m-d'), $weekDates[6]->format('Y-m-d')])
            ->get()
            ->keyBy(function($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        $departements = HrDepartement::all();
        $employes = HrEmploye::where('departement_id', $employe->departement_id)
            ->where('etat', 1)
            ->get();

        return view('backend.pages.hr.time_table.edit', compact(
            'employe',
            'departements',
            'employes',
            'selectedWeek',
            'weekDates',
            'existingTimeTables'
        ));
    }

    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('hr_departement.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le time table! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $request->validate([
            'employe_id' => 'required|exists:hr_employes,id',
            'week' => 'required',
            'schedules' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $employeId = $request->employe_id;
            $week = $request->week;
            
            // Parse la semaine
            $weekParts = explode('-W', $week);
            $year = $weekParts[0];
            $weekNum = str_replace('W', '', $weekParts[1]);
            
            $date = new Carbon();
            $date->setISODate($year, $weekNum);
            $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
            
            $employe = HrEmploye::findOrFail($employeId);
            $month = $startOfWeek->format('F');
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            
            // Supprimer les horaires existants pour cette semaine et cet employé
            HrTimeTable::where('employe_id', $employeId)
                ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $startOfWeek->copy()->addDays(6)->format('Y-m-d')])
                ->delete();

            // Créer les nouveaux horaires
            foreach ($request->schedules as $index => $shift) {
                if ($shift && $shift !== 'OFF') {
                    $currentDate = $startOfWeek->copy()->addDays($index);
                    $dayName = $days[$index];
                    
                    HrTimeTable::create([
                        'employe_id' => $employeId,
                        'day' => $dayName,
                        'week' => $week,
                        'month' => $month,
                        'date' => $currentDate->format('Y-m-d'),
                        'shift_name' => $shift,
                    ]);
                }
            }

            DB::commit();
            session()->flash('success', 'Horaire modifié avec succès !!');
            return redirect()->route('admin.hr-time-tables.index', [
                'week' => $week,
                'departement_id' => $employe->departement_id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Erreur lors de la modification de l\'horaire: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_departement.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer le time table! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $timeTable = HrTimeTable::findOrFail($id);
        $timeTable->delete();

        session()->flash('success', 'Horaire supprimé avec succès !!');
        return redirect()->back();
    }

    public function printByDepartement(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_departement.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation d\'imprimer le time table! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $selectedWeek = $request->get('week', date('Y-\WW'));
        $departementId = $request->get('departement_id');

        if (!$departementId) {
            session()->flash('error', 'Veuillez sélectionner un département !!');
            return redirect()->back();
        }

        $departement = HrDepartement::findOrFail($departementId);

        // Parse la semaine
        $weekParts = explode('-W', $selectedWeek);
        $year = $weekParts[0];
        $week = str_replace('W', '', $weekParts[1]);
        
        $date = new Carbon();
        $date->setISODate($year, $week);
        $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
        
        $weekDates = [];
        for ($i = 0; $i < 7; $i++) {
            $weekDates[] = $startOfWeek->copy()->addDays($i);
        }

        // Récupérer tous les employés du département
        $employes = HrEmploye::where('departement_id', $departementId)
            ->get();

        // Récupérer les horaires
        $timeTables = HrTimeTable::whereHas('employe', function($query) use ($departementId) {
            $query->where('departement_id', $departementId);
        })
        ->whereBetween('date', [$weekDates[0]->format('Y-m-d'), $weekDates[6]->format('Y-m-d')])
        ->with('employe')
        ->get()
        ->groupBy('employe_id')
        ->map(function($items) {
            return $items->keyBy(function($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });
        });

        $setting = DB::table('settings')->orderBy('created_at','desc')->first();

        return view('backend.pages.hr.time_table.print_departement', compact(
            'departement',
            'weekDates',
            'employes',
            'timeTables',
            'selectedWeek',
            'setting'
        ));
    }

    public function printByEmploye(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('hr_departement.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation d\'imprimer le time table! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $selectedWeek = $request->get('week', date('Y-\WW'));
        $employe = HrEmploye::findOrFail($id);

        // Parse la semaine
        $weekParts = explode('-W', $selectedWeek);
        $year = $weekParts[0];
        $week = str_replace('W', '', $weekParts[1]);
        
        $date = new Carbon();
        $date->setISODate($year, $week);
        $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
        
        $weekDates = [];
        for ($i = 0; $i < 7; $i++) {
            $weekDates[] = $startOfWeek->copy()->addDays($i);
        }

        // Récupérer les horaires
        $timeTables = HrTimeTable::where('employe_id', $id)
            ->whereBetween('date', [$weekDates[0]->format('Y-m-d'), $weekDates[6]->format('Y-m-d')])
            ->get()
            ->keyBy(function($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        $setting = DB::table('settings')->orderBy('created_at','desc')->first();

        return view('backend.pages.hr.time_table.print_employe', compact(
            'employe',
            'weekDates',
            'timeTables',
            'selectedWeek',
            'setting'
        ));
    }
}
