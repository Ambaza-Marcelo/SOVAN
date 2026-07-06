<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterialMeasurement;
use App\Models\RawMaterialBigStore;
use App\Models\RawMaterialSmallStore;
use App\Models\RawMaterialBigStoreDetail;
use App\Models\RawMaterialSmallStoreDetail;
use App\Models\RawMaterialExtraBigStore;
use App\Models\RawMaterialExtraBigStoreDetail;
use App\Models\Accounting\AccountAccount;
use Excel;

class RawMaterialController extends Controller
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (is_null($this->user) || !$this->user->can('raw_material.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any raw material !');
        }

        $raw_materials = RawMaterial::with('rawMaterialMeasurement', 'defaultAccount')->orderBy('name')->get();
        return view('backend.pages.raw_material.index', compact('raw_materials'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('raw_material.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any raw material !');
        }
        $categories = RawMaterialCategory::all();
        $raw_material_measurements = RawMaterialMeasurement::all();
        $raw_material_extra_big_stores = RawMaterialExtraBigStore::all();
        $raw_material_big_stores = RawMaterialBigStore::all();
        $raw_material_small_stores = RawMaterialSmallStore::all();
        $accounts = AccountAccount::all();
        return view('backend.pages.raw_material.create', compact(
            'categories','raw_material_measurements','raw_material_big_stores','raw_material_small_stores','raw_material_extra_big_stores','accounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any raw material !');
        }

        // Validation Data
        $request->validate([
            'name' => 'required|max:255',
            'rm_measurement_id' => 'required',
            'purchase_price' => 'required',
            'rmcategory_id' => 'required',
            'store_type' => 'required',
            'code_store' => 'required',
            'default_account_id' => 'required',
        ]);

        try {DB::beginTransaction();

        $store_type = $request->store_type;
        $code_store = $request->code_store;
        $default_account_id = $request->default_account_id;
        // Create New Item
        $raw_material = new RawMaterial();
        $raw_material->name = $request->name;
        $raw_material->quantity = 0;
        $reference = strtoupper(substr($request->name, 0, 3));
        $raw_material->code = $reference.date("y").substr(number_format(time() * mt_rand(), 0, '', ''), 0, 6);
        $raw_material->rm_measurement_id = $request->rm_measurement_id;
        $raw_material->purchase_price = $request->purchase_price;
        $raw_material->cump = $request->purchase_price;
        $raw_material->specification = $request->specification;
        $raw_material->selling_price = $request->selling_price;
        $raw_material->threshold_quantity = $request->threshold_quantity;
        $raw_material->rmcategory_id = $request->rmcategory_id;
        $raw_material->code_store = $code_store;
        $raw_material->store_type = $store_type;
        $raw_material->default_account_id = $default_account_id;
        $raw_material->status = 'active'; // Set status to active by default for new raw materials
        $raw_material->created_by = $this->user->name;
        $raw_material->save();

        $r_material_id = $raw_material->id;

        $raw_material->load('rawMaterialMeasurement');
        $unit = $raw_material->rawMaterialMeasurement ? $raw_material->rawMaterialMeasurement->purchase_unit : '';
        $threshold_quantity = $raw_material->threshold_quantity;
        $purchase_price = $raw_material->purchase_price;
        $selling_price = $raw_material->selling_price;

        // Enregistrer l'article dans le stock choisi dès la création
        if ($raw_material->isActive()) {
        if ($store_type == '1') {
            $raw_material_in_big_store = RawMaterialBigStoreDetail::where('code', $code_store)->where('r_material_id', $r_material_id)->first();
            if (!empty($raw_material_in_big_store)) {
                // Existe déjà : modifier tout sauf la quantité
                $raw_material_in_big_store->r_material_id = $r_material_id;
                $raw_material_in_big_store->threshold_quantity = $threshold_quantity;
                $raw_material_in_big_store->purchase_price = $purchase_price;
                $raw_material_in_big_store->cump = $purchase_price;
                $raw_material_in_big_store->selling_price = $selling_price;
                $raw_material_in_big_store->total_cump_value = $raw_material_in_big_store->quantity * $raw_material->purchase_price;
                $raw_material_in_big_store->total_purchase_value = $raw_material_in_big_store->quantity * $raw_material->purchase_price;
                $raw_material_in_big_store->total_selling_value = $raw_material_in_big_store->quantity * $raw_material->selling_price;
                $raw_material_in_big_store->unit = $unit;
                $raw_material_in_big_store->code = $code_store;
                $raw_material_in_big_store->created_by = $this->user->name;
                $raw_material_in_big_store->save();
            } else {
                // Nouvel enregistrement dans le stock choisi avec quantité 0
                $raw_material_in_big_store = new RawMaterialBigStoreDetail();
                $raw_material_in_big_store->r_material_id = $r_material_id;
                $raw_material_in_big_store->quantity = 0;
                $raw_material_in_big_store->threshold_quantity = $threshold_quantity;
                $raw_material_in_big_store->purchase_price = $purchase_price;
                $raw_material_in_big_store->cump = $purchase_price;
                $raw_material_in_big_store->selling_price = $selling_price;
                $raw_material_in_big_store->total_purchase_value = 0;
                $raw_material_in_big_store->total_selling_value = 0;
                $raw_material_in_big_store->unit = $unit;
                $raw_material_in_big_store->code = $code_store;
                $raw_material_in_big_store->created_by = $this->user->name;
                $raw_material_in_big_store->save();
            }
        } elseif ($store_type == '2') {
            $raw_material_in_small_store = RawMaterialSmallStoreDetail::where('code', $code_store)->where('r_material_id', $r_material_id)->first();
            if (!empty($raw_material_in_small_store)) {
                $raw_material_in_small_store->r_material_id = $r_material_id;
                $raw_material_in_small_store->threshold_quantity = $threshold_quantity;
                $raw_material_in_small_store->purchase_price = $purchase_price;
                $raw_material_in_small_store->cump = $purchase_price;
                $raw_material_in_small_store->selling_price = $selling_price;
                $raw_material_in_small_store->total_purchase_value = ((float)($raw_material_in_small_store->quantity ?? 0)) * $raw_material->purchase_price;
                $raw_material_in_small_store->total_selling_value = ((float)($raw_material_in_small_store->quantity ?? 0)) * $raw_material->selling_price;
                $raw_material_in_small_store->code = $code_store;
                $raw_material_in_small_store->created_by = $this->user->name;
                $raw_material_in_small_store->save();
            } else {
                $raw_material_in_small_store = new RawMaterialSmallStoreDetail();
                $raw_material_in_small_store->r_material_id = $r_material_id;
                $raw_material_in_small_store->quantity = 0;
                $raw_material_in_small_store->threshold_quantity = $threshold_quantity;
                $raw_material_in_small_store->purchase_price = $purchase_price;
                $raw_material_in_small_store->cump = $purchase_price;
                $raw_material_in_small_store->selling_price = $selling_price;
                $raw_material_in_small_store->total_purchase_value = 0;
                $raw_material_in_small_store->total_selling_value = 0;
                $raw_material_in_small_store->code = $code_store;
                $raw_material_in_small_store->created_by = $this->user->name;
                $raw_material_in_small_store->save();
            }
        } else {
            $raw_material_in_extra_big_store = RawMaterialExtraBigStoreDetail::where('code', $code_store)->where('r_material_id', $r_material_id)->first();
            if (!empty($raw_material_in_extra_big_store)) {
                $raw_material_in_extra_big_store->r_material_id = $r_material_id;
                $raw_material_in_extra_big_store->threshold_quantity = $threshold_quantity;
                $raw_material_in_extra_big_store->purchase_price = $purchase_price;
                $raw_material_in_extra_big_store->cump = $purchase_price;
                $raw_material_in_extra_big_store->selling_price = $selling_price;
                $raw_material_in_extra_big_store->total_purchase_value = $raw_material_in_extra_big_store->quantity * $raw_material->purchase_price;
                $raw_material_in_extra_big_store->total_selling_value = $raw_material_in_extra_big_store->quantity * $raw_material->selling_price;
                $raw_material_in_extra_big_store->unit = $unit;
                $raw_material_in_extra_big_store->code = $code_store;
                $raw_material_in_extra_big_store->created_by = $this->user->name;
                $raw_material_in_extra_big_store->save();
            } else {
                $raw_material_in_extra_big_store = new RawMaterialExtraBigStoreDetail();
                $raw_material_in_extra_big_store->r_material_id = $r_material_id;
                $raw_material_in_extra_big_store->quantity = 0;
                $raw_material_in_extra_big_store->threshold_quantity = $threshold_quantity;
                $raw_material_in_extra_big_store->purchase_price = $purchase_price;
                $raw_material_in_extra_big_store->cump = $purchase_price;
                $raw_material_in_extra_big_store->selling_price = $selling_price;
                $raw_material_in_extra_big_store->total_purchase_value = 0;
                $raw_material_in_extra_big_store->total_selling_value = 0;
                $raw_material_in_extra_big_store->unit = $unit;
                $raw_material_in_extra_big_store->code = $code_store;
                $raw_material_in_extra_big_store->created_by = $this->user->name;
                $raw_material_in_extra_big_store->save();
            }
        }
        }

        DB::commit();
        session()->flash('success', 'Raw material has been created !!');
        return redirect()->route('admin.raw-materials.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $code
     * @return \Illuminate\Http\Response
     */
    public function show($code)
    {
        $raw_material_big_store = RawMaterialBigStore::where('code',$code)->first();
        $raw_material_big_stores = RawMaterialBigStoreDetail::where('code',$code)->where('r_material_id','!=','')->get();
        return view('backend.pages.raw_material_big_store.show', compact(
            'raw_material_big_stores','raw_material_big_store'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('raw_material.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any raw material !');
        }

        $raw_material = RawMaterial::find($id);
        $categories = RawMaterialCategory::all();
        $raw_material_measurements = RawMaterialMeasurement::all();
        $raw_material_extra_big_stores = RawMaterialExtraBigStore::all();
        $raw_material_big_stores = RawMaterialBigStore::all();
        $raw_material_small_stores = RawMaterialSmallStore::all();
        $accounts = AccountAccount::all();
        return view('backend.pages.raw_material.edit', compact(
            'raw_material', 
            'categories',
            'raw_material_measurements',
            'raw_material_big_stores',
            'raw_material_small_stores',
            'raw_material_extra_big_stores',
            'accounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('raw_material.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any raw material !');
        }

        // Create New Raw Material

        $request->validate([
            'name' => 'required|max:255',
            'rm_measurement_id' => 'required',
            'purchase_price' => 'required',
            'rmcategory_id' => 'required',
            'store_type' => 'required',
            'code_store' => 'required',
            'default_account_id' => 'required',
        ]);

        try {DB::beginTransaction();

        $store_type = $request->store_type;
        $code_store = $request->code_store;
        $default_account_id = $request->default_account_id;

        $raw_material = RawMaterial::where('id',$id)->first();

        $raw_material->name = $request->name;
        //$raw_material->quantity = $request->quantity;
        $raw_material->rm_measurement_id = $request->rm_measurement_id;
        $raw_material->purchase_price = $request->purchase_price;
        
        $raw_material->specification = $request->specification;
        $raw_material->selling_price = $request->selling_price;
        $raw_material->threshold_quantity = $request->threshold_quantity;
        $raw_material->rmcategory_id = $request->rmcategory_id;
        $raw_material->code_store = $code_store;
        $raw_material->store_type = $store_type;
        $raw_material->default_account_id = $default_account_id;
        $raw_material->updated_by = $this->user->name;
        $raw_material->save();

        $r_material_id = $id;

        $raw_material->load('rawMaterialMeasurement');
        $unit = $raw_material->rawMaterialMeasurement ? $raw_material->rawMaterialMeasurement->purchase_unit : '';
        $threshold_quantity = $raw_material->threshold_quantity;
        $purchase_price = $raw_material->purchase_price;
        $selling_price = $raw_material->selling_price;

        // Mise à jour du stock choisi : si l'article existe → modifier tout sauf la quantité ; sinon → nouvel enregistrement avec quantité 0
        if ($raw_material->isActive()) {
            if ($store_type == '1') {
                $raw_material_in_big_store = RawMaterialBigStoreDetail::where('code', $code_store)->where('r_material_id', $r_material_id)->first();
                if (!empty($raw_material_in_big_store)) {
                    // Article existe dans le stock : modifier tout SAUF la quantité
                    $raw_material_in_big_store->r_material_id = $r_material_id;
                    $raw_material_in_big_store->threshold_quantity = $threshold_quantity;
                    $raw_material_in_big_store->purchase_price = $purchase_price;
                    $raw_material_in_big_store->cump = $purchase_price;
                    $raw_material_in_big_store->selling_price = $selling_price;
                    $raw_material_in_big_store->total_cump_value = $raw_material_in_big_store->quantity * $raw_material->purchase_price;
                    $raw_material_in_big_store->total_purchase_value = $raw_material_in_big_store->quantity * $raw_material->purchase_price;
                    $raw_material_in_big_store->total_selling_value = $raw_material_in_big_store->quantity * $raw_material->selling_price;
                    $raw_material_in_big_store->unit = $unit;
                    $raw_material_in_big_store->code = $code_store;
                    $raw_material_in_big_store->updated_by = $this->user->name;
                    $raw_material_in_big_store->save();
                } else {
                    // Article n'existe pas dans le stock : nouvel enregistrement avec quantité 0
                    $raw_material_in_big_store = new RawMaterialBigStoreDetail();
                    $raw_material_in_big_store->r_material_id = $r_material_id;
                    $raw_material_in_big_store->quantity = 0;
                    $raw_material_in_big_store->threshold_quantity = $threshold_quantity;
                    $raw_material_in_big_store->purchase_price = $purchase_price;
                    $raw_material_in_big_store->cump = $purchase_price;
                    $raw_material_in_big_store->selling_price = $selling_price;
                    $raw_material_in_big_store->total_cump_value = 0;
                    $raw_material_in_big_store->total_purchase_value = 0;
                    $raw_material_in_big_store->total_selling_value = 0;
                    $raw_material_in_big_store->unit = $unit;
                    $raw_material_in_big_store->code = $code_store;
                    $raw_material_in_big_store->created_by = $this->user->name;
                    $raw_material_in_big_store->save();
                }
            } elseif ($store_type == '2') {
                $raw_material_in_small_store = RawMaterialSmallStoreDetail::where('code', $code_store)->where('r_material_id', $r_material_id)->first();
                if (!empty($raw_material_in_small_store)) {
                    // Article existe : modifier tout SAUF quantity
                    $raw_material_in_small_store->r_material_id = $r_material_id;
                    $raw_material_in_small_store->threshold_quantity = $threshold_quantity;
                    $raw_material_in_small_store->purchase_price = $purchase_price;
                    $raw_material_in_small_store->cump = $purchase_price;
                    $raw_material_in_small_store->selling_price = $selling_price;
                    $raw_material_in_small_store->total_cump_value = ((float)($raw_material_in_small_store->quantity ?? 0)) * $raw_material->purchase_price;
                    $raw_material_in_small_store->total_purchase_value = ((float)($raw_material_in_small_store->quantity ?? 0)) * $raw_material->purchase_price;
                    $raw_material_in_small_store->total_selling_value = ((float)($raw_material_in_small_store->quantity ?? 0)) * $raw_material->selling_price;
                    $raw_material_in_small_store->code = $code_store;
                    $raw_material_in_small_store->updated_by = $this->user->name;
                    $raw_material_in_small_store->save();
                } else {
                    // Article n'existe pas : nouvel enregistrement avec quantity 0
                    $raw_material_in_small_store = new RawMaterialSmallStoreDetail();
                    $raw_material_in_small_store->r_material_id = $r_material_id;
                    $raw_material_in_small_store->quantity = 0;
                    $raw_material_in_small_store->threshold_quantity = $threshold_quantity;
                    $raw_material_in_small_store->purchase_price = $purchase_price;
                    $raw_material_in_small_store->cump = $purchase_price;
                    $raw_material_in_small_store->selling_price = $selling_price;
                    $raw_material_in_small_store->total_cump_value = 0;
                    $raw_material_in_small_store->total_purchase_value = 0;
                    $raw_material_in_small_store->total_selling_value = 0;
                    $raw_material_in_small_store->code = $code_store;
                    $raw_material_in_small_store->created_by = $this->user->name;
                    $raw_material_in_small_store->save();
                }
            } else {
                $raw_material_in_extra_big_store = RawMaterialExtraBigStoreDetail::where('code', $code_store)->where('r_material_id', $r_material_id)->first();
                if (!empty($raw_material_in_extra_big_store)) {
                    // Article existe : modifier tout SAUF la quantité
                    $raw_material_in_extra_big_store->r_material_id = $r_material_id;
                    $raw_material_in_extra_big_store->threshold_quantity = $threshold_quantity;
                    $raw_material_in_extra_big_store->purchase_price = $purchase_price;
                    $raw_material_in_extra_big_store->cump = $purchase_price;
                    $raw_material_in_extra_big_store->selling_price = $selling_price;
                    $raw_material_in_extra_big_store->total_cump_value = $raw_material_in_extra_big_store->quantity * $raw_material->purchase_price;
                    $raw_material_in_extra_big_store->total_purchase_value = $raw_material_in_extra_big_store->quantity * $raw_material->purchase_price;
                    $raw_material_in_extra_big_store->total_selling_value = $raw_material_in_extra_big_store->quantity * $raw_material->selling_price;
                    $raw_material_in_extra_big_store->unit = $unit;
                    $raw_material_in_extra_big_store->code = $code_store;
                    $raw_material_in_extra_big_store->updated_by = $this->user->name;
                    $raw_material_in_extra_big_store->save();
                } else {
                    // Article n'existe pas : nouvel enregistrement avec quantité 0
                    $raw_material_in_extra_big_store = new RawMaterialExtraBigStoreDetail();
                    $raw_material_in_extra_big_store->r_material_id = $r_material_id;
                    $raw_material_in_extra_big_store->quantity = 0;
                    $raw_material_in_extra_big_store->threshold_quantity = $threshold_quantity;
                    $raw_material_in_extra_big_store->purchase_price = $purchase_price;
                    $raw_material_in_extra_big_store->cump = $purchase_price;
                    $raw_material_in_extra_big_store->selling_price = $selling_price;
                    $raw_material_in_extra_big_store->total_cump_value = 0;
                    $raw_material_in_extra_big_store->total_purchase_value = 0;
                    $raw_material_in_extra_big_store->total_selling_value = 0;
                    $raw_material_in_extra_big_store->unit = $unit;
                    $raw_material_in_extra_big_store->code = $code_store;
                    $raw_material_in_extra_big_store->created_by = $this->user->name;
                    $raw_material_in_extra_big_store->save();
                }
            }
        } elseif ($raw_material->isInactive()) {
            RawMaterialBigStoreDetail::where('r_material_id', $r_material_id)->delete();
            RawMaterialSmallStoreDetail::where('r_material_id', $r_material_id)->delete();
            RawMaterialExtraBigStoreDetail::where('r_material_id', $r_material_id)->delete();
        }

        DB::commit();
            session()->flash('success', 'Raw material has been updated !!');
            return redirect()->route('admin.raw-materials.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('raw_material.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any raw material !');
        }

        $raw_material = RawMaterial::find($id);
        if (!is_null($raw_material)) {
            $raw_material->delete();
        }

        session()->flash('success', 'Raw material has been deleted !!');
        return back();
    }

    /**
     * Toggle food status (activate/deactivate)
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus($id)
    {
        if (is_null($this->user) || !$this->user->can('raw_material.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to change raw material status !');
        }

        try {
            $raw_material = RawMaterial::findOrFail($id);
            
            // Toggle status
            if ($raw_material->isActive()) {
                $raw_material->status = 'inactive';
                $message = 'Raw material has been deactivated !!';
                
                // Remove from store details when deactivated
                RawMaterialBigStoreDetail::where('r_material_id', $raw_material->id)->delete();
                RawMaterialSmallStoreDetail::where('r_material_id', $raw_material->id)->delete();
            } else {
                $raw_material->status = 'active';
                $message = 'Raw material has been activated !!';
                
                // Re-add to store details when activated (if stores exist)
                if ($raw_material->store_type == '1' && $raw_material->code_store) {
                    $existing_detail = RawMaterialBigStoreDetail::where('code', $raw_material->code_store)->where('r_material_id', $raw_material->id)->first();
                    if (!$existing_detail) {
                        $raw_material_in_big_store = new RawMaterialBigStoreDetail();
                        $raw_material_in_big_store->r_material_id = $raw_material->id;
                        $raw_material_in_big_store->quantity = 0;
                        $raw_material_in_big_store->threshold_quantity = $raw_material->threshold_quantity ?? 0;
                        $raw_material_in_big_store->purchase_price = $raw_material->purchase_price ?? 0;
                        $raw_material_in_big_store->cump = $raw_material->cump ?? 0;
                        $raw_material_in_big_store->selling_price = $raw_material->selling_price ?? 0;
                        $raw_material_in_big_store->unit = $raw_material->unit;
                        $raw_material_in_big_store->code = $raw_material->code_store;
                        $raw_material_in_big_store->created_by = $this->user->name;
                        $raw_material_in_big_store->save();
                    }
                } elseif ($raw_material->store_type == '2' && $raw_material->code_store) {
                    $existing_detail = RawMaterialSmallStoreDetail::where('code', $raw_material->code_store)->where('r_material_id', $raw_material->id)->first();
                    if (!$existing_detail) {
                        $raw_material_in_small_store = new RawMaterialSmallStoreDetail();
                        $raw_material_in_small_store->r_material_id = $raw_material->id;
                        $raw_material_in_small_store->quantity = 0;
                        $raw_material_in_small_store->threshold_quantity = $raw_material->threshold_quantity ?? 0;
                        $raw_material_in_small_store->purchase_price = $raw_material->purchase_price ?? 0;
                        $raw_material_in_small_store->cump = $raw_material->cump ?? 0;
                        $raw_material_in_small_store->selling_price = $raw_material->selling_price ?? 0;
                        $raw_material_in_small_store->code = $raw_material->code_store;
                        $raw_material_in_small_store->created_by = $this->user->name;
                        $raw_material_in_small_store->save();
                    }
                }
            }
            
            $raw_material->updated_by = $this->user->name;
            $raw_material->save();
            
            session()->flash('success', $message);
            return redirect()->route('admin.raw-materials.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Something went wrong !!');
            return redirect()->back();
        }
    }
}
