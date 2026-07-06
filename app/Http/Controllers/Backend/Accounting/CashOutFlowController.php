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
use App\Models\Accounting\CashOutFlow;
use App\Models\Accounting\CashOutFlowDetail;
use App\Models\Accounting\AccountCostCenter;
use App\Models\Accounting\AccountJournalEntry;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountAccount;
use App\Models\RawMaterialReceptionDetail;
use App\Models\MaterialReceptionDetail;
use App\Models\RawMaterialReception;
use App\Models\MaterialReception;
use App\Models\HrEmploye;
use App\Models\Supplier;
use App\Models\RawMaterial;
use App\Models\Material;
use App\Models\Item;
use App\Models\Accounting\Currency;
use App\Models\HrLoan;
use Validator;
use PDF;
use Mail;
use Carbon\Carbon;

class CashOutFlowController extends Controller
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
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any cash out flow !');
        }

        $query = CashOutFlow::query();
        
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
        return view('backend.pages.accounting.cash_outflow.index', compact('datas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($cash_outflow_request_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow !');
        }

        $expenses  = AccountExpense::orderBy('name','asc')->get();
        $suppliers = Supplier::orderBy('supplier_name','asc')->get();
        $employes = HrEmploye::orderBy('firstname','asc')->get();
        $currencies = Currency::orderBy('currency_name','asc')->get();
        $cost_centers = AccountCostCenter::orderBy('name','asc')->get();
        $accounts = AccountAccount::where('account_type',3)->orWhere('account_type',7)->get();

        $data = CashOutFlowRequest::where('cash_outflow_request_no', $cash_outflow_request_no)->first();
        $datas = CashOutFlowRequestDetail::where('cash_outflow_request_no', $cash_outflow_request_no)->get();

        return view('backend.pages.accounting.cash_outflow.create', compact('expenses','suppliers','employes','currencies','cost_centers','datas','data','accounts','cash_outflow_request_no'));
    }

    public function payment($cash_outflow_request_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow !');
        }

        $expenses  = AccountExpense::orderBy('name','asc')->get();
        $suppliers = Supplier::orderBy('supplier_name','asc')->get();
        $employes = HrEmploye::orderBy('firstname','asc')->get();
        $raw_materials = RawMaterial::orderBy('name','asc')->get();
        $materials = Material::orderBy('name','asc')->get();
        $currencies = Currency::orderBy('currency_name','asc')->get();
        $cost_centers = AccountCostCenter::orderBy('name','asc')->get();
        $accounts = AccountAccount::where('account_type',3)->orWhere('account_type',7)->get();

        $data = CashOutFlowRequestDetail::with(['material','item'])->where('cash_outflow_request_no', $cash_outflow_request_no)->first();
        $datas = CashOutFlowRequestDetail::with(['material','item'])->where('cash_outflow_request_no', $cash_outflow_request_no)->get();
        $items = Item::active()->orderBy('name','asc')->get();
        if (!empty($data->r_material_id)) {
            return view('backend.pages.accounting.cash_outflow.raw_material_payment_create', compact('expenses','suppliers','employes','currencies','cost_centers','datas','data','accounts','cash_outflow_request_no','raw_materials'));
        }elseif (!empty($data->material_id) || !empty($data->item_id)) {
            return view('backend.pages.accounting.cash_outflow.material_payment_create', compact('expenses','suppliers','employes','currencies','cost_centers','datas','data','accounts','cash_outflow_request_no','materials','items'));
        }

        
    }

    public function paymentBefore($cash_outflow_request_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow !');
        }

        $expenses  = AccountExpense::orderBy('name','asc')->get();
        $suppliers = Supplier::orderBy('supplier_name','asc')->get();
        $employes = HrEmploye::orderBy('firstname','asc')->get();
        $raw_materials = RawMaterial::orderBy('name','asc')->get();
        $materials = Material::orderBy('name','asc')->get();
        $currencies = Currency::orderBy('currency_name','asc')->get();
        $cost_centers = AccountCostCenter::orderBy('name','asc')->get();
        $accounts = AccountAccount::where('account_type',3)->orWhere('account_type',7)->get();

        $data = CashOutFlowRequestDetail::with(['material','item'])->where('cash_outflow_request_no', $cash_outflow_request_no)->first();
        $datas = CashOutFlowRequestDetail::with(['material','item'])->where('cash_outflow_request_no', $cash_outflow_request_no)->get();
        $items = Item::active()->orderBy('name','asc')->get();
        if (!empty($data->r_material_id)) {
            return view('backend.pages.accounting.cash_outflow.raw_material_payment_create_before', compact('expenses','suppliers','employes','currencies','cost_centers','datas','data','accounts','cash_outflow_request_no','raw_materials'));
        }elseif (!empty($data->material_id) || !empty($data->item_id)) {
            return view('backend.pages.accounting.cash_outflow.material_payment_create_before', compact('expenses','suppliers','employes','currencies','cost_centers','datas','data','accounts','cash_outflow_request_no','materials','items'));
        }

    }

    public function loanPayment($cash_outflow_request_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow !');
        }

        $employes = HrEmploye::orderBy('firstname','asc')->get();
        $currencies = Currency::orderBy('currency_name','asc')->get();
        $cost_centers = AccountCostCenter::orderBy('name','asc')->get();
        $accounts = AccountAccount::where('account_type',3)->orWhere('account_type',7)->get();

        $data = CashOutFlowRequestDetail::where('cash_outflow_request_no', $cash_outflow_request_no)
            ->whereNotNull('loan_id')
            ->firstOrFail();
        
        $loan = HrLoan::with('employe')->findOrFail($data->loan_id);
        $cashOutFlowRequest = CashOutFlowRequest::where('cash_outflow_request_no', $cash_outflow_request_no)->firstOrFail();

        return view('backend.pages.accounting.cash_outflow.loan_create', compact('loan','employes','currencies','cost_centers','accounts','data','cashOutFlowRequest','cash_outflow_request_no'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow !');
        }

        $rules = array(
                'date'  => 'required',
                'asker'  => 'required',
                'description'  => 'required|min:5|max:500',
                'vat_supplier_payer'  => 'required',
                'account_id'  => 'required',
                'cash_outflow_request_no'  => 'required',
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
            $cash_outflow_request_no = $request->cash_outflow_request_no;
            $description = $request->description;
            $created_by = $this->user->name;
            $status = 0;
            $vat_supplier_payer =$request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $account_id = $request->account_id;
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

            $latest = CashOutFlow::orderBy('id','desc')->first();
            if ($latest) {
               $cash_outflow_no = (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $cash_outflow_no = (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $cash_outflow_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$cash_outflow_no;
            $created_by = $this->user->name;

            //create cash_out_flow
            $cash_out_flow = new CashOutFlow();
            $cash_out_flow->date = $date;
            $cash_out_flow->cash_outflow_signature = $cash_outflow_signature;
            $cash_out_flow->cash_outflow_no = $cash_outflow_no;
            $cash_out_flow->asker = $asker;
            $cash_out_flow->cash_outflow_request_no = $cash_outflow_request_no;
            $cash_out_flow->description = $description;
            $cash_out_flow->status = $status;
            $cash_out_flow->vat_supplier_payer = $vat_supplier_payer;
            $cash_out_flow->vat_rate = $vat_rate;
            $cash_out_flow->account_id = $account_id;
            $cash_out_flow->cost_center_id = $cost_center_id;
            $cash_out_flow->employe_id = $employe_id;
            $cash_out_flow->currency_id = $currency_id;
            $cash_out_flow->created_by = $created_by;
            $cash_out_flow->created_at = \Carbon\Carbon::now();
            $cash_out_flow->save();
            //insert details of cash_out_flow No.
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
                    'cash_outflow_request_no' => $cash_outflow_request_no,
                    'status' => $status,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'account_id' => $account_id,
                    'cost_price' => $cost_price,
                    'description' => $description,
                    'cost_center_id' => $cost_center_id,
                    'employe_id' => $employe_id,
                    'currency_id' => $currency_id,
                    'cost_nvat' => $cost_nvat,
                    'vat' => $vat,
                    'cost_wvat' => $cost_wvat,
                    'created_by' => $created_by,
                    'cash_outflow_no' => $cash_outflow_no,
                    'cash_outflow_signature' => $cash_outflow_signature,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }

            CashOutFlowDetail::insert($insert_data);

            CashOutFlowRequest::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag1' => 1]);
            CashOutFlowRequestDetail::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag1' => 1]);

        DB::commit();
            session()->flash('success', 'cash out flow has been created !!');
            return redirect()->route('admin.cash-out-flow.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function storeFoodPayment(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow !');
        }

        $rules = array(
                'date'  => 'required',
                'asker'  => 'required',
                'description'  => 'required|min:5|max:500',
                'vat_supplier_payer'  => 'required',
                'account_id'  => 'required',
                'cash_outflow_request_no'  => 'required',
                //'cost_center_id'  => 'required',
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
            $cash_outflow_request_no = $request->cash_outflow_request_no;
            $description = $request->description;
            $created_by = $this->user->name;
            $status = 0;
            $vat_supplier_payer =$request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $account_id = $request->account_id;
            $cost_center_id = $request->cost_center_id;
            $supplier_id = $request->supplier_id;
            $currency_id = $request->currency_id;
            $r_material_id = $request->r_material_id;
            $quantity = $request->quantity;
            $cost_price = $request->cost_price;

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $latest = CashOutFlow::orderBy('id','desc')->first();
            if ($latest) {
               $cash_outflow_no = (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $cash_outflow_no = (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $cash_outflow_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$cash_outflow_no;
            $created_by = $this->user->name;

            //create cash_out_flow
            $cash_out_flow = new CashOutFlow();
            $cash_out_flow->date = $date;
            $cash_out_flow->cash_outflow_signature = $cash_outflow_signature;
            $cash_out_flow->cash_outflow_no = $cash_outflow_no;
            $cash_out_flow->asker = $asker;
            $cash_out_flow->cash_outflow_request_no = $cash_outflow_request_no;
            $cash_out_flow->description = $description;
            $cash_out_flow->status = $status;
            $cash_out_flow->vat_supplier_payer = $vat_supplier_payer;
            $cash_out_flow->vat_rate = $vat_rate;
            $cash_out_flow->account_id = $account_id;
            $cash_out_flow->cost_center_id = $cost_center_id;
            $cash_out_flow->supplier_id = $supplier_id;
            $cash_out_flow->currency_id = $currency_id;
            $cash_out_flow->created_by = $created_by;
            $cash_out_flow->created_at = \Carbon\Carbon::now();
            $cash_out_flow->save();
            //insert details of cash_out_flow No.
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
                    'cash_outflow_request_no' => $cash_outflow_request_no,
                    'status' => $status,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'account_id' => $account_id,
                    'cost_price' => $cost_price[$count],
                    'description' => $description,
                    'cost_center_id' => $cost_center_id,
                    'supplier_id' => $supplier_id,
                    'currency_id' => $currency_id,
                    'cost_nvat' => $cost_nvat,
                    'vat' => $vat,
                    'cost_wvat' => $cost_wvat,
                    'created_by' => $created_by,
                    'cash_outflow_no' => $cash_outflow_no,
                    'cash_outflow_signature' => $cash_outflow_signature,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }

            CashOutFlowDetail::insert($insert_data);

            CashOutFlowRequest::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag1' => 1]);
            CashOutFlowRequestDetail::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag1' => 1]);

        DB::commit();
            session()->flash('success', 'cash out flow has been created !!');
            return redirect()->route('admin.cash-out-flow.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function storeFoodPaymentBefore(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow !');
        }

        $rules = array(
                'date'  => 'required',
                'asker'  => 'required',
                'description'  => 'required|min:5|max:500',
                'vat_supplier_payer'  => 'required',
                'account_id'  => 'required',
                'cash_outflow_request_no'  => 'required',
                //'cost_center_id'  => 'required',
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
            $cash_outflow_request_no = $request->cash_outflow_request_no;
            $description = $request->description;
            $created_by = $this->user->name;
            $status = 0;
            $flag2 = 1;
            $vat_supplier_payer =$request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $account_id = $request->account_id;
            $cost_center_id = $request->cost_center_id;
            $employe_id = $request->employe_id;
            $currency_id = $request->currency_id;
            $r_material_id = $request->r_material_id;
            $quantity = $request->quantity;
            $cost_price = $request->cost_price;

            $purchase_no = CashOutFlowRequest::where('cash_outflow_request_no',$cash_outflow_request_no)->value('purchase_no');

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $latest = CashOutFlow::orderBy('id','desc')->first();
            if ($latest) {
               $cash_outflow_no = (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $cash_outflow_no = (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $cash_outflow_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$cash_outflow_no;
            $created_by = $this->user->name;

            //create cash_out_flow
            $cash_out_flow = new CashOutFlow();
            $cash_out_flow->date = $date;
            $cash_out_flow->cash_outflow_signature = $cash_outflow_signature;
            $cash_out_flow->cash_outflow_no = $cash_outflow_no;
            $cash_out_flow->asker = $asker;
            $cash_out_flow->purchase_no = $purchase_no;
            $cash_out_flow->cash_outflow_request_no = $cash_outflow_request_no;
            $cash_out_flow->description = $description;
            $cash_out_flow->status = $status;
            $cash_out_flow->flag2 = $flag2;
            $cash_out_flow->vat_supplier_payer = $vat_supplier_payer;
            $cash_out_flow->vat_rate = $vat_rate;
            $cash_out_flow->account_id = $account_id;
            $cash_out_flow->cost_center_id = $cost_center_id;
            $cash_out_flow->employe_id = $employe_id;
            $cash_out_flow->currency_id = $currency_id;
            $cash_out_flow->created_by = $created_by;
            $cash_out_flow->created_at = \Carbon\Carbon::now();
            $cash_out_flow->save();
            //insert details of cash_out_flow No.
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
                    'cash_outflow_request_no' => $cash_outflow_request_no,
                    'status' => $status,
                    'flag2' => $flag2,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'account_id' => $account_id,
                    'cost_price' => $cost_price[$count],
                    'description' => $description,
                    'cost_center_id' => $cost_center_id,
                    'employe_id' => $employe_id,
                    'currency_id' => $currency_id,
                    'cost_nvat' => $cost_nvat,
                    'vat' => $vat,
                    'cost_wvat' => $cost_wvat,
                    'created_by' => $created_by,
                    'cash_outflow_no' => $cash_outflow_no,
                    'cash_outflow_signature' => $cash_outflow_signature,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }

            CashOutFlowDetail::insert($insert_data);

            CashOutFlowRequest::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag1' => 1]);
            CashOutFlowRequestDetail::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag1' => 1]);


        DB::commit();
            session()->flash('success', 'cash out flow has been created !!');
            return redirect()->route('admin.cash-out-flow.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function storeMaterialPayment(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow !');
        }

        $rules = array(
                'date'  => 'required',
                'asker'  => 'required',
                'description'  => 'required|min:5|max:500',
                'vat_supplier_payer'  => 'required',
                'account_id'  => 'required',
                'cash_outflow_request_no'  => 'required',
                //'cost_center_id'  => 'required',
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
            $cash_outflow_request_no = $request->cash_outflow_request_no;
            $description = $request->description;
            $created_by = $this->user->name;
            $status = 0;
            $vat_supplier_payer =$request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $account_id = $request->account_id;
            $cost_center_id = $request->cost_center_id;
            $supplier_id = $request->supplier_id;
            $currency_id = $request->currency_id;
            $quantity = $request->quantity;
            $cost_price = $request->cost_price;

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $latest = CashOutFlow::orderBy('id','desc')->first();
            if ($latest) {
               $cash_outflow_no = (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $cash_outflow_no = (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $cash_outflow_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$cash_outflow_no;
            $created_by = $this->user->name;

            //create cash_out_flow
            $cash_out_flow = new CashOutFlow();
            $cash_out_flow->date = $date;
            $cash_out_flow->cash_outflow_signature = $cash_outflow_signature;
            $cash_out_flow->cash_outflow_no = $cash_outflow_no;
            $cash_out_flow->asker = $asker;
            $cash_out_flow->cash_outflow_request_no = $cash_outflow_request_no;
            $cash_out_flow->description = $description;
            $cash_out_flow->status = $status;
            $cash_out_flow->vat_supplier_payer = $vat_supplier_payer;
            $cash_out_flow->vat_rate = $vat_rate;
            $cash_out_flow->account_id = $account_id;
            $cash_out_flow->cost_center_id = $cost_center_id;
            $cash_out_flow->supplier_id = $supplier_id;
            $cash_out_flow->currency_id = $currency_id;
            $cash_out_flow->created_by = $created_by;
            $cash_out_flow->created_at = \Carbon\Carbon::now();
            $cash_out_flow->save();
            //insert details of cash_out_flow No.
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
                    'cash_outflow_request_no' => $cash_outflow_request_no,
                    'status' => $status,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'account_id' => $account_id,
                    'cost_price' => $cost_price[$count],
                    'description' => $description,
                    'cost_center_id' => $cost_center_id,
                    'supplier_id' => $supplier_id,
                    'currency_id' => $currency_id,
                    'cost_nvat' => $cost_nvat,
                    'vat' => $vat,
                    'cost_wvat' => $cost_wvat,
                    'created_by' => $created_by,
                    'cash_outflow_no' => $cash_outflow_no,
                    'cash_outflow_signature' => $cash_outflow_signature,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }

            CashOutFlowDetail::insert($insert_data);

            CashOutFlowRequest::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag1' => 1]);
            CashOutFlowRequestDetail::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag1' => 1]);

        DB::commit();
            session()->flash('success', 'cash out flow has been created !!');
            return redirect()->route('admin.cash-out-flow.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function storeMaterialPaymentBefore(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow !');
        }

        $rules = array(
                'date'  => 'required',
                'asker'  => 'required',
                'description'  => 'required|min:5|max:500',
                'vat_supplier_payer'  => 'required',
                'account_id'  => 'required',
                'cash_outflow_request_no'  => 'required',
                //'cost_center_id'  => 'required',
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
            $cash_outflow_request_no = $request->cash_outflow_request_no;
            $description = $request->description;
            $created_by = $this->user->name;
            $status = 0;
            $flag2 = 1;
            $vat_supplier_payer =$request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $account_id = $request->account_id;
            $cost_center_id = $request->cost_center_id;
            $employe_id = $request->employe_id;
            $currency_id = $request->currency_id;
            $quantity = $request->quantity;
            $cost_price = $request->cost_price;

            $purchase_no = CashOutFlowRequest::where('cash_outflow_request_no',$cash_outflow_request_no)->value('purchase_no');

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $latest = CashOutFlow::orderBy('id','desc')->first();
            if ($latest) {
               $cash_outflow_no = (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $cash_outflow_no = (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $cash_outflow_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$cash_outflow_no;
            $created_by = $this->user->name;

            //create cash_out_flow
            $cash_out_flow = new CashOutFlow();
            $cash_out_flow->date = $date;
            $cash_out_flow->cash_outflow_signature = $cash_outflow_signature;
            $cash_out_flow->cash_outflow_no = $cash_outflow_no;
            $cash_out_flow->asker = $asker;
            $cash_out_flow->purchase_no = $purchase_no;
            $cash_out_flow->cash_outflow_request_no = $cash_outflow_request_no;
            $cash_out_flow->description = $description;
            $cash_out_flow->status = $status;
            $cash_out_flow->flag2 = $flag2;
            $cash_out_flow->vat_supplier_payer = $vat_supplier_payer;
            $cash_out_flow->vat_rate = $vat_rate;
            $cash_out_flow->account_id = $account_id;
            $cash_out_flow->cost_center_id = $cost_center_id;
            $cash_out_flow->employe_id = $employe_id;
            $cash_out_flow->currency_id = $currency_id;
            $cash_out_flow->created_by = $created_by;
            $cash_out_flow->created_at = \Carbon\Carbon::now();
            $cash_out_flow->save();
            //insert details of cash_out_flow No.
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
                    'cash_outflow_request_no' => $cash_outflow_request_no,
                    'status' => $status,
                    'flag2' => $flag2,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'account_id' => $account_id,
                    'cost_price' => $cost_price[$count],
                    'description' => $description,
                    'cost_center_id' => $cost_center_id,
                    'employe_id' => $employe_id,
                    'currency_id' => $currency_id,
                    'cost_nvat' => $cost_nvat,
                    'vat' => $vat,
                    'cost_wvat' => $cost_wvat,
                    'created_by' => $created_by,
                    'cash_outflow_no' => $cash_outflow_no,
                    'cash_outflow_signature' => $cash_outflow_signature,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }

            CashOutFlowDetail::insert($insert_data);

            CashOutFlowRequest::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag1' => 1]);
            CashOutFlowRequestDetail::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag1' => 1]);


        DB::commit();
            session()->flash('success', 'cash out flow has been created !!');
            return redirect()->route('admin.cash-out-flow.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function storeLoanPayment(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any cash out flow !');
        }

        $rules = array(
            'date'  => 'required',
            'asker'  => 'required',
            'description'  => 'required|min:5|max:500',
            'employe_id'  => 'required',
            'currency_id'  => 'required',
            'loan_id' => 'required',
            'amount_loan' => 'required',
            'account_id' => 'required',
            'cash_outflow_request_no' => 'required'
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
            $cashOutFlowRequest = CashOutFlowRequest::where('cash_outflow_request_no', $request->cash_outflow_request_no)->firstOrFail();
            
            // Vérifier que le statut est 4 (approuvé) et flag1 == 0
            if ($cashOutFlowRequest->status != 4 || $cashOutFlowRequest->flag1 != 0) {
                return response()->json([
                    'error' => ['La demande doit être approuvée (statut 4) et les fonds ne doivent pas encore être sortis (flag1 = 0) !'],
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
            $account_id = $request->account_id;
            $cash_outflow_request_no = $request->cash_outflow_request_no;

            $latest = CashOutFlow::orderBy('id','desc')->first();
            if ($latest) {
               $cash_outflow_no = (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $cash_outflow_no = (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $cash_outflow_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$cash_outflow_no;

            //create cash_out_flow
            $cash_out_flow = new CashOutFlow();
            $cash_out_flow->date = $date;
            $cash_out_flow->cash_outflow_signature = $cash_outflow_signature;
            $cash_out_flow->cash_outflow_no = $cash_outflow_no;
            $cash_out_flow->asker = $asker;
            $cash_out_flow->cash_outflow_request_no = $cash_outflow_request_no;
            $cash_out_flow->description = $description;
            $cash_out_flow->status = $status;
            $cash_out_flow->loan_id = $loan_id;
            $cash_out_flow->employe_id = $employe_id;
            $cash_out_flow->cost_center_id = $cost_center_id;
            $cash_out_flow->currency_id = $currency_id;
            $cash_out_flow->account_id = $account_id;
            $cash_out_flow->created_by = $created_by;
            $cash_out_flow->created_at = \Carbon\Carbon::now();
            $cash_out_flow->save();

            // Insert detail for loan payment
            $data = array(
                'loan_id' => $loan_id,
                'date' => $date,
                'asker' => $asker,
                'cash_outflow_request_no' => $cash_outflow_request_no,
                'status' => $status,
                'employe_id' => $employe_id,
                'cost_price' => $amount_loan,
                'description' => $description,
                'cost_center_id' => $cost_center_id,
                'currency_id' => $currency_id,
                'account_id' => $account_id,
                'cost_nvat' => $amount_loan,
                'vat' => 0,
                'cost_wvat' => $amount_loan,
                'created_by' => $created_by,
                'cash_outflow_no' => $cash_outflow_no,
                'cash_outflow_signature' => $cash_outflow_signature,
                'created_at' => \Carbon\Carbon::now()
            );

            CashOutFlowDetail::insert([$data]);

            // Update flag1 to indicate funds have been released
            CashOutFlowRequest::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag1' => 1]);
            CashOutFlowRequestDetail::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag1' => 1]);

            DB::commit();
            session()->flash('success', 'cash out flow has been created !!');
            return redirect()->route('admin.cash-out-flow.index');
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
    public function show($cash_outflow_no)
    {
        //
         $code = CashOutFlow::where('cash_outflow_no', $cash_outflow_no)->value('cash_outflow_no');
         $cash_out_flows = CashOutFlowDetail::where('cash_outflow_no', $cash_outflow_no)
             ->with(['loan', 'rawMaterial', 'material', 'item', 'expense'])
             ->get();
         $suppliers = Supplier::orderBy('supplier_name', 'asc')->get();
         return view('backend.pages.accounting.cash_outflow.show', compact('cash_out_flows', 'code', 'suppliers'));
    }

    /**
     * Update only the supplier for the cash outflow (permission: supplier.edit).
     */
    public function updateSupplier(Request $request, $cash_outflow_no)
    {
        if (is_null($this->user) || !$this->user->can('supplier.edit')) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier le fournisseur.');
        }
        $request->validate(['supplier_id' => 'required|exists:suppliers,id']);
        $record = CashOutFlow::where('cash_outflow_no', $cash_outflow_no)->firstOrFail();
        $record->update(['supplier_id' => $request->supplier_id]);
        return redirect()->route('admin.cash-out-flow.show', $cash_outflow_no)->with('success', __('messages.supplier_updated'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $cash_outflow_no
     * @return \Illuminate\Http\Response
     */
    public function edit($cash_outflow_request_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any cash out flow !');
        }

        $expenses  = AccountExpense::orderBy('name','asc')->get();
        $suppliers = Supplier::orderBy('supplier_name','asc')->get();
        $employes = HrEmploye::orderBy('firstname','asc')->get();
        $currencies = Currency::orderBy('currency_name','asc')->get();
        $cost_centers = AccountCostCenter::orderBy('name','asc')->get();
        $accounts = AccountAccount::where('account_type',3)->orWhere('account_type',7)->get();
        $data = CashOutFlow::where('cash_outflow_request_no', $cash_outflow_request_no)->first();
        $datas = CashOutFlowRequestDetail::where('cash_outflow_request_no', $cash_outflow_request_no)->get();

        return view('backend.pages.accounting.cash_outflow.edit', compact('expenses','suppliers','employes','currencies','data','datas','cost_centers','accounts','cash_outflow_request_no'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $cash_outflow_no
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $cash_outflow_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any cash out flow !');
        }

        $cash_out_flow = CashOutFlow::where('cash_outflow_no',$cash_outflow_no)->first();

       $rules = array(
                'date'  => 'required',
                'asker'  => 'required',
                'description'  => 'required|min:5|max:500',
                'vat_supplier_payer'  => 'required',
                'account_id'  => 'required',
                'cash_outflow_request_no'  => 'required',
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

            $cash_outflow_no = CashOutFlow::where('cash_outflow_no',$cash_outflow_no)->value('cash_outflow_no');
            $cash_outflow_signature = CashOutFlow::where('cash_outflow_no',$cash_outflow_no)->value('cash_outflow_signature');

            $date = $request->date;
            $asker = $request->asker;
            $cash_outflow_request_no = $request->cash_outflow_request_no;
            $description = $request->description;
            $created_by = $this->user->name;
            $status = 0;
            $vat_supplier_payer =$request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $account_id = $request->account_id;
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

            $cash_out_flow->date = $date;
            $cash_out_flow->cash_outflow_signature = $cash_outflow_signature;
            $cash_out_flow->cash_outflow_no = $cash_outflow_no;
            $cash_out_flow->asker = $asker;
            $cash_out_flow->cash_outflow_request_no = $cash_outflow_request_no;
            $cash_out_flow->description = $description;
            $cash_out_flow->status = $status;
            $cash_out_flow->vat_supplier_payer = $vat_supplier_payer;
            $cash_out_flow->vat_rate = $vat_rate;
            $cash_out_flow->account_id = $account_id;
            $cash_out_flow->cost_center_id = $cost_center_id;
            $cash_out_flow->employe_id = $employe_id;
            $cash_out_flow->currency_id = $currency_id;
            $cash_out_flow->created_by = $created_by;
            $cash_out_flow->save();
            //insert details of cash_out_flow No.
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
                    'cash_outflow_request_no' => $cash_outflow_request_no,
                    'status' => $status,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'account_id' => $account_id,
                    'cost_price' => $cost_price,
                    'description' => $description,
                    'cost_center_id' => $cost_center_id,
                    'employe_id' => $employe_id,
                    'currency_id' => $currency_id,
                    'cost_nvat' => $cost_nvat,
                    'vat' => $vat,
                    'cost_wvat' => $cost_wvat,
                    'created_by' => $created_by,
                    'cash_outflow_no' => $cash_outflow_no,
                    'cash_outflow_signature' => $cash_outflow_signature,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }

            CashOutFlowDetail::where('cash_outflow_no',$cash_outflow_no)->delete();

            CashOutFlowDetail::insert($insert_data);

            CashOutFlowRequest::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag1' => 1]);
            CashOutFlowRequestDetail::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag1' => 1]);

            DB::commit();
            session()->flash('success', 'Cash outflow has been updated successfuly !!');
            return redirect()->route('admin.cash-out-flow.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function validateCashOutFlow($cash_outflow_no)
    {
       if (is_null($this->user) || !$this->user->can('account_cash_ouflow.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any cash out flow !');
        }

            try {DB::beginTransaction();
            CashOutFlow::where('cash_outflow_no', '=', $cash_outflow_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            CashOutFlowDetail::where('cash_outflow_no', '=', $cash_outflow_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);

                DB::commit();
            session()->flash('success', 'cash out flow has been validated !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function reject($cash_outflow_no)
    {
       if (is_null($this->user) || !$this->user->can('account_cash_ouflow.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any cash out flow !');
        }

        try {DB::beginTransaction();

        CashOutFlow::where('cash_outflow_no', '=', $cash_outflow_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);
            CashOutFlowDetail::where('cash_outflow_no', '=', $cash_outflow_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);

                DB::commit();
            session()->flash('success', 'Cash outflow has been rejected successfuly !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function reset($cash_outflow_no)
    {
       if (is_null($this->user) || !$this->user->can('account_cash_ouflow.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any cash out flow !');
        }

        try {DB::beginTransaction();

        CashOutFlow::where('cash_outflow_no', '=', $cash_outflow_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
            CashOutFlowDetail::where('cash_outflow_no', '=', $cash_outflow_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);

                DB::commit();
            session()->flash('success', 'Drinkcash out flow has been reseted !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function markAsNoReturn($cash_outflow_no)
    {
        if (is_null($this->user) || !$this->user->can('fund_transfer.create')) {
            abort(403, 'Sorry !! You are Unauthorized to mark cash out flow as no return !');
        }

        try {
            DB::beginTransaction();

            CashOutFlow::where('cash_outflow_no', '=', $cash_outflow_no)
                ->update(['status' => 40]);
            CashOutFlowDetail::where('cash_outflow_no', '=', $cash_outflow_no)
                ->update(['status' => 40]);

            DB::commit();
            session()->flash('success', 'La sortie de fonds a été marquée comme sans retour de fonds !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue: ' . $e->getMessage());
            return back();
        }
    }

    public function confirm($cash_outflow_no)
    {
       if (is_null($this->user) || !$this->user->can('account_cash_ouflow.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any cash out flow !');
        }

        try {DB::beginTransaction();

            CashOutFlow::where('cash_outflow_no', '=', $cash_outflow_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            CashOutFlowDetail::where('cash_outflow_no', '=', $cash_outflow_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'cash out flow has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function approuve($cash_outflow_no)
    {
       if (is_null($this->user) || !$this->user->can('account_cash_ouflow.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any cash out flow !');
        }


        try {DB::beginTransaction();

            $data = CashOutFlowDetail::with(['employe', 'supplier', 'expense', 'account'])->where('cash_outflow_no', $cash_outflow_no)->first();
            
            if (empty($data)) {
                DB::rollback();
                session()->flash('error', 'Cash out flow detail not found.');
                return redirect()->back();
            }

            $cash_outflow_request_no = $data->cash_outflow_request_no;
            
            // Initialize arrays
            $insert_journal_c_credit_data = [];
            $insert_journal_debit_data = [];
            $insert_journal_debit_data_acc2 = [];
            $insert_journal_expense_debit_data = [];

            $cost_wvat = (float) DB::table('cash_outflow_details')
                ->where('cash_outflow_no', '=', $cash_outflow_no)
                ->sum('cost_wvat');

        // 1) Avance sur salaire : crédit caisse/banque, débit compte avance (employe->account_id)
        if (!empty($data->loan_id) && !empty($data->employe_id)) {
            if (empty($data->account_id)) {
                DB::rollback();
                session()->flash('error', 'Account ID not found. Please configure account_id.');
                return redirect()->back();
            }

            $balance_acc_c_credit = AccountLedger::where('account_id', $data->account_id)->value('balance') ?? 0;
            if ($balance_acc_c_credit < $cost_wvat) {
                session()->flash('error', 'Verify your balance please in '.$data->account->account_name.' !!');
                return back();
            }

            $accountLedgerState = $this->updateLedgerTotals($data->account_id, 'credit', $cost_wvat);

            $insert_journal_c_credit_data[] = [
                'date' => $data->date,
                'document_no' => $data->cash_outflow_no,
                'description' => $data->description,
                'account_id' => $data->account_id,
                'libelle' => $data->description,
                'credit' => $cost_wvat,
                'balance' => $accountLedgerState['balance'],
                'created_at' => \Carbon\Carbon::now(),
            ];

            AccountJournalEntryDetail::insert($insert_journal_c_credit_data);

            if (empty($data->employe) || empty($data->employe->account_id)) {
                DB::rollback();
                session()->flash('error', 'Advance account ID not found for the employee. Please configure account_id for the employee.');
                return redirect()->back();
            }

            $employeeLedgerState = $this->updateLedgerTotals($data->employe->account_id, 'debit', $cost_wvat);

            $insert_journal_debit_data[] = [
                'date' => $data->date,
                'document_no' => $data->cash_outflow_no,
                'description' => $data->description,
                'account_id' => $data->employe->account_id,
                'libelle' => $data->description,
                'debit' => $cost_wvat,
                'balance' => $employeeLedgerState['balance'],
                'created_at' => \Carbon\Carbon::now(),
            ];

            AccountJournalEntryDetail::insert($insert_journal_debit_data);

        // 2) Achat en espèces (employé) : crédit caisse/banque, débit compte sortie de fonds (employe->cash_out_account_id)
        } elseif (!empty($data->employe_id) && $data->flag1 == 0 && $data->flag2 == 1 && empty($data->loan_id)) {
            if (empty($data->account_id)) {
                DB::rollback();
                session()->flash('error', 'Account ID not found. Please configure account_id.');
                return redirect()->back();
            }

            $balance_acc_c_credit = AccountLedger::where('account_id', $data->account_id)->value('balance') ?? 0;
            if ($balance_acc_c_credit < $cost_wvat) {
                session()->flash('error', 'Verify your balance please in '.$data->account->account_name.' !!');
                return back();
            }

            $accountLedgerState = $this->updateLedgerTotals($data->account_id, 'credit', $cost_wvat);

            $insert_journal_c_credit_data[] = [
                'date' => $data->date,
                'document_no' => $data->cash_outflow_no,
                'description' => $data->description,
                'account_id' => $data->account_id,
                'libelle' => $data->description,
                'credit' => $cost_wvat,
                'balance' => $accountLedgerState['balance'],
                'created_at' => \Carbon\Carbon::now(),
            ];

            AccountJournalEntryDetail::insert($insert_journal_c_credit_data);

            if (empty($data->employe) || empty($data->employe->cash_out_account_id)) {
                DB::rollback();
                session()->flash('error', 'Cash Out Account ID not found for the employee. Please configure cash_out_account_id for the employee.');
                return redirect()->back();
            }

            $employeeLedgerState = $this->updateLedgerTotals($data->employe->cash_out_account_id, 'debit', $cost_wvat);

            $insert_journal_debit_data[] = [
                'date' => $data->date,
                'document_no' => $data->cash_outflow_no,
                'description' => $data->description,
                'account_id' => $data->employe->cash_out_account_id,
                'libelle' => $data->description,
                'debit' => $cost_wvat,
                'balance' => $employeeLedgerState['balance'],
                'created_at' => \Carbon\Carbon::now(),
            ];

            AccountJournalEntryDetail::insert($insert_journal_debit_data);

        // 3) Achat au crédit (fournisseur) : crédit caisse/banque, débit compte fournisseur (supplier->account_id)
        } elseif (!empty($data->supplier_id)) {
            if (empty($data->account_id)) {
                DB::rollback();
                session()->flash('error', 'Account ID not found. Please configure account_id.');
                return redirect()->back();
            }

            $balance_acc_c_credit = AccountLedger::where('account_id', $data->account_id)->value('balance') ?? 0;
            if ($balance_acc_c_credit < $cost_wvat) {
                session()->flash('error', 'Verify your balance please in '.$data->account->account_name.' !!');
                return back();
            }

            $accountLedgerState = $this->updateLedgerTotals($data->account_id, 'credit', $cost_wvat);

            $insert_journal_c_credit_data[] = [
                'date' => $data->date,
                'document_no' => $data->cash_outflow_no,
                'description' => $data->description,
                'account_id' => $data->account_id,
                'libelle' => $data->description,
                'credit' => $cost_wvat,
                'balance' => $accountLedgerState['balance'],
                'created_at' => \Carbon\Carbon::now(),
            ];

            AccountJournalEntryDetail::insert($insert_journal_c_credit_data);

            if (empty($data->supplier) || empty($data->supplier->account_id)) {
                DB::rollback();
                session()->flash('error', 'Account ID not found for the supplier. Please configure account_id for the supplier.');
                return redirect()->back();
            }

            $supplierLedgerState = $this->updateLedgerTotals($data->supplier->account_id, 'debit', $cost_wvat);

            $insert_journal_debit_data_acc2[] = [
                'date' => $data->date,
                'document_no' => $data->cash_outflow_no,
                'description' => $data->description,
                'account_id' => $data->supplier->account_id,
                'libelle' => $data->description,
                'debit' => $cost_wvat,
                'balance' => $supplierLedgerState['balance'],
                'created_at' => \Carbon\Carbon::now(),
            ];

            AccountJournalEntryDetail::insert($insert_journal_debit_data_acc2);

            $purchase_no = CashOutFlowRequest::where('cash_outflow_request_no', $data->cash_outflow_request_no)->value('purchase_no');

            if (!empty($data->drink_id)) {
                DrinkReception::where('reception_no', '=', $purchase_no)
                    ->update(['flag2' => 1]);
                DrinkReceptionDetail::where('reception_no', '=', $purchase_no)
                    ->update(['flag2' => 1]);
            } elseif (!empty($data->r_material_id)) {
                RawMaterialReception::where('reception_no', '=', $purchase_no)
                    ->update(['flag2' => 1]);
                RawMaterialReceptionDetail::where('reception_no', '=', $purchase_no)
                    ->update(['flag2' => 1]);
            } elseif (!empty($data->material_id)) {
                MaterialReception::where('reception_no', '=', $purchase_no)
                    ->update(['flag2' => 1]);
                MaterialReceptionDetail::where('reception_no', '=', $purchase_no)
                    ->update(['flag2' => 1]);
            }

        // 4) Dépenses générales (employé, sans loan_id, flag2 = 0) :
        //    crédit caisse/banque + débit des comptes de charges (expense->default_account_id) par ligne
        } elseif (!empty($data->employe_id) && $data->flag1 == 0 && $data->flag2 == 0 && empty($data->loan_id)) {
            if (empty($data->account_id)) {
                DB::rollback();
                session()->flash('error', 'Account ID not found. Please configure account_id.');
                return redirect()->back();
            }

            $balance_acc_c_credit = AccountLedger::where('account_id',$data->account_id)->value('balance') ?? 0;
            if ($balance_acc_c_credit < $cost_wvat) {
                session()->flash('error', 'Verify your balance please in '.$data->account->account_name.' !!');
                return back();
            }

            $accountLedgerState = $this->updateLedgerTotals($data->account_id, 'credit', $cost_wvat);

            $journalEntryCCreditdata = array(
                    'date' => $data->date,
                    'document_no' => $data->cash_outflow_no,
                    'description' => $data->description,
                    'account_id' => $data->account_id,
                    'libelle' => $data->description,
                    'credit' => $cost_wvat,
                    'balance' => $accountLedgerState['balance'],
                    'created_at' => \Carbon\Carbon::now()
                );
                
                $insert_journal_c_credit_data[] = $journalEntryCCreditdata;
                
            AccountJournalEntryDetail::insert($insert_journal_c_credit_data);

            
            //end credit operation

            //start expenses debit
                $expenseDetails = CashOutFlowDetail::with('expense')->where('cash_outflow_no', $cash_outflow_no)->get();
                $insert_journal_expense_debit_data = [];
                foreach ($expenseDetails as $detail) {
                    if (empty($detail->expense) || empty($detail->expense->default_account_id)) {
                        DB::rollback();
                        session()->flash('error', 'Default Account ID not found for the expense. Please configure default_account_id for the expense.');
                        return redirect()->back();
                    }

                    $expenseLedgerState = $this->updateLedgerTotals($detail->expense->default_account_id, 'debit', $detail->cost_wvat);

                    $insert_journal_expense_debit_data[] = [
                        'date' => $detail->date,
                        'document_no' => $detail->cash_outflow_no,
                        'description' => $detail->description,
                        'account_id' => $detail->expense->default_account_id,
                        'libelle' => $detail->description,
                        'debit' => $detail->cost_wvat,
                        'balance' => $expenseLedgerState['balance'],
                        'created_at' => \Carbon\Carbon::now(),
                    ];
                }

                if (!empty($insert_journal_expense_debit_data)) {
                    AccountJournalEntryDetail::insert($insert_journal_expense_debit_data);
                }

            //end expenses debit

        } else {
            DB::rollback();
            session()->flash('error', 'something went wrong !!');
            return back();
        }

            
            CashOutFlow::where('cash_outflow_no', '=', $cash_outflow_no)
                ->update(['status' => 4,'approuved_by' => $this->user->name]);
            CashOutFlowDetail::where('cash_outflow_no', '=', $cash_outflow_no)
                ->update(['status' => 4,'approuved_by' => $this->user->name]);

            // Update flag2 to prevent further operations
            CashOutFlowRequest::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag2' => 1,'flag1' => 1]);
            CashOutFlowRequestDetail::where('cash_outflow_request_no', '=', $cash_outflow_request_no)
                ->update(['flag2' => 1,'flag1' => 1]);

            DB::commit();
            session()->flash('success', 'cash out flow has been approuved !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function document($cash_outflow_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow.print')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }

        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $stat = CashOutFlow::where('cash_outflow_no', $cash_outflow_no)->value('status');
        $description = CashOutFlow::where('cash_outflow_no', $cash_outflow_no)->value('description');
        $date = CashOutFlow::where('cash_outflow_no', $cash_outflow_no)->value('date');
        $data = CashOutFlow::where('cash_outflow_no', $cash_outflow_no)->first();
        if($stat == 3 || $stat == 4 || $stat == 5 || $stat == 6 || $stat == -3){
           $cash_outflow_no = CashOutFlow::where('cash_outflow_no', $cash_outflow_no)->value('cash_outflow_no');
           $cash_outflow_signature = CashOutFlow::where('cash_outflow_no', $cash_outflow_no)->value('cash_outflow_signature');
           $cost_wvat = DB::table('cash_outflow_details')
            ->where('cash_outflow_no', '=', $cash_outflow_no)
            ->sum('cost_wvat');
            $cost_nvat = DB::table('cash_outflow_details')
            ->where('cash_outflow_no', '=', $cash_outflow_no)
            ->sum('cost_nvat');
            $vat = DB::table('cash_outflow_details')
            ->where('cash_outflow_no', '=', $cash_outflow_no)
            ->sum('vat');

           $datas = CashOutFlowDetail::with(['material', 'item', 'rawMaterial', 'expense'])->where('cash_outflow_no', $cash_outflow_no)->get();
           // Forcer une police compatible UTF-8 pour éviter les caractères illisibles
           $pdf = PDF::setOptions([
                'defaultFont' => 'dejavu sans',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ])->loadView('backend.pages.accounting.document.cash_outflow',compact('datas','cash_outflow_no','setting','description','date','cash_outflow_signature','cost_wvat','cost_nvat','vat','data'));

           Storage::put('public/pdf/accounting/cash flow/'.'BON DE SORTIE DE FONDS '.$cash_outflow_no.'.pdf', $pdf->output());

           // download pdf file
           return $pdf->download('BON DE SORTIE DE FONDS  '.$cash_outflow_no.'.pdf'); 
           
        }else if ($stat == -1) {
            session()->flash('error', 'cash out flow has been rejected !!');
            return back();
        }else{
            session()->flash('error', 'wait until cash_out_flow will be validated !!');
            return back();
        }
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($cash_outflow_no)
    {
        if (is_null($this->user) || !$this->user->can('account_cash_ouflow.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any cash out flow !');
        }
        /*
        try {DB::beginTransaction();

        $cash_out_flow = CashOutFlow::where('cash_outflow_no',$cash_outflow_no)->first();
        if (!is_null($cash_out_flow)) {
            $cash_out_flow->delete();
            CashOutFlowDetail::where('cash_outflow_no',$cash_outflow_no)->delete();
        }

        DB::commit();
            session()->flash('success', 'cash out flow has been deleted !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
        */

    }

    /**
     * Increment debit/credit totals and keep AccountLedger balance in sync.
     */
    private function updateAccountLedgerSums($accountId, $debitIncrement = 0, $creditIncrement = 0)
    {
        $ledger = AccountLedger::where('account_id', $accountId)->lockForUpdate()->first();

        if (!$ledger) {
            $ledger = new AccountLedger();
            $ledger->account_id = $accountId;
            $ledger->debit = 0;
            $ledger->credit = 0;
            $ledger->balance = 0;
        }

        $ledger->debit = ($ledger->debit ?? 0) + $debitIncrement;
        $ledger->credit = ($ledger->credit ?? 0) + $creditIncrement;
        $ledger->balance = ($ledger->debit ?? 0) - ($ledger->credit ?? 0);
        $ledger->save();

        return $ledger;
    }

    /**
     * Update ledger totals using the same pattern as FactureController.
     */
    protected function updateLedgerTotals(int $accountId, string $type, float $amount): array
    {
        $ledger = AccountLedger::firstOrNew(['account_id' => $accountId]);

        $currentDebit = (float) ($ledger->debit ?? 0);
        $currentCredit = (float) ($ledger->credit ?? 0);
        $movement = (float) $amount;

        if ($movement === 0.0) {
            return [
                'debit' => $currentDebit,
                'credit' => $currentCredit,
                'balance' => $currentDebit - $currentCredit,
            ];
        }

        if ($type === 'debit') {
            $currentDebit += $movement;
        } else {
            $currentCredit += $movement;
        }

        $ledger->debit = $currentDebit;
        $ledger->credit = $currentCredit;
        $ledger->balance = $currentDebit - $currentCredit;
        $ledger->save();

        return [
            'debit' => $currentDebit,
            'credit' => $currentCredit,
            'balance' => $ledger->balance,
        ];
    }
}
