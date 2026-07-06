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
use App\Models\Material;
use App\Models\Item;
use App\Models\MaterialReception;
use App\Models\MaterialReceptionDetail;
use App\Models\MaterialBigStoreDetail;
use App\Models\MaterialBigStore;
use App\Models\MaterialExtraBigStore;
use App\Models\MaterialExtraBigStoreDetail;
use App\Models\MaterialSupplierOrderDetail;
use App\Models\MaterialSupplierOrder;
use App\Models\MaterialPurchase;
use App\Models\MaterialPurchaseDetail;
use App\Models\MaterialBigReport;
use App\Models\MaterialExtraBigReport;
use App\Models\FinishedGoodsStore;
use App\Models\GoodsStore;
use App\Models\FinishedGoodsStoreDetail;
use App\Models\GoodsStoreDetail;
use App\Models\FinishedGoodReport;
use App\Models\GoodsStoreReport;
use App\Models\Supplier;
use App\Models\Accounting\AccountCostCenter;
use App\Models\Accounting\AccountJournalEntry;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountSetting;
use App\Models\Accounting\CashOutFlowRequest;
use App\Models\Accounting\CashOutFlowRequestDetail;
use App\Exports\MaterialReceptionExport;
use Carbon\Carbon;
use PDF;
use Validator;
use Excel;
use Mail;

