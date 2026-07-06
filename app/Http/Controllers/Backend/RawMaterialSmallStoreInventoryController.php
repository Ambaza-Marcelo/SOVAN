<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesStockDocumentApproval;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Storage;
use App\Models\RawMaterial;
use App\Models\RawMaterialSmallStore;
use App\Models\RawMaterialSmallStoreDetail;
use App\Models\RawMaterialSmallStoreInventory;
use App\Models\RawMaterialSmallStoreInventoryDetail;
use App\Models\RawMaterialSmallReport;
use App\Exports\RawMaterialSmStoreInventoryExport;
use Carbon\Carbon;
use Excel;
use App\Models\Setting;
use App\Mail\DeleteInventoryMail;
use PDF;
use Mail;
use Validator;

class RawMaterialSmallStoreInventoryController extends Controller
{
    use HandlesStockDocumentApproval;

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
    public function index()
    {
        if (is_null($this->user) || !$this->user->can('raw_material_small_inventory.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any inventory !');
        }

        $inventories = RawMaterialSmallStoreInventory::all();
        return view('backend.pages.raw_material_small_store_inventory.index', compact('inventories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($code)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_small_inventory.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any inventory !');
        }

        $raw_materials  = RawMaterial::where('code_store',$code)->orderBy('name','asc')->get();
        $datas = RawMaterialSmallStoreDetail::where('code',$code)->where('r_material_id','!=','')->get();
        return view('backend.pages.raw_material_small_store_inventory.create', compact('datas','raw_materials','code'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('raw_material_small_inventory.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any inventory !');
        }
        $rules = array(
            'r_material_id.*' => 'required',
            'date' => 'required|date',
            'title' => 'required',
            //'quantity.*' => 'required',
            //'purchase_price.*' => 'required',
            'new_quantity.*' => 'required',
            'new_purchase_price.*' => 'required',
            'description' => 'required',
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
            $quantity = $request->quantity;
            $purchase_price = $request->purchase_price;
            $new_quantity = $request->new_quantity;
            $new_price = $request->new_price;
            $title = $request->title;
            $code_store = $request->code_store;
            $new_purchase_price = $request->new_purchase_price;

            if (!empty($quantity)) {
                $quantity = $quantity;
            }else{
                $quantity=0;
            }

            if (!empty($new_quantity)) {
                $new_quantity = $new_quantity;
            }else{
                $new_quantity=0;
            }

            $latest = RawMaterialSmallStoreInventory::latest()->first();
            if ($latest) {
               $inventory_no = 'RMI' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $inventory_no = 'RMI' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $inventory_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$inventory_no;

            $created_by = $this->user->name;
            $description =$request->description; 

            for( $count = 0; $count < count($r_material_id); $count++ ){
                $qty = !empty($quantity[$count]) ? $quantity[$count] : 0;
                $price = !empty($purchase_price[$count]) ? $purchase_price[$count] : 0;
                $new_qty = !empty($new_quantity[$count]) ? $new_quantity[$count] : 0;
                $new_price = !empty($new_purchase_price[$count]) ? $new_purchase_price[$count] : 0;
                $total_purchase_value = $qty * $price;
                $new_total_purchase_value = $new_qty * $new_price;
                $total_cump_value = $total_purchase_value;
                $new_total_cump_value = $new_total_purchase_value;
                $relicat = $qty - $new_qty;
                $data = array(
                    'r_material_id' => $r_material_id[$count],
                    'date' => $date,
                    'title' => $title,
                    'code_store' => $code_store,
                    'quantity' => $qty,
                    'purchase_price' => $price,
                    'cump' => $price,
                    'total_purchase_value' => $total_purchase_value,
                    'total_cump_value' => $total_cump_value,
                    'new_quantity' => $new_qty,
                    'new_purchase_price' => $new_price,
                    'new_cump' => $new_price,
                    'new_total_purchase_value' => $new_total_purchase_value,
                    'new_total_cump_value' => $new_total_cump_value,
                    'relicat' => $relicat,
                    'inventory_no' => $inventory_no,
                    'inventory_signature' => $inventory_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
                
            }
            RawMaterialSmallStoreInventoryDetail::insert($insert_data);
            //create inventory
            $inventory = new RawMaterialSmallStoreInventory();
            $inventory->date = $date;
            $inventory->title = $title;
            $inventory->inventory_no = $inventory_no;
            $inventory->code_store = $code_store;
            $inventory->inventory_signature = $inventory_signature;
            $inventory->description = $description;
            $inventory->created_by = $created_by;
            $inventory->save();

            DB::commit();
            session()->flash('success', 'Inventory has been created !!');
            return redirect()->route('admin.raw-material-small-store-inventory.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
         
    }

    public function referenceInventaire()
    {
        if (is_null($this->user) || !$this->user->can('raw_material_small_inventory.view')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }

        $datas = RawMaterialSmallStoreDetail::where('verified',false)->get();
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.reference_inventaire', compact('datas','setting'), [], 'UTF-8');
        return $pdf->download('reference-inventaire'.'.pdf');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $inventory_no
     * @return \Illuminate\Http\Response
     */
    public function show($inventory_no)
    {
        //
        $code = RawMaterialSmallStoreInventoryDetail::where('inventory_no', $inventory_no)->value('inventory_no');
        $inventories = RawMaterialSmallStoreInventoryDetail::where('inventory_no', $inventory_no)->get();
        return view('backend.pages.raw_material_small_store_inventory.show', compact('inventories','code'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function inventory($id)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_small_inventory.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to make any inventory !');
        }

        $stock = RawMaterialBigStoreDetail::find($id);
        $articles  = Article::all();
        return view('backend.pages.inventory.create', compact('stock', 'articles'));
    }

    public function edit($inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_small_inventory.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any inventory !');
        }

        $inventory = RawMaterialSmallStoreInventory::where('inventory_no', $inventory_no)->first();
        
        if (!$inventory) {
            session()->flash('error', 'Inventory not found !!');
            return redirect()->route('admin.raw-material-small-store-inventory.index');
        }

        // Check if inventory is validated (status = 2)
        if ($inventory->status == 2) {
            session()->flash('error', 'Cannot edit validated inventory !!');
            return redirect()->route('admin.raw-material-small-store-inventory.index');
        }

        $inventoryDetails = RawMaterialSmallStoreInventoryDetail::where('inventory_no', $inventory_no)->get();
        $code = $inventory->code_store;
        $raw_materials = RawMaterial::where('code_store', $code)->orderBy('name', 'asc')->get();
        
        return view('backend.pages.raw_material_small_store_inventory.edit', compact('inventory', 'inventoryDetails', 'raw_materials', 'code'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_small_inventory.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any inventory !');
        }

        $inventory = RawMaterialSmallStoreInventory::where('inventory_no', $inventory_no)->first();
        
        if (!$inventory) {
            session()->flash('error', 'Inventory not found !!');
            return redirect()->route('admin.raw-material-small-store-inventory.index');
        }

        // Check if inventory is validated (status = 2)
        if ($inventory->status == 2) {
            session()->flash('error', 'Cannot edit validated inventory !!');
            return redirect()->route('admin.raw-material-small-store-inventory.index');
        }

        $rules = array(
            'r_material_id.*' => 'required',
            'date' => 'required|date',
            'title' => 'required',
            'new_quantity.*' => 'required',
            'new_purchase_price.*' => 'required',
            'description' => 'required',
        );

        $error = Validator::make($request->all(), $rules);

        if ($error->fails()) {
            return response()->json([
                'error' => $error->errors()->all(),
            ]);
        }

        try {
            DB::beginTransaction();

            $r_material_id = $request->r_material_id ?? $request->food_id;
            $date = Carbon::parse($request->date);
            $quantity = $request->quantity ?? [];
            $purchase_price = $request->purchase_price ?? [];
            $new_quantity = $request->new_quantity;
            $new_purchase_price = $request->new_purchase_price;
            $title = $request->title;
            $code_store = $request->code_store;
            $description = $request->description;
            $updated_by = $this->user->name;

            // Delete existing details
            RawMaterialSmallStoreInventoryDetail::where('inventory_no', $inventory_no)->delete();

            // Insert updated details
            for ($count = 0; $count < count($r_material_id); $count++) {
                $qty = isset($quantity[$count]) && $quantity[$count] != '' ? $quantity[$count] : 0;
                $price = isset($purchase_price[$count]) && $purchase_price[$count] != '' ? $purchase_price[$count] : 0;
                $total_purchase_value = $qty * $price;
                $new_total_purchase_value = $new_quantity[$count] * $new_purchase_price[$count];
                $total_cump_value = $total_purchase_value;
                $new_total_cump_value = $new_total_purchase_value;
                $relicat = $qty - $new_quantity[$count];
                
                $data = array(
                    'r_material_id' => $r_material_id[$count],
                    'date' => $date,
                    'title' => $title,
                    'code_store' => $code_store,
                    'quantity' => $qty,
                    'purchase_price' => $price,
                    'cump' => $price,
                    'total_purchase_value' => $total_purchase_value,
                    'total_cump_value' => $total_cump_value,
                    'new_quantity' => $new_quantity[$count],
                    'new_purchase_price' => $new_purchase_price[$count],
                    'new_cump' => $new_purchase_price[$count],
                    'new_total_purchase_value' => $new_total_purchase_value,
                    'new_total_cump_value' => $new_total_cump_value,
                    'relicat' => $relicat,
                    'inventory_no' => $inventory_no,
                    'inventory_signature' => $inventory->inventory_signature,
                    'created_by' => $inventory->created_by,
                    'updated_by' => $updated_by,
                    'description' => $description,
                    'status' => $inventory->status,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }
            
            RawMaterialSmallStoreInventoryDetail::insert($insert_data);

            // Update inventory
            $inventory->date = $date;
            $inventory->title = $title;
            $inventory->description = $description;
            $inventory->updated_by = $updated_by;
            $inventory->save();

            DB::commit();
            session()->flash('success', 'Inventory has been updated !!');
            return redirect()->route('admin.raw-material-small-store-inventory.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bon_inventaire($inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_small_inventory.view')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }

        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $description = RawMaterialSmallStoreInventory::where('inventory_no', $inventory_no)->value('description');
        $title = RawMaterialSmallStoreInventory::where('inventory_no', $inventory_no)->value('title');
        $inventory_no = RawMaterialSmallStoreInventory::where('inventory_no', $inventory_no)->value('inventory_no');
        $date = RawMaterialSmallStoreInventory::where('inventory_no', $inventory_no)->value('date');
        $inventory_signature = RawMaterialSmallStoreInventory::where('inventory_no', $inventory_no)->value('inventory_signature');
        $datas = RawMaterialSmallStoreInventoryDetail::where('inventory_no', $inventory_no)->get();

        $totalQuantiteTheorique = DB::table('raw_material_small_store_inventory_details')
            ->where('inventory_no', '=', $inventory_no)
            ->sum('quantity');
         $totalQuantitePhysique = DB::table('raw_material_small_store_inventory_details')
            ->where('inventory_no', '=', $inventory_no)
            ->sum('new_quantity');

        $totalValueActuelle = DB::table('raw_material_small_store_inventory_details')
            ->where('inventory_no', '=', $inventory_no)
            ->sum('total_purchase_value');
         $totalValueNew = DB::table('raw_material_small_store_inventory_details')
            ->where('inventory_no', '=', $inventory_no)
            ->sum('new_total_purchase_value');
        $gestionnaire = RawMaterialSmallStoreInventory::where('inventory_no', $inventory_no)->value('created_by');
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.raw_material_small_store_inventory', compact('datas','inventory_no','totalValueActuelle','totalValueNew','gestionnaire','setting','title','description','date','inventory_signature','totalQuantitePhysique','totalQuantiteTheorique'), [], 'UTF-8');//->setPaper('a4', 'landscape');

        Storage::put('public/pdf/raw_material_small_store_inventory/'.'INVENTAIRE AU PETIT STOCK DES MATIÈRES PREMIÈRES '.$inventory_no.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('INVENTAIRE AU PETIT STOCK DES MATIÈRES PREMIÈRES '.$inventory_no.'.pdf');
    }

    public function print($inventory_no) { return $this->bon_inventaire($inventory_no); }

    public function validateInventory($inventory_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_small_inventory.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any inventory !');
        }

        $header = RawMaterialSmallStoreInventory::where('inventory_no', $inventory_no)->first();
        if (!$header) {
            session()->flash('error', 'Inventaire introuvable.');
            return back();
        }
        if ($response = $this->guardInventoryValidate($header)) {
            return $response;
        }

        try {DB::beginTransaction();

        $datas = RawMaterialSmallStoreInventoryDetail::where('inventory_no', $inventory_no)->get();

        foreach($datas as $data){
            $quantiteStockInitial = RawMaterialSmallStoreDetail::where('code', $data->code_store)->where('r_material_id', $data->r_material_id)->value('quantity');
            $valeurStockInitial = RawMaterialSmallStoreDetail::where('code', $data->code_store)->where('r_material_id', $data->r_material_id)->value('total_purchase_value');
            $cump = $data->new_cump ?? $data->new_purchase_price;
            $valueStockFinal = $data->new_quantity * $cump;

                    $sto = array(
                        'r_material_id' => $data->r_material_id,
                        'quantity' => $data->new_quantity,
                        'cump' => $cump,
                        'purchase_price' => $cump,
                        'total_purchase_value' => $valueStockFinal,
                        'updated_by' => $this->user->name,
                    );

                    $reportData = array(
                        'r_material_id' => $data->r_material_id,
                        'quantity_stock_initial' => $quantiteStockInitial,
                        'value_stock_initial' => $valeurStockInitial,
                        'quantity_stockin' => $data->new_quantity,
                        'value_stockin' => $valueStockFinal,
                        'quantity_stock_final' => $data->new_quantity,
                        'value_stock_final' => $valueStockFinal,
                        'document_no' => $data->inventory_no,
                        'type_transaction' => "INVENTAIRE",
                        'description' => $data->description,
                        'code_store' => $data->code_store,
                        'date' => $data->date ?? now(),
                        'created_by' => $this->user->name,
                    );

                    RawMaterialSmallStoreDetail::where('code',$data->code_store)->where('r_material_id',$data->r_material_id)
                        ->update($sto);
                    RawMaterialSmallStoreDetail::where('code',$data->code_store)->where('r_material_id', '=', $data->r_material_id)
                ->update(['verified' => true]);

                RawMaterialSmallReport::insert($reportData);
        }
            RawMaterialSmallStoreInventory::where('inventory_no', '=', $inventory_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            RawMaterialSmallStoreInventoryDetail::where('inventory_no', '=', $inventory_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'inventory has been validated !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function rejectInventory($inventory_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_small_inventory.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any inventory !');
        }

        try {DB::beginTransaction();

            RawMaterialSmallStoreInventory::where('inventory_no', '=', $inventory_no)
                ->update(['status' => 1,'rejected_by' => $this->user->name]);
             RawMaterialSmallStoreInventoryDetail::where('inventory_no', '=', $inventory_no)
                ->update(['status' => 1,'rejected_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'inventory has been rejected !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function resetInventory($inventory_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_small_inventory.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any inventory !');
        }

        try {DB::beginTransaction();

            RawMaterialSmallStoreInventory::where('inventory_no', '=', $inventory_no)
                ->update(['status' => 0,'reseted_by' => $this->user->name]);
                RawMaterialSmallStoreInventoryDetail::where('inventory_no', '=', $inventory_no)
                ->update(['status' => 0,'reseted_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'inventory has been reseted !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function exportToExcel(Request $request,$code)
    {
        return Excel::download(new RawMaterialSmStoreInventoryExport($code), 'INVENTAIRE AU PETIT STOCK DES MATIÈRES PREMIÈRES.xlsx');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_small_inventory.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any inventory !');
        }

        try {DB::beginTransaction();

        $inventory = RawMaterialSmallStoreInventory::where('inventory_no', $inventory_no)->first();
        if (!is_null($inventory)) {
            $inventory->delete();
            RawMaterialSmallStoreInventoryDetail::where('inventory_no',$inventory_no)->delete();

            $email = 'ambazamarcellin2001@gmail.com';
            $auteur = $this->user->name;
            $mailData = [
                    'title' => 'Suppression Inventaire',
                    'email' => $email,
                    'inventory_no' => $inventory_no,
                    'auteur' => $auteur,
                    ];
         
            Mail::to($email)->send(new DeleteInventoryMail($mailData));
        }

        DB::commit();
            session()->flash('success', 'Inventory has been deleted !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
    }
}
