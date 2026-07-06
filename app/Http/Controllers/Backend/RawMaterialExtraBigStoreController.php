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
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterialExtraBigStore;
use App\Models\RawMaterialExtraBigStoreDetail;
use App\Models\Accounting\AccountAccount;
use Carbon\Carbon;
use Excel;
use PDF;

class RawMaterialExtraBigStoreController extends Controller
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
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_store.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any raw material !');
        }

        $raw_material_extra_big_stores = RawMaterialExtraBigStore::all();
        return view('backend.pages.raw_material_extra_big_store.index', compact('raw_material_extra_big_stores'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_store.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any raw material !');
        }

        $accounts = AccountAccount::all();

        return view('backend.pages.raw_material_extra_big_store.create',compact('accounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_store.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any raw material !');
        }

        // Validation Data
        $request->validate([
            'name' => 'required|max:255',
            'emplacement' => 'required|max:20',
            'default_account_id' => 'required'
        ]);

        // Create New store
        try {DB::beginTransaction();

        $raw_material_extra_big_store = new RawMaterialExtraBigStore();
        $raw_material_extra_big_store->name = $request->name;
        $reference = strtoupper(substr($request->name, 0, 3));
        $raw_material_extra_big_store->code = $reference.date("y").substr(number_format(time() * mt_rand(), 0, '', ''), 0, 6);
        $store_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$raw_material_extra_big_store->code;
        $raw_material_extra_big_store->store_signature = $store_signature;
        $raw_material_extra_big_store->emplacement = $request->emplacement;
        $raw_material_extra_big_store->default_account_id = $request->default_account_id;
        $raw_material_extra_big_store->manager = $request->manager;
        $raw_material_extra_big_store->created_by = $this->user->name;
        $raw_material_extra_big_store->save();

        $raw_material_extra_big_store_detail = new RawMaterialExtraBigStoreDetail();
        $raw_material_extra_big_store_detail->name = $request->name;
        $raw_material_extra_big_store_detail->code = $raw_material_extra_big_store->code;
        $raw_material_extra_big_store_detail->store_signature = $store_signature;
        $raw_material_extra_big_store_detail->emplacement = $request->emplacement;
        $raw_material_extra_big_store_detail->default_account_id = $request->default_account_id;
        $raw_material_extra_big_store_detail->manager = $request->manager;
        $raw_material_extra_big_store_detail->created_by = $this->user->name;
        $raw_material_extra_big_store_detail->save();

        DB::commit();
            session()->flash('success', 'Raw Material Big Store has been created !!');
            return redirect()->route('admin.raw-material-extra-big-store.index');
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
        $raw_material_extra_big_store = RawMaterialExtraBigStore::where('code',$code)->first();
        $raw_material_extra_big_store_details = RawMaterialExtraBigStoreDetail::where('code',$code)->where('r_material_id','!=','')->with('rawMaterial.rawMaterialMeasurement')->get();
        return view('backend.pages.raw_material_extra_big_store.show', compact(
            'raw_material_extra_big_store_details','raw_material_extra_big_store'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($code)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_store.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any raw material store !');
        }

        $raw_material_extra_big_store = RawMaterialExtraBigStore::where('code',$code)->first();
        
        if (!$raw_material_extra_big_store) {
            session()->flash('error', 'Store not found!');
            return redirect()->route('admin.raw-material-extra-big-store.index');
        }
        
        $accounts = AccountAccount::all();

        return view('backend.pages.raw_material_extra_big_store.edit', compact(
            'raw_material_extra_big_store','accounts'));
    }

    public function storeStatus($code)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_store.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any raw material store !');
        }

        $datas = RawMaterialExtraBigStoreDetail::where('code',$code)->where('r_material_id','!=','')->get();
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $currentTime = Carbon::now();
        $totalPrixAchat = DB::table('raw_material_extra_big_store_details')->where('code',$code)->sum('total_purchase_value');

        $dateT =  $currentTime->toDateTimeString();

        $totalPrixVente = DB::table('raw_material_extra_big_store_details')->where('code',$code)->sum('total_selling_value');

        $store_signature = RawMaterialExtraBigStoreDetail::where('code',$code)->value('store_signature');

        $dateTime = str_replace([' ',':'], '_', $dateT);
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.raw_material_extra_big_store_status', compact('datas','dateTime','setting','totalPrixAchat','totalPrixVente','store_signature'), [], 'UTF-8');//->setPaper('a4', 'landscape');

        Storage::put('public/raw_material_extra_big_store/Etat_stock/'.'ETAT AU GRAND STOCK DES MATIÈRES PREMIÈRES '.$dateTime.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('ETAT AU GRAND STOCK DES MATIÈRES PREMIÈRES '.$dateTime.'.pdf');
    }

    public function print($code) { return $this->storeStatus($code); }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $code)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_store.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any raw material store !');
        }

        // Create New RawMaterialExtraBigStore
        $raw_material_extra_big_store = RawMaterialExtraBigStore::where('code',$code)->first();
        $raw_material_extra_big_store_detail = RawMaterialExtraBigStoreDetail::where('code',$code)->first();

        // Validation Data
        $request->validate([
            'name' => 'required|max:255',
            'emplacement' => 'required',
            'default_account_id' => 'required'
        ]);

        try {DB::beginTransaction();

        $raw_material_extra_big_store->name = $request->name;
        $raw_material_extra_big_store->emplacement = $request->emplacement;
        $raw_material_extra_big_store->manager = $request->manager;
        $raw_material_extra_big_store->default_account_id = $request->default_account_id;
        $raw_material_extra_big_store->created_by = $this->user->name;
        $raw_material_extra_big_store->save();

        $raw_material_extra_big_store_detail->name = $request->name;
        $raw_material_extra_big_store_detail->emplacement = $request->emplacement;
        $raw_material_extra_big_store_detail->manager = $request->manager;
        $raw_material_extra_big_store_detail->default_account_id = $request->default_account_id;
        $raw_material_extra_big_store_detail->created_by = $this->user->name;
        $raw_material_extra_big_store_detail->save();

        DB::commit();
            session()->flash('success', 'Raw Material Big Store has been updated !!');
            return redirect()->route('admin.raw-material-big-store.index');
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
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_store.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any raw material store !');
        }

        $raw_material_extra_big_store_detail = RawMaterialExtraBigStoreDetail::find($id);
        if (!is_null($raw_material_extra_big_store_detail)) {
            $raw_material_extra_big_store_detail->delete();
        }

        session()->flash('success', 'Raw Material Big Store has been deleted !!');
        return back();
    }
}
