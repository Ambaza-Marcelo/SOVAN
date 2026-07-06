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
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterialPurchaseDetail;
use App\Models\RawMaterialSupplierOrder;
use App\Models\Supplier;
use Validator;
use PDF;
use Mail;
use Carbon\Carbon;

class RawMaterialPurchaseController extends Controller
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
        if (is_null($this->user) || !$this->user->can('raw_material_purchase.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any purchase !');
        }
        $query = RawMaterialPurchase::orderBy('id','desc');
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = \Carbon\Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = \Carbon\Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('date', [$dateDebut, $dateFin]);
        } else {
            $query->take(100);
        }
        $purchases = $query->get();
        return view('backend.pages.raw_material_purchase.index', compact('purchases'));
    }

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_purchase.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export purchases !');
        }
        $dateDebut = $request->input('date_debut') ?? $request->input('start_date');
        $dateFin = $request->input('date_fin') ?? $request->input('end_date');
        if (!$dateDebut || !$dateFin) {
            return redirect()->route('admin.raw-material-purchases.index')
                ->with('error', __('messages.export_select_date_range'));
        }
        $filename = 'achats_matieres_premieres_' . \Carbon\Carbon::parse($dateDebut)->format('Y-m-d') . '_' . \Carbon\Carbon::parse($dateFin)->format('Y-m-d') . '.xlsx';
        return \Excel::download(new \App\Exports\RawMaterialPurchaseExport(), $filename);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('raw_material_purchase.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any purchase !');
        }

        $raw_materials  = RawMaterial::orderBy('name','asc')->get();
        $suppliers = Supplier::orderBy('supplier_name','asc')->get();
        return view('backend.pages.raw_material_purchase.create', compact('raw_materials','suppliers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_purchase.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any purchase !');
        }

        $rules = array(
                'date'  => 'required',
                'quantity.*'  => 'nullable',
                'price.*'  => 'nullable',
                'vat_supplier_payer'  => 'required',
                'supplier_id'  => 'required',
                'description'  => 'required|max:490'
            );

            $error = Validator::make($request->all(),$rules);

            if ($error->fails()) {
                return redirect()->back()->withErrors($error)->withInput();
            }

            $ids = (array)($request->r_material_id ?? $request->food_id ?? []);
            $r_material_id = [];
            $quantity = [];
            $price = [];
            $qtyRaw = (array)($request->quantity ?? []);
            $priceRaw = (array)($request->price ?? []);
            foreach ($ids as $idx => $id) {
                if (!empty($id)) {
                    $r_material_id[] = $id;
                    $quantity[] = $qtyRaw[$idx] ?? 0;
                    $price[] = $priceRaw[$idx] ?? 0;
                }
            }

            if (empty($r_material_id)) {
                return redirect()->back()
                    ->with('error', __('messages.at_least_one_raw_material_required'))
                    ->withInput();
            }

            try {DB::beginTransaction();

            $date = $request->date;
            $vat_supplier_payer = $request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $invoice_currency = $request->invoice_currency;
            $supplier_id = $request->supplier_id;
            $description =$request->description;

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $latest = RawMaterialPurchase::orderBy('id','desc')->first();
            if ($latest) {
               $purchase_no = 'RM-PR' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $purchase_no = 'RM-PR' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $purchase_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$purchase_no;
            $created_by = $this->user->name;

            //create purchase
            $purchase = new RawMaterialPurchase();
            $purchase->date = $date;
            $purchase->purchase_signature = $purchase_signature;
            $purchase->purchase_no = $purchase_no;
            $purchase->vat_supplier_payer = $vat_supplier_payer;
            $purchase->vat_rate = $vat_rate;
            $purchase->invoice_currency = $invoice_currency;
            $purchase->supplier_id = $supplier_id;
            $purchase->created_by = $created_by;
            $purchase->description = $description;
            $purchase->created_at = \Carbon\Carbon::now();
            $purchase->save();
            //insert details of purchase No.
            for( $count = 0; $count < count($r_material_id); $count++ ){

                if($vat_supplier_payer == 1){
                    $price_nvat = (($price[$count] ?? 0)*($quantity[$count] ?? 0));
                    $vat = ($price_nvat* $vat_rate)/100;
                    $price_wvat = $price_nvat + $vat;

                }else{
                    $price_nvat = (($price[$count] ?? 0)*($quantity[$count] ?? 0));
                    $vat = 0;
                    $price_wvat = $price_nvat + $vat;
                }

                $purchase_price = RawMaterial::where('id', $r_material_id[$count])->value('purchase_price');
                $total_value = $price_wvat;
                $data = array(
                    'r_material_id' => $r_material_id[$count],
                    'date' => $date,
                    'quantity' => $quantity[$count] ?? 0,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'invoice_currency' => $invoice_currency,
                    'supplier_id' => $supplier_id,
                    'purchase_price' => $purchase_price,
                    'price' => $price[$count],
                    'description' => $description,
                    'price_nvat' => $price_nvat,
                    'vat' => $vat,
                    'price_wvat' => $price_wvat,
                    'total_value' => $total_value,
                    'created_by' => $created_by,
                    'purchase_no' => $purchase_no,
                    'purchase_signature' => $purchase_signature,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }
            /*
            $mail = Supplier::where('id', $supplier_id)->value('mail');
            $name = Supplier::where('id', $supplier_id)->value('name');

            $mailData = [
                    'title' => 'COMMANDE',
                    'purchase_no' => $purchase_no,
                    'name' => $name,
                    //'body' => 'This is for testing email using smtp.'
                    ];
         
        Mail::to($mail)->send(new OrderMail($mailData));
        */

            RawMaterialPurchaseDetail::insert($insert_data);

        DB::commit();
            session()->flash('success', 'Purchase has been created !!');
            return redirect()->route('admin.raw-material-purchases.index');
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
    public function show($purchase_no)
    {
        //
        $purchase = RawMaterialPurchase::with('supplier')->where('purchase_no', $purchase_no)->first();
        $purchaseDetails = RawMaterialPurchaseDetail::with(['rawMaterial.rawMaterialMeasurement', 'supplier'])
            ->where('purchase_no', $purchase_no)->get();
        
        // Calculate totals
        $totalQuantity = $purchaseDetails->sum('quantity');
        $totalPriceNvat = $purchaseDetails->sum('price_nvat');
        $totalVat = $purchaseDetails->sum('vat');
        $totalPriceWvat = $purchaseDetails->sum('price_wvat');

        $suppliers = Supplier::orderBy('supplier_name', 'asc')->get();
        
        return view('backend.pages.raw_material_purchase.show', compact('purchase', 'purchaseDetails', 'totalQuantity', 'totalPriceNvat', 'totalVat', 'totalPriceWvat', 'suppliers'));
    }

    /**
     * Update only the supplier for the purchase (permission: supplier.edit).
     */
    public function updateSupplier(Request $request, $purchase_no)
    {
        if (is_null($this->user) || !$this->user->can('supplier.edit')) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier le fournisseur.');
        }
        $request->validate(['supplier_id' => 'required|exists:suppliers,id']);
        $purchase = RawMaterialPurchase::where('purchase_no', $purchase_no)->firstOrFail();
        $purchase->update(['supplier_id' => $request->supplier_id]);
        return redirect()->route('admin.raw-material-purchases.show', $purchase_no)->with('success', __('messages.supplier_updated'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $purchase_no
     * @return \Illuminate\Http\Response
     */
    public function edit($purchase_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_purchase.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any purchase !');
        }

        $raw_materials  = RawMaterial::orderBy('name','asc')->get();
        $suppliers = Supplier::orderBy('supplier_name','asc')->get();
        $purchase = RawMaterialPurchase::where('purchase_no', $purchase_no)->first();
        $datas = RawMaterialPurchaseDetail::where('purchase_no', $purchase_no)->get();

        return view('backend.pages.raw_material_purchase.edit', compact('datas', 'purchase', 'raw_materials', 'suppliers'));

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
        if (is_null($this->user) || !$this->user->can('raw_material_purchase.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any purchase !');
        }

       $rules = array(
                'date'  => 'required',
                'quantity.*'  => 'nullable',
                'price.*'  => 'nullable',
                'vat_supplier_payer'  => 'required',
                'supplier_id'  => 'required',
                'description'  => 'required|max:490'
            );

            $error = Validator::make($request->all(),$rules);

            if ($error->fails()) {
                return redirect()->back()->withErrors($error)->withInput();
            }

            $ids = (array)($request->r_material_id ?? $request->food_id ?? []);
            $r_material_id = [];
            $quantity = [];
            $price = [];
            $qtyRaw = (array)($request->quantity ?? []);
            $priceRaw = (array)($request->price ?? []);
            foreach ($ids as $idx => $id) {
                if (!empty($id)) {
                    $r_material_id[] = $id;
                    $quantity[] = $qtyRaw[$idx] ?? 0;
                    $price[] = $priceRaw[$idx] ?? 0;
                }
            }

            if (empty($r_material_id)) {
                return redirect()->back()
                    ->with('error', __('messages.at_least_one_raw_material_required'))
                    ->withInput();
            }

            try {DB::beginTransaction();

            $order_no = RawMaterialSupplierOrder::where('purchase_no',$purchase_no)->value('order_no');

            if (!empty($order_no)) {
                $status = 1;
            }else{
                $status = 1;
            }

            $date = $request->date;
            $vat_supplier_payer = $request->vat_supplier_payer;
            $vat_rate = $request->vat_rate;
            $invoice_currency = $request->invoice_currency;
            $supplier_id = $request->supplier_id;
            $description =$request->description;

            if (!empty($vat_rate)) {
                $vat_rate = $vat_rate;
            }else{
                $vat_rate = 0;
            }

            $created_by = $this->user->name;

            $purchase = RawMaterialPurchase::where('purchase_no',$purchase_no)->first();
            $purchase->date = $date;
            $purchase->vat_supplier_payer = $vat_supplier_payer;
            $purchase->vat_rate = $vat_rate;
            $purchase->invoice_currency = $invoice_currency;
            $purchase->supplier_id = $supplier_id;
            $purchase->created_by = $created_by;
            $purchase->description = $description;
            $purchase->status = $status;
            $purchase->save();
            //insert details of purchase No.
            for( $count = 0; $count < count($r_material_id); $count++ ){

                if($vat_supplier_payer == 1){
                    $price_nvat = (($price[$count] ?? 0)*($quantity[$count] ?? 0));
                    $vat = ($price_nvat* $vat_rate)/100;
                    $price_wvat = $price_nvat + $vat;

                }else{
                    $price_nvat = (($price[$count] ?? 0)*($quantity[$count] ?? 0));
                    $vat = 0;
                    $price_wvat = $price_nvat + $vat;
                }

                $purchase_signature = RawMaterialPurchase::where('purchase_no',$purchase_no)->value('purchase_signature');

                $purchase_price = RawMaterial::where('id', $r_material_id[$count])->value('purchase_price');
                $total_value = $price_wvat;
                $data = array(
                    'r_material_id' => $r_material_id[$count],
                    'date' => $date,
                    'quantity' => $quantity[$count] ?? 0,
                    'vat_supplier_payer' => $vat_supplier_payer,
                    'vat_rate' => $vat_rate,
                    'invoice_currency' => $invoice_currency,
                    'supplier_id' => $supplier_id,
                    'purchase_price' => $purchase_price,
                    'price' => $price[$count],
                    'description' => $description,
                    'price_nvat' => $price_nvat,
                    'vat' => $vat,
                    'price_wvat' => $price_wvat,
                    'total_value' => $total_value,
                    'created_by' => $created_by,
                    'purchase_no' => $purchase_no,
                    'purchase_signature' => $purchase_signature,
                    'status' => $status,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }

            RawMaterialPurchaseDetail::where('purchase_no',$purchase_no)->delete();

            RawMaterialPurchaseDetail::insert($insert_data);

            DB::commit();
            session()->flash('success', 'Plan has been updated successfuly !!');
            return redirect()->route('admin.raw-material-purchases.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function validatePurchase($purchase_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_purchase.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any purchase !');
        }

            try {DB::beginTransaction();
            RawMaterialPurchase::where('purchase_no', '=', $purchase_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            RawMaterialPurchaseDetail::where('purchase_no', '=', $purchase_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);

                DB::commit();
            session()->flash('success', 'purchase has been validated !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function reject($purchase_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_purchase.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any purchase !');
        }

        try {DB::beginTransaction();

        RawMaterialPurchase::where('purchase_no', '=', $purchase_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);
            RawMaterialPurchaseDetail::where('purchase_no', '=', $purchase_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);

                DB::commit();
            session()->flash('success', 'Plan has been rejected successfuly !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function reset($purchase_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_purchase.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any purchase !');
        }

        try {DB::beginTransaction();

        RawMaterialPurchase::where('purchase_no', '=', $purchase_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
            RawMaterialPurchaseDetail::where('purchase_no', '=', $purchase_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);

                DB::commit();
            session()->flash('success', 'Raw Material Purchase has been reseted !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function confirm($purchase_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_purchase.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any purchase !');
        }

        try {DB::beginTransaction();

            RawMaterialPurchase::where('purchase_no', '=', $purchase_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            RawMaterialPurchaseDetail::where('purchase_no', '=', $purchase_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Purchase has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function approuve($purchase_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_purchase.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any purchase !');
        }


        try {DB::beginTransaction();

            RawMaterialPurchase::where('purchase_no', '=', $purchase_no)
                ->update(['status' => 4,'approuved_by' => $this->user->name]);
            RawMaterialPurchaseDetail::where('purchase_no', '=', $purchase_no)
                ->update(['status' => 4,'approuved_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Purchase has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function rawMaterialPurchase($purchase_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_purchase.create')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }

        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $stat = RawMaterialPurchase::where('purchase_no', $purchase_no)->value('status');
        $description = RawMaterialPurchase::where('purchase_no', $purchase_no)->value('description');
        $date = RawMaterialPurchase::where('purchase_no', $purchase_no)->value('date');
        $data = RawMaterialPurchase::where('purchase_no', $purchase_no)->first();
        if($stat == 2 || $stat == 3 || $stat == 4 || $stat == 5 || $stat == 6 || $stat == -3){
           $purchase_no = RawMaterialPurchase::where('purchase_no', $purchase_no)->value('purchase_no');
           $purchase_signature = RawMaterialPurchase::where('purchase_no', $purchase_no)->value('purchase_signature');
           $totalValue = DB::table('raw_material_purchase_details')
            ->where('purchase_no', '=', $purchase_no)
            ->sum('total_value');
            $price_nvat = DB::table('raw_material_purchase_details')
            ->where('purchase_no', '=', $purchase_no)
            ->sum('price_nvat');
            $vat = DB::table('raw_material_purchase_details')
            ->where('purchase_no', '=', $purchase_no)
            ->sum('vat');

           $datas = RawMaterialPurchaseDetail::where('purchase_no', $purchase_no)->get();
           $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.raw_material_purchase', compact('datas','purchase_no','setting','description','date','purchase_signature','totalValue','price_nvat','vat','data'), [], 'UTF-8');

           Storage::put('public/pdf/raw_material_purchase/'.'FICHE_DEMANDE_ACHAT_'.$purchase_no.'.pdf', $pdf->output());

           // download pdf file
           return $pdf->download('FICHE_DEMANDE_ACHAT_'.$purchase_no.'.pdf'); 
           
        }else if ($stat == -1) {
            session()->flash('error', 'Purchase has been rejected !!');
            return back();
        }else{
            session()->flash('error', 'wait until purchase will be validated !!');
            return back();
        }
        
    }

    public function print($purchase_no) { return $this->rawMaterialPurchase($purchase_no); }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($purchase_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_purchase.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any purchase !');
        }
        /*
        try {DB::beginTransaction();

        $purchase = RawMaterialPurchase::where('purchase_no',$purchase_no)->first();
        if (!is_null($purchase)) {
            $purchase->delete();
            RawMaterialPurchaseDetail::where('purchase_no',$purchase_no)->delete();
        }

        DB::commit();
            session()->flash('success', 'Purchase has been deleted !!');
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
