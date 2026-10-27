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
use App\Models\RawMaterialStockout;
use App\Models\RawMaterialStockoutDetail;
use App\Models\RawMaterialBigStoreDetail;
use App\Models\RawMaterialSmallStoreDetail;
use App\Models\RawMaterialBigStore;
use App\Models\RawMaterialSmallStore;
use App\Models\RawMaterialBigReport;
use App\Models\RawMaterialExtraBigStoreDetail;
use App\Models\RawMaterialExtraBigStore;
use App\Models\RawMaterialExtraBigReport;
use App\Models\RawMaterialSmallReport;
use App\Models\Accounting\AccountCostCenter;
use App\Models\Accounting\AccountJournalEntry;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountSetting;
use App\Services\Stock\SauStockoutException;
use App\Services\Stock\SauStockoutJournalService;
use Carbon\Carbon;
use PDF;
use Validator;
use Excel;
use Mail;

class RawMaterialStockoutController extends Controller
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
        if (is_null($this->user) || !$this->user->can('raw_material_stockout.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any stockout !');
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
        if ($this->user->can('raw_material_stockout.view') && $this->user->can('raw_material_small_inventory.view') && $this->user->can('raw_material_big_inventory.view')) {
            $stockouts = $applyDateOrLimit(RawMaterialStockout::orderBy('id','desc'))->get();
            return view('backend.pages.raw_material_stockout.index', compact('stockouts'));
        }
        if ($this->user->can('raw_material_stockout.view') && $this->user->can('raw_material_big_inventory.view')) {
            $stockouts = $applyDateOrLimit(RawMaterialStockout::where('origin_bg_store_id','!=','')->orderBy('id','desc'))->get();
            return view('backend.pages.raw_material_stockout.index', compact('stockouts'));
        }
        if ($this->user->can('raw_material_stockout.view') && $this->user->can('raw_material_small_inventory.view')) {
            $stockouts = $applyDateOrLimit(RawMaterialStockout::where('origin_sm_store_id','!=','')->orderBy('id','desc'))->get();
            return view('backend.pages.raw_material_stockout.index', compact('stockouts'));
        }
        abort(403, 'Sorry !! You are Unauthorized to view any stockout !');
    }

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_stockout.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export stockouts !');
        }
        $dateDebut = $request->input('date_debut') ?? $request->input('start_date');
        $dateFin = $request->input('date_fin') ?? $request->input('end_date');
        if (!$dateDebut || !$dateFin) {
            return redirect()->route('admin.raw-material-stockouts.index')
                ->with('error', __('messages.export_select_date_range'));
        }
        $filename = 'sorties_stock_matieres_premieres_' . Carbon::parse($dateDebut)->format('Y-m-d') . '_' . Carbon::parse($dateFin)->format('Y-m-d') . '.xlsx';
        return Excel::download(new \App\Exports\RawMaterialStockoutExport(), $filename);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('raw_material_stockout.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockout !');
        }

        $raw_materials  = RawMaterial::orderBy('name','asc')->get();
        $raw_material_extra_big_stores = RawMaterialExtraBigStore::all();
        $raw_material_big_stores = RawMaterialBigStore::all();
        $raw_material_small_stores = RawMaterialSmallStore::all();
        return view('backend.pages.raw_material_stockout.create', compact('raw_materials','raw_material_big_stores','raw_material_small_stores','raw_material_extra_big_stores'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('raw_material_stockout.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockout !');
        }

        $rules = array(
                'r_material_id.*'  => 'required',
                'date'  => 'required',
                //'unit.*'  => 'required',
                'quantity.*'  => 'required',
                'asker'  => 'required',
                'destination'  => 'required',
                'item_movement_type' => 'nullable|in:SAU,SP,SV,SD,SC,SAJ',
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
            $asker = $request->asker;
            $destination = $request->destination;
            $origin_sm_store_id = $request->origin_sm_store_id;
            $description =$request->description; 
            $origin_bg_store_id = $request->origin_bg_store_id;
            $origin_extra_store_id = $request->origin_extra_store_id;
            //$unit = $request->unit;
            $quantity = $request->quantity;
            $store_type = $request->store_type;
            $item_movement_type = SauStockoutJournalService::normalizeType($request->item_movement_type);
            

            $latest = RawMaterialStockout::orderBy('id','desc')->first();
            if ($latest) {
               $stockout_no = 'F-BS' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $stockout_no = 'F-BS' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            if (!empty($origin_sm_store_id)) {
                $origin_sm_store_id = $origin_sm_store_id;
            }elseif(!empty($origin_bg_store_id )){
                $origin_bg_store_id = $origin_bg_store_id ;
            }elseif(!empty($origin_extra_store_id)){
                $origin_extra_store_id = $origin_extra_store_id;
            }else{
                abort(403, 'Sorry !! You have to choose a store ! more information contact Marcellin');
            }

            $created_by = $this->user->name;

            $stockout_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$stockout_no;


            for( $count = 0; $count < count($r_material_id); $count++ ){
                if ($store_type == 1) {
                    $purchase_price = RawMaterial::where('id', $r_material_id[$count])->value('cump');

                    $total_value = $quantity[$count] * $purchase_price;
                    $total_purchase_value = $quantity[$count] * $purchase_price;

                    $data = array(
                        'r_material_id' => $r_material_id[$count],
                        'date' => $date,
                        'quantity' => $quantity[$count],
                        //'unit' => $unit[$count],
                        'purchase_price' => $purchase_price,
                        'total_purchase_value' => $total_purchase_value,
                        'asker' => $asker,
                        'destination' => $destination,
                        'origin_sm_store_id' => $origin_sm_store_id,
                        'origin_bg_store_id' => $origin_bg_store_id,
                        'origin_extra_store_id' => $origin_extra_store_id,
                        'stockout_no' => $stockout_no,
                        'stockout_signature' => $stockout_signature,
                        'store_type' => $store_type,
                        'item_movement_type' => $item_movement_type,
                        'created_by' => $created_by,
                        'description' => $description,
                        'status' => 1,
                        'created_at' => \Carbon\Carbon::now()

                    );
                    $insert_data[] = $data;
                }elseif($store_type == 2){
                    $cump = RawMaterial::where('id', $r_material_id[$count])->value('cump');

                    $raw_material = RawMaterial::where('id', $r_material_id[$count])->first();

                    $purchase_price = $cump / $raw_material->rawMaterialMeasurement->equivalent;

                    //$quantityEquivalent = $quantity[$count] / $raw_material->rawMaterialMeasurement->sub_equivalent;

                    $total_purchase_value = $quantity[$count] * $purchase_price;


                    $data = array(
                        'r_material_id' => $r_material_id[$count],
                        'date' => $date,
                        'quantity' => $quantity[$count],
                        'purchase_price' => $purchase_price,
                        'total_purchase_value' => $total_purchase_value,
                        'asker' => $asker,
                        'destination' => $destination,
                        'origin_sm_store_id' => $origin_sm_store_id,
                        'origin_bg_store_id' => $origin_bg_store_id,
                        'stockout_no' => $stockout_no,
                        'stockout_signature' => $stockout_signature,
                        'store_type' => $store_type,
                        'item_movement_type' => $item_movement_type,
                        'created_by' => $created_by,
                        'description' => $description,
                        'status' => 1,
                        'created_at' => \Carbon\Carbon::now()

                    );
                    $insert_data[] = $data;
                }else{
                    $cump = RawMaterial::where('id', $r_material_id[$count])->value('cump');
                    $purchase_price = RawMaterial::where('id', $r_material_id[$count])->value('cump');

                    $total_purchase_value = $quantity[$count] * $purchase_price;

                    $data = array(
                        'r_material_id' => $r_material_id[$count],
                        'date' => $date,
                        'quantity' => $quantity[$count],
                        //'unit' => $unit[$count],
                        'purchase_price' => $purchase_price,
                        'total_purchase_value' => $total_purchase_value,
                        'asker' => $asker,
                        'destination' => $destination,
                        'origin_sm_store_id' => $origin_sm_store_id,
                        'origin_bg_store_id' => $origin_bg_store_id,
                        'origin_extra_store_id' => $origin_extra_store_id,
                        'stockout_no' => $stockout_no,
                        'stockout_signature' => $stockout_signature,
                        'store_type' => $store_type,
                        'item_movement_type' => $item_movement_type,
                        'created_by' => $created_by,
                        'description' => $description,
                        'status' => 1,
                        'created_at' => \Carbon\Carbon::now()

                    );
                    $insert_data[] = $data;
                }

                
            }
            RawMaterialStockoutDetail::insert($insert_data);


            //create stockout
            $stockout = new RawMaterialStockout();
            $stockout->date = $date;
            $stockout->stockout_no = $stockout_no;
            $stockout->stockout_signature = $stockout_signature;
            $stockout->store_type = $store_type;
            $stockout->asker = $asker;
            $stockout->destination = $destination;
            $stockout->origin_sm_store_id = $origin_sm_store_id;
            $stockout->origin_bg_store_id = $origin_bg_store_id;
            $stockout->origin_extra_store_id = $origin_extra_store_id;
            $stockout->created_by = $created_by;
            $stockout->status = 1;
            $stockout->description = $description;
            $stockout->created_at = \Carbon\Carbon::now();
            $stockout->save();

            DB::commit();
            session()->flash('success', 'stockout has been created !!');
            return redirect()->route('admin.raw-material-stockouts.index');
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
    public function show($stockout_no)
    {
        //
        $stockout = RawMaterialStockout::where('stockout_no', $stockout_no)->first();
        $stockoutDetails = RawMaterialStockoutDetail::with(['rawMaterial.rawMaterialMeasurement', 'originSmallStore', 'originBigStore', 'originExtraStore'])
            ->where('stockout_no', $stockout_no)->get();
        
        // Calculate totals
        $totalQuantity = $stockoutDetails->sum(function($detail) {
            return $detail->quantity ?? 0;
        });
        $totalPurchaseAmount = $stockoutDetails->sum('total_purchase_value');
        
        return view('backend.pages.raw_material_stockout.show', compact('stockout', 'stockoutDetails', 'totalQuantity', 'totalPurchaseAmount'));
         
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_stockout.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any stockout !');
        }

        $stockout = RawMaterialStockout::where('stockout_no', $stockout_no)->first();
        
        if (is_null($stockout)) {
            session()->flash('error', 'Stockout not found !!');
            return redirect()->route('admin.raw-material-stockouts.index');
        }

        // Prevent editing when status is 4 (Approuvé/Approved)
        if ($stockout->status == 4) {
            session()->flash('error', 'Cannot edit stockout that has been approved (status = 4) !!');
            return redirect()->route('admin.raw-material-stockouts.index');
        }

        $stockoutDetails = RawMaterialStockoutDetail::with(['rawMaterial.rawMaterialMeasurement'])->where('stockout_no', $stockout_no)->get();
        $raw_materials = RawMaterial::orderBy('name','asc')->get();
        $raw_material_extra_big_stores = RawMaterialExtraBigStore::all();
        $raw_material_big_stores = RawMaterialBigStore::all();
        $raw_material_small_stores = RawMaterialSmallStore::all();
        
        return view('backend.pages.raw_material_stockout.edit', compact('stockout', 'stockoutDetails', 'raw_materials', 'raw_material_big_stores', 'raw_material_small_stores', 'raw_material_extra_big_stores'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_stockout.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any stockout !');
        }
                
        $stockout = RawMaterialStockout::where('stockout_no', $stockout_no)->first();
        
        if (is_null($stockout)) {
            session()->flash('error', 'Stockout not found !!');
            return redirect()->route('admin.raw-material-stockouts.index');
        }

        // Prevent updating when status is 4 (Approuvé/Approved)
        if ($stockout->status == 4) {
            session()->flash('error', 'Cannot update stockout that has been approved (status = 4) !!');
            return redirect()->route('admin.raw-material-stockouts.index');
        }

        $rules = array(
            'r_material_id.*'  => 'required',
            'date'  => 'required',
            'quantity.*'  => 'required',
            'asker'  => 'required',
            'destination'  => 'required',
            'item_movement_type' => 'nullable|in:SAU,SP,SV,SD,SC,SAJ',
            'description'  => 'required|max:490'
        );

        $error = Validator::make($request->all(),$rules);

        if($error->fails()){
            return response()->json([
                'error' => $error->errors()->all(),
            ]);
        }

        try {
            DB::beginTransaction();

            $r_material_id = $request->r_material_id;
            $date = $request->date;
            $asker = $request->asker;
            $destination = $request->destination;
            $origin_sm_store_id = $request->origin_sm_store_id;
            $description = $request->description; 
            $origin_bg_store_id = $request->origin_bg_store_id;
            $origin_extra_store_id = $request->origin_extra_store_id;
            $quantity = $request->quantity;
            $store_type = $request->store_type;
            $item_movement_type = SauStockoutJournalService::normalizeType($request->item_movement_type);

            if (!empty($origin_sm_store_id)) {
                $origin_sm_store_id = $origin_sm_store_id;
            }elseif(!empty($origin_bg_store_id)){
                $origin_bg_store_id = $origin_bg_store_id;
            }elseif(!empty($origin_extra_store_id)){
                $origin_extra_store_id = $origin_extra_store_id;
            }else{
                abort(403, 'Sorry !! You have to choose a store !');
            }

            // Delete existing details
            RawMaterialStockoutDetail::where('stockout_no', $stockout_no)->delete();

            // Insert new details
            for( $count = 0; $count < count($r_material_id); $count++ ){
                if ($store_type == 1) {
                    $purchase_price = RawMaterial::where('id', $r_material_id[$count])->value('cump');
                    $total_value = $quantity[$count] * $purchase_price;
                    $total_purchase_value = $quantity[$count] * $purchase_price;

                    $data = array(
                        'r_material_id' => $r_material_id[$count],
                        'date' => $date,
                        'quantity' => $quantity[$count],
                        'purchase_price' => $purchase_price,
                        'total_purchase_value' => $total_purchase_value,
                        'asker' => $asker,
                        'destination' => $destination,
                        'origin_sm_store_id' => $origin_sm_store_id,
                        'origin_bg_store_id' => $origin_bg_store_id,
                        'origin_extra_store_id' => $origin_extra_store_id,
                        'stockout_no' => $stockout_no,
                        'stockout_signature' => $stockout->stockout_signature,
                        'store_type' => $store_type,
                        'item_movement_type' => $item_movement_type,
                        'created_by' => $stockout->created_by,
                        'description' => $description,
                        'status' => $stockout->status,
                        'created_at' => $stockout->created_at,
                        'updated_at' => \Carbon\Carbon::now()
                    );
                    $insert_data[] = $data;
                }elseif($store_type == 2){
                    $cump = RawMaterial::where('id', $r_material_id[$count])->value('cump');
                    $raw_material = RawMaterial::where('id', $r_material_id[$count])->first();
                    $purchase_price = $cump / $raw_material->rawMaterialMeasurement->equivalent;
                    $total_purchase_value = $quantity[$count] * $purchase_price;

                    $data = array(
                        'r_material_id' => $r_material_id[$count],
                        'date' => $date,
                        'quantity' => $quantity[$count],
                        'purchase_price' => $purchase_price,
                        'total_purchase_value' => $total_purchase_value,
                        'asker' => $asker,
                        'destination' => $destination,
                        'origin_sm_store_id' => $origin_sm_store_id,
                        'origin_bg_store_id' => $origin_bg_store_id,
                        'stockout_no' => $stockout_no,
                        'stockout_signature' => $stockout->stockout_signature,
                        'store_type' => $store_type,
                        'item_movement_type' => $item_movement_type,
                        'created_by' => $stockout->created_by,
                        'description' => $description,
                        'status' => $stockout->status,
                        'created_at' => $stockout->created_at,
                        'updated_at' => \Carbon\Carbon::now()
                    );
                    $insert_data[] = $data;
                }else{
                    $cump = RawMaterial::where('id', $r_material_id[$count])->value('cump');
                    $purchase_price = RawMaterial::where('id', $r_material_id[$count])->value('cump');
                    $total_purchase_value = $quantity[$count] * $purchase_price;

                    $data = array(
                        'r_material_id' => $r_material_id[$count],
                        'date' => $date,
                        'quantity' => $quantity[$count],
                        'purchase_price' => $purchase_price,
                        'total_purchase_value' => $total_purchase_value,
                        'asker' => $asker,
                        'destination' => $destination,
                        'origin_sm_store_id' => $origin_sm_store_id,
                        'origin_bg_store_id' => $origin_bg_store_id,
                        'origin_extra_store_id' => $origin_extra_store_id,
                        'stockout_no' => $stockout_no,
                        'stockout_signature' => $stockout->stockout_signature,
                        'store_type' => $store_type,
                        'item_movement_type' => $item_movement_type,
                        'created_by' => $stockout->created_by,
                        'description' => $description,
                        'status' => $stockout->status,
                        'created_at' => $stockout->created_at,
                        'updated_at' => \Carbon\Carbon::now()
                    );
                    $insert_data[] = $data;
                }
            }
            RawMaterialStockoutDetail::insert($insert_data);

            // Update stockout
            $stockout->date = $date;
            $stockout->store_type = $store_type;
            $stockout->asker = $asker;
            $stockout->destination = $destination;
            $stockout->origin_sm_store_id = $origin_sm_store_id;
            $stockout->origin_bg_store_id = $origin_bg_store_id;
            $stockout->origin_extra_store_id = $origin_extra_store_id;
            $stockout->item_movement_type = $item_movement_type;
            $stockout->description = $description;
            $stockout->updated_by = $this->user->name;
            $stockout->updated_at = \Carbon\Carbon::now();
            $stockout->save();

            DB::commit();
            session()->flash('success', 'stockout has been updated !!');
            return redirect()->route('admin.raw-material-stockouts.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bonSortie($stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_stockout.create')) {
            abort(403, 'Sorry !! You are Unauthorized to print handover document!');
        }
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $code = RawMaterialStockout::where('stockout_no', $stockout_no)->value('stockout_no');
        $datas = RawMaterialStockoutDetail::where('stockout_no', $stockout_no)->get();
        $description = RawMaterialStockout::where('stockout_no', $stockout_no)->value('description');
        $stockout_signature = RawMaterialStockout::where('stockout_no', $stockout_no)->value('stockout_signature');
        $date = RawMaterialStockout::where('stockout_no', $stockout_no)->value('date');
        $totalValue = DB::table('raw_material_stockout_details')
            ->where('stockout_no', '=', $stockout_no)
            ->sum('total_purchase_value');
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.raw_material_stockout', compact('datas','code','totalValue','description','setting','date','stockout_signature','stockout_no'), [], 'UTF-8');

        Storage::put('public/pdf/raw_material_stockout/'.$stockout_no.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('BON_SORTIE_'.$stockout_no.'.pdf');
        
    }

    public function print($stockout_no) { return $this->bonSortie($stockout_no); }

    public function validateStockout($stockout_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_stockout.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any stockout !');
        }

        $stockout = RawMaterialStockout::where('stockout_no', $stockout_no)->first();
        if (!$stockout) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentValidate($stockout)) {
            return $response;
        }

        try{DB::beginTransaction();
            RawMaterialStockout::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            RawMaterialStockoutDetail::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'stockout has been validated !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
    }

    public function reject($stockout_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_stockout.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any stockout !');
        }

        try{DB::beginTransaction();

        RawMaterialStockout::where('stockout_no', '=', $stockout_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);
        RawMaterialStockoutDetail::where('stockout_no', '=', $stockout_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Stockout has been rejected !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
    }

    public function reset($stockout_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_stockout.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any stockout !');
        }

        RawMaterialStockout::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
        RawMaterialStockoutDetail::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);

        session()->flash('success', 'Stockout has been reseted !!');
        return back();
    }

    public function confirm($stockout_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_stockout.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any stockout !');
        }

        $stockout = RawMaterialStockout::where('stockout_no', $stockout_no)->first();
        if (!$stockout) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentConfirm($stockout)) {
            return $response;
        }

        try{DB::beginTransaction();

        RawMaterialStockout::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            RawMaterialStockoutDetail::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Stockout has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
    }

    public function approuve($stockout_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_stockout.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any stockout !');
        }

        $stockoutHeader = RawMaterialStockout::where('stockout_no', $stockout_no)->first();
        if (!$stockoutHeader) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentApprove($stockoutHeader)) {
            return $response;
        }

        $sauService = app(SauStockoutJournalService::class);
        $isSau = SauStockoutJournalService::isSau($stockoutHeader->item_movement_type);
        try {
            $sauService->assertDirectStockoutType($stockoutHeader->item_movement_type);
            if ($isSau) {
                foreach (RawMaterialStockoutDetail::where('stockout_no', $stockout_no)->get() as $detail) {
                    $sauService->resolveDebitAccountId(RawMaterial::find($detail->r_material_id), '#' . $detail->r_material_id);
                }
            }
        } catch (SauStockoutException $ex) {
            session()->flash('error', $ex->getMessage());
            return back();
        }

        try{DB::beginTransaction();

        $reportBigStoreData = [];
        $reportSmallStoreData = [];
        $reportExtraBigStoreData = [];
        $flag = 0;

        $datas = RawMaterialStockoutDetail::where('stockout_no', $stockout_no)->get();
        $accountSetting = AccountSetting::where('active', 1)->first();
        $hasCounterAccount = $isSau || ($accountSetting && ($accountSetting->f_saj_account_id || $accountSetting->f_sp_account_id || $accountSetting->f_sv_account_id || $accountSetting->f_sc_account_id));
        if (!$hasCounterAccount) {
            DB::rollback();
            session()->flash('error', __('messages.approval_accounts_required'));
            return back();
        }
        foreach ($datas as $d) {
            $storeAccountId = null;
            if (!empty($d->origin_bg_store_id)) {
                $storeAccountId = RawMaterialBigStore::where('id', $d->origin_bg_store_id)->value('default_account_id');
            } elseif (!empty($d->origin_sm_store_id)) {
                $storeAccountId = RawMaterialSmallStore::where('id', $d->origin_sm_store_id)->value('default_account_id');
            } elseif (!empty($d->origin_extra_store_id)) {
                $storeAccountId = RawMaterialExtraBigStore::where('id', $d->origin_extra_store_id)->value('default_account_id');
            }
            if (!$storeAccountId) {
                DB::rollback();
                session()->flash('error', __('messages.approval_accounts_required'));
                return back();
            }
        }

        $stockout = RawMaterialStockoutDetail::where('stockout_no', $stockout_no)->first();

        foreach($datas as $data){

            $code_store_origin = RawMaterialBigStore::where('id',$data->origin_bg_store_id)->value('code');

            $cump = RawMaterial::where('id', $data->r_material_id)->value('cump');
            $purchase_price = RawMaterial::where('id', $data->r_material_id)->value('purchase_price');
            if ($cump <= 0) {
                $cump = $purchase_price;
            }else{
                $cump = $cump;
            }

            if ($data->store_type === '1') {

                $valeurStockInitial = RawMaterialBigStoreDetail::where('code',$code_store_origin)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('total_cump_value');
                $quantityStockInitial = RawMaterialBigStoreDetail::where('code',$code_store_origin)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('quantity');
                $quantityRestantBigStore = $quantityStockInitial - $data->quantity;

                $reportBigStore = array(
                    'r_material_id' => $data->r_material_id,
                    'quantity_stock_initial' => $quantityStockInitial,
                    'value_stock_initial' => $valeurStockInitial,
                    'code_store' => $code_store_origin,
                    'date' => $data->date,
                    'cump' => $cump,
                    'quantity_stockout' => $data->quantity,
                    'value_stockout' => $cump * $data->quantity,
                    'quantity_stock_final' => $quantityStockInitial - $data->quantity,
                    'value_stock_final' => $cump * ($quantityStockInitial - $data->quantity),
                    'created_by' => $this->user->name,
                    'type_transaction' => $data->item_movement_type,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportBigStoreData[] = $reportBigStore;

                    $bigStore = array(
                        'r_material_id' => $data->r_material_id,
                        'quantity' => $quantityRestantBigStore,
                        'total_selling_value' => $quantityRestantBigStore * $data->purchase_price,
                        'total_purchase_value' => $quantityRestantBigStore * $data->purchase_price,
                        'total_cump_value' => $quantityRestantBigStore * $data->purchase_price,
                        'created_by' => $this->user->name,
                        'verified' => true,
                        'created_at' => \Carbon\Carbon::now()
                    );


                    if ($data->quantity > $quantityStockInitial) {
                        DB::rollback();
                        session()->flash('error', $this->user->name.' ,Why do you want to stockout a quantity you do not have in your store?please rewrite a valid quantity!');
                        return back();
                    }

                    RawMaterialBigStoreDetail::where('code',$code_store_origin)->where('r_material_id',$data->r_material_id)
                        ->update($bigStore);

                    $flag = 0;
            }elseif ($data->store_type === '2'){
                $code_store_origin = RawMaterialSmallStore::where('id',$data->origin_sm_store_id)->value('code');

                $valeurStockInitial = RawMaterialSmallStoreDetail::where('code',$code_store_origin)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('total_cump_value');
                $quantityStockInitial = RawMaterialSmallStoreDetail::where('code',$code_store_origin)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('quantity');
                $quantityRestantSmallStore = $quantityStockInitial - $data->quantity;

                $cump = $cump/$data->rawMaterial->rawMaterialMeasurement->equivalent;
                $reportSmallStore = array(
                    'r_material_id' => $data->r_material_id,
                    'quantity_stock_initial' => $quantityStockInitial,
                    'value_stock_initial' => $valeurStockInitial,
                    'code_store' => $code_store_origin,
                    'date' => $data->date,
                    'cump' => $cump,
                    'quantity_stockout' => $data->quantity,
                    'value_stockout' => $cump * $data->quantity,
                    'quantity_stock_final' => $quantityStockInitial - $data->quantity,
                    'value_stock_final' => $cump * ($quantityStockInitial - $data->quantity),
                    'created_by' => $this->user->name,
                    'type_transaction' => $data->item_movement_type,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'created_at' => \Carbon\Carbon::now()
                );

                $reportSmallStoreData[] = $reportSmallStore;

                    $smallStore = array(
                        'r_material_id' => $data->r_material_id,
                        'quantity' => $quantityRestantSmallStore,
                        'total_purchase_value' => $quantityRestantSmallStore * $cump,
                        'total_cump_value' => $quantityRestantSmallStore * $cump,
                        'created_by' => $this->user->name,
                        'verified' => true,
                        'created_at' => \Carbon\Carbon::now()
                    );


                    if ($data->quantity > $quantityStockInitial) {
                        DB::rollback();
                        session()->flash('error', $this->user->name.' ,Why do you want to stockout a quantity you do not have in your store?please rewrite a valid quantity!');
                        return back();
                    }

                    RawMaterialSmallStoreDetail::where('code',$code_store_origin)->where('r_material_id',$data->r_material_id)
                        ->update($smallStore);

                    $flag = 0;
            }else{
                $code_store_origin = RawMaterialExtraBigStore::where('id',$data->origin_extra_store_id)->value('code');

                $valeurStockInitial = RawMaterialExtraBigStoreDetail::where('code',$code_store_origin)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('total_selling_value');
                $quantityStockInitial = RawMaterialExtraBigStoreDetail::where('code',$code_store_origin)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('quantity');
                $quantityRestantSmallStore = $quantityStockInitial - $data->quantity;

                $reportextraBigStore = array(
                    'r_material_id' => $data->r_material_id,
                    'quantity_stock_initial' => $quantityStockInitial,
                    'value_stock_initial' => $valeurStockInitial,
                    'code_store' => $code_store_origin,
                    'date' => $data->date,
                    'quantity_stockout' => $data->quantity,
                    'value_stockout' => $cump * $data->quantity,
                    'quantity_stock_final' => $quantityStockInitial - $data->quantity,
                    'value_stock_final' => $cump * ($quantityStockInitial - $data->quantity),
                    'created_by' => $this->user->name,
                    'type_transaction' => $data->item_movement_type,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportExtraBigStoreData[] = $reportextraBigStore;

                    $extraBigStore = array(
                        'r_material_id' => $data->r_material_id,
                        'quantity' => $quantityRestantSmallStore,
                        'total_selling_value' => $quantityRestantSmallStore * $data->selling_price,
                        'total_purchase_value' => $quantityRestantSmallStore * $data->selling_price,
                        'total_cump_value' => $quantityRestantSmallStore * $data->selling_price,
                        'created_by' => $this->user->name,
                        'verified' => false,
                        'created_at' => \Carbon\Carbon::now()
                    );


                    if ($data->quantity > $quantityStockInitial) {
                        DB::rollback();
                        session()->flash('error', $this->user->name.' ,Why do you want to stockout a quantity you do not have in your store?please rewrite a valid quantity!');
                        return back();
                    }

                    RawMaterialExtraBigStoreDetail::where('code',$code_store_origin)->where('r_material_id',$data->r_material_id)
                        ->update($extraBigStore);

                    $flag = 0;
            }
                
  
        }

        $cmp = DB::table('raw_material_stockout_details')
            ->where('stockout_no', '=', $stockout_no)
            ->sum('total_purchase_value');

        if($stockout->store_type == 1 && $flag != 1){
            RawMaterialBigReport::insert($reportBigStoreData);

            if (SauStockoutJournalService::isSau($data->item_movement_type)) {
                $id_acc_stock = RawMaterialBigStore::where('id',$data->origin_bg_store_id)->value('default_account_id');
                $this->postRawMaterialSauStockout($sauService, $id_acc_stock, $reportBigStoreData, $data);
            }elseif ($data->item_movement_type == "SP") {
                //start credit operations
                $id_acc_stock = RawMaterialBigStore::where('id',$data->origin_bg_store_id)->value('default_account_id');

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);


                $journalEntryCreditdata = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $data->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $f_sp_account_id = AccountSetting::where('active',1)->value('f_sp_account_id');

                $spLedgerState = $this->updateLedgerTotals($f_sp_account_id, 'debit', $cmp);
            
                $journalEntryDebitspdataAcc2 = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $f_sp_account_id,
                    'libelle' => $data->description,
                    'debit' => $cmp,
                    'balance' => $spLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_sp_data_acc2[] = $journalEntryDebitspdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_sp_data_acc2);
            
                //end debit operation
            }elseif ($data->item_movement_type == "SV") {
                //start credit operations
                $id_acc_stock = RawMaterialBigStore::where('id',$data->origin_bg_store_id)->value('default_account_id');

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);


                $journalEntryCreditdata = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $data->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $f_sv_account_id = AccountSetting::where('active',1)->value('f_sv_account_id');

                $svLedgerState = $this->updateLedgerTotals($f_sv_account_id, 'debit', $cmp);
            
                $journalEntryDebitsvdataAcc2 = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $f_sv_account_id,
                    'libelle' => $data->description,
                    'debit' => $cmp,
                    'balance' => $svLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_sv_data_acc2[] = $journalEntryDebitsvdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_sv_data_acc2);
            
                //end debit operation
            }elseif ($data->item_movement_type == "SC") {
                //start credit operations
                $id_acc_stock = RawMaterialBigStore::where('id',$data->origin_bg_store_id)->value('default_account_id');

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);


                $journalEntryCreditdata = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $data->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $f_sc_account_id = AccountSetting::where('active',1)->value('f_sc_account_id');

                $scLedgerState = $this->updateLedgerTotals($f_sc_account_id, 'debit', $cmp);
            
                $journalEntryDebitscdataAcc2 = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $f_sc_account_id,
                    'libelle' => $data->description,
                    'debit' => $cmp,
                    'balance' => $scLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_sc_data_acc2[] = $journalEntryDebitscdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_sc_data_acc2);
            
                //end debit operation
            }else{
                //start credit operations
                $id_acc_stock = RawMaterialBigStore::where('id',$data->origin_bg_store_id)->value('default_account_id');

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);

                $journalEntryCreditdata = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $data->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $f_saj_account_id = AccountSetting::where('active',1)->value('f_saj_account_id');

                $sajLedgerState = $this->updateLedgerTotals($f_saj_account_id, 'debit', $cmp);
            
                $journalEntryDebitsajdataAcc2 = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $f_saj_account_id,
                    'libelle' => $data->description,
                    'debit' => $cmp,
                    'balance' => $sajLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_saj_data_acc2[] = $journalEntryDebitsajdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_saj_data_acc2);
            
                //end debit operation
            }
        }elseif ($stockout->store_type == 2 && $flag != 1) {
            RawMaterialSmallReport::insert($reportSmallStoreData);

            if (SauStockoutJournalService::isSau($data->item_movement_type)) {
                $id_acc_stock = RawMaterialSmallStore::where('id',$data->origin_sm_store_id)->value('default_account_id');
                $this->postRawMaterialSauStockout($sauService, $id_acc_stock, $reportSmallStoreData, $data);
            }elseif ($data->item_movement_type == "SP") {
                //start credit operations
                $id_acc_stock = RawMaterialSmallStore::where('id',$data->origin_sm_store_id)->value('default_account_id');

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);


                $journalEntryCreditdata = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $data->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $f_sp_account_id = AccountSetting::where('active',1)->value('f_sp_account_id');

                $spLedgerState = $this->updateLedgerTotals($f_sp_account_id, 'debit', $cmp);
            
                $journalEntryDebitspdataAcc2 = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $f_sp_account_id,
                    'libelle' => $data->description,
                    'debit' => $cmp,
                    'balance' => $spLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_sp_data_acc2[] = $journalEntryDebitspdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_sp_data_acc2);
            
                //end debit operation
            }elseif ($data->item_movement_type == "SV") {
                //start credit operations
                $id_acc_stock = RawMaterialSmallStore::where('id',$data->origin_sm_store_id)->value('default_account_id');

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);


                $journalEntryCreditdata = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $data->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $f_sv_account_id = AccountSetting::where('active',1)->value('f_sv_account_id');

                $svLedgerState = $this->updateLedgerTotals($f_sv_account_id, 'debit', $cmp);
            
                $journalEntryDebitsvdataAcc2 = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $f_sv_account_id,
                    'libelle' => $data->description,
                    'debit' => $cmp,
                    'balance' => $svLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_sv_data_acc2[] = $journalEntryDebitsvdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_sv_data_acc2);
            
                //end debit operation
            }elseif ($data->item_movement_type == "SC") {
                //start credit operations
                $id_acc_stock = RawMaterialSmallStore::where('id',$data->origin_sm_store_id)->value('default_account_id');

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);


                $journalEntryCreditdata = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $data->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $f_sc_account_id = AccountSetting::where('active',1)->value('f_sc_account_id');

                $scLedgerState = $this->updateLedgerTotals($f_sc_account_id, 'debit', $cmp);
            
                $journalEntryDebitscdataAcc2 = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $f_sc_account_id,
                    'libelle' => $data->description,
                    'debit' => $cmp,
                    'balance' => $scLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_sc_data_acc2[] = $journalEntryDebitscdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_sc_data_acc2);
            
                //end debit operation
            }else{
                //start credit operations
                $id_acc_stock = RawMaterialSmallStore::where('id',$data->origin_sm_store_id)->value('default_account_id');

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);

                $journalEntryCreditdata = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $data->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $f_saj_account_id = AccountSetting::where('active',1)->value('f_saj_account_id');

                $sajLedgerState = $this->updateLedgerTotals($f_saj_account_id, 'debit', $cmp);
            
                $journalEntryDebitsajdataAcc2 = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $f_saj_account_id,
                    'libelle' => $data->description,
                    'debit' => $cmp,
                    'balance' => $sajLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_saj_data_acc2[] = $journalEntryDebitsajdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_saj_data_acc2);
            
                //end debit operation
            }
        }else{
            RawMaterialExtraBigReport::insert($reportExtraBigStoreData);

            if (SauStockoutJournalService::isSau($data->item_movement_type)) {
                $id_acc_stock = RawMaterialExtraBigStore::where('id',$data->origin_extra_store_id)->value('default_account_id');
                $this->postRawMaterialSauStockout($sauService, $id_acc_stock, $reportExtraBigStoreData, $data);
            }elseif ($data->item_movement_type == "SP") {
                //start credit operations
                $id_acc_stock = RawMaterialExtraBigStore::where('id',$data->origin_extra_store_id)->value('default_account_id');

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);


                $journalEntryCreditdata = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $data->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $f_sp_account_id = AccountSetting::where('active',1)->value('f_sp_account_id');

                $spLedgerState = $this->updateLedgerTotals($f_sp_account_id, 'debit', $cmp);
            
                $journalEntryDebitspdataAcc2 = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $f_sp_account_id,
                    'libelle' => $data->description,
                    'debit' => $cmp,
                    'balance' => $spLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_sp_data_acc2[] = $journalEntryDebitspdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_sp_data_acc2);
            
                //end debit operation
            }elseif ($data->item_movement_type == "SV") {
                //start credit operations
                $id_acc_stock = RawMaterialExtraBigStore::where('id',$data->origin_extra_store_id)->value('default_account_id');

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);


                $journalEntryCreditdata = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $data->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $f_sv_account_id = AccountSetting::where('active',1)->value('f_sv_account_id');

                $svLedgerState = $this->updateLedgerTotals($f_sv_account_id, 'debit', $cmp);
            
                $journalEntryDebitsvdataAcc2 = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $f_sv_account_id,
                    'libelle' => $data->description,
                    'debit' => $cmp,
                    'balance' => $svLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_sv_data_acc2[] = $journalEntryDebitsvdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_sv_data_acc2);
            
                //end debit operation
            }elseif ($data->item_movement_type == "SC") {
                //start credit operations
                $id_acc_stock = RawMaterialExtraBigStore::where('id',$data->origin_extra_store_id)->value('default_account_id');

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);


                $journalEntryCreditdata = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $data->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $f_sc_account_id = AccountSetting::where('active',1)->value('f_sc_account_id');

                $scLedgerState = $this->updateLedgerTotals($f_sc_account_id, 'debit', $cmp);
            
                $journalEntryDebitscdataAcc2 = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $f_sc_account_id,
                    'libelle' => $data->description,
                    'debit' => $cmp,
                    'balance' => $scLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_sc_data_acc2[] = $journalEntryDebitscdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_sc_data_acc2);
            
                //end debit operation
            }else{
                //start credit operations
                $id_acc_stock = RawMaterialExtraBigStore::where('id',$data->origin_extra_store_id)->value('default_account_id');

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);

                $journalEntryCreditdata = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $data->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $f_saj_account_id = AccountSetting::where('active',1)->value('f_saj_account_id');

                $sajLedgerState = $this->updateLedgerTotals($f_saj_account_id, 'debit', $cmp);
            
                $journalEntryDebitsajdataAcc2 = array(
                    'date' => $data->date,
                    'document_no' => $data->stockout_no,
                    'description' => $data->description,
                    'account_id' => $f_saj_account_id,
                    'libelle' => $data->description,
                    'debit' => $cmp,
                    'balance' => $sajLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_saj_data_acc2[] = $journalEntryDebitsajdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_saj_data_acc2);
            
                //end debit operation
            }
        }

        RawMaterialBigStoreDetail::where('r_material_id','!=','')->update(['verified' => false]);
        RawMaterialSmallStoreDetail::where('r_material_id','!=','')->update(['verified' => false]);
        RawMaterialExtraBigStoreDetail::where('r_material_id','!=','')->update(['verified' => false]);

        RawMaterialStockout::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 4,'approuved_by' => $this->user->name]);
        RawMaterialStockoutDetail::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 4,'approuved_by' => $this->user->name]);

        DB::commit();
        session()->flash('success', 'Stockout has been done successfuly !, from '.$code_store_origin);
        return back();
        } catch (SauStockoutException $e) {
            DB::rollback();
            session()->flash('error', $e->getMessage());
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
    }

    /**
     * Sortie autre : crédit stock magasin / débit compte de chaque fiche matière (valeur CUMP).
     *
     * @throws SauStockoutException
     */
    private function postRawMaterialSauStockout(SauStockoutJournalService $sauService, $stockAccountId, array $reportRows, $data): void
    {
        $lines = [];
        foreach ($reportRows as $row) {
            $rawMaterial = RawMaterial::find($row['r_material_id']);
            $lines[] = [
                'item' => $rawMaterial,
                'amount' => (float) ($row['value_stockout'] ?? 0),
                'label' => $rawMaterial ? $rawMaterial->name : ('#' . $row['r_material_id']),
            ];
        }

        $sauService->post($stockAccountId ? (int) $stockAccountId : null, $sauService->resolveLines($lines), [
            'date' => $data->date,
            'document_no' => $data->stockout_no,
            'description' => $data->description,
        ]);
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $stockout_no
     * @return \Illuminate\Http\Response
     */
    public function destroy($stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_stockout.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any stockout !');
        }

        $stockout = RawMaterialStockout::where('stockout_no',$stockout_no)->first();
        
        if (is_null($stockout)) {
            session()->flash('error', 'Stockout not found !!');
            return back();
        }

        // Prevent deletion when status is 4 (Approuvé/Approved)
        if ($stockout->status == 4) {
            session()->flash('error', 'Cannot delete stockout that has been approved (status = 4) !!');
            return back();
        }

        if (!is_null($stockout)) {
            $stockout->delete();
            RawMaterialStockoutDetail::where('stockout_no',$stockout_no)->delete();
        }

        session()->flash('success', 'Stockout has been deleted !!');
        return back();
    }

}