class MaterialReceptionController extends Controller
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
        if (is_null($this->user) || !$this->user->can('material_reception.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any reception !');
        }

        $query = MaterialReception::query();
        
        // Si des dates sont fournies, filtrer par période
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('created_at', [$dateDebut, $dateFin]);
        } else {
            // Par défaut, afficher les 100 lignes les plus récentes
            $query->take(100);
        }
        
        $receptions = $query->with(['destinationStore', 'destinationExtraStore'])
            ->orderBy('id', 'desc')
            ->get();
        return view('backend.pages.material_reception.index', compact('receptions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($order_no)
    {
        if (is_null($this->user) || !$this->user->can('material_reception.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any reception !');
        }

        $materials = Material::where('store_type', '!=', 2)->orderBy('name', 'asc')->get();
        $material_medium_stores = MaterialBigStore::all();
        $material_big_stores = MaterialExtraBigStore::all();
        $goods_stores = GoodsStore::orderBy('name')->get();
        $suppliers = Supplier::all();
        $data = MaterialSupplierOrder::where('order_no', $order_no)->first();
        $reception = null;
        if (!$data) {
            abort(404, 'Commande fournisseur introuvable.');
        }
        if ($data->status == -5) {
            $reception = MaterialReception::where('order_no', $order_no)->orderBy('reception_no', 'desc')->first();
            $datas = $reception
                ? MaterialReceptionDetail::with(['material.materialMeasurement', 'item'])->where('reception_no', $reception->reception_no)->get()
                : collect();
        } elseif ($data->status == 4) {
            $datas = MaterialSupplierOrderDetail::with(['material.materialMeasurement', 'item'])->where('order_no', $order_no)->get();
        } else {
            abort(403, 'Sorry !! You are Unauthorized!');
        }

        return view('backend.pages.material_reception.create', array_merge(
            compact('materials', 'order_no', 'datas', 'suppliers', 'data', 'material_big_stores', 'material_medium_stores', 'goods_stores', 'reception'),
            $this->receptionOrderLineFlags($datas)
        ));
    }

    public function createWithoutRemaining($order_no)
    {
        if (is_null($this->user) || !$this->user->can('material_reception.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any reception !');
        }

        $materials = Material::where('store_type', '!=', 2)->orderBy('name', 'asc')->get();
        $material_medium_stores = MaterialBigStore::all();
        $material_big_stores = MaterialExtraBigStore::all();
        $goods_stores = GoodsStore::orderBy('name')->get();
        $suppliers = Supplier::orderBy('supplier_name', 'asc')->get();
        $data = MaterialSupplierOrderDetail::with(['material.materialMeasurement', 'item'])->where('order_no', $order_no)->first();
        $datas = MaterialSupplierOrderDetail::with(['material.materialMeasurement', 'item'])->where('order_no', $order_no)->get();
        $reception = null;

        return view('backend.pages.material_reception.create_without', array_merge(
            compact('materials', 'order_no', 'datas', 'data', 'material_big_stores', 'suppliers', 'material_medium_stores', 'goods_stores', 'reception'),
            $this->receptionOrderLineFlags($datas)
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('material_reception.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any reception !');
        }

        $orderDetails = MaterialSupplierOrderDetail::where('order_no', $request->order_no)->get();
        $lineFlags = $this->receptionOrderLineFlags($orderDetails);

        $rules = array(
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
                'description'  => 'required|max:490'
            );

            $error = Validator::make($request->all(), $rules);
            $this->validateReceptionDestinations($error, $request, $lineFlags['hasMaterials'], $lineFlags['hasItems']);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $material_id = $request->material_id ?? [];
            $item_id = $request->item_id ?? [];
            $date = $request->date;
            $vat_supplier_payer = $request->vat_supplier_payer;
            $origin_store_id = $request->origin_store_id;
            $invoice_currency = $request->invoice_currency;
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $type_store = $this->resolveReceptionTypeStore($request, $lineFlags);
            $code_store = $lineFlags['hasItems'] ? trim((string) $request->code_store) : null;
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

            $order = MaterialSupplierOrder::where('order_no',$order_no)->first();
            

            $latest = MaterialReception::orderBy('id','desc')->first();
            if ($latest) {
               $reception_no = 'REC' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $reception_no = 'REC' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $created_by = $this->user->name;

            $reception_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$reception_no;


            $rowCount = max(count($material_id), count($item_id), count($quantity_received));
            for( $count = 0; $count < $rowCount; $count++ ){
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
                    $total_quantity_received = DB::table('material_reception_details')
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
                    'material_id' => $material_id[$count] ?? null,
                    'item_id' => $item_id[$count] ?? null,
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
                    'code_store' => $code_store,
                    'reception_no' => $reception_no,
                    'reception_signature' => $reception_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }
            MaterialReceptionDetail::insert($insert_data);


            //create reception
            $reception = new MaterialReception();
            $reception->date = $date;
            $reception->reception_no = $reception_no;
            $reception->reception_signature = $reception_signature;
            $reception->order_no = $order_no;
            $reception->vat_supplier_payer = $vat_supplier_payer;
            $reception->invoice_no = $invoice_no;
            $reception->type_store = $type_store;
            $reception->code_store = $code_store;
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
            return redirect()->route('admin.material-receptions.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
            
    }

    public function storeWithoutRemaining(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('material_reception.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any reception !');
        }

        $orderDetails = MaterialSupplierOrderDetail::where('order_no', $request->order_no)->get();
        $lineFlags = $this->receptionOrderLineFlags($orderDetails);

        $rules = array(
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
                'description'  => 'required|max:490'
            );

            $error = Validator::make($request->all(), $rules);
            $error->after(function ($validator) use ($request) {
                $material_id = $request->material_id ?? [];
                $item_id = $request->item_id ?? [];
                $quantity_received = $request->quantity_received ?? [];
                $n = max(count($material_id), count($item_id), count($quantity_received));
                for ($i = 0; $i < $n; $i++) {
                    $mid = trim((string)($material_id[$i] ?? ''));
                    $iid = trim((string)($item_id[$i] ?? ''));
                    if ($mid === '' && $iid === '') {
                        $validator->errors()->add('material_id.'.$i, __('messages.each_line_must_contain_item_or_material'));
                    }
                }
            });
            $this->validateReceptionDestinations($error, $request, $lineFlags['hasMaterials'], $lineFlags['hasItems']);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $material_id = $request->material_id ?? [];
            $item_id = $request->item_id ?? [];
            $date = $request->date;
            $vat_supplier_payer = $request->vat_supplier_payer;
            $origin_store_id = $request->origin_store_id;
            $invoice_currency = $request->invoice_currency;
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $type_store = $this->resolveReceptionTypeStore($request, $lineFlags);
            $code_store = $lineFlags['hasItems'] ? trim((string) $request->code_store) : null;
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
            

            $latest = MaterialReception::orderBy('id','desc')->first();
            if ($latest) {
               $reception_no = 'M-RN' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $reception_no = 'M-RN' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $created_by = $this->user->name;

            $reception_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$reception_no;


            $rowCountRn = max(count($material_id), count($item_id), count($quantity_received));
            for( $count = 0; $count < $rowCountRn; $count++ ){
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
                    'material_id' => $material_id[$count] ?? null,
                    'item_id' => $item_id[$count] ?? null,
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
                    'code_store' => $code_store,
                    'reception_no' => $reception_no,
                    'reception_signature' => $reception_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }
            MaterialReceptionDetail::insert($insert_data);


            //create reception
            $reception = new MaterialReception();
            $reception->date = $date;
            $reception->reception_no = $reception_no;
            $reception->reception_signature = $reception_signature;
            $reception->order_no = $order_no;
            $reception->vat_supplier_payer = $vat_supplier_payer;
            $reception->invoice_no = $invoice_no;
            $reception->invoice_currency = $invoice_currency;
            $reception->receptionist = $receptionist;
            $reception->type_store = $type_store;
            $reception->code_store = $code_store;
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

            MaterialSupplierOrder::where('order_no', '=', $order_no)
                ->update(['flag1' => 1]);
            MaterialSupplierOrderDetail::where('order_no', '=', $order_no)
                ->update(['flag1' => 1]);

            DB::commit();
            session()->flash('success', 'reception has been created !!');
            return redirect()->route('admin.material-receptions.index');
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
        $reception = MaterialReception::with(['supplier', 'destinationStore', 'destinationExtraStore'])->where('reception_no', $reception_no)->firstOrFail();
        $receptionDetails = MaterialReceptionDetail::with(['material.materialMeasurement', 'item', 'supplier', 'destinationStore', 'destinationExtraStore'])
            ->where('reception_no', $reception_no)->get();
        
        // Calculate totals
        $totalQuantityOrdered = $receptionDetails->sum('quantity_ordered');
        $totalQuantityReceived = $receptionDetails->sum('quantity_received');
        $totalAmountReceived = $receptionDetails->sum('total_amount_received');

        $suppliers = Supplier::orderBy('supplier_name', 'asc')->get();
        
        return view('backend.pages.material_reception.show', compact('reception', 'receptionDetails', 'totalQuantityOrdered', 'totalQuantityReceived', 'totalAmountReceived', 'suppliers'));
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
        $reception = MaterialReception::where('reception_no', $reception_no)->firstOrFail();
        $reception->update(['supplier_id' => $request->supplier_id]);
        return redirect()->route('admin.material-receptions.show', $reception_no)->with('success', __('messages.supplier_updated'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($order_no)
    {
        if (is_null($this->user) || !$this->user->can('material_reception.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any reception !');
        }

        $reception_no = MaterialReception::where('order_no',$order_no)->value('reception_no');

        $materials = Material::where('store_type', '!=', 2)->orderBy('name', 'asc')->get();
        $material_medium_stores = MaterialBigStore::all();
        $material_big_stores = MaterialExtraBigStore::all();
        $goods_stores = GoodsStore::orderBy('name')->get();
        $suppliers = Supplier::orderBy('supplier_name', 'asc')->get();
        $data = MaterialSupplierOrderDetail::with(['material.materialMeasurement', 'item'])->where('order_no', $order_no)->first();
        $datas = MaterialSupplierOrderDetail::with(['material.materialMeasurement', 'item'])->where('order_no', $order_no)->get();
        $reception = MaterialReception::where('reception_no', $reception_no)->first();

        return view('backend.pages.material_reception.edit', array_merge(
            compact('materials', 'order_no', 'reception_no', 'datas', 'data', 'material_big_stores', 'material_medium_stores', 'suppliers', 'goods_stores', 'reception'),
            $this->receptionOrderLineFlags($datas)
        ));
        
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
        if (is_null($this->user) || !$this->user->can('material_reception.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any reception !');
        }

        $reception = MaterialReception::where('reception_no',$reception_no)->first();

        $orderDetails = MaterialSupplierOrderDetail::where('order_no', $request->order_no)->get();
        $lineFlags = $this->receptionOrderLineFlags($orderDetails);

        $rules = array(
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
                'supplier_id'  => 'required',
                'description'  => 'required|max:490'
            );

            $error = Validator::make($request->all(), $rules);
            $error->after(function ($validator) use ($request) {
                $material_id = $request->material_id ?? [];
                $item_id = $request->item_id ?? [];
                $quantity_received = $request->quantity_received ?? [];
                $n = max(count($material_id), count($item_id), count($quantity_received));
                for ($i = 0; $i < $n; $i++) {
                    $mid = trim((string)($material_id[$i] ?? ''));
                    $iid = trim((string)($item_id[$i] ?? ''));
                    if ($mid === '' && $iid === '') {
                        $validator->errors()->add('material_id.'.$i, __('messages.each_line_must_contain_item_or_material'));
                    }
                }
            });
            $this->validateReceptionDestinations($error, $request, $lineFlags['hasMaterials'], $lineFlags['hasItems']);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $material_id = $request->material_id ?? [];
            $item_id = $request->item_id ?? [];
            $date = $request->date;
            $vat_supplier_payer = $request->vat_supplier_payer;
            $origin_store_id = $request->origin_store_id;
            $invoice_currency = $request->invoice_currency;
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $type_store = $this->resolveReceptionTypeStore($request, $lineFlags);
            $code_store = $lineFlags['hasItems'] ? trim((string) $request->code_store) : null;
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

            $reception_signature = MaterialReception::where('reception_no',$reception_no)->value('reception_signature');

            $rowCountUpd = max(count($material_id), count($item_id), count($quantity_received));
            for( $count = 0; $count < $rowCountUpd; $count++ ){
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
                    'material_id' => $material_id[$count] ?? null,
                    'item_id' => $item_id[$count] ?? null,
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
                    'code_store' => $code_store,
                    'reception_no' => $reception_no,
                    'reception_signature' => $reception_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }

            MaterialReceptionDetail::where('reception_no',$reception_no)->delete();

            MaterialReceptionDetail::insert($insert_data);


            //edit reception
            $reception->date = $date;
            $reception->order_no = $order_no;
            $reception->vat_supplier_payer = $vat_supplier_payer;
            $reception->invoice_no = $invoice_no;
            $reception->invoice_currency = $invoice_currency;
            $reception->receptionist = $receptionist;
            $reception->type_store = $type_store;
            $reception->code_store = $code_store;
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
            return redirect()->route('admin.material-receptions.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

        
    }

    public function fiche_reception($reception_no)
    {
        if (is_null($this->user) || !$this->user->can('material_reception.create')) {
            abort(403, 'Sorry !! You are Unauthorized to print handover document!');
        }
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $code = MaterialReception::where('reception_no', $reception_no)->value('reception_no');
        $datas = MaterialReceptionDetail::with(['material', 'item'])->where('reception_no', $reception_no)->get();
        $receptionniste = MaterialReception::where('reception_no', $reception_no)->value('receptionist');
        $description = MaterialReception::where('reception_no', $reception_no)->value('description');
        $data = MaterialReception::with('supplier')->where('reception_no', $reception_no)->first();
        $supplier = $data;
        $invoice_no = MaterialReception::where('reception_no', $reception_no)->value('invoice_no');
        $invoice_currency = MaterialReception::where('reception_no', $reception_no)->value('invoice_currency');
        $reception_signature = MaterialReception::where('reception_no', $reception_no)->value('reception_signature');
        $date = MaterialReception::where('reception_no', $reception_no)->value('date');
        $totalValue = DB::table('material_reception_details')
            ->where('reception_no', '=', $reception_no)
            ->sum('total_amount_purchase');
        $price_nvat = DB::table('material_reception_details')
            ->where('reception_no', '=', $reception_no)
            ->sum('price_nvat');
        $vat = DB::table('material_reception_details')
            ->where('reception_no', '=', $reception_no)
            ->sum('vat');
        $amountInWords = \App\Helpers\NumberToWordsFrench::convert((int) round($totalValue)) . ' francs burundais';
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])
            ->loadView('backend.pages.document.material_reception', compact('datas','code','totalValue','receptionniste','description','supplier','data','invoice_no','setting','date','reception_signature','invoice_currency','price_nvat','vat','amountInWords'), [], 'UTF-8');

        Storage::put('public/pdf/fiche_reception_materiel/'.$reception_no.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('FICHE DE RECEPTION DES MATERIELS '.$reception_no.'.pdf');
        
    }

    public function print($reception_no) { return $this->fiche_reception($reception_no); }

    public function validateReception($reception_no)
    {
       if (is_null($this->user) || !$this->user->can('material_reception.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any reception !');
        }

        $reception = MaterialReception::where('reception_no', $reception_no)->first();
        if (!$reception) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentValidate($reception)) {
            return $response;
        }

        try {DB::beginTransaction();

            MaterialReception::where('reception_no', '=', $reception_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            MaterialReceptionDetail::where('reception_no', '=', $reception_no)
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
       if (is_null($this->user) || !$this->user->can('material_reception.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any reception !');
        }

        try {DB::beginTransaction();

        MaterialReception::where('reception_no', '=', $reception_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);
        MaterialReceptionDetail::where('reception_no', '=', $reception_no)
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
       if (is_null($this->user) || !$this->user->can('material_reception.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any reception !');
        }

        try {DB::beginTransaction();

        MaterialReception::where('reception_no', '=', $reception_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
        MaterialReceptionDetail::where('reception_no', '=', $reception_no)
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
       if (is_null($this->user) || !$this->user->can('material_reception.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any reception !');
        }

        $reception = MaterialReception::where('reception_no', $reception_no)->first();
        if (!$reception) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentConfirm($reception)) {
            return $response;
        }

        try {DB::beginTransaction();

        MaterialReception::where('reception_no', '=', $reception_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            MaterialReceptionDetail::where('reception_no', '=', $reception_no)
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
       if (is_null($this->user) || !$this->user->can('material_reception.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any reception !');
        }

        $receptionHeader = MaterialReception::where('reception_no', $reception_no)->first();
        if (!$receptionHeader) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentApprove($receptionHeader)) {
            return $response;
        }

        try {DB::beginTransaction();

        $reception = MaterialReception::where('reception_no',$reception_no)->first();
        if (!$reception) {
            DB::rollback();
            session()->flash('error', __('messages.approval_accounts_required'));
            return back();
        }
        $accountSetting = AccountSetting::where('active', 1)->first();
        if (!$accountSetting) {
            DB::rollback();
            session()->flash('error', __('messages.approval_accounts_required'));
            return back();
        }

        $datas = MaterialReceptionDetail::where('reception_no', $reception_no)->get();

        // Initialize arrays
        $reportBigStoreData = [];
        $reportExtraBigStoreData = [];
        $reportFinishedGoodData = [];
        $reportGoodsStoreData = [];
        $flag_md = 0;
        $flag_big = 0;
        $total_material_price_nvat = 0;
        $total_item_fg_price_nvat = 0;
        $total_item_goods_price_nvat = 0;

        foreach($datas as $data){

                if (!empty($data->item_id)) {
                    // Article (produit fini ou marchandise) -> stock FG ou Goods + rapport
                    $item = Item::find($data->item_id);
                    if (!$item) {
                        DB::rollback();
                        session()->flash('error', 'Article introuvable (item_id).');
                        return back();
                    }
                    $qtyReceived = (float) $data->quantity_received;
                    $purchasePrice = (float) ($data->purchase_price ?? 0);

                    if ($item->store_type == Item::STORE_TYPE_FINISHED_GOODS) {
                        $store = FinishedGoodsStore::first();
                        if (!$store) {
                            DB::rollback();
                            session()->flash('error', 'Aucun magasin produits finis configuré.');
                            return back();
                        }
                        $code_store = $store->code;
                        $storeDetail = FinishedGoodsStoreDetail::where('code_store', $code_store)->where('item_id', $data->item_id)->first();
                        if (!$storeDetail) {
                            DB::rollback();
                            session()->flash('error', 'Article non enregistré dans le stock produits finis: ' . ($item->name ?? ''));
                            return back();
                        }
                        $quantityStockInitial = (float) ($storeDetail->quantity ?? 0);
                        $valueStockInitial = StockCumpCalculator::initialValueFromStore(
                            $quantityStockInitial,
                            isset($storeDetail->cump) ? (float) $storeDetail->cump : null,
                            isset($storeDetail->total_cump_value) ? (float) $storeDetail->total_cump_value : null
                        );
                        $cumpResult = StockCumpCalculator::weightedAverageOnStockIn(
                            $quantityStockInitial,
                            $valueStockInitial,
                            $qtyReceived,
                            $purchasePrice,
                            $purchasePrice
                        );
                        $newCump = $cumpResult['cump'];
                        $quantityTotal = $cumpResult['quantity_total'];
                        $value_stockin = $qtyReceived * $newCump;
                        $value_stock_final = $quantityTotal * $newCump;

                        $storeDetail->quantity = $quantityTotal;
                        $storeDetail->cump = $newCump;
                        $storeDetail->updated_by = $this->user->name;
                        $storeDetail->save();

                        $reportFinishedGoodData[] = [
                            'code_store' => $code_store,
                            'item_id' => $data->item_id,
                            'quantity_stock_initial' => $quantityStockInitial,
                            'value_stock_initial' => $valueStockInitial,
                            'quantity_stockin' => $qtyReceived,
                            'value_stockin' => $value_stockin,
                            'stock_total' => $quantityTotal,
                            'quantity_stockout' => 0,
                            'value_stockout' => 0,
                            'quantity_stock_final' => $quantityTotal,
                            'value_stock_final' => $value_stock_final,
                            'cump' => $newCump,
                            'date' => $data->date,
                            'created_by' => $this->user->name,
                            'type_transaction' => 'ACHAT',
                            'document_no' => $data->reception_no,
                            'description' => $data->description ?? '',
                            'created_at' => \Carbon\Carbon::now(),
                        ];
                        $total_item_fg_price_nvat += (float) ($data->price_nvat ?? 0);
                    } else {
                        $code_store = trim((string) ($reception->code_store ?? $data->code_store ?? ''));
                        $store = $code_store !== ''
                            ? GoodsStore::where('code', $code_store)->first()
                            : GoodsStore::first();
                        if (!$store) {
                            DB::rollback();
                            session()->flash('error', 'Aucun magasin marchandises configuré.');
                            return back();
                        }
                        $code_store = $store->code;
                        if ($item->code_store && $item->code_store !== $code_store) {
                            DB::rollback();
                            session()->flash('error', __('messages.reception_item_wrong_goods_store', ['name' => $item->name ?? '']));
                            return back();
                        }
                        $storeDetail = GoodsStoreDetail::where('code_store', $code_store)->where('item_id', $data->item_id)->first();
                        if (!$storeDetail) {
                            DB::rollback();
                            session()->flash('error', __('messages.reception_item_not_in_goods_store', ['name' => $item->name ?? '']));
                            return back();
                        }
                        $quantityStockInitial = (float) ($storeDetail->quantity ?? 0);
                        $valueStockInitial = StockCumpCalculator::initialValueFromStore(
                            $quantityStockInitial,
                            isset($storeDetail->cump) ? (float) $storeDetail->cump : null,
                            isset($storeDetail->total_cump_value) ? (float) $storeDetail->total_cump_value : null
                        );
                        $cumpResult = StockCumpCalculator::weightedAverageOnStockIn(
                            $quantityStockInitial,
                            $valueStockInitial,
                            $qtyReceived,
                            $purchasePrice,
                            $purchasePrice
                        );
                        $newCump = $cumpResult['cump'];
                        $quantityTotal = $cumpResult['quantity_total'];
                        $value_stockin = $qtyReceived * $newCump;
                        $value_stock_final = $quantityTotal * $newCump;

                        $storeDetail->quantity = $quantityTotal;
                        $storeDetail->cump = $newCump;
                        $storeDetail->updated_by = $this->user->name;
                        $storeDetail->save();

                        $reportGoodsStoreData[] = [
                            'code_store' => $code_store,
                            'item_id' => $data->item_id,
                            'quantity_stock_initial' => $quantityStockInitial,
                            'value_stock_initial' => $valueStockInitial,
                            'quantity_stockin' => $qtyReceived,
                            'value_stockin' => $value_stockin,
                            'stock_total' => $quantityTotal,
                            'quantity_stockout' => 0,
                            'value_stockout' => 0,
                            'quantity_stock_final' => $quantityTotal,
                            'value_stock_final' => $value_stock_final,
                            'cump' => $newCump,
                            'date' => $data->date,
                            'created_by' => $this->user->name,
                            'type_transaction' => 'ACHAT',
                            'document_no' => $data->reception_no,
                            'description' => $data->description ?? '',
                            'created_at' => \Carbon\Carbon::now(),
                        ];
                        $total_item_goods_price_nvat += (float) ($data->price_nvat ?? 0);
                    }

                    if ($data->purchase_no) {
                        MaterialPurchase::where('purchase_no', '=', $data->purchase_no)->update(['status' => 6]);
                        MaterialPurchaseDetail::where('purchase_no', '=', $data->purchase_no)->update(['status' => 6]);
                    } else {
                        MaterialSupplierOrder::where('order_no', '=', $data->order_no)->update(['status' => 5]);
                        MaterialSupplierOrderDetail::where('order_no', '=', $data->order_no)->update(['status' => 5]);
                    }
                } elseif (!empty($data->destination_store_id)) {
                    $total_material_price_nvat += (float) ($data->price_nvat ?? 0);
                    $code_store_destination = MaterialBigStore::where('id',$data->destination_store_id)->value('code');

                $valeurStockInitialDestination = MaterialBigStoreDetail::where('code',$code_store_destination)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('total_cump_value') ?? 0;
                $quantityStockInitialDestination = MaterialBigStoreDetail::where('code',$code_store_destination)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('quantity') ?? 0;
                $cumpResult = StockCumpCalculator::weightedAverageOnStockIn(
                    (float) $quantityStockInitialDestination,
                    (float) $valeurStockInitialDestination,
                    (float) $data->quantity_received,
                    (float) $data->purchase_price,
                    (float) $data->purchase_price
                );
                $cump = $cumpResult['cump'];
                $quantityTotalBigStore = $cumpResult['quantity_total'];

                // Calculate value_stockin using cump
                $value_stockin = $data->quantity_received * $cump;
                
                // Calculate value_stock_final using cump
                $quantity_stock_final = $quantityStockInitialDestination + $data->quantity_received;
                $value_stock_final = $quantity_stock_final * $cump;

                $reportBigStore = array(
                    'material_id' => $data->material_id,
                    'quantity_stock_initial' => $quantityStockInitialDestination,
                    'value_stock_initial' => $valeurStockInitialDestination,
                    'code_store' => $code_store_destination,
                    'date' => $data->date,
                    'quantity_stockin' => $data->quantity_received,
                    'value_stockin' => $value_stockin,
                    'quantity_stock_final' => $quantity_stock_final,
                    'value_stock_final' => $value_stock_final,
                    'type_transaction' => 'ACHAT',
                    'cump' => $cump,
                    'document_no' => $data->reception_no,
                    'created_by' => $this->user->name,
                    'description' => $data->description,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportBigStoreData[] = $reportBigStore;

                    $bigStore = array(
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
                        ->update($bigStore);
                        $flag_md = 1;
                        }else{
                            $flag_md = 0;
                            DB::rollback();
                            session()->flash('error', 'this item is not saved in the stock');
                            return back();
                        }


                        if ($data->purchase_no) {
                            MaterialPurchase::where('purchase_no', '=', $data->purchase_no)
                            ->update(['status' => 6]);
                            MaterialPurchaseDetail::where('purchase_no', '=', $data->purchase_no)
                            ->update(['status' => 6]);

                        }else{
                            MaterialSupplierOrder::where('order_no', '=', $data->order_no)
                            ->update(['status' => 5]);
                            MaterialSupplierOrderDetail::where('order_no', '=', $data->order_no)
                            ->update(['status' => 5]);
                        }


                } elseif(!empty($data->dest_extra_store_id)){
                    $total_material_price_nvat += (float) ($data->price_nvat ?? 0);
                    $code_store_destination = MaterialExtraBigStore::where('id',$data->dest_extra_store_id)->value('code');

                $valeurStockInitialDestination = MaterialExtraBigStoreDetail::where('code',$code_store_destination)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('total_cump_value') ?? 0;
                $quantityStockInitialDestination = MaterialExtraBigStoreDetail::where('code',$code_store_destination)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('quantity') ?? 0;
                $cumpResult = StockCumpCalculator::weightedAverageOnStockIn(
                    (float) $quantityStockInitialDestination,
                    (float) $valeurStockInitialDestination,
                    (float) $data->quantity_received,
                    (float) $data->purchase_price,
                    (float) $data->purchase_price
                );
                $cump = $cumpResult['cump'];
                $quantityTotalExtraBigStore = $cumpResult['quantity_total'];

                // Calculate value_stockin using cump
                $value_stockin = $data->quantity_received * $cump;
                
                // Calculate value_stock_final using cump
                $quantity_stock_final = $quantityStockInitialDestination + $data->quantity_received;
                $value_stock_final = $quantity_stock_final * $cump;

                $reportExtraBigStore = array(
                    'material_id' => $data->material_id,
                    'quantity_stock_initial' => $quantityStockInitialDestination,
                    'value_stock_initial' => $valeurStockInitialDestination,
                    'code_store' => $code_store_destination,
                    'date' => $data->date,
                    'quantity_stockin' => $data->quantity_received,
                    'value_stockin' => $value_stockin,
                    'quantity_stock_final' => $quantity_stock_final,
                    'value_stock_final' => $value_stock_final,
                    'type_transaction' => 'ACHAT',
                    'cump' => $cump,
                    'document_no' => $data->reception_no,
                    'created_by' => $this->user->name,
                    'description' => $data->description,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportExtraBigStoreData[] = $reportExtraBigStore;

                    $extraBigStore = array(
                        'material_id' => $data->material_id,
                        'quantity' => $quantityTotalExtraBigStore,
                        'purchase_price' => $cump,
                        'total_purchase_value' => $quantityTotalExtraBigStore * $cump,
                        'cump' => $cump,
                        'total_cump_value' => $quantityTotalExtraBigStore * $cump,
                        'created_by' => $this->user->name,
                        'verified' => false
                    );

                    $materialData = array(
                        'id' => $data->material_id,
                        'quantity' => $quantityTotalExtraBigStore,
                        'cump' => $cump,
                        'purchase_price' => $cump
                    );

                    Material::where('id',$data->material_id)
                        ->update($materialData);

                        $material = MaterialExtraBigStoreDetail::where('code',$code_store_destination)->where("material_id",$data->material_id)->value('material_id');

                        if (!empty($material)) {
                            MaterialExtraBigStoreDetail::where('code',$code_store_destination)->where('material_id',$data->material_id)
                        ->update($extraBigStore);
                        $flag_big = 1;
                        }else{
                            $flag_big = 0;
                            DB::rollback();
                            session()->flash('error', 'this item is not saved in the extra big stock');
                            return back();
                        }


                        if ($data->purchase_no) {
                            MaterialPurchase::where('purchase_no', '=', $data->purchase_no)
                            ->update(['status' => 6]);
                            MaterialPurchaseDetail::where('purchase_no', '=', $data->purchase_no)
                            ->update(['status' => 6]);

                        }else{
                            MaterialSupplierOrder::where('order_no', '=', $data->order_no)
                            ->update(['status' => 5]);
                            MaterialSupplierOrderDetail::where('order_no', '=', $data->order_no)
                            ->update(['status' => 5]);
                        }

                }else{
                    session()->flash('error', 'oops!Something wrong');
                    return back();
                }
  
        }


        if ($flag_md != 0 && !empty($reportBigStoreData)) {
            MaterialBigReport::insert($reportBigStoreData);
        }
        
        if ($flag_big != 0 && !empty($reportExtraBigStoreData)) {
            MaterialExtraBigReport::insert($reportExtraBigStoreData);
        }

        if (!empty($reportFinishedGoodData)) {
            FinishedGoodReport::insert($reportFinishedGoodData);
        }
        if (!empty($reportGoodsStoreData)) {
            GoodsStoreReport::insert($reportGoodsStoreData);
        }

        //start accounting features
        
            $totalValue = DB::table('material_reception_details')
            ->where('reception_no', '=', $reception_no)
            ->sum('total_amount_purchase');

            $item_price_nvat = DB::table('material_reception_details')
            ->where('reception_no', '=', $reception_no)
            ->sum('price_nvat');

            $vat = DB::table('material_reception_details')
            ->where('reception_no', '=', $reception_no)
            ->sum('vat');

            $order_no = $reception->order_no;

            $dataCashOutFlow = CashOutFlowRequest::where('purchase_no',$order_no)->first();

            // Initialize arrays
            $insert_journal_debit_data = [];
            $insert_journal_debit_data_acc2 = [];
            $insert_journal_credit_dataAccEmp = [];
            $insert_journal_credit_data = [];

            if (!empty($dataCashOutFlow->id)) {
                //start debit operations (stock: material and/or items FG/Goods)
                if ($total_material_price_nvat > 0) {
                    $id_acc_stock = null;
                    if ((string) ($reception->type_store ?? '') === '0') {
                        $id_acc_stock = MaterialExtraBigStore::where('id', $reception->dest_extra_store_id)->value('default_account_id');
                    } else {
                        $id_acc_stock = MaterialBigStore::where('id', $reception->destination_store_id)->value('default_account_id');
                    }
                    if (empty($id_acc_stock)) {
                        DB::rollback();
                        session()->flash('error', __('messages.approval_accounts_required'));
                        return redirect()->back();
                    }
                    $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'debit', $total_material_price_nvat);
                    $insert_journal_debit_data[] = [
                        'date' => $reception->date,
                        'document_no' => $reception->reception_no,
                        'description' => $reception->description,
                        'account_id' => $id_acc_stock,
                        'libelle' => $reception->description,
                        'debit' => $total_material_price_nvat,
                        'balance' => $stockLedgerState['balance'],
                        'created_at' => \Carbon\Carbon::now()
                    ];
                }
                if ($total_item_fg_price_nvat > 0) {
                    $fgStore = FinishedGoodsStore::first();
                    if (!$fgStore || empty($fgStore->default_account_id)) {
                        DB::rollback();
                        session()->flash('error', __('messages.approval_accounts_required'));
                        return redirect()->back();
                    }
                    $fgLedgerState = $this->updateLedgerTotals($fgStore->default_account_id, 'debit', $total_item_fg_price_nvat);
                    $insert_journal_debit_data[] = [
                        'date' => $reception->date,
                        'document_no' => $reception->reception_no,
                        'description' => $reception->description,
                        'account_id' => $fgStore->default_account_id,
                        'libelle' => $reception->description,
                        'debit' => $total_item_fg_price_nvat,
                        'balance' => $fgLedgerState['balance'],
                        'created_at' => \Carbon\Carbon::now()
                    ];
                }
                if ($total_item_goods_price_nvat > 0) {
                    $goodsStore = $this->resolveGoodsStoreFromReception($reception);
                    if (!$goodsStore || empty($goodsStore->default_account_id)) {
                        DB::rollback();
                        session()->flash('error', __('messages.approval_accounts_required'));
                        return redirect()->back();
                    }
                    $goodsLedgerState = $this->updateLedgerTotals($goodsStore->default_account_id, 'debit', $total_item_goods_price_nvat);
                    $insert_journal_debit_data[] = [
                        'date' => $reception->date,
                        'document_no' => $reception->reception_no,
                        'description' => $reception->description,
                        'account_id' => $goodsStore->default_account_id,
                        'libelle' => $reception->description,
                        'debit' => $total_item_goods_price_nvat,
                        'balance' => $goodsLedgerState['balance'],
                        'created_at' => \Carbon\Carbon::now()
                    ];
                }
            AccountJournalEntryDetail::insert($insert_journal_debit_data);

            //vat
            $vat_deduc_account_id = AccountSetting::where('active',1)->value('vat_deduc_account_id');

            if (empty($vat_deduc_account_id)) {
                DB::rollback();
                session()->flash('error', 'Account ID for VAT deduction not configured. Please configure vat_deduc_account_id in Account Settings.');
                return redirect()->back();
            }

            $vatLedgerState = $this->updateLedgerTotals($vat_deduc_account_id, 'debit', $vat);
            
            $journalEntryDebitdataAcc2 = array(
                    'date' => $reception->date,
                    'document_no' => $reception->reception_no,
                    'description' => "TVA DEDUCTIBLE",
                    'account_id' => $vat_deduc_account_id,
                    'libelle' => "TVA DEDUCTIBLE",
                    'debit' => $vat,
                    'balance' => $vatLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_data_acc2[] = $journalEntryDebitdataAcc2;

            AccountJournalEntryDetail::insert($insert_journal_debit_data_acc2);
            
            //end debit operation

            //start credit acc_employee operations
            if (empty($dataCashOutFlow->employe->cash_out_account_id)) {
                DB::rollback();
                session()->flash('error', 'Cash Out Account ID not found for the employee ('.$dataCashOutFlow->employe->firstname.') Please configure cash_out_account_id for the employee.');
                return redirect()->back();
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


            MaterialReception::where('reception_no', '=', $reception_no)
                ->update(['flag2' => 1]);
            MaterialReceptionDetail::where('reception_no', '=', $reception_no)
                ->update(['flag2' => 1]);

            //end credit operations
            }else{
                //start debit operations (stock: material and/or items FG/Goods)
                if ($total_material_price_nvat > 0) {
                    $id_acc_stock = null;
                    if ((string) ($reception->type_store ?? '') === '0') {
                        $id_acc_stock = MaterialExtraBigStore::where('id', $reception->dest_extra_store_id)->value('default_account_id');
                    } else {
                        $id_acc_stock = MaterialBigStore::where('id', $reception->destination_store_id)->value('default_account_id');
                    }
                    if (empty($id_acc_stock)) {
                        DB::rollback();
                        session()->flash('error', __('messages.approval_accounts_required'));
                        return redirect()->back();
                    }
                    $stockLedgerState = $this->updateLedgerTotals($id_acc_stock, 'debit', $total_material_price_nvat);
                    $insert_journal_debit_data[] = [
                        'date' => $reception->date,
                        'document_no' => $reception->reception_no,
                        'description' => $reception->description,
                        'account_id' => $id_acc_stock,
                        'libelle' => $reception->description,
                        'debit' => $total_material_price_nvat,
                        'balance' => $stockLedgerState['balance'],
                        'created_at' => \Carbon\Carbon::now()
                    ];
                }
                if ($total_item_fg_price_nvat > 0) {
                    $fgStore = FinishedGoodsStore::first();
                    if (!$fgStore || empty($fgStore->default_account_id)) {
                        DB::rollback();
                        session()->flash('error', __('messages.approval_accounts_required'));
                        return redirect()->back();
                    }
                    $fgLedgerState = $this->updateLedgerTotals($fgStore->default_account_id, 'debit', $total_item_fg_price_nvat);
                    $insert_journal_debit_data[] = [
                        'date' => $reception->date,
                        'document_no' => $reception->reception_no,
                        'description' => $reception->description,
                        'account_id' => $fgStore->default_account_id,
                        'libelle' => $reception->description,
                        'debit' => $total_item_fg_price_nvat,
                        'balance' => $fgLedgerState['balance'],
                        'created_at' => \Carbon\Carbon::now()
                    ];
                }
                if ($total_item_goods_price_nvat > 0) {
                    $goodsStore = $this->resolveGoodsStoreFromReception($reception);
                    if (!$goodsStore || empty($goodsStore->default_account_id)) {
                        DB::rollback();
                        session()->flash('error', __('messages.approval_accounts_required'));
                        return redirect()->back();
                    }
                    $goodsLedgerState = $this->updateLedgerTotals($goodsStore->default_account_id, 'debit', $total_item_goods_price_nvat);
                    $insert_journal_debit_data[] = [
                        'date' => $reception->date,
                        'document_no' => $reception->reception_no,
                        'description' => $reception->description,
                        'account_id' => $goodsStore->default_account_id,
                        'libelle' => $reception->description,
                        'debit' => $total_item_goods_price_nvat,
                        'balance' => $goodsLedgerState['balance'],
                        'created_at' => \Carbon\Carbon::now()
                    ];
                }
            AccountJournalEntryDetail::insert($insert_journal_debit_data);
            //vat
            $vat_deduc_account_id = AccountSetting::where('active',1)->value('vat_deduc_account_id');

            if (empty($vat_deduc_account_id)) {
                DB::rollback();
                session()->flash('error', 'Account ID for VAT deduction not configured. Please configure vat_deduc_account_id in Account Settings.');
                return redirect()->back();
            }

            $vatLedgerState = $this->updateLedgerTotals($vat_deduc_account_id, 'debit', $vat);
            
            $journalEntryDebitdataAcc2 = array(
                    'date' => $reception->date,
                    'document_no' => $reception->reception_no,
                    'description' => "TVA DEDUCTIBLE",
                    'account_id' => $vat_deduc_account_id,
                    'libelle' => "TVA DEDUCTIBLE",
                    'debit' => $vat,
                    'balance' => $vatLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_journal_debit_data_acc2[] = $journalEntryDebitdataAcc2;

            AccountJournalEntryDetail::insert($insert_journal_debit_data_acc2);
            
            //end debit operation

            //start credit operations
            if (empty($reception->supplier->account_id)) {
                DB::rollback();
                session()->flash('error', 'Account ID not found for the supplier. Please configure account_id for the supplier.');
                return redirect()->back();
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

            $purchase_no = MaterialSupplierOrder::where('order_no',$reception->order_no)->value('purchase_no');
        
            if ($reception->type_reception == 0) {
                MaterialSupplierOrder::where('order_no', '=', $reception->order_no)
                ->update(['status' => -5]);
                MaterialSupplierOrderDetail::where('order_no', '=', $reception->order_no)
                ->update(['status' => -5]);
            }else{
                MaterialSupplierOrder::where('order_no', '=', $reception->order_no)
                ->update(['status' => 5]);
                MaterialSupplierOrderDetail::where('order_no', '=', $reception->order_no)
                ->update(['status' => 5]);
            }

            MaterialPurchase::where('purchase_no', '=', $purchase_no)
            ->update(['status' => 6]);
            MaterialPurchaseDetail::where('purchase_no', '=', $purchase_no)
             ->update(['status' => 6]);


            MaterialReception::where('reception_no', '=', $reception_no)
                            ->update(['status' => 4,'approuved_by' => $this->user->name]);
            MaterialReceptionDetail::where('reception_no', '=', $reception_no)
                            ->update(['status' => 4,'approuved_by' => $this->user->name]);
        DB::commit();
            $successMessage = 'Reception has been done successfully!';
            if ($flag_md != 0 && !empty($reportBigStoreData)) {
                $code_store = !empty($reportBigStoreData[0]['code_store']) ? $reportBigStoreData[0]['code_store'] : '';
                $successMessage .= ' Stock: ' . $code_store;
            }
            if ($flag_big != 0 && !empty($reportExtraBigStoreData)) {
                $code_store = !empty($reportExtraBigStoreData[0]['code_store']) ? $reportExtraBigStoreData[0]['code_store'] : '';
                $successMessage .= ' Extra Big Stock: ' . $code_store;
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

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('material_reception.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export receptions !');
        }
        $dateDebut = $request->input('date_debut') ?? $request->input('start_date');
        $dateFin = $request->input('date_fin') ?? $request->input('end_date');
        if (!$dateDebut || !$dateFin) {
            return redirect()->route('admin.material-receptions.index')
                ->with('error', __('messages.export_select_date_range'));
        }
        $filename = 'receptions_materiels_' . \Carbon\Carbon::parse($dateDebut)->format('Y-m-d') . '_' . \Carbon\Carbon::parse($dateFin)->format('Y-m-d') . '.xlsx';
        return Excel::download(new MaterialReceptionExport(), $filename);
    }

    protected function receptionOrderLineFlags($datas): array
    {
        $collection = collect($datas);

        return [
            'hasMaterials' => $collection->contains(fn ($line) => !empty($line->material_id)),
            'hasItems' => $collection->contains(fn ($line) => !empty($line->item_id)),
        ];
    }

    protected function validateReceptionDestinations($validator, Request $request, bool $hasMaterials, bool $hasItems): void
    {
        $validator->after(function ($v) use ($request, $hasMaterials, $hasItems) {
            if ($hasMaterials) {
                $type = (string) ($request->type_store ?? '');
                if (!in_array($type, ['0', '1'], true)) {
                    $v->errors()->add('type_store', __('messages.reception_material_store_required'));
                } elseif ($type === '0' && !$request->dest_extra_store_id) {
                    $v->errors()->add('dest_extra_store_id', __('messages.reception_material_store_required'));
                } elseif ($type === '1' && !$request->destination_store_id) {
                    $v->errors()->add('destination_store_id', __('messages.reception_material_store_required'));
                }
            }

            if ($hasItems && trim((string) ($request->code_store ?? '')) === '') {
                $v->errors()->add('code_store', __('messages.reception_goods_store_required'));
            }
        });
    }

    protected function resolveReceptionTypeStore(Request $request, array $lineFlags): ?string
    {
        if ($lineFlags['hasMaterials']) {
            return (string) ($request->type_store ?? '');
        }

        if ($lineFlags['hasItems']) {
            return MaterialReception::TYPE_STORE_GOODS;
        }

        return $request->type_store;
    }

    protected function resolveGoodsStoreFromReception(MaterialReception $reception): ?GoodsStore
    {
        $code = trim((string) ($reception->code_store ?? ''));
        if ($code !== '') {
            return GoodsStore::where('code', $code)->first();
        }

        return GoodsStore::first();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $reception_no
     * @return \Illuminate\Http\Response
     */
    public function destroy($reception_no)
    {
        if (is_null($this->user) || !$this->user->can('material_reception.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any reception !');
        }
        /*
        try {DB::beginTransaction();

        $reception = MaterialReception::where('reception_no',$reception_no)->first();
        if (!is_null($reception)) {
            $reception->delete();
            MaterialReceptionDetail::where('reception_no',$reception_no)->delete();
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
