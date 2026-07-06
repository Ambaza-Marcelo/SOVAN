<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesStockDocumentApproval;
use App\Http\Controllers\Backend\Concerns\UpdatesLedgerTotals;
use App\Services\Stock\StockCumpCalculator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Storage;
use App\Models\RawMaterial;
use App\Models\RawMaterialReception;
use App\Models\RawMaterialReceptionDetail;
use App\Models\RawMaterialBigStoreDetail;
use App\Models\RawMaterialSmallStoreDetail;
use App\Models\RawMaterialBigStore;
use App\Models\RawMaterialExtraBigStore;
use App\Models\RawMaterialExtraBigStoreDetail;
use App\Models\RawMaterialSupplierOrderDetail;
use App\Models\RawMaterialSupplierOrder;
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterialPurchaseDetail;
use App\Models\RawMaterialBigReport;
use App\Models\Supplier;
use App\Models\RawMaterialExtraBigReport;
use Carbon\Carbon;
use App\Models\Accounting\AccountCostCenter;
use App\Models\Accounting\AccountJournalEntry;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountSetting;
use App\Models\Accounting\CashOutFlowRequest;
use App\Models\Accounting\CashOutFlowRequestDetail;
use App\Exports\RawMaterialReceptionExport;
use PDF;
use Validator;
use Excel;
use Mail;

