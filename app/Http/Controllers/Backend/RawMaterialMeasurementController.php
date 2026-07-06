<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\RawMaterialMeasurement;

class RawMaterialMeasurementController extends Controller
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
        if (is_null($this->user) || !$this->user->can('raw_material_category.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any unit of measurement !');
        }

        $raw_material_measurements = RawMaterialMeasurement::all();
        return view('backend.pages.raw_material_measurement.index', compact('raw_material_measurements'));
    }

    public function create()
    {
        if (is_null($this->user) || !$this->user->can('raw_material_category.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any unit of measurement !');
        }
        return view('backend.pages.raw_material_measurement.create');
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_category.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any unit of measurement !');
        }

        // Validation Data
        $request->validate([
            'purchase_unit' => 'required|max:255',
            'stockout_unit' => 'required|max:255',
            'production_unit' => 'required|max:255',
            'equivalent' => 'required',
            'sub_equivalent' => 'required'
        ]);

        // Create New RawMaterialMeasurement
        $raw_material_measurement = new RawMaterialMeasurement();
        $raw_material_measurement->purchase_unit = $request->purchase_unit;
        $raw_material_measurement->stockout_unit = $request->stockout_unit;
        $raw_material_measurement->production_unit = $request->production_unit;
        $raw_material_measurement->equivalent = $request->equivalent;
        $raw_material_measurement->sub_equivalent = $request->sub_equivalent;
        $raw_material_measurement->save();
        session()->flash('success', 'unit of measurement has been created successfuly !!');
        return redirect()->route('admin.raw-material-measurement.index');

    }

    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_category.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any unit of measurement !');
        }

        $raw_material_measurement = RawMaterialMeasurement::find($id);
        return view('backend.pages.raw_material_measurement.edit', compact('raw_material_measurement'));
    }

    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_category.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any unit of measurement !');
        }

        $raw_material_measurement = RawMaterialMeasurement::find($id);

        // Validation Data
        $request->validate([
            'purchase_unit' => 'required|max:255',
            'stockout_unit' => 'required|max:255',
            'production_unit' => 'required|max:255',
            'equivalent' => 'required',
            'sub_equivalent' => 'required'
        ]);


        $raw_material_measurement->purchase_unit = $request->purchase_unit;
        $raw_material_measurement->stockout_unit = $request->stockout_unit;
        $raw_material_measurement->production_unit = $request->production_unit;
        $raw_material_measurement->equivalent = $request->equivalent;
        $raw_material_measurement->sub_equivalent = $request->sub_equivalent;
        $raw_material_measurement->save();

        session()->flash('success', 'unit of measurement has been updated !!');
        return redirect()->route('admin.raw-material-measurement.index');
    }

    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_category.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any unit of measurement !');
        }

        $raw_material_measurement = RawMaterialMeasurement::find($id);
        if (!is_null($raw_material_measurement)) {
            $raw_material_measurement->delete();
        }

        session()->flash('success', 'unit of measurement has been deleted !!');
        return back();
    }
}