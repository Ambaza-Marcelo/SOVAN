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
use App\Models\Material;
use App\Models\MaterialStockout;
use App\Models\MaterialStockoutDetail;
use App\Models\MaterialBigStoreDetail;
use App\Models\MaterialSmallStoreDetail;
use App\Models\MaterialBigStore;
use App\Models\MaterialSmallStore;
use App\Models\MaterialBigReport;
use App\Models\MaterialSmallReport;
use App\Models\MaterialExtraBigStoreDetail;
use App\Models\MaterialExtraBigStore;
use App\Models\MaterialExtraBigReport;
use App\Models\Accounting\AccountCostCenter;
use App\Models\Accounting\AccountJournalEntry;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountSetting;
use App\Services\Material\MaterialFlowOrchestrator;
use App\Services\Stock\SauStockoutJournalService;
use Carbon\Carbon;
use PDF;
use Validator;
use Excel;
use Mail;

class MaterialStockoutController extends Controller
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
        if (is_null($this->user) || !$this->user->can('material_stockout.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any stockout !');
        }

        $query = MaterialStockout::query();
        
        // Si des dates sont fournies, filtrer par période
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('created_at', [$dateDebut, $dateFin]);
        } else {
            // Par défaut, afficher les 100 lignes les plus récentes
            $query->take(100);
        }
        
        $stockouts = $query->orderBy('id','desc')->get();
        return view('backend.pages.material_stockout.index', compact('stockouts'));
    }

    public function choose()
    {
        if (is_null($this->user) || !$this->user->can('material_stockout.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any stockout !');
        }

        return view('backend.pages.material_stockout.choose');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('material_stockout.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockout !');
        }

        $materials  = Material::orderBy('name','asc')->get();
        $material_origin_stores = MaterialBigStore::all();
        return view('backend.pages.material_stockout.create', compact('materials','material_origin_stores'));
    }

    public function createFromBig()
    {
        if (is_null($this->user) || !$this->user->can('material_stockout.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockout !');
        }

        $materials  = Material::orderBy('name','asc')->get();
        $material_origin_stores = MaterialExtraBigStore::all();
        return view('backend.pages.material_stockout.create_from_big', compact('materials','material_origin_stores'));
    }

    public function createFromSmall()
    {
        if (is_null($this->user) || !$this->user->can('material_stockout.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockout !');
        }

        $materials  = Material::orderBy('name','asc')->get();
        $material_origin_stores = MaterialSmallStore::all();
        return view('backend.pages.material_stockout.create_from_small', compact('materials','material_origin_stores'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('material_stockout.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockout !');
        }

        $rules = array(
                'material_id.*'  => 'required',
                'date'  => 'required',
                'quantity.*'  => 'required',
                'asker'  => 'required',
                'origin_bg_store_id'  => 'required',
                'destination'  => 'required',
                'item_movement_type'  => 'nullable|in:SAU,SP,SV,SD,SC,SAJ',
                'description'  => 'required|max:490'
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $material_id = $request->material_id;
            $date = $request->date;
            $asker = $request->asker;
            $destination = $request->destination;
            $description =$request->description; 
            $origin_bg_store_id = $request->origin_bg_store_id;
            $item_movement_type = SauStockoutJournalService::normalizeType($request->item_movement_type);
            $quantity = $request->quantity;
            $store_type = $request->store_type;
            

            $latest = MaterialStockout::orderBy('id','desc')->first();
            if ($latest) {
               $stockout_no = 'M-BS' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $stockout_no = 'M-BS' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $created_by = $this->user->name;

            $stockout_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$stockout_no;


            for( $count = 0; $count < count($material_id); $count++ ){

                $selling_price = Material::where('id', $material_id[$count])->value('selling_price');
                $purchase_price = Material::where('id', $material_id[$count])->value('purchase_price');

                $total_value = $quantity[$count] * $purchase_price;
                $total_purchase_value = $quantity[$count] * $purchase_price;
                $total_selling_value = $quantity[$count] * $selling_price;

                $data = array(
                    'material_id' => $material_id[$count],
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'purchase_price' => $purchase_price,
                    'selling_price' => $selling_price,
                    'total_purchase_value' => $total_purchase_value,
                    'total_selling_value' => $total_selling_value,
                    'asker' => $asker,
                    'destination' => $destination,
                    'origin_bg_store_id' => $origin_bg_store_id,
                    'item_movement_type' => $item_movement_type,
                    'stockout_no' => $stockout_no,
                    'stockout_signature' => $stockout_signature,
                    'store_type' => $store_type,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }
            MaterialStockoutDetail::insert($insert_data);


            //create stockout
            $stockout = new MaterialStockout();
            $stockout->date = $date;
            $stockout->stockout_no = $stockout_no;
            $stockout->stockout_signature = $stockout_signature;
            $stockout->store_type = $store_type;
            $stockout->asker = $asker;
            $stockout->destination = $destination;
            $stockout->origin_bg_store_id = $origin_bg_store_id;
            $stockout->item_movement_type = $item_movement_type;
            $stockout->created_by = $created_by;
            $stockout->status = 1;
            $stockout->description = $description;
            $stockout->created_at = \Carbon\Carbon::now();
            $stockout->save();

            DB::commit();
            session()->flash('success', 'stockout has been created !!');
            return redirect()->route('admin.material-stockouts.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
            
    }

    public function storeFromBig(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('material_stockout.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockout !');
        }

        $rules = array(
                'material_id.*'  => 'required',
                'date'  => 'required',
                'quantity.*'  => 'required',
                'asker'  => 'required',
                'origin_extra_store_id'  => 'required',
                'destination'  => 'required',
                'item_movement_type'  => 'nullable|in:SAU,SP,SV,SD,SC,SAJ',
                'description'  => 'required'
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $material_id = $request->material_id;
            $date = $request->date;
            $asker = $request->asker;
            $destination = $request->destination;
            $description =$request->description; 
            $origin_extra_store_id = $request->origin_extra_store_id;
            $item_movement_type = SauStockoutJournalService::normalizeType($request->item_movement_type);
            $quantity = $request->quantity;
            $store_type = $request->store_type;
            

            $latest = MaterialStockout::latest()->first();
            if ($latest) {
               $stockout_no = 'M-BS' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $stockout_no = 'M-BS' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $created_by = $this->user->name;

            $stockout_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$stockout_no;


            for( $count = 0; $count < count($material_id); $count++ ){

                $selling_price = Material::where('id', $material_id[$count])->value('selling_price');
                $purchase_price = Material::where('id', $material_id[$count])->value('purchase_price');

                $total_value = $quantity[$count] * $purchase_price;
                $total_purchase_value = $quantity[$count] * $purchase_price;
                $total_selling_value = $quantity[$count] * $selling_price;

                $data = array(
                    'material_id' => $material_id[$count],
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'purchase_price' => $purchase_price,
                    'selling_price' => $selling_price,
                    'total_purchase_value' => $total_purchase_value,
                    'total_selling_value' => $total_selling_value,
                    'asker' => $asker,
                    'destination' => $destination,
                    'origin_extra_store_id' => $origin_extra_store_id,
                    'item_movement_type' => $item_movement_type,
                    'stockout_no' => $stockout_no,
                    'stockout_signature' => $stockout_signature,
                    'store_type' => $store_type,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }
            MaterialStockoutDetail::insert($insert_data);


            //create stockout
            $stockout = new MaterialStockout();
            $stockout->date = $date;
            $stockout->stockout_no = $stockout_no;
            $stockout->stockout_signature = $stockout_signature;
            $stockout->store_type = $store_type;
            $stockout->asker = $asker;
            $stockout->destination = $destination;
            $stockout->origin_extra_store_id = $origin_extra_store_id;
            $stockout->item_movement_type = $item_movement_type;
            $stockout->created_by = $created_by;
            $stockout->status = 1;
            $stockout->description = $description;
            $stockout->created_at = \Carbon\Carbon::now();
            $stockout->save();

            DB::commit();
            session()->flash('success', 'stockout has been created !!');
            return redirect()->route('admin.material-stockouts.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
            
    }


    public function storeFromSmall(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('material_stockout.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockout !');
        }

        $rules = array(
                'material_id.*'  => 'required',
                'date'  => 'required',
                'quantity.*'  => 'required',
                'asker'  => 'required',
                'origin_sm_store_id'  => 'required',
                'destination'  => 'required',
                'item_movement_type'  => 'nullable|in:SAU,SP,SV,SD,SC,SAJ',
                'description'  => 'required'
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $material_id = $request->material_id;
            $date = $request->date;
            $asker = $request->asker;
            $destination = $request->destination;
            $origin_sm_store_id = $request->origin_sm_store_id;
            $description =$request->description; 
            $item_movement_type = SauStockoutJournalService::normalizeType($request->item_movement_type);
            $quantity = $request->quantity;
            $store_type = $request->store_type;
            

            $latest = MaterialStockout::latest()->first();
            if ($latest) {
               $stockout_no = 'M-BS' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $stockout_no = 'M-BS' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $created_by = $this->user->name;

            $stockout_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$stockout_no;


            for( $count = 0; $count < count($material_id); $count++ ){

                $selling_price = Material::where('id', $material_id[$count])->value('selling_price');
                $purchase_price = Material::where('id', $material_id[$count])->value('purchase_price');

                $total_value = $quantity[$count] * $purchase_price;
                $total_purchase_value = $quantity[$count] * $purchase_price;
                $total_selling_value = $quantity[$count] * $purchase_price;

                $data = array(
                    'material_id' => $material_id[$count],
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'purchase_price' => $purchase_price,
                    'selling_price' => $selling_price,
                    'total_purchase_value' => $total_purchase_value,
                    'total_selling_value' => $total_selling_value,
                    'asker' => $asker,
                    'destination' => $destination,
                    'origin_sm_store_id' => $origin_sm_store_id,
                    'item_movement_type' => $item_movement_type,
                    'stockout_no' => $stockout_no,
                    'stockout_signature' => $stockout_signature,
                    'store_type' => $store_type,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }
            MaterialStockoutDetail::insert($insert_data);


            //create stockout
            $stockout = new MaterialStockout();
            $stockout->date = $date;
            $stockout->stockout_no = $stockout_no;
            $stockout->stockout_signature = $stockout_signature;
            $stockout->store_type = $store_type;
            $stockout->asker = $asker;
            $stockout->destination = $destination;
            $stockout->origin_sm_store_id = $origin_sm_store_id;
            $stockout->item_movement_type = $item_movement_type;
            $stockout->created_by = $created_by;
            $stockout->status = 1;
            $stockout->description = $description;
            $stockout->created_at = \Carbon\Carbon::now();
            $stockout->save();

            DB::commit();
            session()->flash('success', 'stockout has been created !!');
            return redirect()->route('admin.material-stockouts.index');
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
        $stockout = MaterialStockout::where('stockout_no', $stockout_no)->first();
        $stockoutDetails = MaterialStockoutDetail::with(['material.materialMeasurement', 'originSmallStore', 'originBigStore', 'originExtraStore'])
            ->where('stockout_no', $stockout_no)->get();
        
        // Calculate totals
        $totalQuantity = $stockoutDetails->sum('quantity');
        $totalPurchaseAmount = $stockoutDetails->sum('total_purchase_value');
        
        return view('backend.pages.material_stockout.show', compact('stockout', 'stockoutDetails', 'totalQuantity', 'totalPurchaseAmount'));
         
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('material_stockout.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any stockout !');
        }

        $stockout = MaterialStockout::where('stockout_no', $stockout_no)->first();
        
        if (is_null($stockout)) {
            session()->flash('error', 'Stockout not found !!');
            return redirect()->route('admin.material-stockouts.index');
        }

        // Prevent editing when status is 4 (Approuvé/Approved)
        if ($stockout->status == 4) {
            session()->flash('error', 'Cannot edit stockout that has been approved (status = 4) !!');
            return redirect()->route('admin.material-stockouts.index');
        }

        $stockoutDetails = MaterialStockoutDetail::with(['material.materialMeasurement'])->where('stockout_no', $stockout_no)->get();
        $materials = Material::orderBy('name','asc')->get();
        $material_big_stores = MaterialBigStore::all();
        $material_small_stores = MaterialSmallStore::all();
        $material_extra_big_stores = MaterialExtraBigStore::all();
        
        return view('backend.pages.material_stockout.edit', compact('stockout', 'stockoutDetails', 'materials', 'material_big_stores', 'material_small_stores', 'material_extra_big_stores'));
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
        if (is_null($this->user) || !$this->user->can('material_stockout.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any stockout !');
        }

        $stockout = MaterialStockout::where('stockout_no', $stockout_no)->first();
        
        if (is_null($stockout)) {
            session()->flash('error', 'Stockout not found !!');
            return redirect()->route('admin.material-stockouts.index');
        }

        // Prevent updating when status is 4 (Approuvé/Approved)
        if ($stockout->status == 4) {
            session()->flash('error', 'Cannot update stockout that has been approved (status = 4) !!');
            return redirect()->route('admin.material-stockouts.index');
        }

        $rules = array(
            'material_id.*'  => 'required',
            'date'  => 'required',
            'quantity.*'  => 'required',
            'asker'  => 'required',
            'destination'  => 'required',
            'item_movement_type'  => 'nullable|in:SAU,SP,SV,SD,SC,SAJ',
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

            $material_id = $request->material_id;
            $date = $request->date;
            $asker = $request->asker;
            $destination = $request->destination;
            $description = $request->description; 
            $item_movement_type = SauStockoutJournalService::normalizeType($request->item_movement_type);
            $origin_bg_store_id = $request->origin_bg_store_id;
            $origin_sm_store_id = $request->origin_sm_store_id;
            $origin_extra_store_id = $request->origin_extra_store_id;
            $quantity = $request->quantity;
            
            // Determine store_type based on which origin store is selected
            if (!empty($origin_sm_store_id)) {
                $store_type = 'sm';
            } elseif (!empty($origin_bg_store_id)) {
                $store_type = 'md';
            } elseif (!empty($origin_extra_store_id)) {
                $store_type = 'bg';
            } else {
                abort(403, 'Sorry !! You have to choose a store !');
            }

            // Delete existing details
            MaterialStockoutDetail::where('stockout_no', $stockout_no)->delete();

            // Insert new details
            for( $count = 0; $count < count($material_id); $count++ ){
                $selling_price = Material::where('id', $material_id[$count])->value('selling_price');
                $purchase_price = Material::where('id', $material_id[$count])->value('purchase_price');

                $total_value = $quantity[$count] * $purchase_price;
                $total_purchase_value = $quantity[$count] * $purchase_price;
                $total_selling_value = $quantity[$count] * $selling_price;

                $data = array(
                    'material_id' => $material_id[$count],
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'purchase_price' => $purchase_price,
                    'selling_price' => $selling_price,
                    'total_purchase_value' => $total_purchase_value,
                    'total_selling_value' => $total_selling_value,
                    'asker' => $asker,
                    'destination' => $destination,
                    'origin_bg_store_id' => $origin_bg_store_id,
                    'origin_sm_store_id' => $origin_sm_store_id,
                    'origin_extra_store_id' => $origin_extra_store_id,
                    'item_movement_type' => $item_movement_type,
                    'stockout_no' => $stockout_no,
                    'stockout_signature' => $stockout->stockout_signature,
                    'store_type' => $store_type,
                    'created_by' => $stockout->created_by,
                    'description' => $description,
                    'status' => $stockout->status,
                    'created_at' => $stockout->created_at,
                    'updated_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }
            MaterialStockoutDetail::insert($insert_data);

            // Update stockout
            $stockout->date = $date;
            $stockout->store_type = $store_type;
            $stockout->asker = $asker;
            $stockout->destination = $destination;
            $stockout->item_movement_type = $item_movement_type;
            $stockout->origin_bg_store_id = $origin_bg_store_id;
            $stockout->origin_sm_store_id = $origin_sm_store_id;
            $stockout->origin_extra_store_id = $origin_extra_store_id;
            $stockout->description = $description;
            $stockout->updated_by = $this->user->name;
            $stockout->updated_at = \Carbon\Carbon::now();
            $stockout->save();

            DB::commit();
            session()->flash('success', 'stockout has been updated !!');
            return redirect()->route('admin.material-stockouts.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bonSortie($stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('material_stockout.create')) {
            abort(403, 'Sorry !! You are Unauthorized to print handover document!');
        }
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $code = MaterialStockout::where('stockout_no', $stockout_no)->value('stockout_no');
        $datas = MaterialStockoutDetail::with('material')->where('stockout_no', $stockout_no)->get();
        $demandeur = MaterialStockout::where('stockout_no', $stockout_no)->value('asker');
        $description = MaterialStockout::where('stockout_no', $stockout_no)->value('description');
        $stockout_signature = MaterialStockout::where('stockout_no', $stockout_no)->value('stockout_signature');
        $date = MaterialStockout::where('stockout_no', $stockout_no)->value('date');
        $totalValue = DB::table('material_stockout_details')
            ->where('stockout_no', '=', $stockout_no)
            ->sum('total_purchase_value');
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.material_stockout', compact('datas','code','totalValue','demandeur','description','stockout_no','setting','date','stockout_signature'), [], 'UTF-8');

        Storage::put('public/pdf/material_stockout/'.$stockout_no.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('BON_SORTIE_'.$stockout_no.'.pdf');
        
    }

    public function print($stockout_no) { return $this->bonSortie($stockout_no); }

    public function validateStockout($stockout_no)
    {
       if (is_null($this->user) || !$this->user->can('material_stockout.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any stockout !');
        }

        $stockout = MaterialStockout::where('stockout_no', $stockout_no)->first();
        if (!$stockout) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentValidate($stockout)) {
            return $response;
        }

        try {DB::beginTransaction();

            MaterialStockout::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            MaterialStockoutDetail::where('stockout_no', '=', $stockout_no)
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
       if (is_null($this->user) || !$this->user->can('material_stockout.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any stockout !');
        }

        try {DB::beginTransaction();

        MaterialStockout::where('stockout_no', '=', $stockout_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);
        MaterialStockoutDetail::where('stockout_no', '=', $stockout_no)
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
       if (is_null($this->user) || !$this->user->can('material_stockout.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any stockout !');
        }

        try {DB::beginTransaction();

        MaterialStockout::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
        MaterialStockoutDetail::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Stockout has been reseted !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
    }

    public function confirm($stockout_no)
    {
       if (is_null($this->user) || !$this->user->can('material_stockout.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any stockout !');
        }

        $stockout = MaterialStockout::where('stockout_no', $stockout_no)->first();
        if (!$stockout) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentConfirm($stockout)) {
            return $response;
        }

        try {DB::beginTransaction();

        MaterialStockout::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            MaterialStockoutDetail::where('stockout_no', '=', $stockout_no)
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
       if (is_null($this->user) || !$this->user->can('material_stockout.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any stockout !');
        }

        $stockoutHeader = MaterialStockout::where('stockout_no', $stockout_no)->first();
        if (!$stockoutHeader) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentApprove($stockoutHeader)) {
            return $response;
        }

        $sauService = app(SauStockoutJournalService::class);
        try {
            $sauService->assertDirectStockoutType($stockoutHeader->item_movement_type);
        } catch (\RuntimeException $ex) {
            session()->flash('error', $ex->getMessage());
            return back();
        }

        try {DB::beginTransaction();

        $datas = MaterialStockoutDetail::where('stockout_no', $stockout_no)->get();

        // Initialize arrays
        $reportBigStoreData = [];
        $reportSmallStoreData = [];
        $reportExtraBigStoreData = [];
        $flag_big = 0;
        $flag_md = 0;
        $flag_sm = 0;
        $orchestrator = app(MaterialFlowOrchestrator::class);

        foreach($datas as $data){

            // CAS 2 : article non stockable — aucun mouvement physique (même en sortie)
            $materialModel = Material::find($data->material_id);
            if ($materialModel && !$orchestrator->affectsPhysicalStock($materialModel)) {
                continue;
            }

            if (!empty($data->origin_bg_store_id)) {
                $code_store_origin = MaterialBigStore::where('id',$data->origin_bg_store_id)->value('code');

                $valeurStockInitial = MaterialBigStoreDetail::where('code',$code_store_origin)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('total_cump_value') ?? 0;
                $quantityStockInitial = MaterialBigStoreDetail::where('code',$code_store_origin)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('quantity') ?? 0;
                $cump = MaterialBigStoreDetail::where('code',$code_store_origin)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('cump') ?? 0;
                
                if ($cump == 0) {
                    $cump = Material::where('id', $data->material_id)->value('cump') ?? 0;
                }
                
                if ($data->quantity > $quantityStockInitial) {
                    DB::rollback();
                    session()->flash('error', $this->user->name.' ,Why do you want to stockout a quantity you do not have in your store?please rewrite a valid quantity!');
                    return redirect()->back();
                }
                
                $quantityRestantBigStore = $quantityStockInitial - $data->quantity;
                $value_stockout = $data->quantity * $cump;
                $quantity_stock_final = $quantityStockInitial - $data->quantity;
                $value_stock_final = $quantity_stock_final * $cump;

                $reportBigStore = array(
                    'material_id' => $data->material_id,
                    'quantity_stock_initial' => $quantityStockInitial,
                    'value_stock_initial' => $valeurStockInitial,
                    'code_store' => $code_store_origin,
                    'date' => $data->date,
                    'quantity_stockout' => $data->quantity,
                    'value_stockout' => $value_stockout,
                    'quantity_stock_final' => $quantity_stock_final,
                    'value_stock_final' => $value_stock_final,
                    'cump' => $cump,
                    'document_no' => $data->stockout_no,
                    'type_transaction' => $data->item_movement_type,
                    'description' => $data->description,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportBigStoreData[] = $reportBigStore;

                    $bigStore = array(
                        'material_id' => $data->material_id,
                        'quantity' => $quantityRestantBigStore,
                        'purchase_price' => $cump,
                        'total_purchase_value' => $quantityRestantBigStore * $cump,
                        'cump' => $cump,
                        'total_cump_value' => $quantityRestantBigStore * $cump,
                        'created_by' => $this->user->name,
                        'verified' => true,
                        'created_at' => \Carbon\Carbon::now()
                    );


                    MaterialBigStoreDetail::where('code',$code_store_origin)->where('material_id',$data->material_id)
                        ->update($bigStore);

                    $flag_md = 1;


            }elseif (!empty($data->origin_sm_store_id)) {
                $code_store_origin = MaterialSmallStore::where('id',$data->origin_sm_store_id)->value('code');

                $valeurStockInitial = MaterialSmallStoreDetail::where('code',$code_store_origin)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('total_cump_value') ?? 0;
                $cump = MaterialSmallStoreDetail::where('code',$code_store_origin)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('cump') ?? 0;
                $quantityStockInitial = MaterialSmallStoreDetail::where('code',$code_store_origin)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('quantity') ?? 0;
                
                if ($cump == 0) {
                    $cump = Material::where('id', $data->material_id)->value('cump') ?? 0;
                }
                
                if ($data->quantity > $quantityStockInitial) {
                    DB::rollback();
                    session()->flash('error', $this->user->name.' ,Why do you want to stockout a quantity you do not have in your store?please rewrite a valid quantity!');
                    return redirect()->back();
                }
                
                $quantityRestantSmallStore = $quantityStockInitial - $data->quantity;
                $value_stockout = $data->quantity * $cump;
                $quantity_stock_final = $quantityStockInitial - $data->quantity;
                $value_stock_final = $quantity_stock_final * $cump;

                $reportSmallStore = array(
                    'material_id' => $data->material_id,
                    'quantity_stock_initial' => $quantityStockInitial,
                    'value_stock_initial' => $valeurStockInitial,
                    'code_store' => $code_store_origin,
                    'date' => $data->date,
                    'quantity_stockout' => $data->quantity,
                    'value_stockout' => $value_stockout,
                    'quantity_stock_final' => $quantity_stock_final,
                    'value_stock_final' => $value_stock_final,
                    'cump' => $cump,
                    'document_no' => $data->stockout_no,
                    'type_transaction' => $data->item_movement_type,
                    'description' => $data->description,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportSmallStoreData[] = $reportSmallStore;

                    $smallStore = array(
                        'material_id' => $data->material_id,
                        'quantity' => $quantityRestantSmallStore,
                        'purchase_price' => $cump,
                        'total_purchase_value' => $quantityRestantSmallStore * $cump,
                        'cump' => $cump,
                        'total_cump_value' => $quantityRestantSmallStore * $cump,
                        'created_by' => $this->user->name,
                        'verified' => false,
                        'created_at' => \Carbon\Carbon::now()
                    );


                    MaterialSmallStoreDetail::where('code',$code_store_origin)->where('material_id',$data->material_id)
                        ->update($smallStore);

                    $flag_sm = 1;

            }elseif (!empty($data->origin_extra_store_id)) {
                $code_store_origin = MaterialExtraBigStore::where('id',$data->origin_extra_store_id)->value('code');

                $valeurStockInitial = MaterialExtraBigStoreDetail::where('code',$code_store_origin)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('total_cump_value') ?? 0;
                $quantityStockInitial = MaterialExtraBigStoreDetail::where('code',$code_store_origin)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('quantity') ?? 0;
                $cump = MaterialExtraBigStoreDetail::where('code',$code_store_origin)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('cump') ?? 0;
                
                if ($cump == 0) {
                    $cump = Material::where('id', $data->material_id)->value('cump') ?? 0;
                }
                
                if ($data->quantity > $quantityStockInitial) {
                    DB::rollback();
                    session()->flash('error', $this->user->name.' ,Why do you want to stockout a quantity you do not have in your store?please rewrite a valid quantity!');
                    return redirect()->back();
                }
                
                $quantityRestantSmallStore = $quantityStockInitial - $data->quantity;
                $value_stockout = $data->quantity * $cump;
                $quantity_stock_final = $quantityStockInitial - $data->quantity;
                $value_stock_final = $quantity_stock_final * $cump;

                $reportExtraBigStore = array(
                    'material_id' => $data->material_id,
                    'quantity_stock_initial' => $quantityStockInitial,
                    'value_stock_initial' => $valeurStockInitial,
                    'code_store' => $code_store_origin,
                    'date' => $data->date,
                    'quantity_stockout' => $data->quantity,
                    'value_stockout' => $value_stockout,
                    'quantity_stock_final' => $quantity_stock_final,
                    'value_stock_final' => $value_stock_final,
                    'cump' => $cump,
                    'document_no' => $data->stockout_no,
                    'type_transaction' => $data->item_movement_type,
                    'description' => $data->description,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportExtraBigStoreData[] = $reportExtraBigStore;

                    $extraStore = array(
                        'material_id' => $data->material_id,
                        'quantity' => $quantityRestantSmallStore,
                        'purchase_price' => $cump,
                        'total_purchase_value' => $quantityRestantSmallStore * $cump,
                        'cump' => $cump,
                        'total_cump_value' => $quantityRestantSmallStore * $cump,
                        'created_by' => $this->user->name,
                        'verified' => false,
                        'created_at' => \Carbon\Carbon::now()
                    );


                    MaterialExtraBigStoreDetail::where('code',$code_store_origin)->where('material_id',$data->material_id)
                        ->update($extraStore);
                    $flag_big = 1;

            }
                
  
        }

        // Valeur comptable = uniquement les lignes stockables réellement sorties (exclut CAS 2)
        $cmp = 0.0;
        foreach (array_merge($reportBigStoreData, $reportSmallStoreData, $reportExtraBigStoreData) as $reportRow) {
            $cmp += (float) ($reportRow['value_stockout'] ?? 0);
        }
        $cmp = round($cmp, 2);

        // Get first data item for accounting
        $firstData = MaterialStockoutDetail::where('stockout_no', $stockout_no)->first();

        // Insert reports
        if (!empty($reportBigStoreData) && $flag_md != 0) {
            MaterialBigReport::insert($reportBigStoreData);
        }
        
        if (!empty($reportSmallStoreData) && $flag_sm != 0) {
            MaterialSmallReport::insert($reportSmallStoreData);
        }
        
        if (!empty($reportExtraBigStoreData) && $flag_big != 0) {
            MaterialExtraBigReport::insert($reportExtraBigStoreData);
        }

        if($flag_md != 0 || $flag_sm != 0 || $flag_big != 0){

            // Initialize arrays
            $insert_journal_credit_data = [];
            $insert_journal_debit_sp_data_acc2 = [];
            $insert_journal_debit_sv_data_acc2 = [];
            $insert_journal_debit_sc_data_acc2 = [];
            $insert_journal_debit_saj_data_acc2 = [];

            // Determine account based on store type
            if (!empty($firstData->origin_bg_store_id)) {
                $id_acc_stock = MaterialBigStore::where('id',$firstData->origin_bg_store_id)->value('default_account_id');
            } elseif (!empty($firstData->origin_sm_store_id)) {
                $id_acc_stock = MaterialSmallStore::where('id',$firstData->origin_sm_store_id)->value('default_account_id');
            } else {
                $id_acc_stock = MaterialExtraBigStore::where('id',$firstData->origin_extra_store_id)->value('default_account_id');
            }

            if (empty($id_acc_stock)) {
                DB::rollback();
                session()->flash('error', __('messages.approval_accounts_required'));
                return redirect()->back();
            }

            if ($firstData->item_movement_type == "SP") {
                //start credit operations

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);


                $journalEntryCreditdata = array(
                    'date' => $firstData->date,
                    'document_no' => $firstData->stockout_no,
                    'description' => $firstData->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $firstData->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $m_sp_account_id = AccountSetting::where('active',1)->value('m_sp_account_id');

                if (empty($m_sp_account_id)) {
                    DB::rollback();
                    session()->flash('error', 'Compte de sortie perte (SP) des matériels non configuré. Renseignez-le dans les paramètres comptables.');
                    return redirect()->back();
                }

                $spLedgerState = $this->updateLedgerTotals($m_sp_account_id, 'debit', $cmp);
            
                $journalEntryDebitspdataAcc2 = array(
                    'date' => $firstData->date,
                    'document_no' => $firstData->stockout_no,
                    'description' => $firstData->description,
                    'account_id' => $m_sp_account_id,
                    'libelle' => $firstData->description,
                    'debit' => $cmp,
                    'balance' => $spLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_sp_data_acc2[] = $journalEntryDebitspdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_sp_data_acc2);
            
                //end debit operation
            }elseif ($firstData->item_movement_type == "SV") {
                //start credit operations
                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);

                $journalEntryCreditdata = array(
                    'date' => $firstData->date,
                    'document_no' => $firstData->stockout_no,
                    'description' => $firstData->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $firstData->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $m_sv_account_id = AccountSetting::where('active',1)->value('m_sv_account_id');

                if (empty($m_sv_account_id)) {
                    DB::rollback();
                    session()->flash('error', 'Compte de sortie vol (SV) des matériels non configuré. Renseignez-le dans les paramètres comptables.');
                    return redirect()->back();
                }

                $svLedgerState = $this->updateLedgerTotals($m_sv_account_id, 'debit', $cmp);
            
                $journalEntryDebitsvdataAcc2 = array(
                    'date' => $firstData->date,
                    'document_no' => $firstData->stockout_no,
                    'description' => $firstData->description,
                    'account_id' => $m_sv_account_id,
                    'libelle' => $firstData->description,
                    'debit' => $cmp,
                    'balance' => $svLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_sv_data_acc2[] = $journalEntryDebitsvdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_sv_data_acc2);
            
                //end debit operation
            }elseif ($firstData->item_movement_type == "SC") {
                //start credit operations
                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);

                $journalEntryCreditdata = array(
                    'date' => $firstData->date,
                    'document_no' => $firstData->stockout_no,
                    'description' => $firstData->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $firstData->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $m_sc_account_id = AccountSetting::where('active',1)->value('m_sc_account_id');

                if (empty($m_sc_account_id)) {
                    DB::rollback();
                    session()->flash('error', 'Compte de sortie casse (SC) des matériels non configuré. Renseignez-le dans les paramètres comptables.');
                    return redirect()->back();
                }

                $scLedgerState = $this->updateLedgerTotals($m_sc_account_id, 'debit', $cmp);
            
                $journalEntryDebitscdataAcc2 = array(
                    'date' => $firstData->date,
                    'document_no' => $firstData->stockout_no,
                    'description' => $firstData->description,
                    'account_id' => $m_sc_account_id,
                    'libelle' => $firstData->description,
                    'debit' => $cmp,
                    'balance' => $scLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_sc_data_acc2[] = $journalEntryDebitscdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_sc_data_acc2);
            
                //end debit operation
            }elseif (SauStockoutJournalService::isSau($firstData->item_movement_type)) {
                // Sortie autre APPROUVÉE (valeur CUMP des lignes stockables) :
                // CAS 1 → Crédit stock (3) / Débit compte de la fiche article
                // CAS 3 → Crédit stock (3) / Débit immobilisation (2) + mise en service + plan
                $sauLines = [];
                foreach (array_merge($reportBigStoreData, $reportSmallStoreData, $reportExtraBigStoreData) as $reportRow) {
                    $material = Material::find($reportRow['material_id']);
                    $sauLines[] = [
                        'item' => $material,
                        'amount' => (float) ($reportRow['value_stockout'] ?? 0),
                        'label' => $material ? $material->name : ('#' . $reportRow['material_id']),
                    ];
                }

                try {
                    $resolvedSauLines = $sauService->resolveLines($sauLines);
                    $sauService->post($id_acc_stock, $resolvedSauLines, [
                        'date' => $firstData->date,
                        'document_no' => $firstData->stockout_no,
                        'description' => $firstData->description,
                    ]);
                } catch (\RuntimeException $ex) {
                    DB::rollback();
                    session()->flash('error', $ex->getMessage());
                    return redirect()->back();
                }

                $approvalDate = \Carbon\Carbon::now();
                foreach ($resolvedSauLines as $line) {
                    if ($line['item'] && $line['amount'] > 0) {
                        $orchestrator->activateOnApprovedStockout($line['item'], $approvalDate, $line['amount']);
                    }
                }
            }else{
                //start credit operations
                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'credit', $cmp);

                $journalEntryCreditdata = array(
                    'date' => $firstData->date,
                    'document_no' => $firstData->stockout_no,
                    'description' => $firstData->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $firstData->description,
                    'credit' => $cmp,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_credit_data[] = $journalEntryCreditdata;
                
                AccountJournalEntryDetail::insert($insert_journal_credit_data);

                //debit adjustment account
                $m_saj_account_id = AccountSetting::where('active',1)->value('m_saj_account_id');

                if (empty($m_saj_account_id)) {
                    DB::rollback();
                    session()->flash('error', __('messages.approval_accounts_required'));
                    return redirect()->back();
                }

                $sajLedgerState = $this->updateLedgerTotals($m_saj_account_id, 'debit', $cmp);
            
                $journalEntryDebitsajdataAcc2 = array(
                    'date' => $firstData->date,
                    'document_no' => $firstData->stockout_no,
                    'description' => $firstData->description,
                    'account_id' => $m_saj_account_id,
                    'libelle' => $firstData->description,
                    'debit' => $cmp,
                    'balance' => $sajLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_saj_data_acc2[] = $journalEntryDebitsajdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_debit_saj_data_acc2);
            
                //end debit operation
            }
        }

        MaterialBigStoreDetail::where('material_id','!=','')->update(['verified' => false]);
        MaterialSmallStoreDetail::where('material_id','!=','')->update(['verified' => false]);
        MaterialExtraBigStoreDetail::where('material_id','!=','')->update(['verified' => false]);

        MaterialStockout::where('stockout_no', '=', $stockout_no)
                    ->update(['status' => 4,'approuved_by' => $this->user->name]);
        MaterialStockoutDetail::where('stockout_no', '=', $stockout_no)
                    ->update(['status' => 4,'approuved_by' => $this->user->name]);

        DB::commit();
            $successMessage = 'Stockout has been done successfully!';
            if (!empty($reportBigStoreData)) {
                $code_store = !empty($reportBigStoreData[0]['code_store']) ? $reportBigStoreData[0]['code_store'] : '';
                $successMessage .= ' From Big Stock: ' . $code_store;
            }
            if (!empty($reportSmallStoreData)) {
                $code_store = !empty($reportSmallStoreData[0]['code_store']) ? $reportSmallStoreData[0]['code_store'] : '';
                $successMessage .= ' From Small Stock: ' . $code_store;
            }
            if (!empty($reportExtraBigStoreData)) {
                $code_store = !empty($reportExtraBigStoreData[0]['code_store']) ? $reportExtraBigStoreData[0]['code_store'] : '';
                $successMessage .= ' From Extra Big Stock: ' . $code_store;
            }
            session()->flash('success', $successMessage);
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
     * @param  int  $stockout_no
     * @return \Illuminate\Http\Response
     */
    public function destroy($stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('material_stockout.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any stockout !');
        }

        $stockout = MaterialStockout::where('stockout_no',$stockout_no)->first();
        
        if (is_null($stockout)) {
            session()->flash('error', 'Stockout not found !!');
            return back();
        }

        // Prevent deletion when status is 4 (Approuvé/Approved)
        if ($stockout->status == 4) {
            session()->flash('error', 'Cannot delete stockout that has been approved (status = 4) !!');
            return back();
        }

        try {
            DB::beginTransaction();

            $stockout->delete();
            MaterialStockoutDetail::where('stockout_no',$stockout_no)->delete();

        DB::commit();
            session()->flash('success', 'Stockout has been deleted !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

}