class RawMaterialReceptionController extends Controller
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
        if (is_null($this->user) || !$this->user->can('raw_material_reception.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any reception !');
        }
        $query = RawMaterialReception::orderBy('id','desc');
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('date', [$dateDebut, $dateFin]);
        } else {
            $query->take(100);
        }
        $receptions = $query->get();
        return view('backend.pages.raw_material_reception.index', compact('receptions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($order_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_reception.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any reception !');
        }

        $raw_materials  = RawMaterial::orderBy('name','asc')->get();
        $destination_stores = RawMaterialBigStore::all();
        $suppliers = Supplier::all();
        $data = RawMaterialSupplierOrder::where('order_no', $order_no)->first();
        if (!$data) {
            abort(404, 'Commande fournisseur introuvable.');
        }
        if ($data->status == -5) {
            $reception = RawMaterialReception::where('order_no',$order_no)->orderBy('reception_no','desc')->first();
            $datas = $reception
                ? RawMaterialReceptionDetail::where('reception_no', $reception->reception_no)->get()
                : collect();
        }elseif ($data->status == 4) {
            $datas = RawMaterialSupplierOrderDetail::where('order_no', $order_no)->get();
        }else{
            abort(403, 'Sorry !! You are Unauthorized!');
        }

        return view('backend.pages.raw_material_reception.create', compact('raw_materials','order_no','datas','destination_stores','suppliers','data'));
    }

    public function createWithoutRemaining($order_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_reception.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any reception !');
        }

        $raw_materials  = RawMaterial::where('store_type','!=',2)->orderBy('name','asc')->get();
        $raw_material_medium_stores = RawMaterialBigStore::all();
        $raw_material_big_stores = RawMaterialExtraBigStore::all();
        $suppliers = Supplier::orderBy('supplier_name','asc')->get();
        $data = RawMaterialSupplierOrderDetail::where('order_no', $order_no)->first();
        $datas = RawMaterialSupplierOrderDetail::where('order_no', $order_no)->get();
        return view('backend.pages.raw_material_reception.create_without', compact('raw_materials','order_no','datas','data','raw_material_medium_stores','raw_material_big_stores','suppliers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('raw_material_reception.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any reception !');
        }

        $rules = array(
                'r_material_id.*'  => 'required',
                'date'  => 'required',
                'quantity_ordered.*'  => 'required',
                'purchase_price.*'  => 'required',
                'quantity_received.*'  => 'required',
                'order_no'  => 'required',
                'invoice_no'  => 'required',
                'receptionist'  => 'required',
                'vat_supplier_payer'  => 'required',
                'type_reception'  => 'required',
                //'vat_rate'  => 'required',
                'invoice_currency'  => 'required',
                'type_store'  => 'required',
                'description'  => 'required|max:490'
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $r_material_id = $request->r_material_id ?? $request->food_id;
            $date = $request->date;
            $vat_supplier_payer = $request->vat_supplier_payer;
            $origin_store_id = $request->origin_store_id;
            $invoice_currency = $request->invoice_currency;
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $type_store = $request->type_store;
            $vat_rate = $request->vat_rate;
            $type_reception = $request->type_reception;
            $order_no = $request->order_no;
            $invoice_no = $request->invoice_no;
            $description =$request->description; 
            $destination_store_id = $request->destination_store_id;
            $dest_extra_store_id = $request->dest_extra_store_id;
            $quantity_ordered = $request->quantity_ordered;
            $purchase_price = $request->purchase_price;
            $selling_price = $request->selling_price;
            $quantity_received = $request->quantity_received;
            $supplier_id = $request->supplier_id;


            $order = RawMaterialSupplierOrder::where('order_no',$order_no)->first();
            

            $latest = RawMaterialReception::orderBy('id','desc')->first();
            if ($latest) {
               $reception_no = 'RM-RN' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $reception_no = 'RM-RN' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $created_by = $this->user->name;

            $reception_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$reception_no;


            for( $count = 0; $count < count($r_material_id); $count++ ){
                if($vat_supplier_payer == 1){
                    $price_nvat = ($purchase_price[$count]*$quantity_received[$count]);
                    $vat = ($price_nvat* $vat_rate)/100;
                    $price_wvat = $price_nvat + $vat;
                    $total_amount_purchase = $price_wvat; 

                }else{
                    $price_nvat = ($purchase_price[$count]*$quantity_received[$count]);
                    $vat = 0;
                    $price_wvat = $price_nvat + $vat;
                    $total_amount_purchase = $price_wvat;
                }
                
                $total_amount_ordered = $quantity_ordered[$count] * $purchase_price[$count];
                $total_amount_received = $total_amount_purchase;


                if ($order->status == -5) {
                    $total_quantity_received = DB::table('raw_material_reception_details')
                    ->where('order_no', '=', $order_no)
                    ->sum('quantity_received');

                    $quantity_remaining = $quantity_ordered[$count] - ($total_quantity_received + $quantity_received[$count]);

                }else{
                    $quantity_remaining = $quantity_ordered[$count] - $quantity_received[$count];
                }

                if ($quantity_remaining > 0) {
                    $type_reception = 0;
                }else{
                    $type_reception = 1;
                }

                $data = array(
                    'r_material_id' => $r_material_id[$count],
                    'date' => $date,
                    'quantity_ordered' => $quantity_ordered[$count],
                    'quantity_received' => $quantity_received[$count],
                    'quantity_remaining' => $quantity_remaining,
                    'vat_rate' => $vat_rate,
                    'purchase_price' => $purchase_price[$count],
                    'type_reception' => $type_reception,
                    'total_amount_ordered' => $total_amount_ordered,
                    'total_amount_received' => $total_amount_received,
                    'total_amount_purchase' => $total_amount_purchase,
                    'order_no' => $order_no,
                    'invoice_no' => $invoice_no,
                    'type_store' => $type_store,
                    'invoice_currency' => $invoice_currency,
                    'vat' => $vat,
                    'price_nvat' => $price_nvat,
                    'price_wvat' => $price_wvat,
                    'supplier_id' => $supplier_id,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'receptionist' => $receptionist,
                    'handingover' => $handingover,
                    'destination_store_id' => $destination_store_id,
                    'dest_extra_store_id' => $dest_extra_store_id,
                    'reception_no' => $reception_no,
                    'reception_signature' => $reception_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }
            RawMaterialReceptionDetail::insert($insert_data);


            //create reception
            $reception = new RawMaterialReception();
            $reception->date = $date;
            $reception->reception_no = $reception_no;
            $reception->reception_signature = $reception_signature;
            $reception->order_no = $order_no;
            $reception->vat_supplier_payer = $vat_supplier_payer;
            $reception->invoice_no = $invoice_no;
            $reception->type_store = $type_store;
            $reception->invoice_currency = $invoice_currency;
            $reception->receptionist = $receptionist;
            $reception->vat_rate = $vat_rate;
            $reception->type_reception = $type_reception;
            $reception->handingover = $handingover;
            $reception->supplier_id = $supplier_id;
            $reception->destination_store_id = $destination_store_id;
            $reception->dest_extra_store_id = $dest_extra_store_id;
            $reception->created_by = $created_by;
            $reception->status = 1;
            $reception->description = $description;
            $reception->created_at = \Carbon\Carbon::now();
            $reception->save();

            DB::commit();
            session()->flash('success', 'reception has been created !!');
            return redirect()->route('admin.raw-material-receptions.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
            
    }

    public function storeWithoutRemaining(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('raw_material_reception.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any reception !');
        }

        $rules = array(
                'r_material_id.*'  => 'required',
                'date'  => 'required',
                'quantity_ordered.*'  => 'required',
                'purchase_price.*'  => 'required',
                'quantity_received.*'  => 'required',
                'order_no'  => 'required',
                'invoice_no'  => 'required',
                'receptionist'  => 'required',
                'vat_supplier_payer'  => 'required',
                'type_reception'  => 'required',
                'invoice_currency'  => 'required',
                'type_store'  => 'required',
                'description'  => 'required|max:490'
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $r_material_id = $request->r_material_id ?? $request->food_id;
            $date = $request->date;
            $vat_supplier_payer = $request->vat_supplier_payer;
            $origin_store_id = $request->origin_store_id;
            $invoice_currency = $request->invoice_currency;
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $type_store = $request->type_store;
            $vat_rate = $request->vat_rate;
            $type_reception = $request->type_reception;
            $order_no = $request->order_no;
            $invoice_no = $request->invoice_no;
            $description =$request->description; 
            $destination_store_id = $request->destination_store_id;
            $dest_extra_store_id = $request->dest_extra_store_id;
            $quantity_ordered = $request->quantity_ordered;
            $purchase_price = $request->purchase_price;
            $selling_price = $request->selling_price;
            $quantity_received = $request->quantity_received;
            $supplier_id = $request->supplier_id;
            

            $latest = RawMaterialReception::orderBy('id','desc')->first();
            if ($latest) {
               $reception_no = 'RM-RN' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $reception_no = 'RM-RN' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $created_by = $this->user->name;

            $reception_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$reception_no;


            for( $count = 0; $count < count($r_material_id); $count++ ){
                if($vat_supplier_payer == 1){
                    $price_nvat = ($purchase_price[$count]*$quantity_received[$count]);
                    $vat = ($price_nvat* $vat_rate)/100;
                    $price_wvat = $price_nvat + $vat;
                    $total_amount_purchase = $price_wvat; 

                }else{
                    $price_nvat = ($purchase_price[$count]*$quantity_received[$count]);
                    $vat = 0;
                    $price_wvat = $price_nvat + $vat;
                    $total_amount_purchase = $price_wvat;
                }

                $total_amount_ordered = $quantity_ordered[$count] * $purchase_price[$count];
                $total_amount_received = $total_amount_purchase;

                $data = array(
                    'r_material_id' => $r_material_id[$count],
                    'date' => $date,
                    'quantity_ordered' => $quantity_ordered[$count],
                    'quantity_received' => $quantity_received[$count],
                    'quantity_remaining' => $quantity_received[$count] - $quantity_ordered[$count],
                    'vat_rate' => $vat_rate,
                    'purchase_price' => $purchase_price[$count],
                    'type_reception' => $type_reception,
                    'total_amount_ordered' => $total_amount_ordered,
                    'total_amount_received' => $total_amount_received,
                    'total_amount_purchase' => $total_amount_purchase,
                    'order_no' => $order_no,
                    'invoice_no' => $invoice_no,
                    'type_store' => $type_store,
                    'invoice_currency' => $invoice_currency,
                    'vat' => $vat,
                    'price_nvat' => $price_nvat,
                    'price_wvat' => $price_wvat,
                    'supplier_id' => $supplier_id,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'receptionist' => $receptionist,
                    'handingover' => $handingover,
                    'destination_store_id' => $destination_store_id,
                    'dest_extra_store_id' => $dest_extra_store_id,
                    'reception_no' => $reception_no,
                    'reception_signature' => $reception_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }
            RawMaterialReceptionDetail::insert($insert_data);


            //create reception
            $reception = new RawMaterialReception();
            $reception->date = $date;
            $reception->reception_no = $reception_no;
            $reception->reception_signature = $reception_signature;
            $reception->order_no = $order_no;
            $reception->vat_supplier_payer = $vat_supplier_payer;
            $reception->invoice_no = $invoice_no;
            $reception->invoice_currency = $invoice_currency;
            $reception->receptionist = $receptionist;
            $reception->type_store = $type_store;
            $reception->vat_rate = $vat_rate;
            $reception->type_reception = $type_reception;
            $reception->handingover = $handingover;
            $reception->supplier_id = $supplier_id;
            $reception->destination_store_id = $destination_store_id;
            $reception->dest_extra_store_id = $dest_extra_store_id;
            $reception->created_by = $created_by;
            $reception->status = 1;
            $reception->description = $description;
            $reception->created_at = \Carbon\Carbon::now();
            $reception->save();

            RawMaterialSupplierOrder::where('order_no', '=', $order_no)
                ->update(['flag1' => 1]);
            RawMaterialSupplierOrderDetail::where('order_no', '=', $order_no)
                ->update(['flag1' => 1]);

            DB::commit();
            session()->flash('success', 'reception has been created !!');
            return redirect()->route('admin.raw-material-receptions.index');
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
    public function show($reception_no)
    {
        //
        $reception = RawMaterialReception::with(['supplier', 'destinationStore', 'destinationExtraStore'])->where('reception_no', $reception_no)->firstOrFail();
        $receptionDetails = RawMaterialReceptionDetail::with(['rawMaterial.rawMaterialMeasurement', 'supplier', 'destinationStore', 'destinationExtraStore'])
            ->where('reception_no', $reception_no)->get();
        
        // Calculate totals
        $totalQuantityOrdered = $receptionDetails->sum('quantity_ordered');
        $totalQuantityReceived = $receptionDetails->sum('quantity_received');
        $totalAmountReceived = $receptionDetails->sum('total_amount_received');

        $suppliers = Supplier::orderBy('supplier_name', 'asc')->get();
        
        return view('backend.pages.raw_material_reception.show', compact('reception', 'receptionDetails', 'totalQuantityOrdered', 'totalQuantityReceived', 'totalAmountReceived', 'suppliers'));
    }

    /**
     * Update only the supplier for the reception (permission: supplier.edit).
     */
    public function updateSupplier(Request $request, $reception_no)
    {
        if (is_null($this->user) || !$this->user->can('supplier.edit')) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier le fournisseur.');
        }
        $request->validate(['supplier_id' => 'required|exists:suppliers,id']);
        $reception = RawMaterialReception::where('reception_no', $reception_no)->firstOrFail();
        $reception->update(['supplier_id' => $request->supplier_id]);
        return redirect()->route('admin.raw-material-receptions.show', $reception_no)->with('success', __('messages.supplier_updated'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($order_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_reception.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any reception !');
        }

        $reception_no = RawMaterialReception::where('order_no',$order_no)->value('reception_no');

        $raw_materials  = RawMaterial::where('store_type','!=',2)->orderBy('name','asc')->get();
        $raw_material_medium_stores = RawMaterialBigStore::all();
        $raw_material_big_stores = RawMaterialExtraBigStore::all();
        $suppliers = Supplier::orderBy('supplier_name','asc')->get();
        $data = RawMaterialSupplierOrderDetail::where('order_no', $order_no)->first();
        $datas = RawMaterialSupplierOrderDetail::where('order_no', $order_no)->get();
        return view('backend.pages.raw_material_reception.edit', compact('raw_materials','order_no','reception_no','datas','data','raw_material_big_stores','raw_material_medium_stores','suppliers'));
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $reception_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_reception.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any reception !');
        }

        $reception = RawMaterialReception::where('reception_no',$reception_no)->first();

        $rules = array(
                'r_material_id.*'  => 'required',
                'date'  => 'required',
                'quantity_ordered.*'  => 'required',
                'purchase_price.*'  => 'required',
                'quantity_received.*'  => 'required',
                'order_no'  => 'required',
                'invoice_no'  => 'required',
                'receptionist'  => 'required',
                'vat_supplier_payer'  => 'required',
                'type_reception'  => 'required',
                'invoice_currency'  => 'required',
                'type_store'  => 'required',
                'description'  => 'required|max:490'
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $r_material_id = $request->r_material_id ?? $request->food_id;
            $date = $request->date;
            $vat_supplier_payer = $request->vat_supplier_payer;
            $origin_store_id = $request->origin_store_id;
            $invoice_currency = $request->invoice_currency;
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $type_store = $request->type_store;
            $vat_rate = $request->vat_rate;
            $type_reception = $request->type_reception;
            $order_no = $request->order_no;
            $invoice_no = $request->invoice_no;
            $description =$request->description; 
            $destination_store_id = $request->destination_store_id;
            $dest_extra_store_id = $request->dest_extra_store_id;
            $quantity_ordered = $request->quantity_ordered;
            $purchase_price = $request->purchase_price;
            $selling_price = $request->selling_price;
            $quantity_received = $request->quantity_received;
            $supplier_id = $request->supplier_id;
            

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $created_by = $this->user->name;

            $reception_signature = RawMaterialReception::where('reception_no',$reception_no)->value('reception_signature');

            for( $count = 0; $count < count($r_material_id); $count++ ){
                if($vat_supplier_payer == 1){
                    $price_nvat = ($purchase_price[$count]*$quantity_received[$count]);
                    $vat = ($price_nvat* $vat_rate)/100;
                    $price_wvat = $price_nvat + $vat;
                    $total_amount_purchase = $price_wvat; 

                }else{
                    $price_nvat = ($purchase_price[$count]*$quantity_received[$count]);
                    $vat = 0;
                    $price_wvat = $price_nvat + $vat;
                    $total_amount_purchase = $price_wvat;
                }

                $total_amount_ordered = $quantity_ordered[$count] * $purchase_price[$count];
                $total_amount_received = $total_amount_purchase;

                $data = array(
                    'r_material_id' => $r_material_id[$count],
                    'date' => $date,
                    'quantity_ordered' => $quantity_ordered[$count],
                    'quantity_received' => $quantity_received[$count],
                    'quantity_remaining' => $quantity_received[$count] - $quantity_ordered[$count],
                    'vat_rate' => $vat_rate,
                    'purchase_price' => $purchase_price[$count],
                    'type_reception' => $type_reception,
                    'total_amount_ordered' => $total_amount_ordered,
                    'total_amount_received' => $total_amount_received,
                    'total_amount_purchase' => $total_amount_purchase,
                    'order_no' => $order_no,
                    'invoice_no' => $invoice_no,
                    'type_store' => $type_store,
                    'invoice_currency' => $invoice_currency,
                    'vat' => $vat,
                    'price_nvat' => $price_nvat,
                    'price_wvat' => $price_wvat,
                    'supplier_id' => $supplier_id,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'receptionist' => $receptionist,
                    'handingover' => $handingover,
                    'destination_store_id' => $destination_store_id,
                    'dest_extra_store_id' => $dest_extra_store_id,
                    'reception_no' => $reception_no,
                    'reception_signature' => $reception_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }

            RawMaterialReceptionDetail::where('reception_no',$reception_no)->delete();

            RawMaterialReceptionDetail::insert($insert_data);


            //edit reception
            $reception->date = $date;
            $reception->reception_no = $reception_no;
            $reception->reception_signature = $reception_signature;
            $reception->order_no = $order_no;
            $reception->vat_supplier_payer = $vat_supplier_payer;
            $reception->invoice_no = $invoice_no;
            $reception->invoice_currency = $invoice_currency;
            $reception->receptionist = $receptionist;
            $reception->type_store = $type_store;
            $reception->vat_rate = $vat_rate;
            $reception->type_reception = $type_reception;
            $reception->handingover = $handingover;
            $reception->supplier_id = $supplier_id;
            $reception->destination_store_id = $destination_store_id;
            $reception->dest_extra_store_id = $dest_extra_store_id;
            $reception->created_by = $created_by;
            $reception->status = 1;
            $reception->description = $description;
            $reception->created_at = \Carbon\Carbon::now();
            $reception->save();

            DB::commit();
            session()->flash('success', 'reception has been created !!');
            return redirect()->route('admin.raw-material-receptions.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

        
    }

    public function fiche_reception($reception_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_reception.create')) {
            abort(403, 'Sorry !! You are Unauthorized to print handover document!');
        }
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $code = RawMaterialReception::where('reception_no', $reception_no)->value('reception_no');
        $datas = RawMaterialReceptionDetail::where('reception_no', $reception_no)->get();
        $receptionniste = RawMaterialReception::where('reception_no', $reception_no)->value('receptionist');
        $description = RawMaterialReception::where('reception_no', $reception_no)->value('description');
        $supplier = RawMaterialReception::where('reception_no', $reception_no)->first();
        $data = RawMaterialReception::where('reception_no', $reception_no)->first();
        $invoice_no = RawMaterialReception::where('reception_no', $reception_no)->value('invoice_no');
        $invoice_currency = RawMaterialReception::where('reception_no', $reception_no)->value('invoice_currency');
        $reception_signature = RawMaterialReception::where('reception_no', $reception_no)->value('reception_signature');
        $date = RawMaterialReception::where('reception_no', $reception_no)->value('date');
        $totalValue = DB::table('raw_material_reception_details')
            ->where('reception_no', '=', $reception_no)
            ->sum('total_amount_purchase');
        $price_nvat = DB::table('raw_material_reception_details')
            ->where('reception_no', '=', $reception_no)
            ->sum('price_nvat');
        $vat = DB::table('raw_material_reception_details')
            ->where('reception_no', '=', $reception_no)
            ->sum('vat');
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.fiche_reception_matieres_premieres', compact('datas','code','totalValue','receptionniste','description','supplier','data','invoice_no','setting','date','reception_signature','invoice_currency','price_nvat','vat'), [], 'UTF-8');

        Storage::put('public/pdf/fiche_reception_matieres_premieres/'.$reception_no.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('FICHE DE RECEPTION DES MATIÈRES PREMIÈRES '.$reception_no.'.pdf');
        
    }

    public function print($reception_no) { return $this->fiche_reception($reception_no); }

    public function validateReception($reception_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_reception.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any reception !');
        }

        $reception = RawMaterialReception::where('reception_no', $reception_no)->first();
        if (!$reception) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentValidate($reception)) {
            return $response;
        }

        try {DB::beginTransaction();

            RawMaterialReception::where('reception_no', '=', $reception_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            RawMaterialReceptionDetail::where('reception_no', '=', $reception_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'reception has been validated !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function reject($reception_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_reception.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any reception !');
        }

        try {DB::beginTransaction();

        RawMaterialReception::where('reception_no', '=', $reception_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);
        RawMaterialReceptionDetail::where('reception_no', '=', $reception_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Reception has been rejected !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function reset($reception_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_reception.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any reception !');
        }

        try {DB::beginTransaction();

        RawMaterialReception::where('reception_no', '=', $reception_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
        RawMaterialReceptionDetail::where('reception_no', '=', $reception_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Reception has been reseted !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function confirm($reception_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_reception.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any reception !');
        }

        $reception = RawMaterialReception::where('reception_no', $reception_no)->first();
        if (!$reception) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentConfirm($reception)) {
            return $response;
        }

        try {DB::beginTransaction();

        RawMaterialReception::where('reception_no', '=', $reception_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            RawMaterialReceptionDetail::where('reception_no', '=', $reception_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Reception has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
    }

    public function approuve($reception_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_reception.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any reception !');
        }

        $receptionHeader = RawMaterialReception::where('reception_no', $reception_no)->first();
        if (!$receptionHeader) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentApprove($receptionHeader)) {
            return $response;
        }

        try {DB::beginTransaction();

        $reception = RawMaterialReception::where('reception_no',$reception_no)->first();
        if (!$reception) {
            DB::rollback();
            session()->flash('error', __('messages.approval_accounts_required'));
            return back();
        }
        $storeAccountId = RawMaterialBigStore::where('id', $reception->destination_store_id)->value('default_account_id');
        $accountSetting = AccountSetting::where('active', 1)->first();
        if (!$storeAccountId || !$accountSetting) {
            DB::rollback();
            session()->flash('error', __('messages.approval_accounts_required'));
            return back();
        }

        $datas = RawMaterialReceptionDetail::where('reception_no', $reception_no)->get();

        $flag_md = 0;
        $flag_big = 0;

        foreach($datas as $data){

                if (!empty($data->destination_store_id)) {
                    $code_store_destination = RawMaterialBigStore::where('id',$data->destination_store_id)->value('code');

                $valeurStockInitialDestination = RawMaterialBigStoreDetail::where('code',$code_store_destination)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('total_cump_value');
                $quantityStockInitialDestination = RawMaterialBigStoreDetail::where('code',$code_store_destination)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('quantity');
                
                // Handle null values
                $valeurStockInitialDestination = $valeurStockInitialDestination ?? 0;
                $quantityStockInitialDestination = $quantityStockInitialDestination ?? 0;
                
                $cumpResult = StockCumpCalculator::weightedAverageOnStockIn(
                    (float) $quantityStockInitialDestination,
                    (float) $valeurStockInitialDestination,
                    (float) $data->quantity_received,
                    (float) $data->purchase_price,
                    (float) $data->purchase_price
                );
                $cump = $cumpResult['cump'];
                $quantityTotalBigStore = $cumpResult['quantity_total'];

                // Calculate stock final (after adding)
                $quantityStockFinal = $quantityStockInitialDestination + $data->quantity_received;
                $valueStockFinal = $quantityStockFinal * $cump;

                $reportBigStore = array(
                    'r_material_id' => $data->r_material_id,
                    'quantity_stock_initial' => $quantityStockInitialDestination,
                    'value_stock_initial' => $valeurStockInitialDestination,
                    'code_store' => $code_store_destination,
                    'document_no' => $data->reception_no,
                    'date' => $data->date,
                    'quantity_stockin' => $data->quantity_received,
                    'value_stockin' => $data->total_amount_received,
                    'quantity_stock_final' => $quantityStockFinal,
                    'value_stock_final' => $valueStockFinal,
                    'cump' => $cump,
                    'type_transaction' => 'ACHATS',
                    'description' => $data->description ?? $reception->description,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportBigStoreData[] = $reportBigStore;

                    $bigStore = array(
                        'r_material_id' => $data->r_material_id,
                        'quantity' => $quantityTotalBigStore,
                        'total_purchase_value' => $quantityTotalBigStore * $data->purchase_price,
                        'cump' => $cump,
                        'total_cump_value' => $quantityTotalBigStore * $cump,
                        'updated_by' => $this->user->name,
                        'verified' => false
                    );

                    $bigStoreData[] = $bigStore;

                    $foodData = array(
                        'id' => $data->r_material_id,
                        'quantity' => $quantityTotalBigStore,
                        'cump' => $cump
                    );

                    RawMaterial::where('id',$data->r_material_id)
                        ->update($foodData);

                        $raw_material = RawMaterialBigStoreDetail::where('code',$code_store_destination)->where("r_material_id",$data->r_material_id)->value('r_material_id');

                        if (!empty($raw_material)) {
                            RawMaterialBigStoreDetail::where('code',$code_store_destination)->where('r_material_id',$data->r_material_id)
                        ->update($bigStore);
                        $flag_md = 1;
                        }else{
                            $flag_md = 0;
                            session()->flash('error', 'this item is not saved in the stock');
                            return back();
                        }


                        if ($data->purchase_no) {
                            RawMaterialPurchase::where('purchase_no', '=', $data->purchase_no)
                            ->update(['status' => 6]);
                            RawMaterialPurchaseDetail::where('purchase_no', '=', $data->purchase_no)
                            ->update(['status' => 6]);

                        }else{
                            RawMaterialSupplierOrder::where('order_no', '=', $data->order_no)
                            ->update(['status' => 5]);
                            RawMaterialSupplierOrderDetail::where('order_no', '=', $data->order_no)
                            ->update(['status' => 5]);
                        }


                }elseif(!empty($data->dest_extra_store_id)){
                    $code_store_destination = RawMaterialExtraBigStore::where('id',$data->dest_extra_store_id)->value('code');

                $valeurStockInitialDestination = RawMaterialExtraBigStoreDetail::where('code',$code_store_destination)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('total_cump_value');
                $quantityStockInitialDestination = RawMaterialExtraBigStoreDetail::where('code',$code_store_destination)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('quantity');
                
                // Handle null values
                $valeurStockInitialDestination = $valeurStockInitialDestination ?? 0;
                $quantityStockInitialDestination = $quantityStockInitialDestination ?? 0;
                
                $cumpResult = StockCumpCalculator::weightedAverageOnStockIn(
                    (float) $quantityStockInitialDestination,
                    (float) $valeurStockInitialDestination,
                    (float) $data->quantity_received,
                    (float) $data->purchase_price,
                    (float) $data->purchase_price
                );
                $cump = $cumpResult['cump'];
                $quantityTotalBigStore = $cumpResult['quantity_total'];
                
                // Calculate stock final (after adding)
                $quantityStockFinal = $quantityStockInitialDestination + $data->quantity_received;
                $valueStockFinal = $quantityStockFinal * $cump;

                $reportBigStore = array(
                    'r_material_id' => $data->r_material_id,
                    'quantity_stock_initial' => $quantityStockInitialDestination,
                    'value_stock_initial' => $valeurStockInitialDestination,
                    'code_store' => $code_store_destination,
                    'date' => $data->date,
                    'quantity_stockin' => $data->quantity_received,
                    'value_stockin' => $data->total_amount_received,
                    'quantity_stock_final' => $quantityStockFinal,
                    'document_no' => $reception_no,
                    'type_transaction' => 'ACHATS',
                    'description' => $data->description ?? $reception->description,
                    'value_stock_final' => $valueStockFinal,
                    'cump' => $cump,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportBigStoreData[] = $reportBigStore;

                    $bigStore = array(
                        'r_material_id' => $data->r_material_id,
                        'quantity' => $quantityTotalBigStore,
                        'total_purchase_value' => $quantityTotalBigStore * $data->purchase_price,
                        'cump' => $cump,
                        'total_cump_value' => $quantityTotalBigStore * $cump,
                        'updated_by' => $this->user->name,
                        'verified' => false
                    );

                    $bigStoreData[] = $bigStore;

                    $foodData = array(
                        'id' => $data->r_material_id,
                        'quantity' => $quantityTotalBigStore,
                        'cump' => $cump
                    );

                    RawMaterial::where('id',$data->r_material_id)
                        ->update($foodData);

                        $raw_material = RawMaterialExtraBigStoreDetail::where('code',$code_store_destination)->where("r_material_id",$data->r_material_id)->value('r_material_id');

                        if (!empty($raw_material)) {
                            RawMaterialExtraBigStoreDetail::where('code',$code_store_destination)->where('r_material_id',$data->r_material_id)
                        ->update($bigStore);
                        $flag_big = 1;
                        }else{
                            $flag_big = 0;
                            session()->flash('error', 'this item is not saved in the stock');
                            return back();
                        }


                        if ($data->purchase_no) {
                            RawMaterialPurchase::where('purchase_no', '=', $data->purchase_no)
                            ->update(['status' => 6]);
                            RawMaterialPurchaseDetail::where('purchase_no', '=', $data->purchase_no)
                            ->update(['status' => 6]);

                        }else{
                            RawMaterialSupplierOrder::where('order_no', '=', $data->order_no)
                            ->update(['status' => 5]);
                            RawMaterialSupplierOrderDetail::where('order_no', '=', $data->order_no)
                            ->update(['status' => 5]);
                        }

                }else{
                    session()->flash('error', 'oops!Something wrong');
                    return back();
                }
  
        }


        if ($flag_md != 0) {
            RawMaterialBigReport::insert($reportBigStoreData);
        }elseif($flag_big != 0){
            RawMaterialExtraBigReport::insert($reportBigStoreData);
        }


        //start accounting features
        
            $totalValue = DB::table('raw_material_reception_details')
            ->where('reception_no', '=', $reception_no)
            ->sum('total_amount_purchase');

            $item_price_nvat = DB::table('raw_material_reception_details')
            ->where('reception_no', '=', $reception_no)
            ->sum('price_nvat');

            $vat = DB::table('raw_material_reception_details')
            ->where('reception_no', '=', $reception_no)
            ->sum('vat');

            $order_no = $reception->order_no;

            $dataCashOutFlow = CashOutFlowRequest::where('purchase_no',$order_no)->first();

            if (!empty($dataCashOutFlow->id)) {
                //start debit operations
            $id_acc_stock = RawMaterialBigStore::where('id',$reception->destination_store_id)->value('default_account_id');

            if (empty($id_acc_stock)) {
                DB::rollback();
                session()->flash('error', 'Default account not found for destination store. Please configure the store account first !!');
                return back();
            }

            $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'debit', $item_price_nvat);


            $journalEntryDebitdata = array(
                    'date' => $reception->date,
                    'document_no' => $reception->reception_no,
                    'description' => $reception->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $reception->description,
                    'debit' => $item_price_nvat,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_debit_data[] = $journalEntryDebitdata;
                
            AccountJournalEntryDetail::insert($insert_journal_debit_data);

            //vat
            if (!empty($vat) && $vat > 0) {
                $vat_deduc_account_id = AccountSetting::where('active',1)->value('vat_deduc_account_id');

                if (empty($vat_deduc_account_id)) {
                    DB::rollback();
                    session()->flash('error', 'VAT deduction account not configured. Please configure account settings first !!');
                    return back();
                }

                $vatLedgerState = $this->updateLedgerTotals($vat_deduc_account_id, 'debit', $vat);
                
                $journalEntryCreditdataAcc2 = array(
                        'date' => $reception->date,
                        'document_no' => $reception->reception_no,
                        'description' => "TVA DEDUCTIBLE",
                        'account_id' => $vat_deduc_account_id,
                        'libelle' => "TVA DEDUCTIBLE",
                        'debit' => $vat,
                        'balance' => $vatLedgerState['balance'],
                        'created_at' => \Carbon\Carbon::now()
                    );
                    $insert_journal_credit_data_acc2[] = $journalEntryCreditdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_credit_data_acc2);
            }
            
            //end debit operation

            //start credit acc_employee operations
            if (empty($dataCashOutFlow->employe) || empty($dataCashOutFlow->employe->cash_out_account_id)) {
                DB::rollback();
                session()->flash('error', 'Employee account not found. Please configure the employee account first !!');
                return back();
            }

            $employeeLedgerState = $this->updateLedgerTotals($dataCashOutFlow->employe->cash_out_account_id, 'credit', $totalValue);

            $journalEntryCreditDataAccEmp = array(
                    'date' => $reception->date,
                    'document_no' => $reception->reception_no,
                    'description' => $reception->description,
                    'account_id' => $dataCashOutFlow->employe->cash_out_account_id,
                    'libelle' => $reception->description,
                    'credit' => $totalValue,
                    'balance' => $employeeLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_credit_dataAccEmp[] = $journalEntryCreditDataAccEmp;

            AccountJournalEntryDetail::insert($insert_journal_credit_dataAccEmp);


            RawMaterialReception::where('reception_no', '=', $reception_no)
                ->update(['flag2' => 1]);
            RawMaterialReceptionDetail::where('reception_no', '=', $reception_no)
                ->update(['flag2' => 1]);

            //end credit operations
            }else{
                //start debit operations
            $id_acc_stock = RawMaterialBigStore::where('id',$reception->destination_store_id)->value('default_account_id');

            if (empty($id_acc_stock)) {
                DB::rollback();
                session()->flash('error', 'Default account not found for destination store. Please configure the store account first !!');
                return back();
            }

            $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'debit', $item_price_nvat);


            $journalEntryDebitdata = array(
                    'date' => $reception->date,
                    'document_no' => $reception->reception_no,
                    'description' => $reception->description,
                    'account_id' => $id_acc_stock,
                    'libelle' => $reception->description,
                    'debit' => $item_price_nvat,
                    'balance' => $stockLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_debit_data[] = $journalEntryDebitdata;
                
            AccountJournalEntryDetail::insert($insert_journal_debit_data);
            
            //vat
            if (!empty($vat) && $vat > 0) {
                $vat_deduc_account_id = AccountSetting::where('active',1)->value('vat_deduc_account_id');

                if (empty($vat_deduc_account_id)) {
                    DB::rollback();
                    session()->flash('error', 'VAT deduction account not configured. Please configure account settings first !!');
                    return back();
                }

                $vatLedgerState = $this->updateLedgerTotals($vat_deduc_account_id, 'debit', $vat);
                
                $journalEntryCreditdataAcc2 = array(
                        'date' => $reception->date,
                        'document_no' => $reception->reception_no,
                        'description' => "TVA DEDUCTIBLE",
                        'account_id' => $vat_deduc_account_id,
                        'libelle' => "TVA DEDUCTIBLE",
                        'debit' => $vat,
                        'balance' => $vatLedgerState['balance'],
                        'created_at' => \Carbon\Carbon::now()
                    );
                    $insert_journal_credit_data_acc2[] = $journalEntryCreditdataAcc2;

                AccountJournalEntryDetail::insert($insert_journal_credit_data_acc2);
            }
            
            //end debit operation

            //start credit operations
            if (empty($reception->supplier) || empty($reception->supplier->account_id)) {
                DB::rollback();
                session()->flash('error', 'Supplier account not found. Please configure the supplier account first !!');
                return back();
            }

            $supplierLedgerState = $this->updateLedgerTotals($reception->supplier->account_id, 'credit', $totalValue);

            $journalEntryCreditdata = array(
                    'date' => $reception->date,
                    'document_no' => $reception->reception_no,
                    'description' => $reception->description,
                    'account_id' => $reception->supplier->account_id,
                    'libelle' => $reception->description,
                    'credit' => $totalValue,
                    'balance' => $supplierLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_credit_data[] = $journalEntryCreditdata;

            AccountJournalEntryDetail::insert($insert_journal_credit_data);

            //end credit operations
            }
        //end accounting features

            $purchase_no = RawMaterialSupplierOrder::where('order_no',$reception->order_no)->value('purchase_no');
        
            if ($reception->type_reception == 0) {
                RawMaterialSupplierOrder::where('order_no', '=', $reception->order_no)
                ->update(['status' => -5]);
                RawMaterialSupplierOrderDetail::where('order_no', '=', $reception->order_no)
                ->update(['status' => -5]);
            }else{
                RawMaterialSupplierOrder::where('order_no', '=', $reception->order_no)
                ->update(['status' => 5]);
                RawMaterialSupplierOrderDetail::where('order_no', '=', $reception->order_no)
                ->update(['status' => 5]);
            }

            RawMaterialPurchase::where('purchase_no', '=', $purchase_no)
            ->update(['status' => 6]);
            RawMaterialPurchaseDetail::where('purchase_no', '=', $purchase_no)
             ->update(['status' => 6]);


            RawMaterialReception::where('reception_no', '=', $reception_no)
                            ->update(['status' => 4,'approuved_by' => $this->user->name]);
            RawMaterialReceptionDetail::where('reception_no', '=', $reception_no)
                            ->update(['status' => 4,'approuved_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Reception has been done successfuly !, to '.$code_store_destination);
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_reception.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export receptions !');
        }
        $dateDebut = $request->input('date_debut') ?? $request->input('start_date');
        $dateFin = $request->input('date_fin') ?? $request->input('end_date');
        if (!$dateDebut || !$dateFin) {
            return redirect()->route('admin.raw-material-receptions.index')
                ->with('error', __('messages.export_select_date_range'));
        }
        $filename = 'receptions_matières_premières_' . Carbon::parse($dateDebut)->format('Y-m-d') . '_' . Carbon::parse($dateFin)->format('Y-m-d') . '.xlsx';
        return Excel::download(new RawMaterialReceptionExport(), $filename);
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $reception_no
     * @return \Illuminate\Http\Response
     */
    public function destroy($reception_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_reception.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any reception !');
        }
        /*
        try {DB::beginTransaction();

        $reception = RawMaterialReception::where('reception_no',$reception_no)->first();
        if (!is_null($reception)) {
            $reception->delete();
            RawMaterialReceptionDetail::where('reception_no',$reception_no)->delete();
        }

        DB::commit();
            session()->flash('success', 'Reception has been deleted !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
        */

    }
}
