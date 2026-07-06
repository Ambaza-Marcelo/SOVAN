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
use App\Models\RawMaterialBigStore;
use App\Models\RawMaterialExtraBigStoreDetail;
use App\Models\RawMaterialExtraBigStoreInventory;
use App\Models\RawMaterialExtraBigStoreInventoryDetail;
use App\Models\RawMaterialBigReport;
use Carbon\Carbon;
use Excel;
use App\Models\Setting;
use App\Mail\DeleteInventoryMail;
use PDF;
use Mail;
use Validator;

class RawMaterialExtraBigStoreInventoryController extends Controller
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
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_inventory.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any inventory !');
        }

        $inventories = RawMaterialExtraBigStoreInventory::all();
        return view('backend.pages.raw_material_extra_big_store_inventory.index', compact('inventories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($code)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_inventory.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any inventory !');
        }

        $raw_materials  = RawMaterial::where('code_store',$code)->orderBy('name','asc')->get();
        $datas = RawMaterialExtraBigStoreDetail::where('code',$code)->where('r_material_id','!=','')->get();
        return view('backend.pages.raw_material_extra_big_store_inventory.create', compact('datas','raw_materials','code'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_inventory.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any inventory !');
        }
        $rules = array(
            'r_material_id.*' => 'required',
            'date' => 'required|date',
            'title' => 'required',
            'quantity.*' => 'required',
            'unit.*' => 'required',
            'purchase_price.*' => 'required',
            'selling_price.*' => 'required',
            'new_quantity.*' => 'required',
            'new_purchase_price.*' => 'required',
            'new_selling_price.*' => 'required',
            'new_unit.*' => 'required',
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
            $unit = $request->unit;
            $quantity = $request->quantity;
            $purchase_price = $request->purchase_price;
            $selling_price = $request->selling_price;
            $new_quantity = $request->new_quantity;
            $new_price = $request->new_price;
            $title = $request->title;
            $code_store = $request->code_store;
            $new_purchase_price = $request->new_purchase_price;
            $new_selling_price = $request->new_selling_price; 
            $new_unit = $request->new_unit; 

            $latest = RawMaterialExtraBigStoreInventory::latest()->first();
            if ($latest) {
               $inventory_no = 'RMI' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $inventory_no = 'RMI' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $inventory_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$inventory_no;

            $created_by = $this->user->name;
            $description =$request->description; 

            for( $count = 0; $count < count($r_material_id); $count++ ){
                $total_purchase_value = $quantity[$count] * $purchase_price[$count];
                $total_selling_value = isset($selling_price[$count]) ? $quantity[$count] * $selling_price[$count] : $total_purchase_value;
                $new_total_purchase_value = $new_quantity[$count] * $new_purchase_price[$count];
                $new_total_selling_value = isset($new_selling_price[$count]) ? $new_quantity[$count] * $new_selling_price[$count] : $new_total_purchase_value;
                $total_cump_value = $total_purchase_value;
                $new_total_cump_value = $new_total_purchase_value;
                $relicat = $quantity[$count] - $new_quantity[$count];
                $data = array(
                    'r_material_id' => $r_material_id[$count],
                    'date' => $date,
                    'title' => $title,
                    'code_store' => $code_store,
                    'quantity' => $quantity[$count],
                    'unit' => $unit[$count] ?? null,
                    'purchase_price' => $purchase_price[$count],
                    'cump' => $purchase_price[$count],
                    'selling_price' => $selling_price[$count] ?? null,
                    'total_purchase_value' => $total_purchase_value,
                    'total_cump_value' => $total_cump_value,
                    'total_selling_value' => $total_selling_value,
                    'new_quantity' => $new_quantity[$count],
                    'new_purchase_price' => $new_purchase_price[$count],
                    'new_cump' => $new_purchase_price[$count],
                    'new_selling_price' => $new_selling_price[$count] ?? null,
                    'new_total_purchase_value' => $new_total_purchase_value,
                    'new_total_cump_value' => $new_total_cump_value,
                    'new_total_selling_value' => $new_total_selling_value,
                    'new_unit' => $new_unit[$count] ?? null,
                    'relicat' => $relicat,
                    'inventory_no' => $inventory_no,
                    'inventory_signature' => $inventory_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
                
            }
            RawMaterialExtraBigStoreInventoryDetail::insert($insert_data);
            //create inventory
            $inventory = new RawMaterialExtraBigStoreInventory();
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
            return redirect()->route('admin.raw-material-extra-big-store-inventory.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
         
    }

    public function referenceInventaire()
    {
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_inventory.view')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }

        $datas = RawMaterialExtraBigStoreDetail::where('verified',false)->get();
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
        $code = RawMaterialExtraBigStoreInventoryDetail::where('inventory_no', $inventory_no)->value('inventory_no');
        $inventories = RawMaterialExtraBigStoreInventoryDetail::where('inventory_no', $inventory_no)->get();
        return view('backend.pages.raw_material_extra_big_store_inventory.show', compact('inventories','code'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function inventory($id)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_inventory.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to make any inventory !');
        }

        $stock = RawMaterialExtraBigStoreDetail::find($id);
        $articles  = Article::all();
        return view('backend.pages.inventory.create', compact('stock', 'articles'));
    }

    public function edit($inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_inventory.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any inventory !');
        }

        $inventory = RawMaterialExtraBigStoreInventory::where('inventory_no', $inventory_no)->first();
        
        if (!$inventory) {
            session()->flash('error', 'Inventory not found !!');
            return redirect()->route('admin.raw-material-extra-big-store-inventory.index');
        }

        // Check if inventory is validated (status = 2)
        if ($inventory->status == 2) {
            session()->flash('error', 'Cannot edit validated inventory !!');
            return redirect()->route('admin.raw-material-extra-big-store-inventory.index');
        }

        $inventoryDetails = RawMaterialExtraBigStoreInventoryDetail::where('inventory_no', $inventory_no)->get();
        $code = $inventory->code_store;
        $raw_materials = RawMaterial::where('code_store', $code)->orderBy('name', 'asc')->get();
        
        return view('backend.pages.raw_material_extra_big_store_inventory.edit', compact('inventory', 'inventoryDetails', 'raw_materials', 'code'));
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
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_inventory.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any inventory !');
        }

        $inventory = RawMaterialExtraBigStoreInventory::where('inventory_no', $inventory_no)->first();
        
        if (!$inventory) {
            session()->flash('error', 'Inventory not found !!');
            return redirect()->route('admin.raw-material-extra-big-store-inventory.index');
        }

        // Check if inventory is validated (status = 2)
        if ($inventory->status == 2) {
            session()->flash('error', 'Cannot edit validated inventory !!');
            return redirect()->route('admin.raw-material-extra-big-store-inventory.index');
        }

        $rules = array(
            'r_material_id.*' => 'required',
            'date' => 'required|date',
            'title' => 'required',
            'quantity.*' => 'required',
            'unit.*' => 'required',
            'purchase_price.*' => 'required',
            'selling_price.*' => 'required',
            'new_quantity.*' => 'required',
            'new_purchase_price.*' => 'required',
            'new_selling_price.*' => 'required',
            'new_unit.*' => 'required',
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
            $unit = $request->unit;
            $quantity = $request->quantity;
            $purchase_price = $request->purchase_price;
            $selling_price = $request->selling_price;
            $new_quantity = $request->new_quantity;
            $new_purchase_price = $request->new_purchase_price;
            $new_selling_price = $request->new_selling_price;
            $new_unit = $request->new_unit;
            $title = $request->title;
            $code_store = $request->code_store;
            $description = $request->description;
            $updated_by = $this->user->name;

            // Delete existing details
            RawMaterialExtraBigStoreInventoryDetail::where('inventory_no', $inventory_no)->delete();

            // Insert updated details
            for ($count = 0; $count < count($r_material_id); $count++) {
                $total_purchase_value = $quantity[$count] * $purchase_price[$count];
                $total_selling_value = isset($selling_price[$count]) ? $quantity[$count] * $selling_price[$count] : $total_purchase_value;
                $new_total_purchase_value = $new_quantity[$count] * $new_purchase_price[$count];
                $new_total_selling_value = isset($new_selling_price[$count]) ? $new_quantity[$count] * $new_selling_price[$count] : $new_total_purchase_value;
                $total_cump_value = $total_purchase_value;
                $new_total_cump_value = $new_total_purchase_value;
                $relicat = $quantity[$count] - $new_quantity[$count];
                
                $data = array(
                    'r_material_id' => $r_material_id[$count],
                    'date' => $date,
                    'title' => $title,
                    'code_store' => $code_store,
                    'quantity' => $quantity[$count],
                    'unit' => $unit[$count] ?? null,
                    'purchase_price' => $purchase_price[$count],
                    'cump' => $purchase_price[$count],
                    'selling_price' => $selling_price[$count] ?? null,
                    'total_purchase_value' => $total_purchase_value,
                    'total_cump_value' => $total_cump_value,
                    'total_selling_value' => $total_selling_value,
                    'new_quantity' => $new_quantity[$count],
                    'new_purchase_price' => $new_purchase_price[$count],
                    'new_cump' => $new_purchase_price[$count],
                    'new_selling_price' => $new_selling_price[$count] ?? null,
                    'new_total_purchase_value' => $new_total_purchase_value,
                    'new_total_cump_value' => $new_total_cump_value,
                    'new_total_selling_value' => $new_total_selling_value,
                    'new_unit' => $new_unit[$count] ?? null,
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
            
            RawMaterialExtraBigStoreInventoryDetail::insert($insert_data);

            // Update inventory
            $inventory->date = $date;
            $inventory->title = $title;
            $inventory->description = $description;
            $inventory->updated_by = $updated_by;
            $inventory->save();

            DB::commit();
            session()->flash('success', 'Inventory has been updated !!');
            return redirect()->route('admin.raw-material-extra-big-store-inventory.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bon_inventaire($inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_inventory.view')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }

        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $description = RawMaterialExtraBigStoreInventory::where('inventory_no', $inventory_no)->value('description');
        $title = RawMaterialExtraBigStoreInventory::where('inventory_no', $inventory_no)->value('title');
        $inventory_no = RawMaterialExtraBigStoreInventory::where('inventory_no', $inventory_no)->value('inventory_no');
        $date = RawMaterialExtraBigStoreInventory::where('inventory_no', $inventory_no)->value('date');
        $inventory_signature = RawMaterialExtraBigStoreInventory::where('inventory_no', $inventory_no)->value('inventory_signature');
        $datas = RawMaterialExtraBigStoreInventoryDetail::where('inventory_no', $inventory_no)->get();

        $totalValueActuelle = DB::table('raw_material_extra_big_store_inventory_details')
            ->where('inventory_no', '=', $inventory_no)
            ->sum('total_purchase_value');
         $totalValueNew = DB::table('raw_material_extra_big_store_inventory_details')
            ->where('inventory_no', '=', $inventory_no)
            ->sum('new_total_purchase_value');
        $gestionnaire = RawMaterialExtraBigStoreInventory::where('inventory_no', $inventory_no)->value('created_by');
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.raw_material_extra_big_store_inventory', compact('datas','inventory_no','totalValueActuelle','totalValueNew','gestionnaire','setting','title','description','date','inventory_signature'), [], 'UTF-8');//->setPaper('a4', 'landscape');

        Storage::put('public/pdf/raw_material_extra_big_store_inventory/'.'BON_INVENTAIRE_'.$inventory_no.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('BON_INVENTAIRE_'.$inventory_no.'.pdf');
    }

    public function print($inventory_no) { return $this->bon_inventaire($inventory_no); }

    public function get_inventory_data()
    {
        return Excel::download(new InventoryExport, 'inventories.xlsx');
    }


    public function validateInventory($inventory_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_extra_big_inventory.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any inventory !');
        }

        $header = RawMaterialExtraBigStoreInventory::where('inventory_no', $inventory_no)->first();
        if (!$header) {
            session()->flash('error', 'Inventaire introuvable.');
            return back();
        }
        if ($response = $this->guardInventoryValidate($header)) {
            return $response;
        }

        try {DB::beginTransaction();

        $datas = RawMaterialExtraBigStoreInventoryDetail::where('inventory_no', $inventory_no)->get();

        foreach($datas as $data){
            $valeurStockInitial = RawMaterialExtraBigStoreDetail::where('code', $data->code_store)->where('r_material_id', $data->r_material_id)->value('total_cump_value');
            $cump = $data->new_cump ?? $data->new_purchase_price;
            $valueStockFinal = $data->new_quantity * $cump;

                $drink_calc = array(
                        'purchase_price' => $cump,
                        'cump' => $cump,
                        'quantity' => $data->new_quantity
                    );


                RawMaterial::where('id',$data->r_material_id)
                        ->update($drink_calc);

                    $sto = array(
                        'r_material_id' => $data->r_material_id,
                        'quantity' => $data->new_quantity,
                        'unit' => $data->new_unit ?? null,
                        'cump' => $cump,
                        'purchase_price' => $cump,
                        'selling_price' => $data->new_selling_price ?? $cump,
                        'total_cump_value' => $valueStockFinal,
                        'total_purchase_value' => $valueStockFinal,
                        'total_selling_value' => ($data->new_selling_price ?? $cump) * $data->new_quantity,
                        'updated_by' => $this->user->name,
                    );

                    $reportData = array(
                        'r_material_id' => $data->r_material_id,
                        'quantity_stock_initial' => $data->quantity,
                        'value_stock_initial' => $data->total_purchase_value ?? $data->total_cump_value ?? 0,
                        'quantity_stockin' => $data->new_quantity,
                        'value_stockin' => $valueStockFinal,
                        'quantity_stock_final' => $data->new_quantity,
                        'value_stock_final' => $valueStockFinal,
                        'document_no' => $data->inventory_no,
                        'type_transaction' => 'INVENTAIRE',
                        'description' => $data->description ?? '',
                        'code_store' => $data->code_store,
                        'date' => $data->date ?? now(),
                        'created_by' => $this->user->name,
                    );

                    RawMaterialExtraBigStoreDetail::where('code',$data->code_store)->where('r_material_id',$data->r_material_id)
                        ->update($sto);
                    RawMaterialExtraBigStoreDetail::where('code',$data->code_store)->where('r_material_id', '=', $data->r_material_id)
                ->update(['verified' => true]);

                RawMaterialBigReport::insert($reportData);
        }
            RawMaterialExtraBigStoreInventory::where('inventory_no', '=', $inventory_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            RawMaterialExtraBigStoreInventoryDetail::where('inventory_no', '=', $inventory_no)
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
       if (is_null($this->user) || !$this->user->can('raw_material_extra_big_inventory.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any inventory !');
        }

        try {DB::beginTransaction();

            RawMaterialExtraBigStoreInventory::where('inventory_no', '=', $inventory_no)
                ->update(['status' => 1,'rejected_by' => $this->user->name]);
             RawMaterialExtraBigStoreInventoryDetail::where('inventory_no', '=', $inventory_no)
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
       if (is_null($this->user) || !$this->user->can('raw_material_extra_big_inventory.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any inventory !');
        }

        try {DB::beginTransaction();

            RawMaterialExtraBigStoreInventory::where('inventory_no', '=', $inventory_no)
                ->update(['status' => 0,'reseted_by' => $this->user->name]);
                RawMaterialExtraBigStoreInventoryDetail::where('inventory_no', '=', $inventory_no)
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_inventory.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any inventory !');
        }

        try {DB::beginTransaction();

        $inventory = RawMaterialExtraBigStoreInventory::where('inventory_no', $inventory_no)->first();
        if (!is_null($inventory)) {
            $inventory->delete();
            RawMaterialExtraBigStoreInventoryDetail::where('inventory_no',$inventory_no)->delete();

            $email = 'ambazamarcellin2001@gmail.com';
            $auteur = $this->user->name;
            $mailData = [
                    'title' => 'Suppression Inventaire (EDEN GARDEN RESORT)',
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
