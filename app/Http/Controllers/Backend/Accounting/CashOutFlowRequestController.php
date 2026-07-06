<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Storage;
use App\Models\Accounting\AccountExpense;
use App\Models\Accounting\CashOutFlowRequest;
use App\Models\Accounting\CashOutFlowRequestDetail;
use App\Models\Accounting\AccountCostCenter;
use App\Models\HrEmploye;
use App\Models\Supplier;
use App\Models\RawMaterial;
use App\Models\Material;
use App\Models\RawMaterialReception;
use App\Models\RawMaterialReceptionDetail;
use App\Models\RawMaterialSupplierOrder;
use App\Models\RawMaterialSupplierOrderDetail;
use App\Models\MaterialReception;
use App\Models\MaterialReceptionDetail;
use App\Models\MaterialSupplierOrder;
use App\Models\MaterialSupplierOrderDetail;
use App\Models\Item;
use App\Models\Accounting\Currency;
use App\Models\HrLoan;
use Validator;
use PDF;
use Mail;
use Carbon\Carbon;

class CashOutFlowRequestController extends Controller
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
    public function index(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any cash out flow request !');
        }

        $query = CashOutFlowRequest::query();
        
        // Si des dates sont fournies, filtrer par période
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('created_at', [$dateDebut, $dateFin]);
        } else {
            // Par défaut, afficher les 100 lignes les plus récentes
            $query->take(100);
        }
        
        $datas = $query->orderBy('id','desc')->get();
        return view('backend.pages.accounting.cash_out_flow_request.index', compact('datas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow request !');
        }

        $expenses  = AccountExpense::orderBy('name','asc')->get();
        $suppliers = Supplier::orderBy('supplier_name','asc')->get();
        $employes = HrEmploye::orderBy('firstname','asc')->get();
        $currencies = Currency::orderBy('currency_name','asc')->get();
        $cost_centers = AccountCostCenter::orderBy('name','asc')->get();
        return view('backend.pages.accounting.cash_out_flow_request.create', compact('expenses','suppliers','employes','currencies','cost_centers'));
    }

    public function rawMaterialPaymentRequest($reception_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow request !');
        }

        $datas = RawMaterialReceptionDetail::where('reception_no', $reception_no)->get();
        $data = RawMaterialReceptionDetail::where('reception_no', $reception_no)->first();
        $raw_materials  = RawMaterial::orderBy('name','asc')->get();
        $suppliers = Supplier::orderBy('supplier_name','asc')->get();
        $employes = HrEmploye::orderBy('firstname','asc')->get();
        $currencies = Currency::orderBy('currency_name','asc')->get();
        $cost_centers = AccountCostCenter::orderBy('name','asc')->get();
        return view('backend.pages.accounting.cash_out_flow_request.raw_material_payment_request_create', compact('raw_materials','suppliers','employes','currencies','cost_centers','datas','data'));
    }

    public function rawMaterialPaymentRequestBefore($order_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow request !');
        }

        $datas = RawMaterialSupplierOrderDetail::where('order_no', $order_no)->get();
        $data = RawMaterialSupplierOrderDetail::where('order_no', $order_no)->first();
        $raw_materials  = RawMaterial::orderBy('name','asc')->get();
        $suppliers = Supplier::orderBy('supplier_name','asc')->get();
        $employes = HrEmploye::orderBy('firstname','asc')->get();
        $currencies = Currency::orderBy('currency_name','asc')->get();
        $cost_centers = AccountCostCenter::orderBy('name','asc')->get();
        return view('backend.pages.accounting.cash_out_flow_request.raw_material_payment_request_create_before', compact('raw_materials','suppliers','employes','currencies','cost_centers','datas','data'));
    }

    public function materialPaymentRequest($reception_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow request !');
        }

        $datas = MaterialReceptionDetail::with(['material.materialMeasurement', 'item'])->where('reception_no', $reception_no)->get();
        $data = MaterialReceptionDetail::with(['material.materialMeasurement', 'item'])->where('reception_no', $reception_no)->first();
        $materials  = Material::orderBy('name','asc')->get();
        $items = Item::active()->orderBy('name','asc')->get();
        $suppliers = Supplier::orderBy('supplier_name','asc')->get();
        $employes = HrEmploye::orderBy('firstname','asc')->get();
        $currencies = Currency::orderBy('currency_name','asc')->get();
        $cost_centers = AccountCostCenter::orderBy('name','asc')->get();
        return view('backend.pages.accounting.cash_out_flow_request.material_payment_request_create', compact('materials','items','suppliers','employes','currencies','cost_centers','datas','data'));
    }

    public function materialPaymentRequestBefore($order_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow request !');
        }

        $datas = MaterialSupplierOrderDetail::with(['material.materialMeasurement', 'item'])->where('order_no', $order_no)->get();
        $data = MaterialSupplierOrderDetail::with(['material.materialMeasurement', 'item'])->where('order_no', $order_no)->first();
        $materials  = Material::orderBy('name','asc')->get();
        $items = Item::active()->orderBy('name','asc')->get();
        $suppliers = Supplier::orderBy('supplier_name','asc')->get();
        $employes = HrEmploye::orderBy('firstname','asc')->get();
        $currencies = Currency::orderBy('currency_name','asc')->get();
        $cost_centers = AccountCostCenter::orderBy('name','asc')->get();
        return view('backend.pages.accounting.cash_out_flow_request.material_payment_request_create_before', compact('materials','items','suppliers','employes','currencies','cost_centers','datas','data'));
    }

    public function loanPaymentRequest($loan_id)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow request !');
        }

        $loan = HrLoan::with('employe')->findOrFail($loan_id);
        
        // Vérifier que le statut est 3 (approuvé)
        if ($loan->status != 3) {
            abort(403, 'Le prêt doit être approuvé (statut 3) pour créer une demande de sortie de fonds !');
        }

        $employes = HrEmploye::orderBy('firstname','asc')->get();
        $currencies = Currency::orderBy('currency_name','asc')->get();
        $cost_centers = AccountCostCenter::orderBy('name','asc')->get();
        
        return view('backend.pages.accounting.cash_out_flow_request.loan_create', compact('loan','employes','currencies','cost_centers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow request !');
        }

        $rules = array(
                'date'  => 'required',
                'asker'  => 'required',
                'description'  => 'required|min:5|max:500',
                'vat_supplier_payer'  => 'required',
                //'supplier_id'  => 'required',
                //'cost_center_id'  => 'required',
                'employe_id'  => 'required',
                'currency_id'  => 'required',
                'quantity.*' => 'required',
                'expense_id.*' => 'required'
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $date = $request->date;
            $asker = $request->asker;
            $description = $request->description;
            $created_by = $this->user->name;
            $status = 0;
            $vat_supplier_payer =$request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $supplier_id = $request->supplier_id;
            $cost_center_id = $request->cost_center_id;
            $employe_id = $request->employe_id;
            $currency_id = $request->currency_id;
            $expense_id = $request->expense_id;
            $quantity = $request->quantity;

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $latest = CashOutFlowRequest::orderBy('id','desc')->first();
            if ($latest) {
               $cash_outflow_request_no = (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $cash_outflow_request_no = (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $cash_outflow_request_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$cash_outflow_request_no;
            $created_by = $this->user->name;

            //create cash_out_flow_request
            $cash_out_flow_request = new CashOutFlowRequest();
            $cash_out_flow_request->date = $date;
            $cash_out_flow_request->cash_outflow_request_signature = $cash_outflow_request_signature;
            $cash_out_flow_request->cash_outflow_request_no = $cash_outflow_request_no;
            $cash_out_flow_request->asker = $asker;
            $cash_out_flow_request->description = $description;
            $cash_out_flow_request->status = $status;
            $cash_out_flow_request->vat_supplier_payer = $vat_supplier_payer;
            $cash_out_flow_request->vat_rate = $vat_rate;
            $cash_out_flow_request->supplier_id = $supplier_id;
            $cash_out_flow_request->cost_center_id = $cost_center_id;
            $cash_out_flow_request->employe_id = $employe_id;
            $cash_out_flow_request->currency_id = $currency_id;
            $cash_out_flow_request->created_by = $created_by;
            $cash_out_flow_request->created_at = \Carbon\Carbon::now();
            $cash_out_flow_request->save();
            //insert details of cash_out_flow_request No.
            for( $count = 0; $count < count($expense_id); $count++ ){
                $cost_price = AccountExpense::where('id', $expense_id[$count])->value('cost_price');
                if($vat_supplier_payer == 1){
                    $cost_nvat = ($cost_price*$quantity[$count]);
                    $vat = ($cost_nvat* $vat_rate)/100;
                    $cost_wvat = $cost_nvat + $vat;

                }else{
                    $cost_nvat = ($cost_price*$quantity[$count]);
                    $vat = 0;
                    $cost_wvat = $cost_nvat + $vat;
                }

                $data = array(
                    'expense_id' => $expense_id[$count],
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'asker' => $asker,
                    'status' => $status,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'supplier_id' => $supplier_id,
                    'cost_price' => $cost_price,
                    'description' => $description,
                    'cost_center_id' => $cost_center_id,
                    'employe_id' => $employe_id,
                    'currency_id' => $currency_id,
                    'cost_nvat' => $cost_nvat,
                    'vat' => $vat,
                    'cost_wvat' => $cost_wvat,
                    'created_by' => $created_by,
                    'cash_outflow_request_no' => $cash_outflow_request_no,
                    'cash_outflow_request_signature' => $cash_outflow_request_signature,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }

            CashOutFlowRequestDetail::insert($insert_data);

        DB::commit();
            session()->flash('success', 'cash out flow request has been created !!');
            return redirect()->route('admin.cash-out-flow-request.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function storeRawMaterialPaymentRequest(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow request !');
        }

        $rules = array(
                'date'  => 'required',
                'asker'  => 'required',
                'description'  => 'required|min:5|max:500',
                'vat_supplier_payer'  => 'required',
                'supplier_id'  => 'required',
                'currency_id'  => 'required',
                'quantity.*' => 'required',
                'cost_price.*' => 'required',
                'r_material_id.*' => 'required'
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $date = $request->date;
            $asker = $request->asker;
            $purchase_no = $request->purchase_no;
            $description = $request->description;
            $created_by = $this->user->name;
            $status = 0;
            $vat_supplier_payer =$request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $supplier_id = $request->supplier_id;
            $cost_center_id = $request->cost_center_id;
            $currency_id = $request->currency_id;
            $r_material_id = $request->r_material_id;
            $cost_price = $request->cost_price;
            $quantity = $request->quantity;

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $latest = CashOutFlowRequest::orderBy('id','desc')->first();
            if ($latest) {
               $cash_outflow_request_no = (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $cash_outflow_request_no = (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $cash_outflow_request_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$cash_outflow_request_no;
            $created_by = $this->user->name;

            //create cash_out_flow_request
            $cash_out_flow_request = new CashOutFlowRequest();
            $cash_out_flow_request->date = $date;
            $cash_out_flow_request->cash_outflow_request_signature = $cash_outflow_request_signature;
            $cash_out_flow_request->cash_outflow_request_no = $cash_outflow_request_no;
            $cash_out_flow_request->asker = $asker;
            $cash_out_flow_request->purchase_no = $purchase_no;
            $cash_out_flow_request->description = $description;
            $cash_out_flow_request->status = $status;
            $cash_out_flow_request->vat_supplier_payer = $vat_supplier_payer;
            $cash_out_flow_request->vat_rate = $vat_rate;
            $cash_out_flow_request->supplier_id = $supplier_id;
            $cash_out_flow_request->cost_center_id = $cost_center_id;
            $cash_out_flow_request->currency_id = $currency_id;
            $cash_out_flow_request->created_by = $created_by;
            $cash_out_flow_request->created_at = \Carbon\Carbon::now();
            $cash_out_flow_request->save();
            //insert details of cash_out_flow_request No.
            for( $count = 0; $count < count($r_material_id); $count++ ){

                if($vat_supplier_payer == 1){
                    $cost_nvat = ($cost_price[$count]*$quantity[$count]);
                    $vat = ($cost_nvat* $vat_rate)/100;
                    $cost_wvat = $cost_nvat + $vat;

                }else{
                    $cost_nvat = ($cost_price[$count]*$quantity[$count]);
                    $vat = 0;
                    $cost_wvat = $cost_nvat + $vat;
                }

                $data = array(
                    'r_material_id' => $r_material_id[$count],
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'asker' => $asker,
                    'purchase_no' => $purchase_no,
                    'status' => $status,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'supplier_id' => $supplier_id,
                    'cost_price' => $cost_price[$count],
                    'description' => $description,
                    'cost_center_id' => $cost_center_id,
                    'currency_id' => $currency_id,
                    'cost_nvat' => $cost_nvat,
                    'vat' => $vat,
                    'cost_wvat' => $cost_wvat,
                    'created_by' => $created_by,
                    'cash_outflow_request_no' => $cash_outflow_request_no,
                    'cash_outflow_request_signature' => $cash_outflow_request_signature,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }

            CashOutFlowRequestDetail::insert($insert_data);

            RawMaterialReception::where('reception_no', '=', $purchase_no)
                ->update(['flag1' => 1]);
            RawMaterialReceptionDetail::where('reception_no', '=', $purchase_no)
                ->update(['flag1' => 1]);

        DB::commit();
            session()->flash('success', 'cash out flow request has been created !!');
            return redirect()->route('admin.cash-out-flow-request.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function storeRawMaterialPaymentRequestBefore(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow request !');
        }

        $rules = array(
                'date'  => 'required',
                'asker'  => 'required',
                'description'  => 'required|min:5|max:500',
                'vat_supplier_payer'  => 'required',
                'employe_id'  => 'required',
                'currency_id'  => 'required',
                'quantity.*' => 'required',
                'cost_price.*' => 'required',
                'r_material_id.*' => 'required'
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $date = $request->date;
            $asker = $request->asker;
            $purchase_no = $request->purchase_no;
            $description = $request->description;
            $created_by = $this->user->name;
            $status = 0;
            $flag2 = 1;
            $vat_supplier_payer =$request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $employe_id = $request->employe_id;
            $cost_center_id = $request->cost_center_id;
            $currency_id = $request->currency_id;
            $r_material_id = $request->r_material_id;
            $cost_price = $request->cost_price;
            $quantity = $request->quantity;

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $latest = CashOutFlowRequest::orderBy('id','desc')->first();
            if ($latest) {
               $cash_outflow_request_no = (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $cash_outflow_request_no = (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $cash_outflow_request_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$cash_outflow_request_no;
            $created_by = $this->user->name;

            //create cash_out_flow_request
            $cash_out_flow_request = new CashOutFlowRequest();
            $cash_out_flow_request->date = $date;
            $cash_out_flow_request->cash_outflow_request_signature = $cash_outflow_request_signature;
            $cash_out_flow_request->cash_outflow_request_no = $cash_outflow_request_no;
            $cash_out_flow_request->asker = $asker;
            $cash_out_flow_request->purchase_no = $purchase_no;
            $cash_out_flow_request->description = $description;
            $cash_out_flow_request->status = $status;
            $cash_out_flow_request->flag2 = $flag2;
            $cash_out_flow_request->vat_supplier_payer = $vat_supplier_payer;
            $cash_out_flow_request->vat_rate = $vat_rate;
            $cash_out_flow_request->employe_id = $employe_id;
            $cash_out_flow_request->cost_center_id = $cost_center_id;
            $cash_out_flow_request->currency_id = $currency_id;
            $cash_out_flow_request->created_by = $created_by;
            $cash_out_flow_request->created_at = \Carbon\Carbon::now();
            $cash_out_flow_request->save();
            //insert details of cash_out_flow_request No.
            for( $count = 0; $count < count($r_material_id); $count++ ){

                if($vat_supplier_payer == 1){
                    $cost_nvat = ($cost_price[$count]*$quantity[$count]);
                    $vat = ($cost_nvat* $vat_rate)/100;
                    $cost_wvat = $cost_nvat + $vat;

                }else{
                    $cost_nvat = ($cost_price[$count]*$quantity[$count]);
                    $vat = 0;
                    $cost_wvat = $cost_nvat + $vat;
                }

                $data = array(
                    'r_material_id' => $r_material_id[$count],
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'asker' => $asker,
                    'purchase_no' => $purchase_no,
                    'status' => $status,
                    'flag2' => $flag2,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'employe_id' => $employe_id,
                    'cost_price' => $cost_price[$count],
                    'description' => $description,
                    'cost_center_id' => $cost_center_id,
                    'currency_id' => $currency_id,
                    'cost_nvat' => $cost_nvat,
                    'vat' => $vat,
                    'cost_wvat' => $cost_wvat,
                    'created_by' => $created_by,
                    'cash_outflow_request_no' => $cash_outflow_request_no,
                    'cash_outflow_request_signature' => $cash_outflow_request_signature,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }

            CashOutFlowRequestDetail::insert($insert_data);

            RawMaterialSupplierOrder::where('order_no', '=', $purchase_no)
                ->update(['flag2' => 1]);
            RawMaterialSupplierOrderDetail::where('order_no', '=', $purchase_no)
                ->update(['flag2' => 1]);

        DB::commit();
            session()->flash('success', 'cash out flow request has been created !!');
            return redirect()->route('admin.cash-out-flow-request.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function storeMaterialPaymentRequest(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow request !');
        }

        $rules = array(
                'date'  => 'required',
                'asker'  => 'required',
                'description'  => 'required|min:5|max:500',
                'vat_supplier_payer'  => 'required',
                'supplier_id'  => 'required',
                'currency_id'  => 'required',
                'quantity.*' => 'required',
                'cost_price.*' => 'required',
            );

            $error = Validator::make($request->all(),$rules);
            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }
            $material_id = $request->material_id ?? [];
            $item_id = $request->item_id ?? [];
            $cost_price = $request->cost_price;
            $quantity = $request->quantity;
            $rowCount = max(count($material_id), count($item_id));
            if ($rowCount < 1) {
                return response()->json(['error' => [__('messages.at_least_one_line_required')]]);
            }
            for ($i = 0; $i < $rowCount; $i++) {
                $mid = isset($material_id[$i]) ? (trim($material_id[$i]) ?: null) : null;
                $iid = isset($item_id[$i]) ? (trim($item_id[$i]) ?: null) : null;
                if (!$mid && !$iid) {
                    return response()->json(['error' => [__('messages.each_line_must_have_material_or_item')]]);
                }
            }

            try {DB::beginTransaction();

            $date = $request->date;
            $asker = $request->asker;
            $purchase_no = $request->purchase_no;
            $description = $request->description;
            $created_by = $this->user->name;
            $status = 0;
            $vat_supplier_payer =$request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $supplier_id = $request->supplier_id;
            $cost_center_id = $request->cost_center_id;
            $currency_id = $request->currency_id;
            $cost_price = $request->cost_price;
            $quantity = $request->quantity;

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $latest = CashOutFlowRequest::orderBy('id','desc')->first();
            if ($latest) {
               $cash_outflow_request_no = (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $cash_outflow_request_no = (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $cash_outflow_request_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$cash_outflow_request_no;
            $created_by = $this->user->name;

            //create cash_out_flow_request
            $cash_out_flow_request = new CashOutFlowRequest();
            $cash_out_flow_request->date = $date;
            $cash_out_flow_request->cash_outflow_request_signature = $cash_outflow_request_signature;
            $cash_out_flow_request->cash_outflow_request_no = $cash_outflow_request_no;
            $cash_out_flow_request->asker = $asker;
            $cash_out_flow_request->purchase_no = $purchase_no;
            $cash_out_flow_request->description = $description;
            $cash_out_flow_request->status = $status;
            $cash_out_flow_request->vat_supplier_payer = $vat_supplier_payer;
            $cash_out_flow_request->vat_rate = $vat_rate;
            $cash_out_flow_request->supplier_id = $supplier_id;
            $cash_out_flow_request->cost_center_id = $cost_center_id;
            $cash_out_flow_request->currency_id = $currency_id;
            $cash_out_flow_request->created_by = $created_by;
            $cash_out_flow_request->created_at = \Carbon\Carbon::now();
            $cash_out_flow_request->save();
            //insert details of cash_out_flow_request No.
            for( $count = 0; $count < $rowCount; $count++ ){
                $mid = isset($material_id[$count]) && trim($material_id[$count]) !== '' ? $material_id[$count] : null;
                $iid = isset($item_id[$count]) && trim($item_id[$count]) !== '' ? $item_id[$count] : null;

                if($vat_supplier_payer == 1){
                    $cost_nvat = ($cost_price[$count]*$quantity[$count]);
                    $vat = ($cost_nvat* $vat_rate)/100;
                    $cost_wvat = $cost_nvat + $vat;

                }else{
                    $cost_nvat = ($cost_price[$count]*$quantity[$count]);
                    $vat = 0;
                    $cost_wvat = $cost_nvat + $vat;
                }

                $data = array(
                    'material_id' => $mid,
                    'item_id' => $iid,
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'asker' => $asker,
                    'purchase_no' => $purchase_no,
                    'status' => $status,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'supplier_id' => $supplier_id,
                    'cost_price' => $cost_price[$count],
                    'description' => $description,
                    'cost_center_id' => $cost_center_id,
                    'currency_id' => $currency_id,
                    'cost_nvat' => $cost_nvat,
                    'vat' => $vat,
                    'cost_wvat' => $cost_wvat,
                    'created_by' => $created_by,
                    'cash_outflow_request_no' => $cash_outflow_request_no,
                    'cash_outflow_request_signature' => $cash_outflow_request_signature,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }

            CashOutFlowRequestDetail::insert($insert_data);

            MaterialReception::where('reception_no', '=', $purchase_no)
                ->update(['flag1' => 1]);
            MaterialReceptionDetail::where('reception_no', '=', $purchase_no)
                ->update(['flag1' => 1]);

        DB::commit();
            session()->flash('success', 'cash out flow request has been created !!');
            return redirect()->route('admin.cash-out-flow-request.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function storeMaterialPaymentRequestBefore(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow request !');
        }

        $rules = array(
                'date'  => 'required',
                'asker'  => 'required',
                'description'  => 'required|min:5|max:500',
                'vat_supplier_payer'  => 'required',
                'employe_id'  => 'required',
                'currency_id'  => 'required',
                'quantity.*' => 'required',
                'cost_price.*' => 'required',
            );

            $error = Validator::make($request->all(),$rules);
            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }
            $material_id = $request->material_id ?? [];
            $item_id = $request->item_id ?? [];
            $cost_price = $request->cost_price;
            $quantity = $request->quantity;
            $rowCount = max(count($material_id), count($item_id));
            if ($rowCount < 1) {
                return response()->json(['error' => [__('messages.at_least_one_line_required')]]);
            }
            for ($i = 0; $i < $rowCount; $i++) {
                $mid = isset($material_id[$i]) ? (trim($material_id[$i]) ?: null) : null;
                $iid = isset($item_id[$i]) ? (trim($item_id[$i]) ?: null) : null;
                if (!$mid && !$iid) {
                    return response()->json(['error' => [__('messages.each_line_must_have_material_or_item')]]);
                }
            }

            try {DB::beginTransaction();

            $date = $request->date;
            $asker = $request->asker;
            $purchase_no = $request->purchase_no;
            $description = $request->description;
            $created_by = $this->user->name;
            $status = 0;
            $flag2 = 1;
            $vat_supplier_payer =$request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $employe_id = $request->employe_id;
            $cost_center_id = $request->cost_center_id;
            $currency_id = $request->currency_id;
            $cost_price = $request->cost_price;
            $quantity = $request->quantity;

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $latest = CashOutFlowRequest::orderBy('id','desc')->first();
            if ($latest) {
               $cash_outflow_request_no = (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $cash_outflow_request_no = (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $cash_outflow_request_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$cash_outflow_request_no;
            $created_by = $this->user->name;

            //create cash_out_flow_request
            $cash_out_flow_request = new CashOutFlowRequest();
            $cash_out_flow_request->date = $date;
            $cash_out_flow_request->cash_outflow_request_signature = $cash_outflow_request_signature;
            $cash_out_flow_request->cash_outflow_request_no = $cash_outflow_request_no;
            $cash_out_flow_request->asker = $asker;
            $cash_out_flow_request->purchase_no = $purchase_no;
            $cash_out_flow_request->description = $description;
            $cash_out_flow_request->status = $status;
            $cash_out_flow_request->flag2 = $flag2;
            $cash_out_flow_request->vat_supplier_payer = $vat_supplier_payer;
            $cash_out_flow_request->vat_rate = $vat_rate;
            $cash_out_flow_request->employe_id = $employe_id;
            $cash_out_flow_request->cost_center_id = $cost_center_id;
            $cash_out_flow_request->currency_id = $currency_id;
            $cash_out_flow_request->created_by = $created_by;
            $cash_out_flow_request->created_at = \Carbon\Carbon::now();
            $cash_out_flow_request->save();
            //insert details of cash_out_flow_request No.
            for( $count = 0; $count < $rowCount; $count++ ){
                $mid = isset($material_id[$count]) && trim($material_id[$count]) !== '' ? $material_id[$count] : null;
                $iid = isset($item_id[$count]) && trim($item_id[$count]) !== '' ? $item_id[$count] : null;

                if($vat_supplier_payer == 1){
                    $cost_nvat = ($cost_price[$count]*$quantity[$count]);
                    $vat = ($cost_nvat* $vat_rate)/100;
                    $cost_wvat = $cost_nvat + $vat;

                }else{
                    $cost_nvat = ($cost_price[$count]*$quantity[$count]);
                    $vat = 0;
                    $cost_wvat = $cost_nvat + $vat;
                }

                $data = array(
                    'material_id' => $mid,
                    'item_id' => $iid,
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'asker' => $asker,
                    'purchase_no' => $purchase_no,
                    'status' => $status,
                    'flag2' => $flag2,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'employe_id' => $employe_id,
                    'cost_price' => $cost_price[$count],
                    'description' => $description,
                    'cost_center_id' => $cost_center_id,
                    'currency_id' => $currency_id,
                    'cost_nvat' => $cost_nvat,
                    'vat' => $vat,
                    'cost_wvat' => $cost_wvat,
                    'created_by' => $created_by,
                    'cash_outflow_request_no' => $cash_outflow_request_no,
                    'cash_outflow_request_signature' => $cash_outflow_request_signature,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }

            CashOutFlowRequestDetail::insert($insert_data);

            MaterialSupplierOrder::where('order_no', '=', $purchase_no)
                ->update(['flag2' => 1]);
            MaterialSupplierOrderDetail::where('order_no', '=', $purchase_no)
                ->update(['flag2' => 1]);

        DB::commit();
            session()->flash('success', 'cash out flow request has been created !!');
            return redirect()->route('admin.cash-out-flow-request.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function storeLoanPaymentRequest(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow request !');
        }

        $rules = array(
            'date'  => 'required',
            'asker'  => 'required',
            'description'  => 'required|min:5|max:500',
            'employe_id'  => 'required',
            'currency_id'  => 'required',
            'loan_id' => 'required',
            'amount_loan' => 'required'
        );

        $error = Validator::make($request->all(),$rules);

        if($error->fails()){
            return response()->json([
                'error' => $error->errors()->all(),
            ]);
        }

        try {
            DB::beginTransaction();

            $loan = HrLoan::findOrFail($request->loan_id);
            
            // Vérifier que le statut est 3 (approuvé)
            if ($loan->status != 3) {
                return response()->json([
                    'error' => ['Le prêt doit être approuvé (statut 3) pour créer une demande de sortie de fonds !'],
                ]);
            }

            $date = $request->date;
            $asker = $request->asker;
            $description = $request->description;
            $created_by = $this->user->name;
            $status = 0;
            $employe_id = $request->employe_id;
            $currency_id = $request->currency_id;
            $loan_id = $request->loan_id;
            $amount_loan = (float) $request->amount_loan;
            $cost_center_id = $request->cost_center_id;

            $latest = CashOutFlowRequest::orderBy('id','desc')->first();
            if ($latest) {
               $cash_outflow_request_no = (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $cash_outflow_request_no = (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $cash_outflow_request_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$cash_outflow_request_no;

            //create cash_out_flow_request
            $cash_out_flow_request = new CashOutFlowRequest();
            $cash_out_flow_request->date = $date;
            $cash_out_flow_request->cash_outflow_request_signature = $cash_outflow_request_signature;
            $cash_out_flow_request->cash_outflow_request_no = $cash_outflow_request_no;
            $cash_out_flow_request->asker = $asker;
            $cash_out_flow_request->description = $description;
            $cash_out_flow_request->status = $status;
            $cash_out_flow_request->loan_id = $loan_id;
            $cash_out_flow_request->employe_id = $employe_id;
            $cash_out_flow_request->cost_center_id = $cost_center_id;
            $cash_out_flow_request->currency_id = $currency_id;
            $cash_out_flow_request->created_by = $created_by;
            $cash_out_flow_request->created_at = \Carbon\Carbon::now();
            $cash_out_flow_request->save();

            // Insert detail for loan payment request
            $data = array(
                'loan_id' => $loan_id,
                'date' => $date,
                'asker' => $asker,
                'status' => $status,
                'employe_id' => $employe_id,
                'cost_price' => $amount_loan,
                'description' => $description,
                'cost_center_id' => $cost_center_id,
                'currency_id' => $currency_id,
                'cost_nvat' => $amount_loan,
                'vat' => 0,
                'cost_wvat' => $amount_loan,
                'created_by' => $created_by,
                'cash_outflow_request_no' => $cash_outflow_request_no,
                'cash_outflow_request_signature' => $cash_outflow_request_signature,
                'created_at' => \Carbon\Carbon::now()
            );

            CashOutFlowRequestDetail::insert([$data]);

            HrLoan::where('id', '=', $loan_id)
                ->update(['status' => 4]);

            DB::commit();
            session()->flash('success', 'cash out flow request has been created !!');
            return redirect()->route('admin.cash-out-flow-request.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($cash_outflow_request_no)
    {
        //
         $code = CashOutFlowRequest::where('cash_outflow_request_no', $cash_outflow_request_no)->value('cash_outflow_request_no');
         $cash_out_flow_requests = CashOutFlowRequestDetail::where('cash_outflow_request_no', $cash_outflow_request_no)
             ->with(['loan', 'rawMaterial', 'material', 'item', 'expense'])
             ->get();
         $suppliers = Supplier::orderBy('supplier_name', 'asc')->get();
         return view('backend.pages.accounting.cash_out_flow_request.show', compact('cash_out_flow_requests', 'code', 'suppliers'));
    }

    /**
     * Update only the supplier for the cash out flow request (permission: supplier.edit).
     */
    public function updateSupplier(Request $request, $cash_outflow_request_no)
    {
        if (is_null($this->user) || !$this->user->can('supplier.edit')) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier le fournisseur.');
        }
        $request->validate(['supplier_id' => 'required|exists:suppliers,id']);
        $record = CashOutFlowRequest::where('cash_outflow_request_no', $cash_outflow_request_no)->firstOrFail();
        $record->update(['supplier_id' => $request->supplier_id]);
        return redirect()->route('admin.cash-out-flow-request.show', $cash_outflow_request_no)->with('success', __('messages.supplier_updated'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $cash_outflow_request_no
     * @return \Illuminate\Http\Response
     */
    public function edit($cash_outflow_request_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any cash out flow request !');
        }

        $expenses  = AccountExpense::orderBy('name','asc')->get();
        $suppliers = Supplier::orderBy('supplier_name','asc')->get();
        $employes = HrEmploye::orderBy('firstname','asc')->get();
        $currencies = Currency::orderBy('currency_name','asc')->get();
        $cost_centers = AccountCostCenter::orderBy('name','asc')->get();
        $data = CashOutFlowRequest::where('cash_outflow_request_no', $cash_outflow_request_no)->first();
        $datas = CashOutFlowRequestDetail::where('cash_outflow_request_no', $cash_outflow_request_no)->get();

        return view('backend.pages.accounting.cash_out_flow_request.edit', compact('expenses','suppliers','employes','currencies','data','datas','cost_centers'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $cash_outflow_request_no
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $cash_outflow_request_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any cash out flow request !');
        }

        $cash_out_flow_request = CashOutFlowRequest::where('cash_outflow_request_no',$cash_outflow_request_no)->first();

       $rules = array(
                'date'  => 'required',
                'asker'  => 'required',
                'description'  => 'required|min:5|max:500',
                'vat_supplier_payer'  => 'required',
                //'supplier_id'  => 'required',
                //'cost_center_id'  => 'required',
                //'employe_id'  => 'required',
                'currency_id'  => 'required',
                'quantity.*' => 'required',
                'expense_id.*' => 'required'
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $cash_outflow_request_no = CashOutFlowRequest::where('cash_outflow_request_no',$cash_outflow_request_no)->value('cash_outflow_request_no');
            $cash_outflow_request_signature = CashOutFlowRequest::where('cash_outflow_request_no',$cash_outflow_request_no)->value('cash_outflow_request_signature');

            $date = $request->date;
            $asker = $request->asker;
            $description = $request->description;
            $created_by = $this->user->name;
            $status = 0;
            $vat_supplier_payer =$request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $supplier_id = $request->supplier_id;
            $cost_center_id = $request->cost_center_id;
            $employe_id = $request->employe_id;
            $currency_id = $request->currency_id;
            $expense_id = $request->expense_id;
            $quantity = $request->quantity;

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $created_by = $this->user->name;

            $cash_out_flow_request->date = $date;
            $cash_out_flow_request->cash_outflow_request_signature = $cash_outflow_request_signature;
            $cash_out_flow_request->cash_outflow_request_no = $cash_outflow_request_no;
            $cash_out_flow_request->asker = $asker;
            $cash_out_flow_request->description = $description;
            $cash_out_flow_request->status = $status;
            $cash_out_flow_request->vat_supplier_payer = $vat_supplier_payer;
            $cash_out_flow_request->vat_rate = $vat_rate;
            $cash_out_flow_request->supplier_id = $supplier_id;
            $cash_out_flow_request->cost_center_id = $cost_center_id;
            $cash_out_flow_request->employe_id = $employe_id;
            $cash_out_flow_request->currency_id = $currency_id;
            $cash_out_flow_request->created_by = $created_by;
            $cash_out_flow_request->created_at = \Carbon\Carbon::now();
            $cash_out_flow_request->save();
            //insert details of cash_out_flow_request No.
            for( $count = 0; $count < count($expense_id); $count++ ){
                $cost_price = AccountExpense::where('id', $expense_id[$count])->value('cost_price');
                if($vat_supplier_payer == 1){
                    $cost_nvat = ($cost_price*$quantity[$count]);
                    $vat = ($cost_nvat* $vat_rate)/100;
                    $cost_wvat = $cost_nvat + $vat;

                }else{
                    $cost_nvat = ($cost_price*$quantity[$count]);
                    $vat = 0;
                    $cost_wvat = $cost_nvat + $vat;
                }

                $data = array(
                    'expense_id' => $expense_id[$count],
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'asker' => $asker,
                    'status' => $status,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'supplier_id' => $supplier_id,
                    'cost_price' => $cost_price,
                    'description' => $description,
                    'cost_center_id' => $cost_center_id,
                    'employe_id' => $employe_id,
                    'currency_id' => $currency_id,
                    'cost_nvat' => $cost_nvat,
                    'vat' => $vat,
                    'cost_wvat' => $cost_wvat,
                    'created_by' => $created_by,
                    'cash_outflow_request_no' => $cash_outflow_request_no,
                    'cash_outflow_request_signature' => $cash_outflow_request_signature,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }

            CashOutFlowRequestDetail::where('cash_outflow_request_no',$cash_outflow_request_no)->delete();

            CashOutFlowRequestDetail::insert($insert_data);

            DB::commit();
            session()->flash('success', 'Cash Outflow request has been updated successfuly !!');
            return redirect()->route('admin.cash-out-flow-request.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function validateCashOutFlowRequest($cash_outflow_request_no)
    {
       if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any cash out flow request !');
        }

            try {DB::beginTransaction();
            CashOutFlowRequest::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            CashOutFlowRequestDetail::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);

                DB::commit();
            session()->flash('success', 'cash out flow request has been validated !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function reject($cash_outflow_request_no)
    {
       if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any cash out flow request !');
        }

        try {DB::beginTransaction();

        CashOutFlowRequest::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);
            CashOutFlowRequestDetail::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);

                DB::commit();
            session()->flash('success', 'Cash Outflow request has been rejected successfuly !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function reset($cash_outflow_request_no)
    {
       if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any cash out flow request !');
        }

        try {DB::beginTransaction();

        CashOutFlowRequest::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
            CashOutFlowRequestDetail::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);

                DB::commit();
            session()->flash('success', 'Drinkcash out flow request has been reseted !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function confirm($cash_outflow_request_no)
    {
       if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any cash out flow request !');
        }

        try {DB::beginTransaction();

            CashOutFlowRequest::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            CashOutFlowRequestDetail::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'cash out flow request has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function approuve($cash_outflow_request_no)
    {
       if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any cash out flow request !');
        }


        try {DB::beginTransaction();

            CashOutFlowRequest::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['status' => 4,'approuved_by' => $this->user->name]);
            CashOutFlowRequestDetail::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['status' => 4,'approuved_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'cash out flow request has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function document($cash_outflow_request_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.print')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }

        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $stat = CashOutFlowRequest::where('cash_outflow_request_no', $cash_outflow_request_no)->value('status');
        $description = CashOutFlowRequest::where('cash_outflow_request_no', $cash_outflow_request_no)->value('description');
        $date = CashOutFlowRequest::where('cash_outflow_request_no', $cash_outflow_request_no)->value('date');
        $data = CashOutFlowRequest::where('cash_outflow_request_no', $cash_outflow_request_no)->first();
        if($stat == 2 || $stat == 3 || $stat == 4 || $stat == 5 || $stat == 6 || $stat == -3){
           $cash_outflow_request_no = CashOutFlowRequest::where('cash_outflow_request_no', $cash_outflow_request_no)->value('cash_outflow_request_no');
           $cash_outflow_request_signature = CashOutFlowRequest::where('cash_outflow_request_no', $cash_outflow_request_no)->value('cash_outflow_request_signature');
           $cost_wvat = DB::table('cash_outflow_request_details')
            ->where('cash_outflow_request_no', '=', $cash_outflow_request_no)
            ->sum('cost_wvat');
            $cost_nvat = DB::table('cash_outflow_request_details')
            ->where('cash_outflow_request_no', '=', $cash_outflow_request_no)
            ->sum('cost_nvat');
            $vat = DB::table('cash_outflow_request_details')
            ->where('cash_outflow_request_no', '=', $cash_outflow_request_no)
            ->sum('vat');

           $datas = CashOutFlowRequestDetail::with(['material', 'item', 'rawMaterial', 'expense'])->where('cash_outflow_request_no', $cash_outflow_request_no)->get();
           $pdf = PDF::setOptions([
                'defaultFont' => 'dejavu sans',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ])->loadView('backend.pages.accounting.document.cash_outflow_request',compact('datas','cash_outflow_request_no','setting','description','date','cash_outflow_request_signature','cost_wvat','cost_nvat','vat','data'));

           Storage::put('public/pdf/accounting/cash flow request/'.'FICHE DE DEMANDE DE SORTIE DE FONDS '.$cash_outflow_request_no.'.pdf', $pdf->output());

           // download pdf file
           return $pdf->download('FICHE DE DEMANDE DE SORTIE DE FONDS '.$cash_outflow_request_no.'.pdf'); 
           
        }else if ($stat == -1) {
            session()->flash('error', 'cash out flow request has been rejected !!');
            return back();
        }else{
            session()->flash('error', 'wait until cash_out_flow_request will be validated !!');
            return back();
        }
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($cash_outflow_request_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow_request.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any cash out flow request !');
        }
        /*
        try {DB::beginTransaction();

        $cash_out_flow_request = CashOutFlowRequest::where('cash_outflow_request_no',$cash_outflow_request_no)->first();
        if (!is_null($cash_out_flow_request)) {
            $cash_out_flow_request->delete();
            CashOutFlowRequestDetail::where('cash_outflow_request_no',$cash_outflow_request_no)->delete();
        }

        DB::commit();
            session()->flash('success', 'cash out flow request has been deleted !!');
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
