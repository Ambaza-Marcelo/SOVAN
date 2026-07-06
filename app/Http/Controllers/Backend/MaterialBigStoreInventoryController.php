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
use App\Models\Material;
use App\Models\MaterialBigStore;
use App\Models\MaterialBigStoreDetail;
use App\Models\MaterialBigStoreInventory;
use App\Models\MaterialBigStoreInventoryDetail;
use App\Models\MaterialBigReport;
use App\Support\StockInventoryReportBuilder;
use Carbon\Carbon;
use Excel;
use App\Models\Setting;
use App\Exports\MaterialMdStoreInventoryExport;
use App\Mail\DeleteInventoryMail;
use PDF;
use Mail;
use Validator;

class MaterialBigStoreInventoryController extends Controller
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
        if (is_null($this->user) || !$this->user->can('material_big_inventory.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any inventory !');
        }

        $inventories = MaterialBigStoreInventory::all();
        return view('backend.pages.material_big_store_inventory.index', compact('inventories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($code)
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any inventory !');
        }

        $materials  = Material::where('code_store',$code)->orderBy('name','asc')->get();
        $datas = MaterialBigStoreDetail::where('code',$code)->where('material_id','!=','')->get();
        return view('backend.pages.material_big_store_inventory.create', compact('datas','materials','code'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('material_big_inventory.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any inventory !');
        }
        $rules = array(
            'material_id.*' => 'required',
            'date' => 'required|date',
            'title' => 'required',
            'quantity.*' => 'required',
            'purchase_price.*' => 'required',
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

            $material_id = $request->material_id;
            $date = $request->date;
            $quantity = $request->quantity;
            $purchase_price = $request->purchase_price;
            $new_quantity = $request->new_quantity;
            $new_price = $request->new_price;
            $title = $request->title;
            $code_store = $request->code_store;
            $new_purchase_price = $request->new_purchase_price;

            $latest = MaterialBigStoreInventory::latest()->first();
            if ($latest) {
               $inventory_no = 'BI' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $inventory_no = 'BI' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $inventory_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$inventory_no;

            $created_by = $this->user->name;
            $description = $request->description; 

            for( $count = 0; $count < count($material_id); $count++ ){
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
                    'material_id' => $material_id[$count],
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
            MaterialBigStoreInventoryDetail::insert($insert_data);
            //create inventory
            $inventory = new MaterialBigStoreInventory();
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
            return redirect()->route('admin.material-big-store-inventory.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
         
    }

    public function referenceInventaire()
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.view')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }

        $datas = MaterialBigStoreDetail::where('verified',false)->get();
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
        $code = MaterialBigStoreInventoryDetail::where('inventory_no', $inventory_no)->value('inventory_no');
        $inventories = MaterialBigStoreInventoryDetail::where('inventory_no', $inventory_no)->get();
        return view('backend.pages.material_big_store_inventory.show', compact('inventories','code'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function inventory($id)
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to make any inventory !');
        }

        $stock = MaterialBigStoreDetail::find($id);
        $articles  = Article::all();
        return view('backend.pages.inventory.create', compact('stock', 'articles'));
    }

    public function edit($inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any inventory !');
        }

        $inventory = MaterialBigStoreInventory::where('inventory_no', $inventory_no)->first();
        
        if (!$inventory) {
            session()->flash('error', 'Inventory not found !!');
            return redirect()->route('admin.material-big-store-inventory.index');
        }

        // Check if inventory is validated (status = 2)
        if ($inventory->status == 2) {
            session()->flash('error', 'Cannot edit validated inventory !!');
            return redirect()->route('admin.material-big-store-inventory.index');
        }
        
        $inventoryDetails = MaterialBigStoreInventoryDetail::where('inventory_no', $inventory_no)->get();
        $code = $inventory->code_store;
        $materials = Material::where('code_store', $code)->orderBy('name', 'asc')->get();
        
        return view('backend.pages.material_big_store_inventory.edit', compact('inventory', 'inventoryDetails', 'materials', 'code'));
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
        if (is_null($this->user) || !$this->user->can('material_big_inventory.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any inventory !');
        }

        $inventory = MaterialBigStoreInventory::where('inventory_no', $inventory_no)->first();
        
        if (!$inventory) {
            session()->flash('error', 'Inventory not found !!');
            return redirect()->route('admin.material-big-store-inventory.index');
        }

        // Check if inventory is validated (status = 2)
        if ($inventory->status == 2) {
            session()->flash('error', 'Cannot edit validated inventory !!');
            return redirect()->route('admin.material-big-store-inventory.index');
        }

        $rules = array(
            'material_id.*' => 'required',
            'date' => 'required|date',
            'title' => 'required',
            'quantity.*' => 'required',
            'purchase_price.*' => 'required',
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

            $material_id = $request->material_id;
            $date = Carbon::parse($request->date);
            $quantity = $request->quantity;
            $purchase_price = $request->purchase_price;
            $new_quantity = $request->new_quantity;
            $new_purchase_price = $request->new_purchase_price;
            $title = $request->title;
            $code_store = $request->code_store;
            $description = $request->description;
            $updated_by = $this->user->name;

            // Delete existing details
            MaterialBigStoreInventoryDetail::where('inventory_no', $inventory_no)->delete();

            // Insert updated details
            for ($count = 0; $count < count($material_id); $count++) {
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
                    'material_id' => $material_id[$count],
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
            
            MaterialBigStoreInventoryDetail::insert($insert_data);

            // Update inventory
            $inventory->date = $date;
            $inventory->title = $title;
            $inventory->description = $description;
            $inventory->updated_by = $updated_by;
            $inventory->save();

            DB::commit();
            session()->flash('success', 'Inventory has been updated !!');
            return redirect()->route('admin.material-big-store-inventory.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bon_inventaire($inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.view')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }

        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $description = MaterialBigStoreInventory::where('inventory_no', $inventory_no)->value('description');
        $title = MaterialBigStoreInventory::where('inventory_no', $inventory_no)->value('title');
        $inventory_no = MaterialBigStoreInventory::where('inventory_no', $inventory_no)->value('inventory_no');
        $date = MaterialBigStoreInventory::where('inventory_no', $inventory_no)->value('date');
        $inventory_signature = MaterialBigStoreInventory::where('inventory_no', $inventory_no)->value('inventory_signature');
        $datas = MaterialBigStoreInventoryDetail::where('inventory_no', $inventory_no)->get();

        $totalValueActuelle = DB::table('material_big_store_inventory_details')
            ->where('inventory_no', '=', $inventory_no)
            ->sum('total_purchase_value');
         $totalValueNew = DB::table('material_big_store_inventory_details')
            ->where('inventory_no', '=', $inventory_no)
            ->sum('new_total_purchase_value');
        $gestionnaire = MaterialBigStoreInventory::where('inventory_no', $inventory_no)->value('created_by');
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.material_big_store_inventory', compact('datas','inventory_no','totalValueActuelle','totalValueNew','gestionnaire','setting','title','description','date','inventory_signature'), [], 'UTF-8');//->setPaper('a4', 'landscape');

        Storage::put('public/pdf/material_big_store_inventory/'.'BON_INVENTAIRE_'.$inventory_no.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('BON_INVENTAIRE_'.$inventory_no.'.pdf');
    }

    public function print($inventory_no) { return $this->bon_inventaire($inventory_no); }

    public function validateInventory($inventory_no)
    {
       if (is_null($this->user) || !$this->user->can('material_big_inventory.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any inventory !');
        }

        $header = MaterialBigStoreInventory::where('inventory_no', $inventory_no)->first();
        if (!$header) {
            session()->flash('error', 'Inventaire introuvable.');
            return back();
        }
        if ($response = $this->guardInventoryValidate($header)) {
            return $response;
        }

        try {DB::beginTransaction();
            

        $datas = MaterialBigStoreInventoryDetail::where('inventory_no', $inventory_no)->get();

        foreach($datas as $data){
            // Get initial stock values
            $quantityStockInitial = MaterialBigStoreDetail::where('code',$data->code_store)->where('material_id', $data->material_id)->value('quantity') ?? 0;
            $valeurStockInitial = MaterialBigStoreDetail::where('code',$data->code_store)->where('material_id', $data->material_id)->value('total_cump_value') ?? 0;
            
            // Remplacer en stock: quantity par new_quantity, cump par new_cump (nouveau coût unitaire moyen pondéré)
            $cump = $data->new_cump ?? $data->new_purchase_price;
            
            // Calculate final stock values
            $quantityStockFinal = $data->new_quantity;
            $valueStockFinal = $quantityStockFinal * $cump;
            $valueStockin = $quantityStockFinal * $cump;

                $material_calc = array(
                        'purchase_price' => $cump,
                        'cump' => $cump,
                        'quantity' => $quantityStockFinal
                    );

                Material::where('id',$data->material_id)
                        ->update($material_calc);

                    $sto = array(
                        'material_id' => $data->material_id,
                        'quantity' => $quantityStockFinal,
                        'cump' => $cump,
                        'purchase_price' => $cump,
                        'total_cump_value' => $valueStockFinal,
                        'total_purchase_value' => $valueStockFinal,
                        'updated_by' => $this->user->name,
                    );

                    $reportData = array_merge(
                        StockInventoryReportBuilder::movementPayload(
                            (float) $quantityStockInitial,
                            (float) $quantityStockFinal,
                            (float) $cump,
                            (float) $valeurStockInitial
                        ),
                        [
                            'material_id' => $data->material_id,
                            'document_no' => $data->inventory_no,
                            'type_transaction' => 'INVENTAIRE',
                            'description' => $data->description,
                            'code_store' => $data->code_store,
                            'created_by' => $this->user->name,
                            'created_at' => \Carbon\Carbon::now(),
                        ]
                    );

                    MaterialBigStoreDetail::where('code',$data->code_store)->where('material_id',$data->material_id)
                        ->update($sto);
                    MaterialBigStoreDetail::where('code',$data->code_store)->where('material_id', '=', $data->material_id)
                ->update(['verified' => true]);

                MaterialBigReport::insert($reportData);
        }
            MaterialBigStoreInventory::where('inventory_no', '=', $inventory_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            MaterialBigStoreInventoryDetail::where('inventory_no', '=', $inventory_no)
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
       if (is_null($this->user) || !$this->user->can('material_big_inventory.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any inventory !');
        }

        try {DB::beginTransaction();

            MaterialBigStoreInventory::where('inventory_no', '=', $inventory_no)
                ->update(['status' => 1,'rejected_by' => $this->user->name]);
             MaterialBigStoreInventoryDetail::where('inventory_no', '=', $inventory_no)
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
       if (is_null($this->user) || !$this->user->can('material_big_inventory.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any inventory !');
        }

        try {DB::beginTransaction();

            MaterialBigStoreInventory::where('inventory_no', '=', $inventory_no)
                ->update(['status' => 0,'reseted_by' => $this->user->name]);
                MaterialBigStoreInventoryDetail::where('inventory_no', '=', $inventory_no)
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
        return Excel::download(new MaterialMdStoreInventoryExport($code), 'INVENTAIRE A L INTERMEDIAIRE STOCK DES MATERIELS.xlsx');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any inventory !');
        }

        try {DB::beginTransaction();

        $inventory = MaterialBigStoreInventory::where('inventory_no', $inventory_no)->first();
        if (!is_null($inventory)) {
            $inventory->delete();
            MaterialBigStoreInventoryDetail::where('inventory_no',$inventory_no)->delete();

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
