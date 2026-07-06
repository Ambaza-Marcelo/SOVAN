<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Storage;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MaterialSmallStore;
use App\Models\MaterialSmallStoreDetail;
use App\Models\Accounting\AccountAccount;
use Carbon\Carbon;
use Excel;
use PDF;


class MaterialSmallStoreController extends Controller
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
        if (is_null($this->user) || !$this->user->can('material_small_store.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any material !');
        }

        $material_small_stores = MaterialSmallStore::with('account')->get();
        return view('backend.pages.material_small_store.index', compact('material_small_stores'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('material_small_store.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any material !');
        }

        $accounts = AccountAccount::all();

        return view('backend.pages.material_small_store.create',compact('accounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('material_small_store.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any material !');
        }

        // Validation Data
        $request->validate([
            'name' => 'required|max:255',
            'emplacement' => 'required|max:20',
            'default_account_id' => 'required'
        ]);

        // Create New store

        try {DB::beginTransaction();

        $material_small_store = new MaterialSmallStore();
        $material_small_store->name = $request->name;
        $reference = strtoupper(substr($request->name, 0, 3));
        $material_small_store->code = $reference.date("y").substr(number_format(time() * mt_rand(), 0, '', ''), 0, 6);
        $store_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$material_small_store->code;
        $material_small_store->store_signature = $store_signature;
        $material_small_store->emplacement = $request->emplacement;
        $material_small_store->default_account_id = $request->default_account_id;
        $material_small_store->manager = $request->manager;
        $material_small_store->created_by = $this->user->name;
        $material_small_store->save();

        $material_small_store_detail = new MaterialSmallStoreDetail();
        $material_small_store_detail->name = $request->name;
        $material_small_store_detail->code = $material_small_store->code;
        $material_small_store_detail->store_signature = $store_signature;
        $material_small_store_detail->emplacement = $request->emplacement;
        $material_small_store_detail->default_account_id = $request->default_account_id;
        $material_small_store_detail->manager = $request->manager;
        $material_small_store_detail->created_by = $this->user->name;
        $material_small_store_detail->save();

        DB::commit();
            session()->flash('success', 'Material Small Store has been created !!');
            return redirect()->route('admin.material-small-store.index');
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
        $material_small_store = MaterialSmallStore::where('code',$code)->first();
        $material_small_stores = MaterialSmallStoreDetail::where('code',$code)->where('material_id','!=','')->get();
        return view('backend.pages.material_small_store.show', compact(
            'material_small_stores','material_small_store'));
    }

    public function storeStatus($code)
    {
        if (is_null($this->user) || !$this->user->can('material_small_store.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any material store  !');
        }

        $datas = MaterialSmallStoreDetail::where('code',$code)->where('material_id','!=','')->get();
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $currentTime = Carbon::now();
        $totalPrixAchat = DB::table('material_small_store_details')->where('code',$code)->sum('total_purchase_value');

        $dateT =  $currentTime->toDateTimeString();

        $store_signature = MaterialSmallStoreDetail::where('code',$code)->value('store_signature');

        $dateTime = str_replace([' ',':'], '_', $dateT);
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.material_small_store_status', compact('datas','dateTime','setting','totalPrixAchat','store_signature'), [], 'UTF-8')->setPaper('a4', 'landscape');

        Storage::put('public/material_small_store/Etat_stock/'.'ETAT AU PETIT STOCK DES MATERIELS '.$dateTime.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('ETAT AU PETIT STOCK DES MATERIELS '.$dateTime.'.pdf');
    }

    public function print($code) { return $this->storeStatus($code); }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($code)
    {
        if (is_null($this->user) || !$this->user->can('material_small_store.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any material store !');
        }

        $material_small_store = MaterialSmallStore::where('code', $code)->first();
        $accounts = AccountAccount::all();
        return view('backend.pages.material_small_store.edit', compact(
            'material_small_store','accounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $code)
    {
        if (is_null($this->user) || !$this->user->can('material_small_store.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any material store !');
        }

        // Create New MaterialSmallStore
        $material_small_store = MaterialSmallStore::where('code', $code)->first();
        $material_small_store_detail = MaterialSmallStoreDetail::where('code', $code)->first();

        if (!$material_small_store || !$material_small_store_detail) {
            session()->flash('error', 'Material Small Store not found !!');
            return redirect()->back();
        }

        // Validation Data
        $request->validate([
            'name' => 'required|max:255',
            'emplacement' => 'required',
            'default_account_id' => 'required'
        ]);

        try {DB::beginTransaction();

        $material_small_store->name = $request->name;
        $material_small_store->emplacement = $request->emplacement;
        $material_small_store->manager = $request->manager;
        $material_small_store->default_account_id = $request->default_account_id;
        $material_small_store->created_by = $this->user->name;
        $material_small_store->save();

        $material_small_store_detail->name = $request->name;
        $material_small_store_detail->emplacement = $request->emplacement;
        $material_small_store_detail->manager = $request->manager;
        $material_small_store_detail->default_account_id = $request->default_account_id;
        $material_small_store_detail->created_by = $this->user->name;
        $material_small_store_detail->save();

        DB::commit();
            session()->flash('success', 'Material Small Store has been updated !!');
            return redirect()->route('admin.material-small-store.index');
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
        if (is_null($this->user) || !$this->user->can('material_small_store.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any material store !');
        }

        
    }
}
