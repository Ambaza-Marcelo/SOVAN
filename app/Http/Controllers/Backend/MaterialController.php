<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MaterialMeasurement;
use App\Models\MaterialBigStore;
use App\Models\MaterialSmallStore;
use App\Models\MaterialBigStoreDetail;
use App\Models\MaterialSmallStoreDetail;
use App\Models\MaterialExtraBigStore;
use App\Models\MaterialExtraBigStoreDetail;
use App\Models\Accounting\AccountAccount;
use App\Models\Setting;
use App\Exports\GenericIndexExport;
use Excel;
use PDF;

class MaterialController extends Controller
{
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
        if (is_null($this->user) || !$this->user->can('material.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any material !');
        }

        $materials = Material::with('account', 'materialMeasurement')->get();
        return view('backend.pages.material.index', compact('materials'));
    }

    public function create()
    {
        if (is_null($this->user) || !$this->user->can('material.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any material !');
        }
        $categories = MaterialCategory::all();
        $material_measurements = MaterialMeasurement::all();
        $material_extra_big_stores = MaterialExtraBigStore::all();
        $material_big_stores = MaterialBigStore::all();
        $material_small_stores = MaterialSmallStore::all();
        $accounts = AccountAccount::all();
        return view('backend.pages.material.create', compact(
            'categories', 'material_measurements', 'material_big_stores', 'material_small_stores', 'material_extra_big_stores', 'accounts'
        ));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('material.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any material !');
        }

        $isStockable = $request->has('is_stockable') && (string) $request->input('is_stockable') === '1';
        $isAmortizable = $request->has('is_amortizable') && (string) $request->input('is_amortizable') === '1';

        $rules = [
            'name' => 'required|max:255',
            'material_measurement_id' => 'required',
            'purchase_price' => 'required|numeric|min:0',
            'default_account_id' => 'nullable|exists:account_accounts,id',
        ];

        if ($isStockable) {
            $rules['store_type'] = 'required';
            $rules['code_store'] = 'required';
            $rules['threshold_quantity'] = 'nullable|numeric|min:0';
        }

        if ($isAmortizable) {
            $rules['service_start_date'] = 'nullable|date';
            $rules['duration_years'] = 'required|integer|min:1|max:50';
            $rules['depreciation_base'] = 'nullable|numeric|min:0';
            $rules['residual_value'] = 'nullable|numeric|min:0';
            $rules['immobilization_account_id'] = 'required|exists:account_accounts,id';
            $rules['depreciation_expense_account_id'] = 'nullable|exists:account_accounts,id';
            $rules['depreciation_asset_account_id'] = 'nullable|exists:account_accounts,id';
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $store_type = $isStockable ? $request->store_type : null;
            $code_store = $isStockable ? $request->code_store : null;
            $default_account_id = $request->filled('default_account_id') ? $request->default_account_id : null;

            $material = new Material();
            $material->name = $request->name;
            $material->quantity = 0;
            $reference = strtoupper(substr($request->name, 0, 3));
            $material->code = $reference.date("y").substr(number_format(time() * mt_rand(), 0, '', ''), 0, 6);
            $material->material_measurement_id = $request->material_measurement_id;
            $material->purchase_price = $request->purchase_price;
            $material->specification = $request->specification;
            $material->cump = $request->purchase_price;
            $material->threshold_quantity = $isStockable ? ($request->threshold_quantity ?? 0) : 0;
            $material->mcategory_id = $request->mcategory_id ?? $request->dcategory_id;
            $material->code_store = $code_store;
            $material->store_type = $store_type;
            $material->default_account_id = $default_account_id;
            $material->is_stockable = $isStockable;
            $material->created_by = $this->user->name;
            $this->applyDepreciationFields($material, $request, $isAmortizable);
            $material->save();

            // Action stockable : créer la fiche stock dans le magasin choisi
            if ($isStockable) {
                $this->syncMaterialToStore($material, $store_type, $code_store, false);
            }

            DB::commit();
            session()->flash('success', 'Material has been created !!');
            return redirect()->route('admin.materials.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function show($code)
    {
        //
    }

    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('material.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any material !');
        }

        $material = Material::find($id);
        $categories = MaterialCategory::all();
        $material_measurements = MaterialMeasurement::all();
        $material_extra_big_stores = MaterialExtraBigStore::all();
        $material_big_stores = MaterialBigStore::all();
        $material_small_stores = MaterialSmallStore::all();
        $accounts = AccountAccount::all();
        return view('backend.pages.material.edit', compact(
            'material',
            'categories',
            'material_measurements',
            'material_big_stores',
            'material_small_stores',
            'material_extra_big_stores',
            'accounts'
        ));
    }

    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('material.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any material !');
        }

        $isStockable = $request->has('is_stockable') && (string) $request->input('is_stockable') === '1';
        $isAmortizable = $request->has('is_amortizable') && (string) $request->input('is_amortizable') === '1';

        $rules = [
            'name' => 'required|max:255',
            'material_measurement_id' => 'required',
            'purchase_price' => 'required|numeric|min:0',
            'default_account_id' => 'nullable|exists:account_accounts,id',
        ];

        if ($isStockable) {
            $rules['store_type'] = 'required';
            $rules['code_store'] = 'required';
            $rules['threshold_quantity'] = 'nullable|numeric|min:0';
        }

        if ($isAmortizable) {
            $rules['service_start_date'] = 'nullable|date';
            $rules['duration_years'] = 'required|integer|min:1|max:50';
            $rules['depreciation_base'] = 'nullable|numeric|min:0';
            $rules['residual_value'] = 'nullable|numeric|min:0';
            $rules['immobilization_account_id'] = 'required|exists:account_accounts,id';
            $rules['depreciation_expense_account_id'] = 'nullable|exists:account_accounts,id';
            $rules['depreciation_asset_account_id'] = 'nullable|exists:account_accounts,id';
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $store_type = $isStockable ? $request->store_type : null;
            $code_store = $isStockable ? $request->code_store : null;
            $default_account_id = $request->filled('default_account_id') ? $request->default_account_id : null;

            $material = Material::where('id', $id)->firstOrFail();
            $material->name = $request->name;
            $material->unit = $request->unit;
            $material->material_measurement_id = $request->material_measurement_id;
            $material->purchase_price = $request->purchase_price;
            $material->specification = $request->specification;
            $material->cump = $request->purchase_price;
            $material->threshold_quantity = $isStockable ? ($request->threshold_quantity ?? 0) : 0;
            $material->mcategory_id = $request->mcategory_id ?? $request->dcategory_id;
            $material->code_store = $code_store;
            $material->store_type = $store_type;
            $material->default_account_id = $default_account_id;
            $material->is_stockable = $isStockable;
            $material->created_by = $this->user->name;
            $this->applyDepreciationFields($material, $request, $isAmortizable);
            $material->save();

            if ($isStockable) {
                $this->syncMaterialToStore($material, $store_type, $code_store, true);
            }

            DB::commit();
            session()->flash('success', 'Material has been updated !!');
            return redirect()->route('admin.materials.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('material.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any material !');
        }

        $material = Material::find($id);
        if (!is_null($material)) {
            $material->delete();
        }

        session()->flash('success', 'Material has been deleted !!');
        return back();
    }

    /**
     * Active / désactive le matériel.
     */
    public function toggleStatus($id)
    {
        if (is_null($this->user) || !$this->user->can('material.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any material !');
        }

        $material = Material::findOrFail($id);
        $material->status = ((string) $material->status === '1' || $material->status === 1) ? '0' : '1';
        $material->updated_by = $this->user->name;
        $material->save();

        session()->flash('success', 'Statut du matériel mis à jour.');
        return back();
    }

    /**
     * Affiche le plan d'amortissement linéaire d'un matériel.
     */
    public function depreciation($id)
    {
        if (is_null($this->user) || !$this->user->can('material.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any material !');
        }

        $material = Material::with([
            'account',
            'depreciationExpenseAccount',
            'depreciationAssetAccount',
            'immobilizationAccount',
        ])->findOrFail($id);

        if (!$material->is_amortizable) {
            session()->flash('error', 'Ce matériel n\'est pas marqué comme amortissable.');
            return redirect()->route('admin.materials.edit', $material->id);
        }

        return view('backend.pages.material.depreciation', compact('material'));
    }

    /**
     * Export Excel du plan d'amortissement.
     */
    public function exportDepreciationExcel($id)
    {
        if (is_null($this->user) || !$this->user->can('material.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export depreciation !');
        }

        $material = Material::findOrFail($id);

        if (!$material->is_amortizable) {
            session()->flash('error', 'Ce matériel n\'est pas marqué comme amortissable.');
            return redirect()->route('admin.materials.index');
        }

        $schedule = $material->depreciation_schedule;
        $rows = [];
        foreach ($schedule['schedule'] ?? [] as $row) {
            $rows[] = [
                $row['year'],
                (float) ($row['base_amortissable'] ?? 0),
                (float) ($row['taux'] ?? 0),
                (int) ($row['days'] ?? 0),
                (float) ($row['dotation'] ?? 0),
                (float) ($row['cumulative'] ?? 0),
                (float) ($row['vnc'] ?? 0),
            ];
        }

        $headings = [
            'Année', 'Base amortissable', 'Taux %', 'Jours', 'Dotation', 'Cumul amortissements', 'VNC',
        ];

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $material->code ?: $material->name);

        return Excel::download(
            new GenericIndexExport($rows, $headings),
            'amortissement_' . $safeName . '_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    /**
     * Export PDF du plan d'amortissement.
     */
    public function exportDepreciationPdf($id)
    {
        if (is_null($this->user) || !$this->user->can('material.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export depreciation !');
        }

        $material = Material::with([
            'account',
            'depreciationExpenseAccount',
            'depreciationAssetAccount',
        ])->findOrFail($id);

        if (!$material->is_amortizable) {
            session()->flash('error', 'Ce matériel n\'est pas marqué comme amortissable.');
            return redirect()->route('admin.materials.index');
        }

        $schedule = $material->depreciation_schedule;
        $setting = Setting::first();
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $material->code ?: $material->name);

        $pdf = PDF::setOptions([
            'defaultFont' => 'dejavu sans',
            'isRemoteEnabled' => true,
        ])->loadView('backend.pages.document.material_depreciation', compact('material', 'schedule', 'setting'))
          ->setPaper('a4', 'portrait');

        return $pdf->download('amortissement_' . $safeName . '_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Applique les champs d'amortissement selon le flag is_amortizable.
     */
    protected function applyDepreciationFields(Material $material, Request $request, bool $isAmortizable = null): void
    {
        if ($isAmortizable === null) {
            $isAmortizable = $request->has('is_amortizable') && (string) $request->input('is_amortizable') === '1';
        }

        $material->is_amortizable = $isAmortizable;

        if (!$isAmortizable) {
            // Action non amortissable : neutraliser le plan
            $material->depreciation_base = null;
            $material->residual_value = 0;
            $material->service_start_date = null;
            $material->duration_years = 3;
            $material->depreciation_expense_account_id = null;
            $material->depreciation_asset_account_id = null;
            $material->immobilization_account_id = null;
            return;
        }

        // Action amortissable : renseigner la base et les comptes
        $material->depreciation_base = $request->filled('depreciation_base')
            ? $request->depreciation_base
            : $request->purchase_price;
        $material->residual_value = $request->filled('residual_value') ? $request->residual_value : 0;
        $material->service_start_date = $request->service_start_date ?: null;
        $material->duration_years = $request->filled('duration_years') ? (int) $request->duration_years : 3;
        $material->depreciation_expense_account_id = $request->depreciation_expense_account_id ?: null;
        $material->depreciation_asset_account_id = $request->depreciation_asset_account_id ?: null;
        $material->immobilization_account_id = $request->immobilization_account_id ?: null;
    }

    /**
     * Action stockable : synchronise le matériel dans le magasin choisi.
     */
    protected function syncMaterialToStore(Material $material, $storeType, $codeStore, bool $preserveExistingQty = false): void
    {
        $materialId = $material->id;
        $unit = $material->unit;
        $thresholdQuantity = $material->threshold_quantity;
        $purchasePrice = $material->purchase_price;
        $existingQty = 0;

        if ($storeType == '1') {
            $row = MaterialBigStoreDetail::where('code', $codeStore)->where('material_id', $materialId)->first();
            if ($preserveExistingQty && $row) {
                $existingQty = $row->quantity;
            }
            if (!$row) {
                $row = new MaterialBigStoreDetail();
                $row->quantity = 0;
            } else {
                $row->quantity = $preserveExistingQty ? $existingQty : $row->quantity;
            }
            $row->material_id = $materialId;
            $row->threshold_quantity = $thresholdQuantity;
            $row->purchase_price = $purchasePrice;
            $row->cump = $purchasePrice;
            $row->total_purchase_value = ((float) $row->quantity) * ((float) $purchasePrice);
            $row->unit = $unit;
            $row->code = $codeStore;
            $row->created_by = $this->user->name;
            $row->save();
            return;
        }

        if ($storeType == '0') {
            $row = MaterialExtraBigStoreDetail::where('code', $codeStore)->where('material_id', $materialId)->first();
            if ($preserveExistingQty && $row) {
                $existingQty = $row->quantity;
            }
            if (!$row) {
                $row = new MaterialExtraBigStoreDetail();
                $row->quantity = 0;
            } else {
                $row->quantity = $preserveExistingQty ? $existingQty : $row->quantity;
            }
            $row->material_id = $materialId;
            $row->threshold_quantity = $thresholdQuantity;
            $row->purchase_price = $purchasePrice;
            $row->cump = $purchasePrice;
            $row->total_purchase_value = ((float) $row->quantity) * ((float) $purchasePrice);
            $row->unit = $unit;
            $row->code = $codeStore;
            $row->created_by = $this->user->name;
            $row->save();
            return;
        }

        // store_type == 2 : petit stock
        $row = MaterialSmallStoreDetail::where('code', $codeStore)->where('material_id', $materialId)->first();
        if ($preserveExistingQty && $row) {
            $existingQty = $row->quantity;
        }
        if (!$row) {
            $row = new MaterialSmallStoreDetail();
            $row->quantity = 0;
        } else {
            $row->quantity = $preserveExistingQty ? $existingQty : $row->quantity;
        }
        $row->material_id = $materialId;
        $row->threshold_quantity = $thresholdQuantity;
        $row->purchase_price = $purchasePrice;
        $row->cump = $purchasePrice;
        $row->total_purchase_value = ((float) $row->quantity) * ((float) $purchasePrice);
        $row->unit = $unit;
        $row->code = $codeStore;
        $row->created_by = $this->user->name;
        $row->save();
    }
}
