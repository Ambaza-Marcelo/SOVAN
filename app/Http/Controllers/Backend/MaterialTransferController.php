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
use App\Models\MaterialTransfer;
use App\Models\MaterialTransferDetail;
use App\Models\MaterialBigStoreDetail;
use App\Models\MaterialSmallStoreDetail;
use App\Models\MaterialBigStore;
use App\Models\MaterialExtraBigStore;
use App\Models\MaterialExtraBigStoreDetail;
use App\Models\MaterialExtraBigReport;
use App\Models\MaterialSmallStore;
use App\Models\MaterialRequisitionDetail;
use App\Models\MaterialRequisition;
use App\Models\MaterialBigReport;
use App\Models\MaterialSmallReport;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountJournalEntryDetail;
use Carbon\Carbon;
use PDF;
use Validator;
use Excel;
use Mail;

class MaterialTransferController extends Controller
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
        if (is_null($this->user) || !$this->user->can('material_transfer.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any transfer !');
        }

        $query = MaterialTransfer::query();
        
        // Si des dates sont fournies, filtrer par période
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('created_at', [$dateDebut, $dateFin]);
        } else {
            // Par défaut, afficher les 100 lignes les plus récentes
            $query->take(100);
        }
        
        $transfers = $query->orderBy('id','desc')->get();
        return view('backend.pages.material_transfer.index', compact('transfers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($requisition_no)
    {
        if (is_null($this->user) || !$this->user->can('material_transfer.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any transfer !');
        }

        $materials  = Material::orderBy('name','asc')->get();
        $origin_stores = MaterialBigStore::all();
        $destination_stores = MaterialSmallStore::all();
        $datas = MaterialRequisitionDetail::where('requisition_no', $requisition_no)->get();
        return view('backend.pages.material_transfer.create', compact('materials','requisition_no','datas','origin_stores','destination_stores'));
    }

    public function createFromBig($requisition_no)
    {
        if (is_null($this->user) || !$this->user->can('material_transfer.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any transfer !');
        }

        $materials  = Material::orderBy('name','asc')->get();
        $origin_stores = MaterialExtraBigStore::all();
        $destination_stores = MaterialBigStore::all();
        $datas = MaterialRequisitionDetail::where('requisition_no', $requisition_no)->get();
        return view('backend.pages.material_transfer.create_from_big', compact('materials','requisition_no','datas','origin_stores','destination_stores'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('material_transfer.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any transfer !');
        }

        $rules = array(
                'material_id.*'  => 'required',
                'date'  => 'required',
                'quantity_requisitioned.*'  => 'required',
                'price.*'  => 'required',
                'quantity_transfered.*'  => 'required',
                'origin_store_id'  => 'required',
                'requisition_no'  => 'required',
                'destination_store_id'  => 'required',
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
            $origin_store_id = $request->origin_store_id;
            $requisition_no = $request->requisition_no;
            $type_store = $request->type_store;
            $description =$request->description; 
            $destination_store_id = $request->destination_store_id;
            $quantity_requisitioned = $request->quantity_requisitioned;
            $price = $request->price;
            $quantity_transfered = $request->quantity_transfered;
            

            $latest = MaterialTransfer::latest()->first();
            if ($latest) {
               $transfer_no = 'M-BT' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $transfer_no = 'M-BT' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $created_by = $this->user->name;

            $transfer_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$transfer_no;


            for( $count = 0; $count < count($material_id); $count++ ){
                $total_value_requisitioned = $quantity_requisitioned[$count] * $price[$count];
                $total_value_transfered = $quantity_transfered[$count] * $price[$count];
                $data = array(
                    'material_id' => $material_id[$count],
                    'date' => $date,
                    'quantity_requisitioned' => $quantity_requisitioned[$count],
                    'quantity_transfered' => $quantity_transfered[$count],
                    'price' => $price[$count],
                    'total_value_requisitioned' => $total_value_requisitioned,
                    'total_value_transfered' => $total_value_transfered,
                    'requisition_no' => $requisition_no,
                    'type_store' => $type_store,
                    'origin_store_id' => $origin_store_id,
                    'destination_store_id' => $destination_store_id,
                    'transfer_no' => $transfer_no,
                    'transfer_signature' => $transfer_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }
            MaterialTransferDetail::insert($insert_data);


            //create transfer
            $transfer = new MaterialTransfer();
            $transfer->date = $date;
            $transfer->transfer_no = $transfer_no;
            $transfer->transfer_signature = $transfer_signature;
            $transfer->requisition_no = $requisition_no;
            $transfer->type_store = $type_store;
            $transfer->origin_store_id = $origin_store_id;
            $transfer->destination_store_id = $destination_store_id;
            $transfer->created_by = $created_by;
            $transfer->status = 1;
            $transfer->description = $description;
            $transfer->created_at = \Carbon\Carbon::now();
            $transfer->save();

            // Update requisition status to 5 in TransferDetail
            // Update requisition status to 5
            MaterialRequisition::where('requisition_no', $requisition_no)->update(['status' => 5]);
            MaterialRequisitionDetail::where('requisition_no', $requisition_no)->update(['status' => 5]);

            DB::commit();
            session()->flash('success', 'transfer has been created !!');
            return redirect()->route('admin.material-transfers.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
            
    }


    public function storeFromBig(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('material_transfer.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any transfer !');
        }

        $rules = array(
                'material_id.*'  => 'required',
                'date'  => 'required',
                'quantity_requisitioned.*'  => 'required',
                'price.*'  => 'required',
                'quantity_transfered.*'  => 'required',
                'origin_extra_store_id'  => 'required',
                'requisition_no'  => 'required',
                'dest_bg_store_id'  => 'required',
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
            $origin_extra_store_id = $request->origin_extra_store_id;
            $requisition_no = $request->requisition_no;
            $type_store = $request->type_store;
            $description =$request->description; 
            $dest_bg_store_id = $request->dest_bg_store_id;
            $quantity_requisitioned = $request->quantity_requisitioned;
            $price = $request->price;
            $quantity_transfered = $request->quantity_transfered;
            

            $latest = MaterialTransfer::latest()->first();
            if ($latest) {
               $transfer_no = 'BT' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $transfer_no = 'BT' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $created_by = $this->user->name;

            $transfer_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$transfer_no;


            for( $count = 0; $count < count($material_id); $count++ ){
                $total_value_requisitioned = $quantity_requisitioned[$count] * $price[$count];
                $total_value_transfered = $quantity_transfered[$count] * $price[$count];
                $data = array(
                    'material_id' => $material_id[$count],
                    'date' => $date,
                    'quantity_requisitioned' => $quantity_requisitioned[$count],
                    'quantity_transfered' => $quantity_transfered[$count],
                    'price' => $price[$count],
                    'total_value_requisitioned' => $total_value_requisitioned,
                    'total_value_transfered' => $total_value_transfered,
                    'requisition_no' => $requisition_no,
                    'type_store' => $type_store,
                    'origin_extra_store_id' => $origin_extra_store_id,
                    'dest_bg_store_id' => $dest_bg_store_id,
                    'transfer_no' => $transfer_no,
                    'transfer_signature' => $transfer_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now()

                );
                $insert_data[] = $data;

                
            }
            MaterialTransferDetail::insert($insert_data);


            //create transfer
            $transfer = new MaterialTransfer();
            $transfer->date = $date;
            $transfer->transfer_no = $transfer_no;
            $transfer->transfer_signature = $transfer_signature;
            $transfer->requisition_no = $requisition_no;
            $transfer->type_store = $type_store;
            $transfer->origin_extra_store_id = $origin_extra_store_id;
            $transfer->dest_bg_store_id = $dest_bg_store_id;
            $transfer->created_by = $created_by;
            $transfer->status = 1;
            $transfer->description = $description;
            $transfer->created_at = \Carbon\Carbon::now();
            $transfer->save();

            // Update requisition status to 5 in TransferDetail
            // Update requisition status to 5
            MaterialRequisition::where('requisition_no', $requisition_no)->update(['status' => 5]);
            MaterialRequisitionDetail::where('requisition_no', $requisition_no)->update(['status' => 5]);

            DB::commit();
            session()->flash('success', 'transfer has been created !!');
            return redirect()->route('admin.material-transfers.index');
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
    public function show($transfer_no)
    {
        //
        $transfer = MaterialTransfer::with(['originStore', 'destinationStore', 'originExtraStore', 'destinationBigStore'])->where('transfer_no', $transfer_no)->first();
        $transferDetails = MaterialTransferDetail::with(['material.materialMeasurement', 'origineStore', 'destinationStore', 'originExtraStore', 'destinationBigStore'])
            ->where('transfer_no', $transfer_no)->get();
        
        // Calculate totals
        $totalQuantityRequisitioned = $transferDetails->sum('quantity_requisitioned');
        $totalQuantityTransfered = $transferDetails->sum('quantity_transfered');
        $totalValueTransfered = $transferDetails->sum('total_value_transfered');
        
        return view('backend.pages.material_transfer.show', compact('transfer', 'transferDetails', 'totalQuantityRequisitioned', 'totalQuantityTransfered', 'totalValueTransfered'));
         
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($transfer_no)
    {
        if (is_null($this->user) || !$this->user->can('material_transfer.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any transfer !');
        }

        $transfer = MaterialTransfer::where('transfer_no', $transfer_no)->first();
        if (is_null($transfer)) {
            session()->flash('error', 'Transfer not found !!');
            return redirect()->route('admin.material-transfers.index');
        }

        $materials = Material::orderBy('name','asc')->get();
        $origin_stores = MaterialBigStore::all();
        $destination_stores = MaterialSmallStore::all();
        $origin_extra_stores = MaterialExtraBigStore::all();
        $destination_bg_stores = MaterialBigStore::all();
        
        // Get data from requisitions instead of transfer details
        $requisition_no = $transfer->requisition_no;
        $datas = MaterialRequisitionDetail::where('requisition_no', $requisition_no)->get();

        return view('backend.pages.material_transfer.edit', compact('transfer', 'materials', 'requisition_no', 'datas', 'origin_stores', 'destination_stores', 'origin_extra_stores', 'destination_bg_stores'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $transfer_no)
    {
        if (is_null($this->user) || !$this->user->can('material_transfer.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any transfer !');
        }

        $transfer = MaterialTransfer::where('transfer_no', $transfer_no)->first();
        if (is_null($transfer)) {
            session()->flash('error', 'Transfer not found !!');
            return redirect()->route('admin.material-transfers.index');
        }

        $rules = array(
            'material_id.*'  => 'required',
            'date'  => 'required',
            'quantity_requisitioned.*'  => 'required',
            'price.*'  => 'required',
            'quantity_transfered.*'  => 'required',
            'requisition_no'  => 'required',
            'description'  => 'required'
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
            $origin_store_id = $request->origin_store_id;
            $origin_extra_store_id = $request->origin_extra_store_id;
            $requisition_no = $request->requisition_no;
            $type_store = $transfer->type_store; // Keep original type_store
            $description = $request->description;
            $destination_store_id = $request->destination_store_id;
            $dest_bg_store_id = $request->dest_bg_store_id;
            $quantity_requisitioned = $request->quantity_requisitioned;
            $price = $request->price;
            $quantity_transfered = $request->quantity_transfered;
            $updated_by = $this->user->name;

            // Delete old details
            MaterialTransferDetail::where('transfer_no', $transfer_no)->delete();

            // Insert updated details
            $insert_data = [];
            for ($count = 0; $count < count($material_id); $count++) {
                $total_value_requisitioned = $quantity_requisitioned[$count] * $price[$count];
                $total_value_transfered = $quantity_transfered[$count] * $price[$count];
                
                $data = array(
                    'material_id' => $material_id[$count],
                    'date' => $date,
                    'quantity_requisitioned' => $quantity_requisitioned[$count],
                    'quantity_transfered' => $quantity_transfered[$count],
                    'price' => $price[$count],
                    'total_value_requisitioned' => $total_value_requisitioned,
                    'total_value_transfered' => $total_value_transfered,
                    'requisition_no' => $requisition_no,
                    'type_store' => $type_store,
                    'origin_store_id' => $origin_store_id,
                    'origin_extra_store_id' => $origin_extra_store_id,
                    'destination_store_id' => $destination_store_id,
                    'dest_bg_store_id' => $dest_bg_store_id,
                    'transfer_no' => $transfer_no,
                    'transfer_signature' => $transfer->transfer_signature, // Keep original signature
                    'created_by' => $transfer->created_by, // Keep original creator
                    'updated_by' => $updated_by,
                    'description' => $description,
                    'status' => $transfer->status, // Keep original status
                    'created_at' => $transfer->created_at, // Keep original created_at
                    'updated_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }

            MaterialTransferDetail::insert($insert_data);

            // Update main transfer
            $transfer->date = $date;
            $transfer->description = $description;
            $transfer->updated_by = $updated_by;
            if ($origin_store_id) {
                $transfer->origin_store_id = $origin_store_id;
            }
            if ($origin_extra_store_id) {
                $transfer->origin_extra_store_id = $origin_extra_store_id;
            }
            if ($destination_store_id) {
                $transfer->destination_store_id = $destination_store_id;
            }
            if ($dest_bg_store_id) {
                $transfer->dest_bg_store_id = $dest_bg_store_id;
            }
            $transfer->save();

            // Update requisition status to 5 in TransferDetail
            // Update requisition status to 5
            MaterialRequisition::where('requisition_no', $requisition_no)->update(['status' => 5]);
            MaterialRequisitionDetail::where('requisition_no', $requisition_no)->update(['status' => 5]);

            DB::commit();
            session()->flash('success', 'Transfer has been updated !!');
            return redirect()->route('admin.material-transfers.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bonTransfert($transfer_no)
    {
        if (is_null($this->user) || !$this->user->can('material_transfer.create')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $transfer_no = MaterialTransfer::where('transfer_no', $transfer_no)->value('transfer_no');
        $datas = MaterialTransferDetail::where('transfer_no', $transfer_no)->get();
        $data = MaterialTransfer::where('transfer_no', $transfer_no)->first();
        $description = MaterialTransfer::where('transfer_no', $transfer_no)->value('description');
        $requisition_no = MaterialTransfer::where('transfer_no', $transfer_no)->value('requisition_no');
        $transfer_signature = MaterialTransfer::where('transfer_no', $transfer_no)->value('transfer_signature');
        $date = MaterialTransfer::where('transfer_no', $transfer_no)->value('date');
        $totalValueTransfered = DB::table('material_transfer_details')
            ->where('transfer_no', '=', $transfer_no)
            ->sum('total_value_transfered');
        $totalValueRequisitioned = DB::table('material_transfer_details')
            ->where('transfer_no', '=', $transfer_no)
            ->sum('total_value_requisitioned');
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.material_transfert', compact('datas','transfer_no','totalValueTransfered','totalValueRequisitioned','data','description','requisition_no','setting','date','transfer_signature'), [], 'UTF-8');

        Storage::put('public/pdf/material_transfert/'.$transfer_no.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('bon_transfert'.$transfer_no.'.pdf');
        
    }

    public function print($transfer_no) { return $this->bonTransfert($transfer_no); }

    public function validateTransfer($transfer_no)
    {
       if (is_null($this->user) || !$this->user->can('material_transfer.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any transfer !');
        }

        $transfer = MaterialTransfer::where('transfer_no', $transfer_no)->first();
        if (!$transfer) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentValidate($transfer)) {
            return $response;
        }

        try {DB::beginTransaction();

            MaterialTransfer::where('transfer_no', '=', $transfer_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            MaterialTransferDetail::where('transfer_no', '=', $transfer_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'transfer has been validated !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function reject($transfer_no)
    {
       if (is_null($this->user) || !$this->user->can('material_transfer.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any transfer !');
        }

        try {DB::beginTransaction();

        MaterialTransfer::where('transfer_no', '=', $transfer_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);
        MaterialTransferDetail::where('transfer_no', '=', $transfer_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Transfer has been rejected !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function reset($transfer_no)
    {
       if (is_null($this->user) || !$this->user->can('material_transfer.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any transfer !');
        }

        try {DB::beginTransaction();

        MaterialTransfer::where('transfer_no', '=', $transfer_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
        MaterialTransferDetail::where('transfer_no', '=', $transfer_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Transfer has been reseted !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function confirm($transfer_no)
    {
       if (is_null($this->user) || !$this->user->can('material_transfer.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any transfer !');
        }

        $transfer = MaterialTransfer::where('transfer_no', $transfer_no)->first();
        if (!$transfer) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentConfirm($transfer)) {
            return $response;
        }

        try {DB::beginTransaction();

        MaterialTransfer::where('transfer_no', '=', $transfer_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            MaterialTransferDetail::where('transfer_no', '=', $transfer_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Transfer has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function approuve($transfer_no)
    {
       if (is_null($this->user) || !$this->user->can('material_transfer.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any transfer !');
        }

        $transferHeader = MaterialTransfer::where('transfer_no', $transfer_no)->first();
        if (!$transferHeader) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentApprove($transferHeader)) {
            return $response;
        }

        try {DB::beginTransaction();

        $transfer = MaterialTransfer::where('transfer_no', $transfer_no)->first();
        $transferMainDate = $transfer ? $transfer->date : Carbon::now();

        $datas = MaterialTransferDetail::where('transfer_no', $transfer_no)->get();

        // Initialize arrays
        $reportBigStoreData = [];
        $reportSmallStoreData = [];
        $reportMediumStoreData = [];
        $flag_material_bg = 0;
        $flag_material_md = 0;
        $flag_material_sm = 0;
        
        // Accounting variables for type_store == 'bg'
        $totalAccountingValue = 0;
        $originAccountId = null;
        $destinationAccountId = null;
        $transferDate = null;

        foreach($datas as $data){

                if ($data->type_store == 'md') {
                    $code_store_origin = MaterialBigStore::where('id',$data->origin_store_id)->value('code');
                $code_store_destination = MaterialSmallStore::where('id',$data->destination_store_id)->value('code');

                $valeurStockInitialOrigine = MaterialBigStoreDetail::where('code',$code_store_origin)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('total_cump_value') ?? 0;
                $quantityStockInitialOrigine = MaterialBigStoreDetail::where('code',$code_store_origin)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('quantity') ?? 0;

                $valeurStockInitialDestination = MaterialSmallStoreDetail::where('code',$code_store_destination)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('total_cump_value') ?? 0;
                $quantityStockInitialDestination = MaterialSmallStoreDetail::where('code',$code_store_destination)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('quantity') ?? 0;

                // Get CUMP from origin store
                $cump = MaterialBigStoreDetail::where('code',$code_store_origin)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('cump') ?? 0;
                if ($cump == 0) {
                    $cump = Material::where('id', $data->material_id)->value('cump') ?? 0;
                    if ($cump == 0) {
                        $cump = Material::where('id', $data->material_id)->value('purchase_price') ?? 0;
                    }
                }

                if ($data->quantity_transfered > $quantityStockInitialOrigine) {
                    DB::rollback();
                    session()->flash('error', 'Why do you want transfering quantity you do not have in your store?please rewrite a valid quantity!');
                    return redirect()->back();
                }

                $quantityRestantBigStore = $quantityStockInitialOrigine - $data->quantity_transfered;
                $value_stockout = $data->quantity_transfered * $cump;
                $quantity_stock_final_origin = $quantityStockInitialOrigine - $data->quantity_transfered;
                $value_stock_final_origin = $quantity_stock_final_origin * $cump;

                $value_stockin = $data->quantity_transfered * $cump;
                $quantity_stock_final_destination = $quantityStockInitialDestination + $data->quantity_transfered;
                $value_stock_final_destination = $quantity_stock_final_destination * $cump;

                // Report for origin store (SORTIE TRANSFERT)
                $reportBigStore = array(
                    'material_id' => $data->material_id,
                    'quantity_stock_initial' => $quantityStockInitialOrigine,
                    'value_stock_initial' => $valeurStockInitialOrigine,
                    'code_store' => $code_store_origin,
                    'date' => $data->date,
                    'quantity_stockout' => $data->quantity_transfered,
                    'value_stockout' => $value_stockout,
                    'quantity_stock_final' => $quantity_stock_final_origin,
                    'value_stock_final' => $value_stock_final_origin,
                    'cump' => $cump,
                    'document_no' => $transfer_no,
                    'type_transaction' => "SORTIE TRANSFERT",
                    'description' => $data->description,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportBigStoreData[] = $reportBigStore;

                // Report for destination store (ENTREE TRANSFERT)
                $reportSmallStore = array(
                    'material_id' => $data->material_id,
                    'quantity_stock_initial' => $quantityStockInitialDestination,
                    'value_stock_initial' => $valeurStockInitialDestination,
                    'code_store' => $code_store_destination,
                    'date' => $data->date,
                    'quantity_stockin' => $data->quantity_transfered,
                    'value_stockin' => $value_stockin,
                    'quantity_stock_final' => $quantity_stock_final_destination,
                    'value_stock_final' => $value_stock_final_destination,
                    'cump' => $cump,
                    'document_no' => $transfer_no,
                    'type_transaction' => "ENTREE TRANSFERT",
                    'description' => $data->description,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportSmallStoreData[] = $reportSmallStore;

                $bigStore = array(
                    'material_id' => $data->material_id,
                    'quantity' => $quantityRestantBigStore,
                    'purchase_price' => $cump,
                    'cump' => $cump,
                    'total_purchase_value' => $quantityRestantBigStore * $cump,
                    'total_cump_value' => $quantityRestantBigStore * $cump,
                    'created_by' => $this->user->name,
                    'verified' => false,
                    'created_at' => \Carbon\Carbon::now()
                );


                $smStore = array(
                    'material_id' => $data->material_id,
                    'quantity' => $quantity_stock_final_destination,
                    'purchase_price' => $cump,
                    'cump' => $cump,
                    'total_cump_value' => $value_stock_final_destination,
                    'total_purchase_value' => $value_stock_final_destination,
                    'verified' => false,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );

                        $material = MaterialSmallStoreDetail::where('code',$code_store_destination)->where("material_id",$data->material_id)->value('material_id');


                $material = MaterialSmallStoreDetail::where('code',$code_store_destination)->where("material_id",$data->material_id)->value('material_id');

                MaterialBigStoreDetail::where('code',$code_store_origin)->where('material_id',$data->material_id)
                    ->update($bigStore);

                $flag_material_md = 1;

                if (!empty($material)) {
                    MaterialSmallStoreDetail::where('code',$code_store_destination)->where('material_id',$data->material_id)
                        ->update($smStore);
                } else {
                    $smallStoreData = array(
                        'material_id' => $data->material_id,
                        'quantity' => $data->quantity_transfered,
                        'cump' => $cump,
                        'code' => $code_store_destination,
                        'purchase_price' => $cump,
                        'total_cump_value' => $value_stockin,
                        'total_purchase_value' => $value_stockin,
                        'verified' => false,
                        'created_by' => $this->user->name,
                        'created_at' => \Carbon\Carbon::now()
                    );
                    MaterialSmallStoreDetail::insert($smallStoreData);
                }

                $flag_material_sm = 1;

                MaterialRequisition::where('requisition_no', '=', $data->requisition_no)
                    ->update(['status' => 5]);
                MaterialRequisitionDetail::where('requisition_no', '=', $data->requisition_no)
                    ->update(['status' => 5]);


                }elseif ($data->type_store == 'bg') {
                    $code_store_origin = MaterialExtraBigStore::where('id',$data->origin_extra_store_id)->value('code');
                $code_store_destination = MaterialBigStore::where('id',$data->dest_bg_store_id)->value('code');

                $valeurStockInitialOrigine = MaterialExtraBigStoreDetail::where('code',$code_store_origin)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('total_cump_value') ?? 0;
                $quantityStockInitialOrigine = MaterialExtraBigStoreDetail::where('code',$code_store_origin)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('quantity') ?? 0;

                $valeurStockInitialDestination = MaterialBigStoreDetail::where('code',$code_store_destination)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('total_cump_value') ?? 0;
                $quantityStockInitialDestination = MaterialBigStoreDetail::where('code',$code_store_destination)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('quantity') ?? 0;

                // Get CUMP from origin store
                $cump = MaterialExtraBigStoreDetail::where('code',$code_store_origin)->where('material_id','!=', '')->where('material_id', $data->material_id)->value('cump') ?? 0;
                if ($cump == 0) {
                    $cump = Material::where('id', $data->material_id)->value('cump') ?? 0;
                    if ($cump == 0) {
                        $cump = Material::where('id', $data->material_id)->value('purchase_price') ?? 0;
                    }
                }

                if ($data->quantity_transfered > $quantityStockInitialOrigine) {
                    DB::rollback();
                    session()->flash('error', 'Why do you want transfering quantity you do not have in your store?please rewrite a valid quantity!');
                    return redirect()->back();
                }

                $quantityRestantBigStore = $quantityStockInitialOrigine - $data->quantity_transfered;
                $value_stockout = $data->quantity_transfered * $cump;
                $quantity_stock_final_origin = $quantityStockInitialOrigine - $data->quantity_transfered;
                $value_stock_final_origin = $quantity_stock_final_origin * $cump;

                $value_stockin = $data->quantity_transfered * $cump;
                $quantity_stock_final_destination = $quantityStockInitialDestination + $data->quantity_transfered;
                $value_stock_final_destination = $quantity_stock_final_destination * $cump;

                // Accumulate accounting values for type_store == 'bg'
                $originStore = MaterialExtraBigStore::where('code', $code_store_origin)->first();
                $destinationStore = MaterialBigStore::where('code', $code_store_destination)->first();
                
                if ($originStore && $originStore->default_account_id && $destinationStore && $destinationStore->default_account_id) {
                    if ($originAccountId === null) {
                        $originAccountId = $originStore->default_account_id;
                        $destinationAccountId = $destinationStore->default_account_id;
                        $transferDate = $transferMainDate;
                    }
                    // Accumulate total value transferred
                    $totalAccountingValue += $value_stockout;
                }

                // Report for origin store (SORTIE TRANSFERT)
                $reportBigStore = array(
                    'material_id' => $data->material_id,
                    'quantity_stock_initial' => $quantityStockInitialOrigine,
                    'value_stock_initial' => $valeurStockInitialOrigine,
                    'code_store' => $code_store_origin,
                    'date' => $data->date,
                    'quantity_stockout' => $data->quantity_transfered,
                    'value_stockout' => $value_stockout,
                    'quantity_stock_final' => $quantity_stock_final_origin,
                    'value_stock_final' => $value_stock_final_origin,
                    'cump' => $cump,
                    'document_no' => $transfer_no,
                    'type_transaction' => "SORTIE TRANSFERT",
                    'description' => $data->description,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportBigStoreData[] = $reportBigStore;

                // Report for destination store (ENTREE TRANSFERT)
                $reportMediumStore = array(
                    'material_id' => $data->material_id,
                    'quantity_stock_initial' => $quantityStockInitialDestination,
                    'value_stock_initial' => $valeurStockInitialDestination,
                    'code_store' => $code_store_destination,
                    'date' => $data->date,
                    'quantity_stockin' => $data->quantity_transfered,
                    'value_stockin' => $value_stockin,
                    'quantity_stock_final' => $quantity_stock_final_destination,
                    'value_stock_final' => $value_stock_final_destination,
                    'cump' => $cump,
                    'document_no' => $transfer_no,
                    'type_transaction' => "ENTREE TRANSFERT",
                    'description' => $data->description,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportMediumStoreData[] = $reportMediumStore;

                $bigStore = array(
                    'material_id' => $data->material_id,
                    'quantity' => $quantityRestantBigStore,
                    'purchase_price' => $cump,
                    'cump' => $cump,
                    'total_purchase_value' => $quantityRestantBigStore * $cump,
                    'total_cump_value' => $quantityRestantBigStore * $cump,
                    'created_by' => $this->user->name,
                    'verified' => false,
                    'created_at' => \Carbon\Carbon::now()
                );


                $mdStore = array(
                    'material_id' => $data->material_id,
                    'quantity' => $quantity_stock_final_destination,
                    'purchase_price' => $cump,
                    'cump' => $cump,
                    'total_cump_value' => $value_stock_final_destination,
                    'total_purchase_value' => $value_stock_final_destination,
                    'verified' => false,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );

                        $material = MaterialBigStoreDetail::where('code',$code_store_destination)->where("material_id",$data->material_id)->value('material_id');


                $material = MaterialBigStoreDetail::where('code',$code_store_destination)->where("material_id",$data->material_id)->value('material_id');

                MaterialExtraBigStoreDetail::where('code',$code_store_origin)->where('material_id',$data->material_id)
                    ->update($bigStore);

                $flag_material_bg = 1;

                if (!empty($material)) {
                    MaterialBigStoreDetail::where('code',$code_store_destination)->where('material_id',$data->material_id)
                        ->update($mdStore);
                } else {
                    $mediumStoreData = array(
                        'material_id' => $data->material_id,
                        'quantity' => $data->quantity_transfered,
                        'cump' => $cump,
                        'code' => $code_store_destination,
                        'purchase_price' => $cump,
                        'total_cump_value' => $value_stockin,
                        'total_purchase_value' => $value_stockin,
                        'verified' => false,
                        'created_by' => $this->user->name,
                        'created_at' => \Carbon\Carbon::now()
                    );
                    MaterialBigStoreDetail::insert($mediumStoreData);
                }

                $flag_material_md = 1;

                MaterialRequisition::where('requisition_no', '=', $data->requisition_no)
                    ->update(['status' => 5]);
                MaterialRequisitionDetail::where('requisition_no', '=', $data->requisition_no)
                    ->update(['status' => 5]);

                }
  
        }

        // Accounting entries for type_store == 'bg' (after processing all details)
        if ($flag_material_bg != 0 && $totalAccountingValue > 0 && $originAccountId && $destinationAccountId) {
            // Vérifier la balance du compte d'origine avant de créditer
            $originLedger = AccountLedger::where('account_id', $originAccountId)->first();
            $currentDebit = (float) ($originLedger->debit ?? 0);
            $currentCredit = (float) ($originLedger->credit ?? 0);
            $currentBalance = $currentDebit - $currentCredit;
            
            // La balance doit être positive (débit > crédit) et suffisante pour supporter le crédit
            if ($currentBalance <= 0) {
                DB::rollback();
                session()->flash('error', 'Impossible d\'approuver le transfert. Le compte du grand stock a une balance de ' . number_format($currentBalance, 2) . '. Il n\'y a pas de valeur en stock ou la valeur a déjà été transférée.');
                return redirect()->back();
            }
            
            if ($currentBalance < $totalAccountingValue) {
                DB::rollback();
                session()->flash('error', 'Balance insuffisante du compte du grand stock. Balance actuelle: ' . number_format($currentBalance, 2) . ', Valeur transférée demandée: ' . number_format($totalAccountingValue, 2) . '. Il n\'y a pas suffisamment de valeur en stock pour effectuer ce transfert.');
                return redirect()->back();
            }
            
            // Créditer le compte du grand stock (origine)
            $originLedgerState = $this->updateLedgerTotals($originAccountId, 'credit', $totalAccountingValue);
            
            $journalEntryCreditOrigin = [
                'date' => $transferDate,
                'document_no' => $transfer_no,
                'description' => 'Transfert GRAND STOCK VERS STOCK INTERMEDIAIRE',
                'account_id' => $originAccountId,
                'libelle' => 'Transfert GRAND STOCK VERS STOCK INTERMEDIAIRE',
                'credit' => $totalAccountingValue,
                'debit' => 0,
                'balance' => $originLedgerState['balance'],
                'active' => 1,
                'created_at' => Carbon::now()
            ];
            
            AccountJournalEntryDetail::insert([$journalEntryCreditOrigin]);
            
            // Débiter le compte du stock intermédiaire (destination)
            $destinationLedgerState = $this->updateLedgerTotals($destinationAccountId, 'debit', $totalAccountingValue);
            
            $journalEntryDebitDestination = [
                'date' => $transferDate,
                'document_no' => $transfer_no,
                'description' => 'Transfert GRAND STOCK VERS STOCK INTERMEDIAIRE',
                'account_id' => $destinationAccountId,
                'libelle' => 'Transfert GRAND STOCK VERS STOCK INTERMEDIAIRE',
                'debit' => $totalAccountingValue,
                'credit' => 0,
                'balance' => $destinationLedgerState['balance'],
                'active' => 1,
                'created_at' => Carbon::now()
            ];
            
            AccountJournalEntryDetail::insert([$journalEntryDebitDestination]);
        }

        // Insert reports
        if ($flag_material_bg != 0) {
            if (!empty($reportBigStoreData)) {
                MaterialExtraBigReport::insert($reportBigStoreData);
            }
            if (!empty($reportMediumStoreData)) {
                MaterialBigReport::insert($reportMediumStoreData);
            }
        } elseif ($flag_material_md != 0) {
            if (!empty($reportBigStoreData)) {
                MaterialBigReport::insert($reportBigStoreData);
            }
            if (!empty($reportSmallStoreData)) {
                MaterialSmallReport::insert($reportSmallStoreData);
            }
        }

        MaterialTransfer::where('transfer_no', '=', $transfer_no)
                        ->update(['status' => 4,'approuved_by' => $this->user->name]);
        MaterialTransferDetail::where('transfer_no', '=', $transfer_no)
                        ->update(['status' => 4,'approuved_by' => $this->user->name]);

        // Update requisition status to 6 (closed)
        $requisition_no = MaterialTransfer::where('transfer_no', $transfer_no)->value('requisition_no');
        if ($requisition_no) {
            MaterialRequisition::where('requisition_no', $requisition_no)->update(['status' => 6]);
            MaterialRequisitionDetail::where('requisition_no', $requisition_no)->update(['status' => 6]);
        }

            $successMessage = 'Transfer has been done successfully!';
            if (!empty($reportBigStoreData)) {
                $code_origin = !empty($reportBigStoreData[0]['code_store']) ? $reportBigStoreData[0]['code_store'] : '';
                $successMessage .= ' From: ' . $code_origin;
            }
            if (!empty($reportSmallStoreData)) {
                $code_dest = !empty($reportSmallStoreData[0]['code_store']) ? $reportSmallStoreData[0]['code_store'] : '';
                $successMessage .= ' To: ' . $code_dest;
            }
            if (!empty($reportMediumStoreData)) {
                $code_dest = !empty($reportMediumStoreData[0]['code_store']) ? $reportMediumStoreData[0]['code_store'] : '';
                $successMessage .= ' To: ' . $code_dest;
            }

            DB::commit();
            
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
     * @param  int  $transfer_no
     * @return \Illuminate\Http\Response
     */
    public function destroy($transfer_no)
    {
        if (is_null($this->user) || !$this->user->can('material_transfer.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any transfer !');
        }

        try {DB::beginTransaction();

        $transfer = MaterialTransfer::where('transfer_no',$transfer_no)->first();
        if (!is_null($transfer)) {
            $transfer->delete();
            MaterialTransferDetail::where('transfer_no',$transfer_no)->delete();
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
    }

}
