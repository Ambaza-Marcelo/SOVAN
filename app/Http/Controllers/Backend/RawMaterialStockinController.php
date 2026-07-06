<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesStockDocumentApproval;
use App\Http\Controllers\Backend\Concerns\UpdatesLedgerTotals;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Storage;
use App\Models\RawMaterial;
use App\Models\RawMaterialStockin;
use App\Models\RawMaterialStockinDetail;
use App\Models\RawMaterialBigStoreDetail;
use App\Models\RawMaterialBigStore;
use App\Models\RawMaterialExtraBigStore;
use App\Models\RawMaterialExtraBigStoreDetail;
use App\Models\RawMaterialSmallStore;
use App\Models\RawMaterialSmallStoreDetail;
use App\Models\RawMaterialBigReport;
use App\Models\RawMaterialExtraBigReport;
use App\Models\RawMaterialSmallReport;
use App\Models\Accounting\AccountCostCenter;
use App\Models\Accounting\AccountJournalEntry;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountSetting;
use Carbon\Carbon;
use PDF;
use App\Services\Stock\StockCumpCalculator;
use Validator;
use Excel;
use Mail;

class RawMaterialStockinController extends Controller
{
    use HandlesStockDocumentApproval;
    use UpdatesLedgerTotals;

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
    public function index(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_stockin.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any stockin !');
        }
        $applyDateOrLimit = function ($query) use ($request) {
            if ($request->filled('date_debut') && $request->filled('date_fin')) {
                $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
                $dateFin = Carbon::parse($request->date_fin)->endOfDay();
                $query->whereBetween('date', [$dateDebut, $dateFin]);
            } else {
                $query->take(100);
            }
            return $query;
        };
        if ($this->user->can('raw_material_stockin.view') && $this->user->can('raw_material_small_inventory.view') && $this->user->can('raw_material_big_inventory.view')) {
            $stockins = $applyDateOrLimit(RawMaterialStockin::orderBy('id','desc'))->get();
            return view('backend.pages.raw_material_stockin.index', compact('stockins'));
        }
        if ($this->user->can('raw_material_stockin.view') && $this->user->can('raw_material_big_inventory.view')) {
            $stockins = $applyDateOrLimit(RawMaterialStockin::where('dest_bg_store_id','!=','')->orderBy('id','desc'))->get();
            return view('backend.pages.raw_material_stockin.index', compact('stockins'));
        }
        if ($this->user->can('raw_material_stockin.view') && $this->user->can('raw_material_small_inventory.view')) {
            $stockins = $applyDateOrLimit(RawMaterialStockin::where('dest_sm_store_id','!=','')->orderBy('id','desc'))->get();
            return view('backend.pages.raw_material_stockin.index', compact('stockins'));
        }
        abort(403, 'Sorry !! You are Unauthorized to view any stockin !');
    }

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_stockin.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export stockins !');
        }
        $dateDebut = $request->input('date_debut') ?? $request->input('start_date');
        $dateFin = $request->input('date_fin') ?? $request->input('end_date');
        if (!$dateDebut || !$dateFin) {
            return redirect()->route('admin.raw-material-stockins.index')
                ->with('error', __('messages.export_select_date_range'));
        }
        $filename = 'entrees_stock_matieres_premieres_' . Carbon::parse($dateDebut)->format('Y-m-d') . '_' . Carbon::parse($dateFin)->format('Y-m-d') . '.xlsx';
        return Excel::download(new \App\Exports\RawMaterialStockinExport(), $filename);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('raw_material_stockin.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockin !');
        }

        $raw_materials  = RawMaterial::orderBy('name','asc')->get();
        $raw_material_big_stores = RawMaterialBigStore::all();
        $raw_material_extra_big_stores = RawMaterialExtraBigStore::all();
        $raw_material_small_stores = RawMaterialSmallStore::all();
        return view('backend.pages.raw_material_stockin.create', compact('raw_materials','raw_material_big_stores','raw_material_extra_big_stores','raw_material_small_stores'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('raw_material_stockin.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockin !');
        }

        $rules = array(
                'r_material_id.*'  => 'required',
                'date'  => 'required',
                //'unit.*'  => 'required',
                'quantity.*'  => 'required',
                'purchase_price.*'  => 'required',
                'handingover'  => 'required',
                'origin'  => 'required',
                'receptionist'  => 'required',
                'item_movement_type'  => 'required',
                'store_type'  => 'required',
                'description'  => 'required|max:490'
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $r_material_id = $request->r_material_id;
            $date = $request->date;
            $invoice_currency = $request->invoice_currency;
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $origin = $request->origin;
            $store_type = $request->store_type;
            $description =$request->description; 
            $item_movement_type =$request->item_movement_type; 
            $dest_extra_store_id = $request->dest_extra_store_id;
            $dest_bg_store_id = $request->dest_bg_store_id;
            $dest_sm_store_id = $request->dest_sm_store_id;
            $unit = $request->unit;
            $quantity = $request->quantity;
            $purchase_price = $request->purchase_price;
            

            $latest = RawMaterialStockin::orderBy('id','desc')->first();
            if ($latest) {
               $stockin_no = 'F-BE' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $stockin_no = 'F-BE' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            if (!empty($dest_sm_store_id)) {
                $dest_sm_store_id = $dest_sm_store_id;
            }elseif(!empty($dest_bg_store_id )){
                $dest_bg_store_id = $dest_bg_store_id ;
            }elseif(!empty($dest_extra_store_id)){
                $dest_extra_store_id = $dest_extra_store_id;
            }else{
                abort(403, 'Sorry !! You have to choose a store ! more information contact Marcellin');
            }

            $created_by = $this->user->name;

            $stockin_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$stockin_no;


            for( $count = 0; $count < count($r_material_id); $count++ ){
                $total_amount_purchase = $quantity[$count] * $purchase_price[$count];

                $data = array(
                    'r_material_id' => $r_material_id[$count],
                    'date' => $date,
                    'store_type' => $store_type,
                    'quantity' => $quantity[$count],
                    //'unit' => $unit[$count],
                    'purchase_price' => $purchase_price[$count],
                    'total_amount_purchase' => $total_amount_purchase,
                    'receptionist' => $receptionist,
                    'handingover' => $handingover,
                    'origin' => $origin,
                    'dest_extra_store_id' => $dest_extra_store_id,
                    'dest_bg_store_id' => $dest_bg_store_id,
                    'dest_sm_store_id' => $dest_sm_store_id,
                    'stockin_no' => $stockin_no,
                    'stockin_signature' => $stockin_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'item_movement_type' => $item_movement_type,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }
            RawMaterialStockinDetail::insert($insert_data);


            //create stockin
            $stockin = new RawMaterialStockin();
            $stockin->date = $date;
            $stockin->stockin_no = $stockin_no;
            $stockin->stockin_signature = $stockin_signature;
            $stockin->receptionist = $receptionist;
            $stockin->handingover = $handingover;
            $stockin->origin = $origin;
            $stockin->dest_extra_store_id = $dest_extra_store_id;
            $stockin->dest_bg_store_id = $dest_bg_store_id;
            $stockin->dest_sm_store_id = $dest_sm_store_id;
            $stockin->created_by = $created_by;
            $stockin->store_type = $store_type;
            $stockin->status = 1;
            $stockin->item_movement_type = $item_movement_type;
            $stockin->description = $description;
            $stockin->created_at = \Carbon\Carbon::now();
            $stockin->save();

            DB::commit();
            session()->flash('success', 'stockin has been created !!');
            return redirect()->route('admin.raw-material-stockins.index');
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($stockin_no)
    {
        //
        $stockin = RawMaterialStockin::where('stockin_no', $stockin_no)->first();
        $stockinDetails = RawMaterialStockinDetail::with(['rawMaterial.rawMaterialMeasurement'])
            ->where('stockin_no', $stockin_no)->get();
        
        // Calculate totals
        $totalQuantity = $stockinDetails->sum('quantity');
        $totalPurchaseAmount = $stockinDetails->sum('total_amount_purchase');
        
        return view('backend.pages.raw_material_stockin.show', compact('stockin', 'stockinDetails', 'totalQuantity', 'totalPurchaseAmount'));
         
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_stockin.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any stockin !');
        }

        $stockin = RawMaterialStockin::where('stockin_no', $stockin_no)->first();
        
        if (is_null($stockin)) {
            session()->flash('error', 'Stockin not found !!');
            return redirect()->route('admin.raw-material-stockins.index');
        }

        // Prevent editing when status is 4 (Approuvé/Approved)
        if ($stockin->status == 4) {
            session()->flash('error', 'Cannot edit stockin that has been approved (status = 4) !!');
            return redirect()->route('admin.raw-material-stockins.index');
        }

        $stockinDetails = RawMaterialStockinDetail::with(['rawMaterial.rawMaterialMeasurement'])->where('stockin_no', $stockin_no)->get();
        $raw_materials = RawMaterial::orderBy('name','asc')->get();
        $raw_material_extra_big_stores = RawMaterialExtraBigStore::all();
        $raw_material_big_stores = RawMaterialBigStore::all();
        $raw_material_small_stores = RawMaterialSmallStore::all();
        
        return view('backend.pages.raw_material_stockin.edit', compact(
            'stockin', 
            'stockinDetails', 
            'raw_materials', 
            'raw_material_extra_big_stores', 
            'raw_material_big_stores', 
            'raw_material_small_stores'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_stockin.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any stockin !');
        }

        $stockin = RawMaterialStockin::where('stockin_no', $stockin_no)->first();
        
        if (is_null($stockin)) {
            session()->flash('error', 'Stockin not found !!');
            return redirect()->route('admin.raw-material-stockins.index');
        }

        // Prevent updating when status is 4 (Approuvé/Approved)
        if ($stockin->status == 4) {
            session()->flash('error', 'Cannot update stockin that has been approved (status = 4) !!');
            return redirect()->route('admin.raw-material-stockins.index');
        }

        $rules = array(
            'r_material_id.*'  => 'required',
            'date'  => 'required',
            'quantity.*'  => 'required',
            'purchase_price.*'  => 'required',
            'handingover'  => 'required',
            'origin'  => 'required',
            'store_type'  => 'required',
            'receptionist'  => 'required',
            'item_movement_type'  => 'required',
            'description'  => 'required|max:490'
        );

        $error = Validator::make($request->all(),$rules);

        if($error->fails()){
            return response()->json([
                'error' => $error->errors()->all(),
            ]);
        }

        try {DB::beginTransaction();

            $r_material_id = $request->r_material_id;
            $date = Carbon::parse($request->date);
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $origin = $request->origin;
            $store_type = $request->store_type;
            $description = $request->description; 
            $dest_bg_store_id = $request->dest_bg_store_id;
            $dest_sm_store_id = $request->dest_sm_store_id;
            $dest_extra_store_id = $request->dest_extra_store_id;
            $item_movement_type = $request->item_movement_type;
            $quantity = $request->quantity;
            $purchase_price = $request->purchase_price;
            
            $updated_by = $this->user->name;

            if (!empty($dest_sm_store_id)) {
                $dest_sm_store_id = $dest_sm_store_id;
            }elseif(!empty($dest_bg_store_id )){
                $dest_bg_store_id = $dest_bg_store_id ;
            }elseif(!empty($dest_extra_store_id)){
                $dest_extra_store_id = $dest_extra_store_id;
            }else{
                abort(403, 'Sorry !! You have to choose a store ! more information contact Marcellin');
            }

            // Delete existing details
            RawMaterialStockinDetail::where('stockin_no', $stockin_no)->delete();

            // Insert updated details
            for( $count = 0; $count < count($r_material_id); $count++ ){
                $total_amount_purchase = $quantity[$count] * $purchase_price[$count];

                $data = array(
                    'r_material_id' => $r_material_id[$count],
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'purchase_price' => $purchase_price[$count],
                    'total_amount_purchase' => $total_amount_purchase,
                    'receptionist' => $receptionist,
                    'handingover' => $handingover,
                    'origin' => $origin,
                    'store_type' => $store_type,
                    'dest_bg_store_id' => $dest_bg_store_id,
                    'dest_sm_store_id' => $dest_sm_store_id,
                    'dest_extra_store_id' => $dest_extra_store_id,
                    'item_movement_type' => $item_movement_type,
                    'stockin_no' => $stockin_no,
                    'stockin_signature' => $stockin->stockin_signature,
                    'created_by' => $stockin->created_by,
                    'updated_by' => $updated_by,
                    'description' => $description,
                    'status' => $stockin->status, // Keep existing status
                    'created_at' => $stockin->created_at,
                    'updated_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }
            RawMaterialStockinDetail::insert($insert_data);

            // Update stockin
            $stockin->date = $date;
            $stockin->receptionist = $receptionist;
            $stockin->handingover = $handingover;
            $stockin->origin = $origin;
            $stockin->store_type = $store_type;
            $stockin->dest_bg_store_id = $dest_bg_store_id;
            $stockin->dest_sm_store_id = $dest_sm_store_id;
            $stockin->dest_extra_store_id = $dest_extra_store_id;
            $stockin->item_movement_type = $item_movement_type;
            $stockin->updated_by = $updated_by;
            $stockin->description = $description;
            $stockin->updated_at = \Carbon\Carbon::now();
            $stockin->save();

            DB::commit();
            session()->flash('success', 'Stockin has been updated !!');
            return redirect()->route('admin.raw-material-stockins.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bonEntree($stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_stockin.create')) {
            abort(403, 'Sorry !! You are Unauthorized to print handover document!');
        }
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $stockin_no = RawMaterialStockin::where('stockin_no', $stockin_no)->value('stockin_no');
        $datas = RawMaterialStockinDetail::where('stockin_no', $stockin_no)->get();
        $data = RawMaterialStockinDetail::where('stockin_no', $stockin_no)->first();
        $receptionist = RawMaterialStockin::where('stockin_no', $stockin_no)->value('receptionist');
        $description = RawMaterialStockin::where('stockin_no', $stockin_no)->value('description');
        $origin = RawMaterialStockin::where('stockin_no', $stockin_no)->value('origin');
        $handingover = RawMaterialStockin::where('stockin_no', $stockin_no)->value('handingover');
        $stockin_signature = RawMaterialStockin::where('stockin_no', $stockin_no)->value('stockin_signature');
        $date = RawMaterialStockin::where('stockin_no', $stockin_no)->value('date');
        $totalValue = DB::table('raw_material_stockin_details')
            ->where('stockin_no', '=', $stockin_no)
            ->sum('total_amount_purchase');
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.raw_material_stockin', compact('datas','stockin_no','totalValue','receptionist','description','origin','handingover','setting','date','data','stockin_signature'), [], 'UTF-8');

        Storage::put('public/pdf/raw_material_stockin/'.$stockin_no.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('BON_ENTREE_'.$stockin_no.'.pdf');
        
    }

    /**
     * Impression par numéro de document (alias pour bonEntree).
     */
    public function print($stockin_no)
    {
        return $this->bonEntree($stockin_no);
    }

    public function validateStockin($stockin_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_stockin.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any stockin !');
        }

        $stockin = RawMaterialStockin::where('stockin_no', $stockin_no)->first();
        if (!$stockin) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentValidate($stockin)) {
            return $response;
        }

        try {DB::beginTransaction();

            RawMaterialStockin::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            RawMaterialStockinDetail::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'stockin has been validated !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function reject($stockin_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_stockin.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any stockin !');
        }

        try {DB::beginTransaction();

        RawMaterialStockin::where('stockin_no', '=', $stockin_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);
        RawMaterialStockinDetail::where('stockin_no', '=', $stockin_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Stockin has been rejected !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
    }

    public function reset($stockin_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_stockin.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any stockin !');
        }

        try {DB::beginTransaction();

        RawMaterialStockin::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
        RawMaterialStockinDetail::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Stockin has been reseted !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function confirm($stockin_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_stockin.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any stockin !');
        }

        $stockin = RawMaterialStockin::where('stockin_no', $stockin_no)->first();
        if (!$stockin) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentConfirm($stockin)) {
            return $response;
        }

        try {DB::beginTransaction();

        RawMaterialStockin::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            RawMaterialStockinDetail::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Stockin has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function approuve($stockin_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_stockin.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any stockin !');
        }

        $stockinHeader = RawMaterialStockin::where('stockin_no', $stockin_no)->first();
        if (!$stockinHeader) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentApprove($stockinHeader)) {
            return $response;
        }

        try {DB::beginTransaction();

        $datas = RawMaterialStockinDetail::where('stockin_no', $stockin_no)->get();
        $accountSetting = AccountSetting::where('active', 1)->first();
        if (!$accountSetting || !$accountSetting->f_eaj_account_id) {
            DB::rollback();
            session()->flash('error', __('messages.approval_accounts_required'));
            return back();
        }
        foreach ($datas as $d) {
            $storeAccountId = null;
            if (!empty($d->dest_bg_store_id)) {
                $storeAccountId = RawMaterialBigStore::where('id', $d->dest_bg_store_id)->value('default_account_id');
            } elseif (!empty($d->dest_sm_store_id)) {
                $storeAccountId = RawMaterialSmallStore::where('id', $d->dest_sm_store_id)->value('default_account_id');
            } elseif (!empty($d->dest_extra_store_id)) {
                $storeAccountId = RawMaterialExtraBigStore::where('id', $d->dest_extra_store_id)->value('default_account_id');
            }
            if (!$storeAccountId) {
                DB::rollback();
                session()->flash('error', __('messages.approval_accounts_required'));
                return back();
            }
        }

        foreach($datas as $data){

                if ($data->store_type == '2') {
                    $code_store_destination = RawMaterialSmallStore::where('id',$data->dest_sm_store_id)->value('code');

                $valeurStockInitialDestination = RawMaterialSmallStoreDetail::where('code',$code_store_destination)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('total_cump_value');
                $quantityStockInitialDestination = RawMaterialSmallStoreDetail::where('code',$code_store_destination)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('quantity');
                $cumpResult = StockCumpCalculator::weightedAverageOnStockIn(
                    (float) $quantityStockInitialDestination,
                    (float) $valeurStockInitialDestination,
                    (float) $data->quantity,
                    (float) $data->purchase_price,
                    (float) $data->purchase_price
                );
                $cump = $cumpResult['cump'];
                $quantityTotalBigStore = $cumpResult['quantity_total'];

                $reportSmallStore = array(
                    'r_material_id' => $data->r_material_id,
                    'quantity_stock_initial' => $quantityStockInitialDestination,
                    'value_stock_initial' => $valeurStockInitialDestination,
                    'code_store' => $code_store_destination,
                    'date' => $data->date,
                    'quantity_stockin' => $data->quantity,
                    'value_stockin' => $data->total_amount_purchase,
                    'quantity_stock_final' => $quantityStockInitialDestination + $data->quantity,
                    'value_stock_final' => $cump * ($quantityStockInitialDestination + $data->quantity),
                    'cump' => $cump,
                    'created_by' => $this->user->name,
                    'type_transaction' => $data->item_movement_type,
                    'document_no' => $data->stockin_no,
                    'description' => $data->description,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportSmallStoreData[] = $reportSmallStore;

                    $smallStore = array(
                        'r_material_id' => $data->r_material_id,
                        'quantity' => $quantityTotalBigStore,
                        'purchase_price' => $data->purchase_price,
                        'total_purchase_value' => $quantityTotalBigStore * $data->purchase_price,
                        'cump' => $cump,
                        'total_cump_value' => $quantityTotalBigStore * $cump,
                        'created_by' => $this->user->name,
                        'verified' => false
                    );

                    $bigStoreData[] = $smallStore;

                    $rawMaterialData = array(
                        'id' => $data->r_material_id,
                        'quantity' => $quantityTotalBigStore,
                        'cump' => $cump,
                        'purchase_price' => $data->purchase_price,
                    );

                    RawMaterial::where('id',$data->r_material_id)
                        ->update($rawMaterialData);

                        $existingDetailId = RawMaterialSmallStoreDetail::where('code',$code_store_destination)->where("r_material_id",$data->r_material_id)->value('r_material_id');

                        if (!empty($existingDetailId)) {
                            RawMaterialSmallStoreDetail::where('code',$code_store_destination)->where('r_material_id',$data->r_material_id)
                        ->update($smallStore);
                        $flag = 1;
                        }else{
                            $flag = 0;
                            session()->flash('error', 'this item is not saved in the stock');
                            return back();
                        }
  
                }elseif ($data->store_type == '1') {
                    $code_store_destination = RawMaterialBigStore::where('id',$data->dest_bg_store_id)->value('code');

                $valeurStockInitialDestination = RawMaterialBigStoreDetail::where('code',$code_store_destination)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('total_cump_value');
                $quantityStockInitialDestination = RawMaterialBigStoreDetail::where('code',$code_store_destination)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('quantity');
                $cumpResult = StockCumpCalculator::weightedAverageOnStockIn(
                    (float) $quantityStockInitialDestination,
                    (float) $valeurStockInitialDestination,
                    (float) $data->quantity,
                    (float) $data->purchase_price,
                    (float) $data->purchase_price
                );
                $cump = $cumpResult['cump'];
                $quantityTotalBigStore = $cumpResult['quantity_total'];

                $reportBigStore = array(
                    'r_material_id' => $data->r_material_id,
                    'quantity_stock_initial' => $quantityStockInitialDestination,
                    'value_stock_initial' => $valeurStockInitialDestination,
                    'code_store' => $code_store_destination,
                    'date' => $data->date,
                    'quantity_stockin' => $data->quantity,
                    'value_stockin' => $data->total_amount_purchase,
                    'quantity_stock_final' => $quantityStockInitialDestination + $data->quantity,
                    'value_stock_final' => $cump * ($quantityStockInitialDestination + $data->quantity),
                    'cump' => $cump,
                    'created_by' => $this->user->name,
                    'type_transaction' => $data->item_movement_type,
                    'document_no' => $data->stockin_no,
                    'description' => $data->description,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportBigStoreData[] = $reportBigStore;

                    $bigStore = array(
                        'r_material_id' => $data->r_material_id,
                        'quantity' => $quantityTotalBigStore,
                        'purchase_price' => $data->purchase_price,
                        'total_purchase_value' => $quantityTotalBigStore * $data->purchase_price,
                        'cump' => $cump,
                        'total_cump_value' => $quantityTotalBigStore * $cump,
                        'created_by' => $this->user->name,
                        'verified' => false
                    );

                    $bigStoreData[] = $bigStore;

                    $rawMaterialData = array(
                        'id' => $data->r_material_id,
                        'quantity' => $quantityTotalBigStore,
                        'cump' => $cump,
                        'purchase_price' => $data->purchase_price,
                    );

                    RawMaterial::where('id',$data->r_material_id)
                        ->update($rawMaterialData);

                        $existingDetailId = RawMaterialBigStoreDetail::where('code',$code_store_destination)->where("r_material_id",$data->r_material_id)->value('r_material_id');

                        if (!empty($existingDetailId)) {
                            RawMaterialBigStoreDetail::where('code',$code_store_destination)->where('r_material_id',$data->r_material_id)
                        ->update($bigStore);
                        $flag = 1;
                        }else{
                            $flag = 0;
                            session()->flash('error', 'this item is not saved in the stock');
                            return back();
                        }
  
                }else{
                    $code_store_destination = RawMaterialExtraBigStore::where('id',$data->dest_extra_store_id)->value('code');

                $valeurStockInitialDestination = RawMaterialExtraBigStoreDetail::where('code',$code_store_destination)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('total_cump_value');
                $quantityStockInitialDestination = RawMaterialExtraBigStoreDetail::where('code',$code_store_destination)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('quantity');
                $cumpResult = StockCumpCalculator::weightedAverageOnStockIn(
                    (float) $quantityStockInitialDestination,
                    (float) $valeurStockInitialDestination,
                    (float) $data->quantity,
                    (float) $data->purchase_price,
                    (float) $data->purchase_price
                );
                $cump = $cumpResult['cump'];
                $quantityTotalBigStore = $cumpResult['quantity_total'];

                $reportExtraBigStore = array(
                    'r_material_id' => $data->r_material_id,
                    'quantity_stock_initial' => $quantityStockInitialDestination,
                    'value_stock_initial' => $valeurStockInitialDestination,
                    'code_store' => $code_store_destination,
                    'date' => $data->date,
                    'quantity_stockin' => $data->quantity,
                    'value_stockin' => $data->total_amount_purchase,
                    'quantity_stock_final' => $quantityStockInitialDestination + $data->quantity,
                    'value_stock_final' => $cump * ($quantityStockInitialDestination + $data->quantity),
                    'cump' => $cump,
                    'created_by' => $this->user->name,
                    'type_transaction' => $data->item_movement_type,
                    'document_no' => $data->stockin_no,
                    'description' => $data->description,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportExtraBigStoreData[] = $reportExtraBigStore;

                    $bigStore = array(
                        'r_material_id' => $data->r_material_id,
                        'quantity' => $quantityTotalBigStore,
                        'purchase_price' => $data->purchase_price,
                        'total_purchase_value' => $quantityTotalBigStore * $data->purchase_price,
                        'cump' => $cump,
                        'total_cump_value' => $quantityTotalBigStore * $cump,
                        'created_by' => $this->user->name,
                        'verified' => false
                    );

                    $bigStoreData[] = $bigStore;

                    $rawMaterialData = array(
                        'id' => $data->r_material_id,
                        'quantity' => $quantityTotalBigStore,
                        'cump' => $cump,
                        'purchase_price' => $data->purchase_price,
                    );

                    RawMaterial::where('id',$data->r_material_id)
                        ->update($rawMaterialData);

                        $existingDetailId = RawMaterialExtraBigStoreDetail::where('code',$code_store_destination)->where("r_material_id",$data->r_material_id)->value('r_material_id');

                        if (!empty($existingDetailId)) {
                            RawMaterialExtraBigStoreDetail::where('code',$code_store_destination)->where('r_material_id',$data->r_material_id)
                        ->update($bigStore);
                        $flag = 1;
                        }else{
                            $flag = 0;
                            session()->flash('error', 'this item is not saved in the stock');
                            return back();
                        }
  
                }
        }

        $totalValue = DB::table('raw_material_stockin_details')
            ->where('stockin_no', '=', $stockin_no)
            ->sum('total_amount_purchase');

        if(!empty($data->dest_bg_store_id) && $flag != 0){
            RawMaterialBigReport::insert($reportBigStoreData);

            //start debit operations
                $id_acc_stock = RawMaterialBigStore::where('id',$data->dest_bg_store_id)->value('default_account_id');

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'debit', $totalValue);

                $journalEntryDebitdata = array(
                    'date' => $data->date,
                    'document_no' => $data->stockin_no,
                    'description' => $data->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $data->description,
                    'debit' => $totalValue,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_debit_data[] = $journalEntryDebitdata;
                
                AccountJournalEntryDetail::insert($insert_journal_debit_data);

                //credit adjustment account
                $f_eaj_account_id = AccountSetting::where('active',1)->value('f_eaj_account_id');

                $eajLedgerState = $this->updateLedgerTotals($f_eaj_account_id, 'credit', $totalValue);
            
                $journalEntryCrediteajdataAcc2 = array(
                    'date' => $data->date,
                    'document_no' => $data->stockin_no,
                    'description' => $data->description,
                    'account_id' => $f_eaj_account_id,
                    'libelle' => $data->description,
                    'credit' => $totalValue,
                    'balance' => $eajLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_credit_eaj_data_acc2[] = $journalEntryCrediteajdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_credit_eaj_data_acc2);
            
                //end credit operation
        }elseif (!empty($data->dest_sm_store_id) && $flag != 0) {
            RawMaterialSmallReport::insert($reportSmallStoreData);

            //start debit operations
                $id_acc_stock = RawMaterialSmallStore::where('id',$data->dest_sm_store_id)->value('default_account_id');

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'debit', $totalValue);

                $journalEntryDebitdata = array(
                    'date' => $data->date,
                    'document_no' => $data->stockin_no,
                    'description' => $data->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $data->description,
                    'debit' => $totalValue,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_debit_data[] = $journalEntryDebitdata;
                
                AccountJournalEntryDetail::insert($insert_journal_debit_data);

                //credit adjustment account
                $f_eaj_account_id = AccountSetting::where('active',1)->value('f_eaj_account_id');

                $eajLedgerState = $this->updateLedgerTotals($f_eaj_account_id, 'credit', $totalValue);
            
                $journalEntryCrediteajdataAcc2 = array(
                    'date' => $data->date,
                    'document_no' => $data->stockin_no,
                    'description' => $data->description,
                    'account_id' => $f_eaj_account_id,
                    'libelle' => $data->description,
                    'credit' => $totalValue,
                    'balance' => $eajLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_credit_eaj_data_acc2[] = $journalEntryCrediteajdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_credit_eaj_data_acc2);
            
                //end credit operation
        }else{
            RawMaterialExtraBigReport::insert($reportExtraBigStoreData);

            //start debit operations
                $id_acc_stock = RawMaterialExtraBigStore::where('id',$data->dest_extra_store_id)->value('default_account_id');

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'debit', $totalValue);

                $journalEntryDebitdata = array(
                    'date' => $data->date,
                    'document_no' => $data->stockin_no,
                    'description' => $data->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $data->description,
                    'debit' => $totalValue,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_debit_data[] = $journalEntryDebitdata;
                
                AccountJournalEntryDetail::insert($insert_journal_debit_data);

                //credit adjustment account
                $f_eaj_account_id = AccountSetting::where('active',1)->value('f_eaj_account_id');

                $eajLedgerState = $this->updateLedgerTotals($f_eaj_account_id, 'credit', $totalValue);
            
                $journalEntryCrediteajdataAcc2 = array(
                    'date' => $data->date,
                    'document_no' => $data->stockin_no,
                    'description' => $data->description,
                    'account_id' => $f_eaj_account_id,
                    'libelle' => $data->description,
                    'credit' => $totalValue,
                    'balance' => $eajLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_credit_eaj_data_acc2[] = $journalEntryCrediteajdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_credit_eaj_data_acc2);
            
                //end credit operation
        }
            RawMaterialStockin::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 4,'approuved_by' => $this->user->name]);
            RawMaterialStockinDetail::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 4,'approuved_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Stockin has been done successfuly !, to '.$code_store_destination);
            return back();
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
     * @param  int  $stockin_no
     * @return \Illuminate\Http\Response
     */
    public function destroy($stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_stockin.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any stockin !');
        }

        $stockin = RawMaterialStockin::where('stockin_no',$stockin_no)->first();
        
        if (is_null($stockin)) {
            session()->flash('error', 'Stockin not found !!');
            return back();
        }

        // Prevent deletion when status is 4 (Approuvé/Approved)
        if ($stockin->status == 4) {
            session()->flash('error', 'Cannot delete stockin that has been approved (status = 4) !!');
            return back();
        }

        try {DB::beginTransaction();

        if (!is_null($stockin)) {
            $stockin->delete();
            RawMaterialStockinDetail::where('stockin_no',$stockin_no)->delete();
        }

        DB::commit();
            session()->flash('success', 'Stockin has been deleted !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

}
