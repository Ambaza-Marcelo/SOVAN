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
use App\Models\Material;
use App\Models\Item;
use App\Models\MaterialPurchase;
use App\Models\MaterialPurchaseDetail;
use App\Models\MaterialSupplierOrder;
use App\Models\MaterialSupplierOrderDetail;
use App\Models\MaterialReception;
use App\Models\Supplier;
use App\Exports\MaterialSupplierOrderExport;
use Validator;
use PDF;
use Mail;
use Excel;
use Carbon\Carbon;

class MaterialSupplierOrderController extends Controller
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
        if (is_null($this->user) || !$this->user->can('material_supplier_order.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any order !');
        }

        $query = MaterialSupplierOrder::query();
        
        // Si des dates sont fournies, filtrer par période
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('created_at', [$dateDebut, $dateFin]);
        } else {
            // Par défaut, afficher les 100 lignes les plus récentes
            $query->take(100);
        }
        
        $orders = $query->orderBy('id','desc')->get();
        return view('backend.pages.material_supplier_order.index', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($purchase_no)
    {
        if (is_null($this->user) || !$this->user->can('material_supplier_order.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any order !');
        }

        $materials  = Material::orderBy('name','asc')->get();
        $items = Item::active()->orderBy('name','asc')->get();
        $suppliers  = Supplier::orderBy('supplier_name','asc')->get();
        $data = MaterialPurchaseDetail::with(['material', 'item'])->where('purchase_no', $purchase_no)->first();
        $datas = MaterialPurchaseDetail::with(['material.materialMeasurement', 'item'])->where('purchase_no', $purchase_no)->get();
        return view('backend.pages.material_supplier_order.create', compact('materials','items','purchase_no','datas','data','suppliers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('material_supplier_order.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any order !');
        }

        $rules = array(
                'date'  => 'required',
                'vat_supplier_payer'  => 'required',
                'supplier_id'  => 'required',
                'quantity.*'  => 'required',
                'purchase_price.*'  => 'required',
                'purchase_no'  => 'required',
                'description'  => 'required|max:490'
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            $material_ids = $request->material_id ?? [];
            $item_ids = $request->item_id ?? [];
            $quantity = $request->quantity;
            $rowCount = count($quantity);
            for ($i = 0; $i < $rowCount; $i++) {
                $mid = $material_ids[$i] ?? null;
                $iid = $item_ids[$i] ?? null;
                if (empty($mid) && empty($iid)) {
                    return response()->json(['error' => [__('messages.line_must_have_item_finished_or_material')]]);
                }
                if (!empty($mid) && !empty($iid)) {
                    return response()->json(['error' => [__('messages.each_line_one_type_only')]]);
                }
            }

            try {DB::beginTransaction();

            $date = $request->date;
            $purchase_no = $request->purchase_no;
            $description =$request->description; 
            $vat_supplier_payer = $request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $invoice_currency = $request->invoice_currency;
            $supplier_id = $request->supplier_id;
            $quantity = $request->quantity;
            $purchase_price = $request->purchase_price;

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $latest = MaterialSupplierOrder::orderBy('id','desc')->first();
            if ($latest) {
               $order_no = 'M-PO' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $order_no = 'M-PO' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $created_by = $this->user->name;
            
            $order_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$order_no;

            $purchase_price_arr = $request->purchase_price;

            for($count = 0; $count < $rowCount; $count++){
                $mid = $material_ids[$count] ?? null;
                $iid = $item_ids[$count] ?? null;
                $material_id_val = !empty($mid) ? $mid : null;
                $item_id_val = !empty($iid) ? $iid : null;

                if($vat_supplier_payer == 1){
                    $price_nvat = ($purchase_price_arr[$count]*$quantity[$count]);
                    $vat = ($price_nvat* $vat_rate)/100;
                    $price_wvat = $price_nvat + $vat;

                }else{
                    $price_nvat = ($purchase_price_arr[$count]*$quantity[$count]);
                    $vat = 0;
                    $price_wvat = $price_nvat + $vat;
                }

                $total_value = $price_wvat;

                $data = array(
                    'material_id' => $material_id_val,
                    'item_id' => $item_id_val,
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'invoice_currency' => $invoice_currency,
                    'supplier_id' => $supplier_id,
                    'purchase_price' => $purchase_price_arr[$count],
                    'price_nvat' => $price_nvat,
                    'vat' => $vat,
                    'price_wvat' => $price_wvat,
                    'total_value' => $total_value,
                    'purchase_no' => $purchase_no,
                    'order_no' => $order_no,
                    'order_signature' => $order_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }
            MaterialSupplierOrderDetail::insert($insert_data);


            //create order
            $order = new MaterialSupplierOrder();
            $order->date = $date;
            $order->order_no = $order_no;
            $order->order_signature = $order_signature;
            $order->purchase_no = $purchase_no;
            $order->vat_supplier_payer = $vat_supplier_payer;
            $order->vat_rate = $vat_rate;
            $order->invoice_currency = $invoice_currency;
            $order->supplier_id = $supplier_id;
            $order->created_by = $created_by;
            $order->status = 1;
            $order->description = $description;
            $order->created_at = \Carbon\Carbon::now();
            $order->save();

            $purchase_no = MaterialSupplierOrder::where('order_no',$order_no)->value('purchase_no');

            MaterialPurchase::where('purchase_no', '=', $purchase_no)
                ->update(['flag1' => 1]);
            MaterialPurchaseDetail::where('purchase_no', '=', $purchase_no)
                ->update(['flag1' => 1]);

            DB::commit();
            session()->flash('success', 'order has been created !!');
            return redirect()->route('admin.material-supplier-orders.index');
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
    public function show($order_no)
    {
        //
        $order = MaterialSupplierOrder::with('supplier')->where('order_no', $order_no)->first();
        $orderDetails = MaterialSupplierOrderDetail::with(['material.materialMeasurement', 'supplier'])
            ->where('order_no', $order_no)->get();
        
        // Calculate totals
        $totalQuantity = $orderDetails->sum('quantity');
        $totalPriceNvat = $orderDetails->sum('price_nvat');
        $totalVat = $orderDetails->sum('vat');
        $totalPriceWvat = $orderDetails->sum('price_wvat');

        $suppliers = Supplier::orderBy('supplier_name', 'asc')->get();
        
        return view('backend.pages.material_supplier_order.show', compact('order', 'orderDetails', 'totalQuantity', 'totalPriceNvat', 'totalVat', 'totalPriceWvat', 'suppliers'));
    }

    /**
     * Update only the supplier for the order (permission: supplier.edit).
     */
    public function updateSupplier(Request $request, $order_no)
    {
        if (is_null($this->user) || !$this->user->can('supplier.edit')) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier le fournisseur.');
        }
        $request->validate(['supplier_id' => 'required|exists:suppliers,id']);
        $order = MaterialSupplierOrder::where('order_no', $order_no)->firstOrFail();
        $order->update(['supplier_id' => $request->supplier_id]);
        return redirect()->route('admin.material-supplier-orders.show', $order_no)->with('success', __('messages.supplier_updated'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($purchase_no)
    {
        if (is_null($this->user) || !$this->user->can('material_supplier_order.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any order !');
        }

        $materials  = Material::orderBy('name','asc')->get();
        $items = Item::active()->orderBy('name','asc')->get();
        $suppliers  = Supplier::orderBy('supplier_name','asc')->get();
        $data = MaterialPurchaseDetail::with(['material', 'item'])->where('purchase_no', $purchase_no)->first();
        $datas = MaterialPurchaseDetail::with(['material.materialMeasurement', 'item'])->where('purchase_no', $purchase_no)->get();
        return view('backend.pages.material_supplier_order.edit', compact('materials','items','purchase_no','datas','data','suppliers'));
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $purchase_no
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $purchase_no)
    {
        if (is_null($this->user) || !$this->user->can('material_supplier_order.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any order !');
        }

        $order_no = MaterialSupplierOrder::where('purchase_no',$purchase_no)->value('order_no');

        $order = MaterialSupplierOrder::where('order_no',$order_no)->first();

        $rules = array(
                'date'  => 'required',
                'vat_supplier_payer'  => 'required',
                'supplier_id'  => 'required',
                'quantity.*'  => 'required',
                'purchase_price.*'  => 'required',
                'purchase_no'  => 'required',
                'description'  => 'required|max:490'
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            $material_ids = $request->material_id ?? [];
            $item_ids = $request->item_id ?? [];
            $quantity = $request->quantity;
            $purchase_price = $request->purchase_price;
            $rowCount = count($quantity);
            for ($i = 0; $i < $rowCount; $i++) {
                $mid = $material_ids[$i] ?? null;
                $iid = $item_ids[$i] ?? null;
                if (empty($mid) && empty($iid)) {
                    return response()->json(['error' => [__('messages.line_must_have_item_finished_or_material')]]);
                }
                if (!empty($mid) && !empty($iid)) {
                    return response()->json(['error' => [__('messages.each_line_one_type_only')]]);
                }
            }

            try {DB::beginTransaction();

            $reception_no = MaterialReception::where('order_no',$order_no)->value('reception_no');

            if (!empty($reception_no)) {
                $status = 1;
            }else{
                $status = 1;
            }

            $date = $request->date;
            $purchase_no = $request->purchase_no;
            $description =$request->description; 
            $vat_supplier_payer = $request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $invoice_currency = $request->invoice_currency;
            $supplier_id = $request->supplier_id;

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }
            

            $created_by = $this->user->name;

            for( $count = 0; $count < $rowCount; $count++ ){
                $mid = $material_ids[$count] ?? null;
                $iid = $item_ids[$count] ?? null;
                $material_id_val = !empty($mid) ? $mid : null;
                $item_id_val = !empty($iid) ? $iid : null;

                if($vat_supplier_payer == 1){
                    $price_nvat = ($purchase_price[$count]*$quantity[$count]);
                    $vat = ($price_nvat* $vat_rate)/100;
                    $price_wvat = $price_nvat + $vat;

                }else{
                    $price_nvat = ($purchase_price[$count]*$quantity[$count]);
                    $vat = 0;
                    $price_wvat = $price_nvat + $vat;
                }

                $order_signature = MaterialSupplierOrder::where('order_no',$order_no)->value('order_signature');

                $total_value = $price_wvat;
                $data = array(
                    'material_id' => $material_id_val,
                    'item_id' => $item_id_val,
                    'date' => $date,
                    'quantity' => $quantity[$count],
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'invoice_currency' => $invoice_currency,
                    'supplier_id' => $supplier_id,
                    'purchase_price' => $purchase_price[$count],
                    'price_nvat' => $price_nvat,
                    'vat' => $vat,
                    'price_wvat' => $price_wvat,
                    'total_value' => $total_value,
                    'purchase_no' => $purchase_no,
                    'order_no' => $order_no,
                    'order_signature' => $order_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => $status,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }

            MaterialSupplierOrderDetail::where('order_no',$order_no)->delete();

            MaterialSupplierOrderDetail::insert($insert_data);


            //edit order
            $order->date = $date;
            $order->purchase_no = $purchase_no;
            $order->vat_supplier_payer = $vat_supplier_payer;
            $order->vat_rate = $vat_rate;
            $order->invoice_currency = $invoice_currency;
            $order->supplier_id = $supplier_id;
            $order->created_by = $created_by;
            $order->status = $status;
            $order->description = $description;
            $order->created_at = \Carbon\Carbon::now();
            $order->save();

            DB::commit();
            session()->flash('success', 'order has been created !!');
            return redirect()->route('admin.material-supplier-orders.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
    }

    public function validateOrder($order_no)
    {
       if (is_null($this->user) || !$this->user->can('material_supplier_order.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any order !');
        }

        try {DB::beginTransaction();

            MaterialSupplierOrder::where('order_no', '=', $order_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            MaterialSupplierOrderDetail::where('order_no', '=', $order_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);

                DB::commit();
            session()->flash('success', 'order has been validated !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function reject($order_no)
    {
       if (is_null($this->user) || !$this->user->can('material_supplier_order.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any order !');
        }

        try {DB::beginTransaction();

        MaterialSupplierOrder::where('order_no', '=', $order_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);
            MaterialSupplierOrderDetail::where('order_no', '=', $order_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);

                DB::commit();
            session()->flash('success', 'Order has been rejected !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function reset($order_no)
    {
       if (is_null($this->user) || !$this->user->can('material_supplier_order.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any order !');
        }

        try {DB::beginTransaction();

        MaterialSupplierOrder::where('order_no', '=', $order_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
            MaterialSupplierOrderDetail::where('order_no', '=', $order_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);

         DB::commit();
            session()->flash('success', 'Order has been reseted !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function confirm($order_no)
    {
       if (is_null($this->user) || !$this->user->can('material_supplier_order.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any order !');
        }

        try {DB::beginTransaction();

        MaterialSupplierOrder::where('order_no', '=', $order_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            MaterialSupplierOrderDetail::where('order_no', '=', $order_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Order has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function approuve($order_no)
    {
       if (is_null($this->user) || !$this->user->can('material_supplier_order.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any order !');
        }

        try {DB::beginTransaction();

        MaterialSupplierOrder::where('order_no', '=', $order_no)
                ->update(['status' => 4,'approuved_by' => $this->user->name]);
            MaterialSupplierOrderDetail::where('order_no', '=', $order_no)
                ->update(['status' => 4,'approuved_by' => $this->user->name]);

        $purchase_no = MaterialSupplierOrder::where('order_no',$order_no)->value('purchase_no');

        MaterialPurchase::where('purchase_no', '=', $purchase_no)
                        ->update(['status' => 5]);
        MaterialPurchaseDetail::where('purchase_no', '=', $purchase_no)
                        ->update(['status' => 5]);

        DB::commit();
            session()->flash('success', 'Order has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function materialSupplierOrder($order_no)
    {
        if (is_null($this->user) || !$this->user->can('material_supplier_order.create')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }

        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $stat = MaterialSupplierOrder::where('order_no', $order_no)->value('status');
        $description = MaterialSupplierOrder::where('order_no', $order_no)->value('description');
        $date = MaterialSupplierOrder::where('order_no', $order_no)->value('date');
        $data = MaterialSupplierOrder::where('order_no', $order_no)->first();
        if($stat == 2 || $stat == 3 || $stat == 4 || $stat == 5 || $stat == -3){
           $order_no = MaterialSupplierOrder::where('order_no', $order_no)->value('order_no');
           $order_signature = MaterialSupplierOrder::where('order_no', $order_no)->value('order_signature');
           $totalValue = DB::table('material_supplier_order_details')
            ->where('order_no', '=', $order_no)
            ->sum('total_value');
            $price_nvat = DB::table('material_supplier_order_details')
            ->where('order_no', '=', $order_no)
            ->sum('price_nvat');
            $vat = DB::table('material_supplier_order_details')
            ->where('order_no', '=', $order_no)
            ->sum('vat');

           $datas = MaterialSupplierOrderDetail::with(['material.materialMeasurement', 'item', 'supplier'])->where('order_no', $order_no)->get();
           $data = MaterialSupplierOrder::with('supplier')->where('order_no', $order_no)->first();
           $amountInWords = \App\Helpers\NumberToWordsFrench::convert((int) round($totalValue)) . ' francs burundais';
           $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])
            ->loadView('backend.pages.document.material_supplier_order', compact('datas','order_no','setting','description','date','order_signature','totalValue','data','price_nvat','vat','amountInWords'), [], 'UTF-8');

           Storage::put('public/pdf/material_supplier_order/'.'BON DE COMMANDE FOURNISSEUR '.$order_no.'.pdf', $pdf->output());

           // download pdf file
           return $pdf->download('BON DE COMMANDE FOURNISSEUR '.$order_no.'.pdf'); 
           
        }else if ($stat == -1) {
            session()->flash('error', 'Order has been rejected !!');
            return back();
        }else{
            session()->flash('error', 'wait until Order will be validated !!');
            return back();
        }
        
    }

    public function print($order_no) { return $this->materialSupplierOrder($order_no); }

    /**
     * Export material supplier orders to Excel for the given date range.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('material_supplier_order.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export orders !');
        }
        $dateDebut = $request->input('date_debut') ?? $request->input('start_date');
        $dateFin = $request->input('date_fin') ?? $request->input('end_date');
        if (!$dateDebut || !$dateFin) {
            return redirect()->route('admin.material-supplier-orders.index')
                ->with('error', __('messages.export_select_date_range'));
        }
        $filename = 'commandes_fournisseurs_materiels_' . Carbon::parse($dateDebut)->format('Y-m-d') . '_' . Carbon::parse($dateFin)->format('Y-m-d') . '.xlsx';
        return Excel::download(new MaterialSupplierOrderExport(), $filename);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $order_no
     * @return \Illuminate\Http\Response
     */
    public function destroy($order_no)
    {
        if (is_null($this->user) || !$this->user->can('material_supplier_order.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any order !');
        }
        /*
        try {DB::beginTransaction();

        $order = MaterialSupplierOrder::where('order_no',$order_no)->first();
        if (!is_null($order)) {
            $order->delete();
            MaterialSupplierOrderDetail::where('order_no',$order_no)->delete();
        }

        DB::commit();
            session()->flash('success', 'Transfer has been deleted !!');
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
