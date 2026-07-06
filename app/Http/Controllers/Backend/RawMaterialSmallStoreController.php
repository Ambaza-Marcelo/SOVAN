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
use App\Models\RawMaterialSmallStore;
use App\Models\RawMaterialSmallStoreDetail;
use App\Exports\RawMaterialSmallStoreExport;
use App\Models\Accounting\AccountAccount;
use Carbon\Carbon;
use Excel;
use PDF;

class RawMaterialSmallStoreController extends Controller
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
        if (is_null($this->user) || !$this->user->can('raw_material_small_store.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any raw material !');
        }

        $raw_material_small_stores = RawMaterialSmallStore::with('defaultAccount')->get();
        $hasRecord = RawMaterialSmallStore::count() > 0;
        return view('backend.pages.raw_material_small_store.index', compact('raw_material_small_stores', 'hasRecord'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('raw_material_small_store.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any raw material !');
        }

        // Check if a record already exists
        if (RawMaterialSmallStore::count() > 0) {
            session()->flash('error', 'Only one Petit Stock des matières premières record is allowed. Please edit the existing record instead.');
            return redirect()->route('admin.raw-material-small-store.index');
        }

        $accounts = AccountAccount::all();

        return view('backend.pages.raw_material_small_store.create',compact('accounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_small_store.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any raw material !');
        }

        // Check if a record already exists
        if (RawMaterialSmallStore::count() > 0) {
            session()->flash('error', 'Only one Petit Stock des matières premières record is allowed. Please edit the existing record instead.');
            return redirect()->route('admin.raw-material-small-store.index');
        }

        // Validation Data
        $request->validate([
            'name' => 'required|max:255',
            'emplacement' => 'required|max:20',
            'default_account_id' => 'required'
        ]);

        // Create New store
        $raw_material_small_store = new RawMaterialSmallStore();
        $raw_material_small_store->name = $request->name;
        $reference = strtoupper(substr($request->name, 0, 3));
        $raw_material_small_store->code = $reference.date("y").substr(number_format(time() * mt_rand(), 0, '', ''), 0, 6);
        $store_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$raw_material_small_store->code;
        $raw_material_small_store->store_signature = $store_signature;
        $raw_material_small_store->emplacement = $request->emplacement;
        $raw_material_small_store->default_account_id = $request->default_account_id;
        $raw_material_small_store->manager = $request->manager;
        $raw_material_small_store->created_by = $this->user->name;
        $raw_material_small_store->save();

        // Create New store
        $raw_material_small_store_detail = new RawMaterialSmallStoreDetail();
        $raw_material_small_store_detail->name = $request->name;
        $raw_material_small_store_detail->code = $raw_material_small_store->code;
        $raw_material_small_store_detail->emplacement = $request->emplacement;
        $raw_material_small_store_detail->default_account_id = $request->default_account_id;
        $raw_material_small_store_detail->manager = $request->manager;
        $raw_material_small_store_detail->created_by = $this->user->name;
        $raw_material_small_store_detail->save();
        session()->flash('success', 'Petit Stock des matières premières has been created !!');
        return redirect()->route('admin.raw-material-small-store.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $code
     * @return \Illuminate\Http\Response
     */
    public function show($code)
    {
        $raw_material_small_store = RawMaterialSmallStore::where('code', $code)->first();

        if (!$raw_material_small_store) {
            session()->flash('error', 'Stock introuvable.');
            return redirect()->route('admin.raw-material-small-store.index');
        }

        // Détails du stock : tous les articles de ce stock (code), avec relation rawMaterial
        $raw_material_small_store_details = RawMaterialSmallStoreDetail::where('code', $code)
            ->whereNotNull('r_material_id')
            ->where('r_material_id', '>', 0)
            ->with(['rawMaterial.rawMaterialMeasurement'])
            ->get()
            ->filter(function ($detail) {
                return $detail->rawMaterial && $detail->rawMaterial->isActive();
            });

        $hasRecord = RawMaterialSmallStore::count() > 0;

        return view('backend.pages.raw_material_small_store.show', compact(
            'raw_material_small_store_details',
            'raw_material_small_store',
            'hasRecord'
        ));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($code)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_small_store.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any raw material store !');
        }

        $raw_material_small_store = RawMaterialSmallStore::where('code',$code)->first();
        
        if (!$raw_material_small_store) {
            session()->flash('error', 'Store not found!');
            return redirect()->route('admin.raw-material-small-store.index');
        }
        
        $accounts = AccountAccount::all();

        return view('backend.pages.raw_material_small_store.edit', compact(
            'raw_material_small_store','accounts'));
    }

    public function storeStatus($code)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_small_store.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any raw material store !');
        }

        $datas = RawMaterialSmallStoreDetail::where('code',$code)->where('r_material_id','!=','')->get();
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $currentTime = Carbon::now();
        $totalPrixAchat = DB::table('raw_material_small_store_details')->where('code',$code)->sum('total_cump_value');

        $dateT =  $currentTime->toDateTimeString();

        $totalPrixVente = DB::table('raw_material_small_store_details')->where('code',$code)->sum('total_selling_value');

        $store_signature = RawMaterialSmallStoreDetail::where('code',$code)->value('store_signature');

        $dateTime = str_replace([' ',':'], '_', $dateT);
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.raw_material_small_store_status', compact('datas','dateTime','setting','totalPrixAchat','totalPrixVente','store_signature'), [], 'UTF-8');//->setPaper('a4', 'landscape');

        Storage::put('public/raw_material_small_store/Etat_stock/'.'ETAT DE PETIT STOCK DES MATIÈRES PREMIÈRES '.$dateTime.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('ETAT DE PETIT STOCK DES MATIÈRES PREMIÈRES '.$dateTime.'.pdf');
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
        if (is_null($this->user) || !$this->user->can('raw_material_small_store.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any raw material store !');
        }

        // Create New RawMaterialSmallStore
        $raw_material_small_store = RawMaterialSmallStore::where('code',$code)->first();
        $raw_material_small_store_detail = RawMaterialSmallStoreDetail::where('code',$code)->first();

        // Validation Data
        $request->validate([
            'name' => 'required|max:255',
            'emplacement' => 'required',
            'default_account_id' => 'required'
        ]);


        $raw_material_small_store->name = $request->name;
        $raw_material_small_store->emplacement = $request->emplacement;
        $raw_material_small_store->default_account_id = $request->default_account_id;
        $raw_material_small_store->manager = $request->manager;
        $raw_material_small_store->created_by = $this->user->name;
        $raw_material_small_store->save();

        $raw_material_small_store_detail->name = $request->name;
        $raw_material_small_store_detail->emplacement = $request->emplacement;
        $raw_material_small_store_detail->manager = $request->manager;
        $raw_material_small_store_detail->default_account_id = $request->default_account_id;
        $raw_material_small_store_detail->created_by = $this->user->name;
        $raw_material_small_store_detail->save();

        session()->flash('success', 'Petit Stock des matières premières has been updated !!');
        return redirect()->route('admin.raw-material-small-store.index');
    }

    public function exportToExcel(Request $request,$code)
    {
        return Excel::download(new RawMaterialSmallStoreExport($code), 'ETAT DU PETIT STOCK DES MATIÈRES PREMIÈRES.xlsx');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_small_store.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any raw material store !');
        }

        
    }
}
