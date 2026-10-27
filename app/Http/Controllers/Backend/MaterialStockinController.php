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
use App\Models\MaterialStockin;
use App\Models\MaterialStockinDetail;
use App\Models\MaterialBigStoreDetail;
use App\Models\MaterialBigStore;
use App\Models\MaterialExtraBigStore;
use App\Models\MaterialExtraBigStoreDetail;
use App\Models\MaterialSmallStore;
use App\Models\MaterialSmallStoreDetail;
use App\Models\MaterialSmallReport;
use App\Models\MaterialBigReport;
use App\Models\MaterialExtraBigReport;
use App\Models\Accounting\AccountCostCenter;
use App\Models\Accounting\AccountJournalEntry;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Services\Stock\StockCumpCalculator;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountSetting;
use App\Services\Material\MaterialFlowOrchestrator;
use Carbon\Carbon;
use PDF;
use Validator;
use Excel;
use Mail;

class MaterialStockinController extends Controller
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
        if (is_null($this->user) || !$this->user->can('material_stockin.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any stockin !');
        }

        $query = MaterialStockin::query();
        
        // Si des dates sont fournies, filtrer par période
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('created_at', [$dateDebut, $dateFin]);
        } else {
            // Par défaut, afficher les 100 lignes les plus récentes
            $query->take(100);
        }
        
        $stockins = $query->orderBy('id','desc')->get();
        return view('backend.pages.material_stockin.index', compact('stockins'));
    }

    public function choose()
    {
        if (is_null($this->user) || !$this->user->can('material_stockin.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any stockin !');
        }

        return view('backend.pages.material_stockin.choose');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('material_stockin.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockin !');
        }

        $materials  = Material::orderBy('name','asc')->get();
        $destination_stores = MaterialBigStore::all();
        return view('backend.pages.material_stockin.create', compact('materials','destination_stores'));
    }

    public function createFromBig()
    {
        if (is_null($this->user) || !$this->user->can('material_stockin.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockin !');
        }

        $materials  = Material::orderBy('name','asc')->get();
        $destination_stores = MaterialExtraBigStore::all();
        return view('backend.pages.material_stockin.create_from_big', compact('materials','destination_stores'));
    }

    public function createFromSmall()
    {
        if (is_null($this->user) || !$this->user->can('material_stockin.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockin !');
        }

        $materials  = Material::orderBy('name','asc')->get();
        $destination_stores = MaterialSmallStore::all();
        return view('backend.pages.material_stockin.create_from_small', compact('materials','destination_stores'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('material_stockin.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockin !');
        }

        $rules = array(
                'material_id.*'  => 'required',
                'date'  => 'required',
                'quantity.*'  => 'required',
                'purchase_price.*'  => 'required',
                'handingover'  => 'required',
                'origin'  => 'required',
                'receptionist'  => 'required',
                'destination_store_id'  => 'required',
                'item_movement_type'  => 'required|in:EN,ER,EAJ,EAU',
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
            $invoice_currency = $request->invoice_currency;
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $origin = $request->origin;
            $store_type = $request->store_type;
            $description =$request->description; 
            $destination_store_id = $request->destination_store_id;
            $item_movement_type = $request->item_movement_type;
            $quantity = $request->quantity;
            $purchase_price = $request->purchase_price;
            

            $latest = MaterialStockin::orderBy('id','desc')->first();
            if ($latest) {
               $stockin_no = 'M-BE' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $stockin_no = 'M-BE' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $created_by = $this->user->name;

            $stockin_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$stockin_no;


            for( $count = 0; $count < count($material_id); $count++ ){
                $total_amount_purchase = $quantity[$count] * $purchase_price[$count];

                $data = array(
                    'material_id' => $material_id[$count],
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'purchase_price' => $purchase_price[$count],
                    'total_amount_purchase' => $total_amount_purchase,
                    'receptionist' => $receptionist,
                    'handingover' => $handingover,
                    'origin' => $origin,
                    'store_type' => $store_type,
                    'dest_bg_store_id' => $destination_store_id,
                    'dest_extra_store_id' => null,
                    'dest_sm_store_id' => null,
                    'item_movement_type' => $item_movement_type,
                    'stockin_no' => $stockin_no,
                    'stockin_signature' => $stockin_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }
            MaterialStockinDetail::insert($insert_data);


            //create stockin
            $stockin = new MaterialStockin();
            $stockin->date = $date;
            $stockin->stockin_no = $stockin_no;
            $stockin->stockin_signature = $stockin_signature;
            $stockin->receptionist = $receptionist;
            $stockin->handingover = $handingover;
            $stockin->origin = $origin;
            $stockin->store_type = $store_type;
            $stockin->dest_bg_store_id = $destination_store_id;
            $stockin->item_movement_type = $item_movement_type;
            $stockin->created_by = $created_by;
            $stockin->status = 1;
            $stockin->description = $description;
            $stockin->created_at = \Carbon\Carbon::now();
            $stockin->save();

            DB::commit();
            session()->flash('success', 'stockin has been created !!');
            return redirect()->route('admin.material-stockins.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
            
    }

    public function storeFromBig(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('material_stockin.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockin !');
        }

        $rules = array(
                'material_id.*'  => 'required',
                'date'  => 'required',
                'quantity.*'  => 'required',
                'purchase_price.*'  => 'required',
                'handingover'  => 'required',
                'origin'  => 'required',
                'receptionist'  => 'required',
                'dest_extra_store_id'  => 'required',
                'item_movement_type'  => 'required|in:EN,ER,EAJ,EAU',
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
            $invoice_currency = $request->invoice_currency;
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $origin = $request->origin;
            $store_type = $request->store_type;
            $description =$request->description; 
            $dest_extra_store_id = $request->dest_extra_store_id;
            $item_movement_type = $request->item_movement_type;
            $quantity = $request->quantity;
            $purchase_price = $request->purchase_price;
            

            $latest = MaterialStockin::latest()->first();
            if ($latest) {
               $stockin_no = 'M-BE' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $stockin_no = 'M-BE' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $created_by = $this->user->name;

            $stockin_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$stockin_no;


            for( $count = 0; $count < count($material_id); $count++ ){
                $total_amount_purchase = $quantity[$count] * $purchase_price[$count];

                $data = array(
                    'material_id' => $material_id[$count],
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'purchase_price' => $purchase_price[$count],
                    'total_amount_purchase' => $total_amount_purchase,
                    'receptionist' => $receptionist,
                    'handingover' => $handingover,
                    'origin' => $origin,
                    'store_type' => $store_type,
                    'dest_extra_store_id' => $dest_extra_store_id,
                    'item_movement_type' => $item_movement_type,
                    'stockin_no' => $stockin_no,
                    'stockin_signature' => $stockin_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }
            MaterialStockinDetail::insert($insert_data);


            //create stockin
            $stockin = new MaterialStockin();
            $stockin->date = $date;
            $stockin->stockin_no = $stockin_no;
            $stockin->stockin_signature = $stockin_signature;
            $stockin->receptionist = $receptionist;
            $stockin->handingover = $handingover;
            $stockin->origin = $origin;
            $stockin->store_type = $store_type;
            $stockin->dest_extra_store_id = $dest_extra_store_id;
            $stockin->item_movement_type = $item_movement_type;
            $stockin->created_by = $created_by;
            $stockin->status = 1;
            $stockin->description = $description;
            $stockin->created_at = \Carbon\Carbon::now();
            $stockin->save();

            DB::commit();
            session()->flash('success', 'stockin has been created !!');
            return redirect()->route('admin.material-stockins.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
        
    }


    public function storeFromSmall(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('material_stockin.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockin !');
        }

        $rules = array(
                'material_id.*'  => 'required',
                'date'  => 'required',
                'quantity.*'  => 'required',
                'purchase_price.*'  => 'required',
                'handingover'  => 'required',
                'origin'  => 'required',
                'receptionist'  => 'required',
                'dest_sm_store_id'  => 'required',
                'item_movement_type'  => 'required|in:EN,ER,EAJ,EAU',
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
            $invoice_currency = $request->invoice_currency;
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $origin = $request->origin;
            $store_type = $request->store_type;
            $description =$request->description; 
            $dest_sm_store_id = $request->dest_sm_store_id;
            $item_movement_type = $request->item_movement_type;
            $quantity = $request->quantity;
            $purchase_price = $request->purchase_price;
            

            $latest = MaterialStockin::latest()->first();
            if ($latest) {
               $stockin_no = 'M-BE' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $stockin_no = 'M-BE' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $created_by = $this->user->name;

            $stockin_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$stockin_no;


            for( $count = 0; $count < count($material_id); $count++ ){
                $total_amount_purchase = $quantity[$count] * $purchase_price[$count];

                $data = array(
                    'material_id' => $material_id[$count],
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'purchase_price' => $purchase_price[$count],
                    'total_amount_purchase' => $total_amount_purchase,
                    'receptionist' => $receptionist,
                    'handingover' => $handingover,
                    'origin' => $origin,
                    'store_type' => $store_type,
                    'dest_sm_store_id' => $dest_sm_store_id,
                    'item_movement_type' => $item_movement_type,
                    'stockin_no' => $stockin_no,
                    'stockin_signature' => $stockin_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }
            MaterialStockinDetail::insert($insert_data);


            //create stockin
            $stockin = new MaterialStockin();
            $stockin->date = $date;
            $stockin->stockin_no = $stockin_no;
            $stockin->stockin_signature = $stockin_signature;
            $stockin->receptionist = $receptionist;
            $stockin->handingover = $handingover;
            $stockin->origin = $origin;
            $stockin->store_type = $store_type;
            $stockin->dest_sm_store_id = $dest_sm_store_id;
            $stockin->item_movement_type = $item_movement_type;
            $stockin->created_by = $created_by;
            $stockin->status = 1;
            $stockin->description = $description;
            $stockin->created_at = \Carbon\Carbon::now();
            $stockin->save();

            DB::commit();
            session()->flash('success', 'stockin has been created !!');
            return redirect()->route('admin.material-stockins.index');
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
        $stockin = MaterialStockin::where('stockin_no', $stockin_no)->first();
        $stockinDetails = MaterialStockinDetail::with(['material.materialMeasurement', 'destinationbigStore', 'destinationSmallStore', 'destinationExtraStore'])
            ->where('stockin_no', $stockin_no)->get();
        
        // Calculate totals
        $totalQuantity = $stockinDetails->sum('quantity');
        $totalPurchaseAmount = $stockinDetails->sum('total_amount_purchase');
        
        return view('backend.pages.material_stockin.show', compact('stockin', 'stockinDetails', 'totalQuantity', 'totalPurchaseAmount'));
         
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('material_stockin.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any stockin !');
        }

        $stockin = MaterialStockin::where('stockin_no', $stockin_no)->first();
        
        if (is_null($stockin)) {
            session()->flash('error', 'Stockin not found !!');
            return redirect()->route('admin.material-stockins.index');
        }

        // Prevent editing when status is 4 (Approuvé/Approved)
        if ($stockin->status == 4) {
            session()->flash('error', 'Cannot edit stockin that has been approved (status = 4) !!');
            return redirect()->route('admin.material-stockins.index');
        }

        $stockinDetails = MaterialStockinDetail::with(['material.materialMeasurement'])->where('stockin_no', $stockin_no)->get();
        $materials = Material::orderBy('name','asc')->get();
        
        // Get stores based on store_type
        $material_big_stores = MaterialBigStore::all();
        $material_extra_big_stores = MaterialExtraBigStore::all();
        $material_small_stores = MaterialSmallStore::all();
        
        return view('backend.pages.material_stockin.edit', compact(
            'stockin', 
            'stockinDetails', 
            'materials', 
            'material_big_stores', 
            'material_extra_big_stores', 
            'material_small_stores'
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
        if (is_null($this->user) || !$this->user->can('material_stockin.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any stockin !');
        }

        $stockin = MaterialStockin::where('stockin_no', $stockin_no)->first();
        
        if (is_null($stockin)) {
            session()->flash('error', 'Stockin not found !!');
            return redirect()->route('admin.material-stockins.index');
        }

        // Prevent updating when status is 4 (Approuvé/Approved)
        if ($stockin->status == 4) {
            session()->flash('error', 'Cannot update stockin that has been approved (status = 4) !!');
            return redirect()->route('admin.material-stockins.index');
        }

        $rules = array(
            'material_id.*'  => 'required',
            'date'  => 'required',
            'quantity.*'  => 'required',
            'purchase_price.*'  => 'required',
            'handingover'  => 'required',
            'origin'  => 'required',
            'receptionist'  => 'required',
            'item_movement_type'  => 'required|in:EN,ER,EAJ,EAU',
            'description'  => 'required|max:490'
        );

        // Add store validation based on store_type
        if ($stockin->store_type == 'md') {
            $rules['destination_store_id'] = 'required';
        } elseif ($stockin->store_type == 'bg') {
            $rules['dest_extra_store_id'] = 'required';
        } elseif ($stockin->store_type == 'sm') {
            $rules['dest_sm_store_id'] = 'required';
        }

        $error = Validator::make($request->all(),$rules);

        if($error->fails()){
            return response()->json([
                'error' => $error->errors()->all(),
            ]);
        }

        try {DB::beginTransaction();

            $material_id = $request->material_id;
            $date = Carbon::parse($request->date);
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $origin = $request->origin;
            $store_type = $stockin->store_type; // Keep original store_type
            $description = $request->description; 
            $item_movement_type = $request->item_movement_type;
            $destination_store_id = $request->destination_store_id ?? null;
            $dest_extra_store_id = $request->dest_extra_store_id ?? null;
            $dest_sm_store_id = $request->dest_sm_store_id ?? null;
            $quantity = $request->quantity;
            $purchase_price = $request->purchase_price;
            
            $updated_by = $this->user->name;

            // Validate store selection based on store_type
            if ($store_type == 'md' && empty($destination_store_id)) {
                abort(403, 'Sorry !! You have to choose a store ! more information contact Marcellin');
            } elseif ($store_type == 'bg' && empty($dest_extra_store_id)) {
                abort(403, 'Sorry !! You have to choose a store ! more information contact Marcellin');
            } elseif ($store_type == 'sm' && empty($dest_sm_store_id)) {
                abort(403, 'Sorry !! You have to choose a store ! more information contact Marcellin');
            }

            // Delete existing details
            MaterialStockinDetail::where('stockin_no', $stockin_no)->delete();

            // Insert updated details
            for( $count = 0; $count < count($material_id); $count++ ){
                $total_amount_purchase = $quantity[$count] * $purchase_price[$count];

                $data = array(
                    'material_id' => $material_id[$count],
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'purchase_price' => $purchase_price[$count],
                    'total_amount_purchase' => $total_amount_purchase,
                    'receptionist' => $receptionist,
                    'handingover' => $handingover,
                    'origin' => $origin,
                    'store_type' => $store_type,
                    'dest_bg_store_id' => ($store_type == 'md') ? $destination_store_id : null,
                    'dest_extra_store_id' => $dest_extra_store_id,
                    'dest_sm_store_id' => $dest_sm_store_id,
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
            MaterialStockinDetail::insert($insert_data);

            // Update stockin
            $stockin->date = $date;
            $stockin->receptionist = $receptionist;
            $stockin->handingover = $handingover;
            $stockin->origin = $origin;
            $stockin->item_movement_type = $item_movement_type;
            $stockin->dest_bg_store_id = ($store_type == 'md') ? $destination_store_id : null;
            $stockin->dest_extra_store_id = $dest_extra_store_id;
            $stockin->dest_sm_store_id = $dest_sm_store_id;
            $stockin->updated_by = $updated_by;
            $stockin->description = $description;
            $stockin->updated_at = \Carbon\Carbon::now();
            $stockin->save();

            DB::commit();
            session()->flash('success', 'Stockin has been updated !!');
            return redirect()->route('admin.material-stockins.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bonEntree($stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('material_stockin.create')) {
            abort(403, 'Sorry !! You are Unauthorized to print handover document!');
        }
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $code = MaterialStockin::where('stockin_no', $stockin_no)->value('stockin_no');
        $datas = MaterialStockinDetail::with('material')->where('stockin_no', $stockin_no)->get();
        $receptionniste = MaterialStockin::where('stockin_no', $stockin_no)->value('receptionist');
        $description = MaterialStockin::where('stockin_no', $stockin_no)->value('description');
        $stockin_signature = MaterialStockin::where('stockin_no', $stockin_no)->value('stockin_signature');
        $date = MaterialStockin::where('stockin_no', $stockin_no)->value('date');
        $totalValue = DB::table('material_stockin_details')
            ->where('stockin_no', '=', $stockin_no)
            ->sum('total_amount_purchase');
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.material_stockin', compact('datas','code','totalValue','receptionniste','description','setting','stockin_no','date','stockin_signature'), [], 'UTF-8');

        Storage::put('public/pdf/material_stockin/'.$stockin_no.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('BON_ENTREE_'.$stockin_no.'.pdf');
        
    }

    public function print($stockin_no) { return $this->bonEntree($stockin_no); }

    public function validateStockin($stockin_no)
    {
       if (is_null($this->user) || !$this->user->can('material_stockin.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any stockin !');
        }

        $stockin = MaterialStockin::where('stockin_no', $stockin_no)->first();
        if (!$stockin) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentValidate($stockin)) {
            return $response;
        }

        try {DB::beginTransaction();

            MaterialStockin::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            MaterialStockinDetail::where('stockin_no', '=', $stockin_no)
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
       if (is_null($this->user) || !$this->user->can('material_stockin.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any stockin !');
        }

        try {DB::beginTransaction();

        MaterialStockin::where('stockin_no', '=', $stockin_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);
        MaterialStockinDetail::where('stockin_no', '=', $stockin_no)
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
       if (is_null($this->user) || !$this->user->can('material_stockin.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any stockin !');
        }

        try {DB::beginTransaction();

        MaterialStockin::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
        MaterialStockinDetail::where('stockin_no', '=', $stockin_no)
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
       if (is_null($this->user) || !$this->user->can('material_stockin.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any stockin !');
        }

        $stockin = MaterialStockin::where('stockin_no', $stockin_no)->first();
        if (!$stockin) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentConfirm($stockin)) {
            return $response;
        }

        try {DB::beginTransaction();

        MaterialStockin::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            MaterialStockinDetail::where('stockin_no', '=', $stockin_no)
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
       if (is_null($this->user) || !$this->user->can('material_stockin.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any stockin !');
        }

        $stockinHeader = MaterialStockin::where('stockin_no', $stockin_no)->first();
        if (!$stockinHeader) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentApprove($stockinHeader)) {
            return $response;
        }

        try {DB::beginTransaction();

        $datas = MaterialStockinDetail::where('stockin_no', $stockin_no)->get();

        // Initialize arrays
        $reportBigStoreData = [];
        $reportExtraBigStoreData = [];
        $reportSmallStoreData = [];
        $flag_md = 0;
        $orchestrator = app(MaterialFlowOrchestrator::class);
        $immaterialActivations = [];

        foreach($datas as $data){

                $materialModel = Material::find($data->material_id);
                if ($materialModel && !$orchestrator->affectsPhysicalStock($materialModel)) {
                    // CAS 2 : non stockable + amortissable — pas de mouvement physique
                    $amount = (float) ($data->total_amount_purchase ?? (($data->quantity ?? 0) * ($data->purchase_price ?? 0)));
                    $immaterialActivations[] = [
                        'material' => $materialModel,
                        'detail' => $data,
                        'amount' => $amount,
                    ];
                    continue;
                }

                if ($data->store_type == 'md') {
                    $code_store_destination = MaterialBigStore::where('id',$data->dest_bg_store_id)->value('code');

                $valeurStockInitialDestination = MaterialBigStoreDetail::where('code',$code_store_destination)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('total_cump_value') ?? 0;
                $quantityStockInitialDestination = MaterialBigStoreDetail::where('code',$code_store_destination)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('quantity') ?? 0;
                $cumpResult = StockCumpCalculator::weightedAverageOnStockIn(
                    (float) $quantityStockInitialDestination,
                    (float) $valeurStockInitialDestination,
                    (float) $data->quantity,
                    (float) $data->purchase_price,
                    (float) $data->purchase_price
                );
                $cump = $cumpResult['cump'];
                $quantityTotalBigStore = $cumpResult['quantity_total'];

                // Calculate value_stockin using cump
                $value_stockin = $data->quantity * $cump;
                $quantity_stock_final = $quantityStockInitialDestination + $data->quantity;
                $value_stock_final = $quantity_stock_final * $cump;

                $reportBigStore = array(
                    'material_id' => $data->material_id,
                    'quantity_stock_initial' => $quantityStockInitialDestination,
                    'value_stock_initial' => $valeurStockInitialDestination,
                    'code_store' => $code_store_destination,
                    'date' => $data->date,
                    'quantity_stockin' => $data->quantity,
                    'value_stockin' => $value_stockin,
                    'quantity_stock_final' => $quantity_stock_final,
                    'value_stock_final' => $value_stock_final,
                    'cump' => $cump,
                    'document_no' => $data->stockin_no,
                    'type_transaction' => $data->item_movement_type,
                    'description' => $data->description,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportBigStoreData[] = $reportBigStore;

                    $mediumStore = array(
                        'material_id' => $data->material_id,
                        'quantity' => $quantityTotalBigStore,
                        'purchase_price' => $cump,
                        'total_purchase_value' => $quantityTotalBigStore * $cump,
                        'cump' => $cump,
                        'total_cump_value' => $quantityTotalBigStore * $cump,
                        'created_by' => $this->user->name,
                        'verified' => false
                    );

                    $materialData = array(
                        'id' => $data->material_id,
                        'quantity' => $quantityTotalBigStore,
                        'cump' => $cump,
                        'purchase_price' => $cump
                    );

                    Material::where('id',$data->material_id)
                        ->update($materialData);

                        $material = MaterialBigStoreDetail::where('code',$code_store_destination)->where("material_id",$data->material_id)->value('material_id');

                        if (!empty($material)) {
                            MaterialBigStoreDetail::where('code',$code_store_destination)->where('material_id',$data->material_id)
                        ->update($mediumStore);
                        $flag_md = 1;
                        }else{
                            $flag_md = 0;
                            DB::rollback();
                            DB::rollback();
                            session()->flash('error', 'this item is not saved in the stock');
                            return back();
                        }

                }elseif ($data->store_type == 'bg') {
                    $code_store_destination = MaterialExtraBigStore::where('id',$data->dest_extra_store_id)->value('code');

                $valeurStockInitialDestination = MaterialExtraBigStoreDetail::where('code',$code_store_destination)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('total_cump_value') ?? 0;
                $quantityStockInitialDestination = MaterialExtraBigStoreDetail::where('code',$code_store_destination)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('quantity') ?? 0;
                $cumpResult = StockCumpCalculator::weightedAverageOnStockIn(
                    (float) $quantityStockInitialDestination,
                    (float) $valeurStockInitialDestination,
                    (float) $data->quantity,
                    (float) $data->purchase_price,
                    (float) $data->purchase_price
                );
                $cump = $cumpResult['cump'];
                $quantityTotalBigStore = $cumpResult['quantity_total'];

                // Calculate value_stockin using cump
                $value_stockin = $data->quantity * $cump;
                $quantity_stock_final = $quantityStockInitialDestination + $data->quantity;
                $value_stock_final = $quantity_stock_final * $cump;

                $reportExtraBigStore = array(
                    'material_id' => $data->material_id,
                    'quantity_stock_initial' => $quantityStockInitialDestination,
                    'value_stock_initial' => $valeurStockInitialDestination,
                    'code_store' => $code_store_destination,
                    'date' => $data->date,
                    'quantity_stockin' => $data->quantity,
                    'value_stockin' => $value_stockin,
                    'quantity_stock_final' => $quantity_stock_final,
                    'value_stock_final' => $value_stock_final,
                    'cump' => $cump,
                    'document_no' => $data->stockin_no,
                    'type_transaction' => $data->item_movement_type,
                    'description' => $data->description,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportExtraBigStoreData[] = $reportExtraBigStore;

                    $extraBigStore = array(
                        'material_id' => $data->material_id,
                        'quantity' => $quantityTotalBigStore,
                        'purchase_price' => $cump,
                        'total_purchase_value' => $quantityTotalBigStore * $cump,
                        'cump' => $cump,
                        'total_cump_value' => $quantityTotalBigStore * $cump,
                        'created_by' => $this->user->name,
                        'verified' => false
                    );

                    $materialData = array(
                        'id' => $data->material_id,
                        'quantity' => $quantityTotalBigStore,
                        'cump' => $cump,
                        'purchase_price' => $cump
                    );

                    Material::where('id',$data->material_id)
                        ->update($materialData);

                        $material = MaterialExtraBigStoreDetail::where('code',$code_store_destination)->where("material_id",$data->material_id)->value('material_id');

                        if (!empty($material)) {
                            MaterialExtraBigStoreDetail::where('code',$code_store_destination)->where('material_id',$data->material_id)
                        ->update($extraBigStore);
                        $flag_md = 1;
                        }else{
                            $flag_md = 0;
                            DB::rollback();
                            session()->flash('error', 'this item is not saved in the stock');
                            return back();
                        }


                }elseif ($data->store_type == 'sm') {
                    $code_store_destination = MaterialSmallStore::where('id',$data->dest_sm_store_id)->value('code');

                $valeurStockInitialDestination = MaterialSmallStoreDetail::where('code',$code_store_destination)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('total_cump_value') ?? 0;
                $quantityStockInitialDestination = MaterialSmallStoreDetail::where('code',$code_store_destination)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('quantity') ?? 0;
                $cumpResult = StockCumpCalculator::weightedAverageOnStockIn(
                    (float) $quantityStockInitialDestination,
                    (float) $valeurStockInitialDestination,
                    (float) $data->quantity,
                    (float) $data->purchase_price,
                    (float) $data->purchase_price
                );
                $cump = $cumpResult['cump'];
                $quantityTotalBigStore = $cumpResult['quantity_total'];

                // Calculate value_stockin using cump
                $value_stockin = $data->quantity * $cump;
                $quantity_stock_final = $quantityStockInitialDestination + $data->quantity;
                $value_stock_final = $quantity_stock_final * $cump;

                $reportSmallStore = array(
                    'material_id' => $data->material_id,
                    'quantity_stock_initial' => $quantityStockInitialDestination,
                    'value_stock_initial' => $valeurStockInitialDestination,
                    'code_store' => $code_store_destination,
                    'date' => $data->date,
                    'quantity_stockin' => $data->quantity,
                    'value_stockin' => $value_stockin,
                    'quantity_stock_final' => $quantity_stock_final,
                    'value_stock_final' => $value_stock_final,
                    'cump' => $cump,
                    'document_no' => $data->stockin_no,
                    'type_transaction' => $data->item_movement_type,
                    'description' => $data->description,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportSmallStoreData[] = $reportSmallStore;

                    $smallStore = array(
                        'material_id' => $data->material_id,
                        'quantity' => $quantityTotalBigStore,
                        'purchase_price' => $cump,
                        'total_purchase_value' => $quantityTotalBigStore * $cump,
                        'cump' => $cump,
                        'total_cump_value' => $quantityTotalBigStore * $cump,
                        'created_by' => $this->user->name,
                        'verified' => false
                    );

                    $materialData = array(
                        'id' => $data->material_id,
                        'quantity' => $quantityTotalBigStore,
                        'cump' => $cump,
                        'purchase_price' => $cump
                    );

                    Material::where('id',$data->material_id)
                        ->update($materialData);

                        $material = MaterialSmallStoreDetail::where('code',$code_store_destination)->where("material_id",$data->material_id)->value('material_id');

                        if (!empty($material)) {
                            MaterialSmallStoreDetail::where('code',$code_store_destination)->where('material_id',$data->material_id)
                        ->update($smallStore);
                        $flag_md = 1;
                        }else{
                            $flag_md = 0;
                            DB::rollback();
                            session()->flash('error', 'this item is not saved in the stock');
                            return back();
                        }

                }else{
                    DB::rollback();
                    session()->flash('error', 'OOPS! Something wrong');
                    return back();
                }
  
        }

        // Valeur classe 3 = uniquement les lignes stockables (exclut CAS 2 immobilisations)
        $totalValue = 0.0;
        foreach (array_merge($reportBigStoreData, $reportExtraBigStoreData, $reportSmallStoreData) as $reportRow) {
            $totalValue += (float) ($reportRow['value_stockin'] ?? 0);
        }
        $totalValue = round($totalValue, 2);

        // Insert reports based on store type
        if (!empty($reportBigStoreData) && $flag_md != 0) {
            MaterialBigReport::insert($reportBigStoreData);
        }
        
        if (!empty($reportExtraBigStoreData) && $flag_md != 0) {
            MaterialExtraBigReport::insert($reportExtraBigStoreData);
        }
        
        if (!empty($reportSmallStoreData) && $flag_md != 0) {
            MaterialSmallReport::insert($reportSmallStoreData);
        }

        if ($flag_md != 0) {
            //start debit operations
                // Initialize arrays
                $insert_journal_debit_data = [];
                $insert_journal_credit_eaj_data_acc2 = [];
                
                // Get the first data item to determine store type
                $firstData = MaterialStockinDetail::where('stockin_no', $stockin_no)->first();
                
                if ($firstData->store_type == 'md') {
                    $id_acc_stock = MaterialBigStore::where('id',$firstData->dest_bg_store_id)->value('default_account_id');
                } elseif ($firstData->store_type == 'bg') {
                    $id_acc_stock = MaterialExtraBigStore::where('id',$firstData->dest_extra_store_id)->value('default_account_id');
                } else {
                    $id_acc_stock = MaterialSmallStore::where('id',$firstData->dest_sm_store_id)->value('default_account_id');
                }

                if (empty($id_acc_stock)) {
                    DB::rollback();
                    session()->flash('error', __('messages.approval_accounts_required'));
                    return redirect()->back();
                }

                $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'debit', $totalValue);

                $journalEntryDebitdata = array(
                    'date' => $firstData->date,
                    'document_no' => $firstData->stockin_no,
                    'description' => $firstData->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $firstData->description,
                    'debit' => $totalValue,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_debit_data[] = $journalEntryDebitdata;
                
                AccountJournalEntryDetail::insert($insert_journal_debit_data);

                //credit adjustment account
                $m_eaj_account_id = AccountSetting::where('active',1)->value('m_eaj_account_id');

                if (empty($m_eaj_account_id)) {
                    DB::rollback();
                    session()->flash('error', __('messages.approval_accounts_required'));
                    return redirect()->back();
                }

                $eajLedgerState = $this->updateLedgerTotals($m_eaj_account_id, 'credit', $totalValue);
            
                $journalEntryCrediteajdataAcc2 = array(
                    'date' => $firstData->date,
                    'document_no' => $firstData->stockin_no,
                    'description' => $firstData->description,
                    'account_id' => $m_eaj_account_id,
                    'libelle' => $firstData->description,
                    'credit' => $totalValue,
                    'balance' => $eajLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_credit_eaj_data_acc2[] = $journalEntryCrediteajdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_credit_eaj_data_acc2);
            
                //end credit operation
        }

        // CAS 2 : immobilisation + mise en service / plan d'amortissement
        if (!empty($immaterialActivations)) {
            $m_eaj_account_id = AccountSetting::where('active',1)->value('m_eaj_account_id');
            if (empty($m_eaj_account_id)) {
                DB::rollback();
                session()->flash('error', __('messages.approval_accounts_required'));
                return redirect()->back();
            }
            $insertImmobilisationJournal = [];
            $approvalDate = \Carbon\Carbon::now();
            foreach ($immaterialActivations as $row) {
                $mat = $row['material'];
                $detail = $row['detail'];
                $amount = (float) $row['amount'];
                if ($amount <= 0) {
                    continue;
                }
                try {
                    $debitAccountId = $orchestrator->resolveStockinDebitAccountId($mat, null);
                } catch (\Throwable $ex) {
                    DB::rollback();
                    session()->flash('error', $ex->getMessage());
                    return redirect()->back();
                }

                $debitLedger = $this->updateLedgerTotals($debitAccountId, 'debit', $amount);
                $creditLedger = $this->updateLedgerTotals((int) $m_eaj_account_id, 'credit', $amount);

                $insertImmobilisationJournal[] = [
                    'date' => $detail->date,
                    'document_no' => $detail->stockin_no,
                    'description' => $detail->description,
                    'account_id' => $debitAccountId,
                    'libelle' => ($detail->description ?? '') . ' - Immobilisation ' . $mat->name,
                    'debit' => $amount,
                    'credit' => 0,
                    'balance' => $debitLedger['balance'],
                    'created_at' => \Carbon\Carbon::now(),
                ];
                $insertImmobilisationJournal[] = [
                    'date' => $detail->date,
                    'document_no' => $detail->stockin_no,
                    'description' => $detail->description,
                    'account_id' => (int) $m_eaj_account_id,
                    'libelle' => ($detail->description ?? '') . ' - Immobilisation ' . $mat->name,
                    'debit' => 0,
                    'credit' => $amount,
                    'balance' => $creditLedger['balance'],
                    'created_at' => \Carbon\Carbon::now(),
                ];

                // CAS 2 : service_start_date = date d'approbation + plan linéaire immédiat
                $orchestrator->activateOnApprovedEntry($mat, $approvalDate, $amount);
            }
            if (!empty($insertImmobilisationJournal)) {
                AccountJournalEntryDetail::insert($insertImmobilisationJournal);
            }
        }

        MaterialStockin::where('stockin_no', '=', $stockin_no)
                        ->update(['status' => 4,'approuved_by' => $this->user->name]);
        MaterialStockinDetail::where('stockin_no', '=', $stockin_no)
                        ->update(['status' => 4,'approuved_by' => $this->user->name]);

        DB::commit();
            $successMessage = 'Stockin has been done successfully!';
            if (!empty($reportBigStoreData)) {
                $code_store = !empty($reportBigStoreData[0]['code_store']) ? $reportBigStoreData[0]['code_store'] : '';
                $successMessage .= ' Stock: ' . $code_store;
            }
            if (!empty($reportExtraBigStoreData)) {
                $code_store = !empty($reportExtraBigStoreData[0]['code_store']) ? $reportExtraBigStoreData[0]['code_store'] : '';
                $successMessage .= ' Extra Big Stock: ' . $code_store;
            }
            if (!empty($reportSmallStoreData)) {
                $code_store = !empty($reportSmallStoreData[0]['code_store']) ? $reportSmallStoreData[0]['code_store'] : '';
                $successMessage .= ' Small Stock: ' . $code_store;
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
     * @param  int  $stockin_no
     * @return \Illuminate\Http\Response
     */
    public function destroy($stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('material_stockin.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any stockin !');
        }

        $stockin = MaterialStockin::where('stockin_no',$stockin_no)->first();
        
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
            MaterialStockinDetail::where('stockin_no',$stockin_no)->delete();
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
