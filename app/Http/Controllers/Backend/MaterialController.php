<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
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
use Excel;

class MaterialController extends Controller
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
        if (is_null($this->user) || !$this->user->can('material.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any material !');
        }

        $materials = Material::with('account', 'materialMeasurement')->get();
        return view('backend.pages.material.index', compact('materials'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
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
            'categories','material_measurements','material_big_stores','material_small_stores','material_extra_big_stores','accounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('material.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any material !');
        }

        // Validation Data
        $request->validate([
            'name' => 'required|max:255',
            'material_measurement_id' => 'required',
            'purchase_price' => 'required',
            'default_account_id' => 'required',
            'store_type' => 'required',
            'code_store' => 'required',
        ]);

        try {DB::beginTransaction();

        $store_type = $request->store_type;
        $code_store = $request->code_store;
        $default_account_id = $request->default_account_id;
        // Create New Item
        $material = new Material();
        $material->name = $request->name;
        $material->quantity = 0;
        $reference = strtoupper(substr($request->name, 0, 3));
        $material->code = $reference.date("y").substr(number_format(time() * mt_rand(), 0, '', ''), 0, 6);
        $material->material_measurement_id = $request->material_measurement_id;
        $material->purchase_price = $request->purchase_price;
        
        $material->specification = $request->specification;
        $material->cump = $request->purchase_price;
        $material->threshold_quantity = $request->threshold_quantity;
        $material->mcategory_id = $request->mcategory_id;
        $material->code_store = $code_store;
        $material->store_type = $store_type;
        $material->default_account_id = $default_account_id;
        $material->created_by = $this->user->name;
        $material->save();

        $material_id = Material::latest()->first()->id;

            $unit = Material::where('id',$material_id)->value('unit');
            $code_store = Material::where('id',$material_id)->value('code_store');
            $quantity = Material::where('id',$material_id)->value('quantity');
            $threshold_quantity = Material::where('id',$material_id)->value('threshold_quantity');
            $purchase_price = Material::where('id',$material_id)->value('purchase_price');
            $selling_price = Material::where('id',$material_id)->value('selling_price');
            $specification = Material::where('id',$material_id)->value('specification');

        if ($store_type == '1') {
            $material_big_store_code = MaterialBigStoreDetail::where('code',$code_store)->value('code');
            $material_in_big_store = MaterialBigStoreDetail::where('code',$code_store)->where('material_id',$material_id)->first();
            if (!empty($material_in_big_store)) {
                $material_in_big_store->material_id = $material_id;
                $material_in_big_store->quantity = $quantity;
                $material_in_big_store->threshold_quantity = $threshold_quantity;
                $material_in_big_store->purchase_price = $purchase_price;
                $material_in_big_store->cump = $purchase_price;
                $material_in_big_store->total_purchase_value = $quantity * $material->purchase_price;
                $material_in_big_store->unit = $unit;
                $material_in_big_store->code = $code_store;
                $material_in_big_store->created_by = $this->user->name;
                $material_in_big_store->save();
            }else{
                $material_in_big_store = new MaterialBigStoreDetail();
                $material_in_big_store->material_id = $material_id;
                $material_in_big_store->quantity = $quantity;
                $material_in_big_store->threshold_quantity = $threshold_quantity;
                $material_in_big_store->purchase_price = $purchase_price;
                $material_in_big_store->cump = $purchase_price;
                $material_in_big_store->total_purchase_value = $quantity * $material->purchase_price;
                $material_in_big_store->unit = $unit;
                $material_in_big_store->code = $code_store;
                $material_in_big_store->created_by = $this->user->name;
                $material_in_big_store->save();
            }
        }elseif($store_type == '2'){
            $material_small_store_code = MaterialSmallStoreDetail::where('code',$code_store)->value('code');
            $material_in_small_store = MaterialSmallStoreDetail::where('code',$code_store)->where('material_id',$material_id)->first();
            if (!empty($material_in_small_store)) {
                $material_in_small_store->material_id = $material_id;
                $material_in_small_store->quantity = $quantity;
                $material_in_small_store->threshold_quantity = $threshold_quantity;
                $material_in_small_store->purchase_price = $purchase_price;
                $material_in_small_store->cump = $purchase_price;
                $material_in_small_store->total_purchase_value = $quantity * $material->purchase_price;
                $material_in_small_store->unit = $unit;
                $material_in_small_store->code = $code_store;
                $material_in_small_store->created_by = $this->user->name;
                $material_in_small_store->save();
            }else{
                $material_in_small_store = new MaterialSmallStoreDetail();
                $material_in_small_store->material_id = $material_id;
                $material_in_small_store->quantity = $quantity;
                $material_in_small_store->threshold_quantity = $threshold_quantity;
                $material_in_small_store->purchase_price = $purchase_price;
                $material_in_small_store->cump = $purchase_price;
                $material_in_small_store->total_purchase_value = $quantity * $material->purchase_price;
                $material_in_small_store->unit = $unit;
                $material_in_small_store->code = $code_store;
                $material_in_small_store->created_by = $this->user->name;
                $material_in_small_store->save();
            }
        }elseif ($store_type == '0') {
            $material_big_store_code = MaterialExtraBigStoreDetail::where('code',$code_store)->value('code');
            $material_in_big_store = MaterialExtraBigStoreDetail::where('code',$code_store)->where('material_id',$material_id)->first();
            if (!empty($material_in_big_store)) {
                $material_in_big_store->material_id = $material_id;
                $material_in_big_store->quantity = $quantity;
                $material_in_big_store->threshold_quantity = $threshold_quantity;
                $material_in_big_store->purchase_price = $purchase_price;
                $material_in_big_store->cump = $purchase_price;
                $material_in_big_store->total_purchase_value = $quantity * $material->purchase_price;
                $material_in_big_store->unit = $unit;
                $material_in_big_store->code = $code_store;
                $material_in_big_store->created_by = $this->user->name;
                $material_in_big_store->save();
            }else{
                $material_in_big_store = new MaterialExtraBigStoreDetail();
                $material_in_big_store->material_id = $material_id;
                $material_in_big_store->quantity = $quantity;
                $material_in_big_store->threshold_quantity = $threshold_quantity;
                $material_in_big_store->purchase_price = $purchase_price;
                $material_in_big_store->cump = $purchase_price;
                $material_in_big_store->total_purchase_value = $quantity * $material->purchase_price;
                $material_in_big_store->unit = $unit;
                $material_in_big_store->code = $code_store;
                $material_in_big_store->created_by = $this->user->name;
                $material_in_big_store->save();
            }
        }

        DB::commit();
            session()->flash('success', 'Material has been created !!');
            return redirect()->route('admin.materials.index');
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
        //
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
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
        if (is_null($this->user) || !$this->user->can('material.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any material !');
        }

        // Create New Material

        $request->validate([
            'name' => 'required|max:255',
            'material_measurement_id' => 'required',
            'purchase_price' => 'required',
            'default_account_id' => 'required',
            'store_type' => 'required',
            'code_store' => 'required',
        ]);

        try {DB::beginTransaction();

        $store_type = $request->store_type;
        $code_store = $request->code_store;
        $default_account_id = $request->default_account_id;

        $material = Material::where('id',$id)->first();

        $reference = strtoupper(substr($request->name, 0, 3));
        /*
        $material->code = $reference.date("y").substr(number_format(time() * mt_rand(), 0, '', ''), 0, 6);
        */
        $material->name = $request->name;
        //$material->quantity = $request->quantity;
        $material->unit = $request->unit;
        $material->material_measurement_id = $request->material_measurement_id;
        $material->purchase_price = $request->purchase_price;
        
        $material->specification = $request->specification;
        $material->cump = $request->purchase_price;
        $material->threshold_quantity = $request->threshold_quantity;
        $material->mcategory_id = $request->mcategory_id;
        $material->code_store = $code_store;
        $material->store_type = $store_type;
        $material->default_account_id = $default_account_id;
        $material->created_by = $this->user->name;
        $material->save();

        $material_id = $id;

            $unit = Material::where('id',$material_id)->value('unit');
            $quantity = Material::where('id',$material_id)->value('quantity');
            $threshold_quantity = Material::where('id',$material_id)->value('threshold_quantity');
            $purchase_price = Material::where('id',$material_id)->value('purchase_price');
            $selling_price = Material::where('id',$material_id)->value('selling_price');
            $specification = Material::where('id',$material_id)->value('specification');

        if ($store_type == '1') {
            $material_big_store_code = MaterialBigStoreDetail::where('code',$code_store)->value('code');
            $material_in_big_store = MaterialBigStoreDetail::where('code',$code_store)->where('material_id',$material_id)->first();
            $quantity = MaterialBigStoreDetail::where('code',$code_store)->where('material_id',$material_id)->value('quantity');
            if (!empty($material_in_big_store)) {
                $material_in_big_store->material_id = $material_id;
                $material_in_big_store->quantity = $quantity;
                $material_in_big_store->threshold_quantity = $threshold_quantity;
                $material_in_big_store->purchase_price = $purchase_price;
                $material_in_big_store->cump = $purchase_price;
                $material_in_big_store->total_purchase_value = $quantity * $material->purchase_price;
                $material_in_big_store->unit = $unit;
                $material_in_big_store->code = $code_store;
                $material_in_big_store->created_by = $this->user->name;
                $material_in_big_store->save();
            }else{
                $material_in_big_store = new MaterialBigStoreDetail();
                $material_in_big_store->material_id = $material_id;
                $material_in_big_store->quantity = 0;
                $material_in_big_store->threshold_quantity = $threshold_quantity;
                $material_in_big_store->purchase_price = $purchase_price;
                $material_in_big_store->cump = $purchase_price;
                $material_in_big_store->total_purchase_value = $quantity * $material->purchase_price;
                $material_in_big_store->unit = $unit;
                $material_in_big_store->code = $code_store;
                $material_in_big_store->created_by = $this->user->name;
                $material_in_big_store->save();
            }
        }elseif ($store_type == '0') {
            $material_big_store_code = MaterialExtraBigStoreDetail::where('code',$code_store)->value('code');
            $material_in_big_store = MaterialExtraBigStoreDetail::where('code',$code_store)->where('material_id',$material_id)->first();
            $quantity = MaterialExtraBigStoreDetail::where('code',$code_store)->where('material_id',$material_id)->value('quantity');
            if (!empty($material_in_big_store)) {
                $material_in_big_store->material_id = $material_id;
                $material_in_big_store->quantity = $quantity;
                $material_in_big_store->threshold_quantity = $threshold_quantity;
                $material_in_big_store->purchase_price = $purchase_price;
                $material_in_big_store->cump = $purchase_price;
                $material_in_big_store->total_purchase_value = $quantity * $material->purchase_price;
                $material_in_big_store->unit = $unit;
                $material_in_big_store->code = $code_store;
                $material_in_big_store->created_by = $this->user->name;
                $material_in_big_store->save();
            }else{
                $material_in_big_store = new MaterialExtraBigStoreDetail();
                $material_in_big_store->material_id = $material_id;
                $material_in_big_store->quantity = 0;
                $material_in_big_store->threshold_quantity = $threshold_quantity;
                $material_in_big_store->purchase_price = $purchase_price;
                $material_in_big_store->cump = $purchase_price;
                $material_in_big_store->total_purchase_value = $quantity * $material->purchase_price;
                $material_in_big_store->unit = $unit;
                $material_in_big_store->code = $code_store;
                $material_in_big_store->created_by = $this->user->name;
                $material_in_big_store->save();
            }
        }else{
            $material_small_store_code = MaterialSmallStoreDetail::where('code',$code_store)->value('code');
            $material_in_small_store = MaterialSmallStoreDetail::where('code',$code_store)->where('material_id',$material_id)->first();
            if (!empty($material_in_small_store)) {
                $material_in_small_store->material_id = $material_id;
                $material_in_small_store->quantity = $quantity;
                $material_in_small_store->threshold_quantity = $threshold_quantity;
                $material_in_small_store->purchase_price = $purchase_price;
                $material_in_small_store->cump = $purchase_price;
                $material_in_small_store->total_purchase_value = $quantity * $material->purchase_price;
                $material_in_small_store->unit = $unit;
                $material_in_small_store->code = $code_store;
                $material_in_small_store->created_by = $this->user->name;
                $material_in_small_store->save();
            }else{
                $material_in_small_store = new MaterialSmallStoreDetail();
                $material_in_small_store->material_id = $material_id;
                $material_in_small_store->quantity = 0;
                $material_in_small_store->threshold_quantity = $threshold_quantity;
                $material_in_small_store->purchase_price = $purchase_price;
                $material_in_small_store->cump = $purchase_price;
                $material_in_small_store->total_purchase_value = $quantity * $material->purchase_price;
                $material_in_small_store->unit = $unit;
                $material_in_small_store->code = $code_store;
                $material_in_small_store->created_by = $this->user->name;
                $material_in_small_store->save();
            }
        }

        DB::commit();
            session()->flash('success', 'Material has been updated !!');
            return redirect()->route('admin.materials.index');
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
}
