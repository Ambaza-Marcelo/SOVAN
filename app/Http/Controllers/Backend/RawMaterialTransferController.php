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
use App\Models\RawMaterial;
use App\Models\RawMaterialTransfer;
use App\Models\RawMaterialTransferDetail;
use App\Models\RawMaterialBigStoreDetail;
use App\Models\RawMaterialSmallStoreDetail;
use App\Models\RawMaterialBigStore;
use App\Models\RawMaterialExtraBigStore;
use App\Models\RawMaterialExtraBigStoreDetail;
use App\Models\RawMaterialExtraBigReport;
use App\Models\RawMaterialSmallStore;
use App\Models\RawMaterialRequisitionDetail;
use App\Models\RawMaterialRequisition;
use App\Models\RawMaterialBigReport;
use App\Models\RawMaterialSmallReport;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use Carbon\Carbon;
use PDF;
use Validator;
use Excel;
use Mail;

class RawMaterialTransferController extends Controller
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
        if (is_null($this->user) || !$this->user->can('raw_material_transfer.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any transfer !');
        }
        $query = RawMaterialTransfer::orderBy('id','desc');
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = \Carbon\Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = \Carbon\Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('date', [$dateDebut, $dateFin]);
        } else {
            $query->take(100);
        }
        $transfers = $query->get();
        $requisitions = RawMaterialRequisition::whereIn('requisition_no', $transfers->pluck('requisition_no'))->get()->keyBy('requisition_no');
        return view('backend.pages.raw_material_transfer.index', compact('transfers', 'requisitions'));
    }

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_transfer.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export transfers !');
        }
        $dateDebut = $request->input('date_debut') ?? $request->input('start_date');
        $dateFin = $request->input('date_fin') ?? $request->input('end_date');
        if (!$dateDebut || !$dateFin) {
            return redirect()->route('admin.raw-material-transfers.index')
                ->with('error', __('messages.export_select_date_range'));
        }
        $filename = 'transferts_matieres_premieres_' . \Carbon\Carbon::parse($dateDebut)->format('Y-m-d') . '_' . \Carbon\Carbon::parse($dateFin)->format('Y-m-d') . '.xlsx';
        return \Excel::download(new \App\Exports\RawMaterialTransferExport(), $filename);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create($requisition_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_transfer.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any transfer !');
        }

        $raw_materials  = RawMaterial::orderBy('name','asc')->get();
        $origin_stores = RawMaterialBigStore::all();
        $destination_stores = RawMaterialSmallStore::all();
        $datas = RawMaterialRequisitionDetail::where('requisition_no', $requisition_no)->get();
        return view('backend.pages.raw_material_transfer.create', compact('raw_materials','requisition_no','datas','origin_stores','destination_stores'));
    }

    public function createFromBig($requisition_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_transfer.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any transfer !');
        }

        $raw_materials  = RawMaterial::orderBy('name','asc')->get();
        $origin_stores = RawMaterialExtraBigStore::all();
        $destination_stores = RawMaterialBigStore::all();
        $datas = RawMaterialRequisitionDetail::where('requisition_no', $requisition_no)->get();
        return view('backend.pages.raw_material_transfer.create_from_big', compact('raw_materials','requisition_no','datas','origin_stores','destination_stores'));
    }

    public function portion($transfer_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_transfer.portion')) {
            abort(403, 'Sorry !! You are Unauthorized to create any transfer !');
        }

        $raw_materials  = RawMaterial::where('store_type','!=',2)->orderBy('name','asc')->get();
        $origin_stores = RawMaterialBigStore::all();
        $destination_stores = RawMaterialSmallStore::all();
        $data = RawMaterialTransferDetail::where('transfer_no', $transfer_no)->first();
        $datas = RawMaterialTransferDetail::where('transfer_no', $transfer_no)->get();
        return view('backend.pages.raw_material_transfer.portion', compact('raw_materials','transfer_no','datas','origin_stores','destination_stores','data'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('raw_material_transfer.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any transfer !');
        }

        $rules = array(
                'r_material_id.*'  => 'required',
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

            $r_material_id = $request->r_material_id;
            $date = \Carbon\Carbon::now();
            $origin_store_id = $request->origin_store_id;
            $requisition_no = $request->requisition_no;
            $type_store = $request->type_store;
            $description =$request->description; 
            $destination_store_id = $request->destination_store_id;
            $quantity_requisitioned = $request->quantity_requisitioned;
            $price = $request->price;
            $quantity_transfered = $request->quantity_transfered;
            

            $latest = RawMaterialTransfer::latest()->first();
            if ($latest) {
               $transfer_no = 'BT' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $transfer_no = 'BT' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $created_by = $this->user->name;

            $transfer_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$transfer_no;


            for( $count = 0; $count < count($r_material_id); $count++ ){
                $total_value_requisitioned = $quantity_requisitioned[$count] * $price[$count];
                $total_value_transfered = $quantity_transfered[$count] * $price[$count];
                $data = array(
                    'r_material_id' => $r_material_id[$count],
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
            RawMaterialTransferDetail::insert($insert_data);


            //create transfer
            $transfer = new RawMaterialTransfer();
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

            // Update requisition status to 5 (transferred in pending)
            RawMaterialRequisition::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 5]);
            RawMaterialRequisitionDetail::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 5]);

            DB::commit();
            session()->flash('success', 'transfer has been created !!');
            return redirect()->route('admin.raw-material-transfers.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
            
    }

    public function storeFromBig(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('raw_material_transfer.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any transfer !');
        }

        $rules = array(
                'r_material_id.*'  => 'required',
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

            $r_material_id = $request->r_material_id;
            $date = \Carbon\Carbon::now();
            $origin_extra_store_id = $request->origin_extra_store_id;
            $requisition_no = $request->requisition_no;
            $type_store = $request->type_store;
            $description =$request->description; 
            $dest_bg_store_id = $request->dest_bg_store_id;
            $quantity_requisitioned = $request->quantity_requisitioned;
            $price = $request->price;
            $quantity_transfered = $request->quantity_transfered;
            

            $latest = RawMaterialTransfer::latest()->first();
            if ($latest) {
               $transfer_no = 'BT' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $transfer_no = 'BT' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $created_by = $this->user->name;

            $transfer_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$transfer_no;


            for( $count = 0; $count < count($r_material_id); $count++ ){
                $total_value_requisitioned = $quantity_requisitioned[$count] * $price[$count];
                $total_value_transfered = $quantity_transfered[$count] * $price[$count];
                $data = array(
                    'r_material_id' => $r_material_id[$count],
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
            RawMaterialTransferDetail::insert($insert_data);


            //create transfer
            $transfer = new RawMaterialTransfer();
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

            // Update requisition status to 5 (transferred in pending)
            RawMaterialRequisition::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 5]);
            RawMaterialRequisitionDetail::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 5]);

            DB::commit();
            session()->flash('success', 'transfer has been created !!');
            return redirect()->route('admin.raw-material-transfers.index');
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
        $transfer = RawMaterialTransfer::with(['originStore', 'originExtraStore', 'destinationStore', 'destinationBigStore'])
            ->where('transfer_no', $transfer_no)->first();
        
        if (is_null($transfer)) {
            session()->flash('error', 'Transfer not found !!');
            return redirect()->route('admin.raw-material-transfers.index');
        }
        
        $transferDetails = RawMaterialTransferDetail::with(['rawMaterial.rawMaterialMeasurement'])
            ->where('transfer_no', $transfer_no)->get();
        
        // Calculate totals
        $totalQuantityRequisitioned = $transferDetails->sum('quantity_requisitioned');
        $totalQuantityTransfered = $transferDetails->sum('quantity_transfered');
        $totalQuantityPortion = $transferDetails->sum('quantity_transfered');
        $totalValueTransfered = $transferDetails->sum('total_value_transfered');
        
        return view('backend.pages.raw_material_transfer.show', compact('transfer', 'transferDetails', 'totalQuantityRequisitioned', 'totalQuantityTransfered', 'totalQuantityPortion', 'totalValueTransfered'));
         
    }

    public function storePortion(Request $request, $transfer_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_transfer.portion')) {
            abort(403, 'Sorry !! You are Unauthorized to portion any raw material !');
        }

        $rules = array(
                'r_material_id.*'  => 'required',
                'quantity_portion.*'  => 'required',
                'price.*'  => 'required',
                'quantity_transfered.*'  => 'required',
                'description_portion'  => 'required'
            );

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $r_material_id = $request->r_material_id;
            $date_portion = \Carbon\Carbon::now();
            $origin_store_id = $request->origin_store_id;
            $description_portion =$request->description_portion; 
            $destination_store_id = $request->destination_store_id;
            //$unit_portion = $request->unit_portion;
            $quantity_portion = $request->quantity_portion;
            $price = $request->price;
            $quantity_transfered = $request->quantity_transfered ?? $request->quantity_portion;
            $portioned_by = $this->user->name;

            for( $count = 0; $count < count($r_material_id); $count++ ){
                $qty = $quantity_portion[$count] ?? $quantity_transfered[$count] ?? 0;
                $value_portion = $qty * $price[$count];
                $data = array(
                    'quantity_transfered' => $qty,
                    'total_value_transfered' => $value_portion,
                    'description' => $description_portion,
                    'updated_by' => $portioned_by,
                    'updated_at' => \Carbon\Carbon::now()
                );
                RawMaterialTransferDetail::where('transfer_no',$transfer_no)->where('r_material_id',$r_material_id[$count])->update($data);
            }

            RawMaterialTransfer::where('transfer_no', '=', $transfer_no)
                ->update(['updated_by' => $portioned_by]);
            RawMaterialTransferDetail::where('transfer_no', '=', $transfer_no)
                ->update(['updated_by' => $portioned_by]);

            DB::commit();
            session()->flash('success', 'Raw material has been portioned successfully !!');
            return redirect()->route('admin.raw-material-transfers.index');
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }
            
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($transfer_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_transfer.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any transfer !');
        }

        $transfer = RawMaterialTransfer::where('transfer_no', $transfer_no)->first();
        if (is_null($transfer)) {
            session()->flash('error', 'Transfer not found !!');
            return redirect()->route('admin.raw-material-transfers.index');
        }

        // Check if requisition status is 6 (cannot edit when fully transferred)
        $requisition = RawMaterialRequisition::where('requisition_no', $transfer->requisition_no)->first();
        if ($requisition && $requisition->status == 6) {
            session()->flash('error', 'Cannot edit a transfer when requisition status is 6 (Transferred) !!');
            return redirect()->route('admin.raw-material-transfers.index');
        }

        // Get transfer details
        $transferDetails = RawMaterialTransferDetail::with(['rawMaterial.rawMaterialMeasurement'])
            ->where('transfer_no', $transfer_no)->get();

        // Get requisition details (for reference data)
        $requisitionDetails = RawMaterialRequisitionDetail::with(['rawMaterial.rawMaterialMeasurement'])
            ->where('requisition_no', $transfer->requisition_no)->get();

        // Get all raw materials for dropdown
        $raw_materials = RawMaterial::orderBy('name', 'asc')->get();

        // Get stores based on transfer type
        if ($transfer->type_store == 'md') {
            $origin_stores = RawMaterialBigStore::all();
            $destination_stores = RawMaterialSmallStore::all();
        } else {
            $origin_stores = RawMaterialExtraBigStore::all();
            $destination_stores = RawMaterialBigStore::all();
        }

        return view('backend.pages.raw_material_transfer.edit', compact(
            'transfer', 
            'transferDetails', 
            'requisitionDetails', 
            'raw_materials', 
            'origin_stores', 
            'destination_stores',
            'requisition'
        ));
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
        if (is_null($this->user) || !$this->user->can('raw_material_transfer.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any transfer !');
        }

        $transfer = RawMaterialTransfer::where('transfer_no', $transfer_no)->first();
        if (is_null($transfer)) {
            session()->flash('error', 'Transfer not found !!');
            return redirect()->route('admin.raw-material-transfers.index');
        }

        // Check if requisition status is 6 (cannot edit when fully transferred)
        $requisition = RawMaterialRequisition::where('requisition_no', $transfer->requisition_no)->first();
        if ($requisition && $requisition->status == 6) {
            session()->flash('error', 'Cannot edit a transfer when requisition status is 6 (Transferred) !!');
            return redirect()->route('admin.raw-material-transfers.index');
        }

        $rules = array(
            'r_material_id.*'  => 'required',
            'date'  => 'required',
            'quantity_requisitioned.*'  => 'required',
            'price.*'  => 'required',
            'quantity_transfered.*'  => 'required',
            'description'  => 'required'
        );

        // Add store validation based on type
        if ($transfer->type_store == 'md') {
            $rules['origin_store_id'] = 'required';
            $rules['destination_store_id'] = 'required';
        } else {
            $rules['origin_extra_store_id'] = 'required';
            $rules['dest_bg_store_id'] = 'required';
        }

        $error = Validator::make($request->all(), $rules);

        if ($error->fails()) {
            return response()->json([
                'error' => $error->errors()->all(),
            ]);
        }

        try {
            DB::beginTransaction();

            $r_material_id = $request->r_material_id;
            $date = \Carbon\Carbon::parse($request->date);
            $description = $request->description;
            $quantity_requisitioned = $request->quantity_requisitioned;
            $price = $request->price;
            $quantity_transfered = $request->quantity_transfered;
            $updated_by = $this->user->name;

            // Update store IDs based on type
            if ($transfer->type_store == 'md') {
                $origin_store_id = $request->origin_store_id;
                $destination_store_id = $request->destination_store_id;
                $transfer->origin_store_id = $origin_store_id;
                $transfer->destination_store_id = $destination_store_id;
            } else {
                $origin_extra_store_id = $request->origin_extra_store_id;
                $dest_bg_store_id = $request->dest_bg_store_id;
                $transfer->origin_extra_store_id = $origin_extra_store_id;
                $transfer->dest_bg_store_id = $dest_bg_store_id;
            }

            // Delete old transfer details
            RawMaterialTransferDetail::where('transfer_no', $transfer_no)->delete();

            // Insert updated transfer details
            $insert_data = [];
            for ($count = 0; $count < count($r_material_id); $count++) {
                $total_value_requisitioned = $quantity_requisitioned[$count] * $price[$count];
                $total_value_transfered = $quantity_transfered[$count] * $price[$count];
                
                $data = array(
                    'r_material_id' => $r_material_id[$count],
                    'date' => $date,
                    'quantity_requisitioned' => $quantity_requisitioned[$count],
                    'quantity_transfered' => $quantity_transfered[$count],
                    'price' => $price[$count],
                    'total_value_requisitioned' => $total_value_requisitioned,
                    'total_value_transfered' => $total_value_transfered,
                    'requisition_no' => $transfer->requisition_no,
                    'type_store' => $transfer->type_store,
                    'transfer_no' => $transfer_no,
                    'transfer_signature' => $transfer->transfer_signature,
                    'created_by' => $transfer->created_by, // Keep original creator
                    'updated_by' => $updated_by,
                    'description' => $description,
                    'status' => $transfer->status, // Keep original status
                    'created_at' => $transfer->created_at, // Keep original created_at
                    'updated_at' => \Carbon\Carbon::now()
                );

                // Add store IDs based on type
                if ($transfer->type_store == 'md') {
                    $data['origin_store_id'] = $origin_store_id;
                    $data['destination_store_id'] = $destination_store_id;
                } else {
                    $data['origin_extra_store_id'] = $origin_extra_store_id;
                    $data['dest_bg_store_id'] = $dest_bg_store_id;
                }

                $insert_data[] = $data;
            }

            RawMaterialTransferDetail::insert($insert_data);

            // Update main transfer
            $transfer->date = $date;
            $transfer->description = $description;
            $transfer->updated_by = $updated_by;
            $transfer->save();

            DB::commit();
            session()->flash('success', 'Transfer has been updated !!');
            return redirect()->route('admin.raw-material-transfers.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bonTransfert($transfer_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_transfer.create')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $transfer_no = RawMaterialTransfer::where('transfer_no', $transfer_no)->value('transfer_no');
        $datas = RawMaterialTransferDetail::where('transfer_no', $transfer_no)->get();
        $data = RawMaterialTransfer::where('transfer_no', $transfer_no)->first();
        $description = RawMaterialTransfer::where('transfer_no', $transfer_no)->value('description');
        $requisition_no = RawMaterialTransfer::where('transfer_no', $transfer_no)->value('requisition_no');
        $transfer_signature = RawMaterialTransfer::where('transfer_no', $transfer_no)->value('transfer_signature');
        $date = RawMaterialTransfer::where('transfer_no', $transfer_no)->value('date');
        $totalValueTransfered = DB::table('raw_material_transfer_details')
            ->where('transfer_no', '=', $transfer_no)
            ->sum('total_value_transfered');
        $totalValueRequisitioned = DB::table('raw_material_transfer_details')
            ->where('transfer_no', '=', $transfer_no)
            ->sum('total_value_requisitioned');
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.raw_material_transfert', compact('datas','transfer_no','totalValueTransfered','totalValueRequisitioned','data','description','requisition_no','setting','date','transfer_signature'), [], 'UTF-8');

        Storage::put('public/pdf/raw_material_transfert/'.$transfer_no.'.pdf', $pdf->output());

        // download pdf file
        return $pdf->download('BON DE TRANSFERT '.$transfer_no.'.pdf');
        
    }

    public function print($transfer_no) { return $this->bonTransfert($transfer_no); }

    public function validateTransfer($transfer_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_transfer.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any transfer !');
        }

        $transfer = RawMaterialTransfer::where('transfer_no', $transfer_no)->first();
        if (!$transfer) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentValidate($transfer)) {
            return $response;
        }

        try {DB::beginTransaction();

            RawMaterialTransfer::where('transfer_no', '=', $transfer_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            RawMaterialTransferDetail::where('transfer_no', '=', $transfer_no)
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
       if (is_null($this->user) || !$this->user->can('raw_material_transfer.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any transfer !');
        }

        try {DB::beginTransaction();

        RawMaterialTransfer::where('transfer_no', '=', $transfer_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);
        RawMaterialTransferDetail::where('transfer_no', '=', $transfer_no)
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
       if (is_null($this->user) || !$this->user->can('raw_material_transfer.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any transfer !');
        }

        try {DB::beginTransaction();

        RawMaterialTransfer::where('transfer_no', '=', $transfer_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
        RawMaterialTransferDetail::where('transfer_no', '=', $transfer_no)
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
       if (is_null($this->user) || !$this->user->can('raw_material_transfer.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any transfer !');
        }

        $transfer = RawMaterialTransfer::where('transfer_no', $transfer_no)->first();
        if (!$transfer) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentConfirm($transfer)) {
            return $response;
        }

        try {DB::beginTransaction();

        RawMaterialTransfer::where('transfer_no', '=', $transfer_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            RawMaterialTransferDetail::where('transfer_no', '=', $transfer_no)
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
       if (is_null($this->user) || !$this->user->can('raw_material_transfer.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to approuve any transfer !');
        }

        $transferHeader = RawMaterialTransfer::where('transfer_no', $transfer_no)->first();
        if (!$transferHeader) {
            session()->flash('error', 'Document introuvable.');
            return back();
        }
        if ($response = $this->guardStockDocumentApprove($transferHeader)) {
            return $response;
        }

        try {DB::beginTransaction();


        $datas = RawMaterialTransferDetail::where('transfer_no', $transfer_no)->get();

        $flag_food_bg = 0;
        $flag_food_md = 0;
        $flag_food_sm = 0;
        
        // Variables for accounting entries (for type 'md' transfers)
        $totalTransferValue = 0;
        $originStoreId = null;
        $destinationStoreId = null;
        $transferDate = null;
        $transferDescription = null;

        foreach($datas as $data){

                if ($data->type_store == 'md') {
                    $code_store_origin = RawMaterialBigStore::where('id',$data->origin_store_id)->value('code');
                    $code_store_destination = RawMaterialSmallStore::where('id',$data->destination_store_id)->value('code');
                    
                    // Store IDs and values for accounting entries
                    if ($originStoreId === null) {
                        $originStoreId = $data->origin_store_id;
                        $destinationStoreId = $data->destination_store_id;
                        $transferDate = $data->date;
                        $transferDescription = $data->description ?? 'Transfer: ' . $transfer_no;
                    }
                    $totalTransferValue += $data->total_value_transfered;

                $valeurStockInitialOrigine = RawMaterialBigStoreDetail::where('code',$code_store_origin)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('total_cump_value');
                $quantityStockInitialOrigine = RawMaterialBigStoreDetail::where('code',$code_store_origin)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('quantity');
                $quantityRestantBigStore = $quantityStockInitialOrigine - $data->quantity_transfered;

                $valeurStockInitialDestination = RawMaterialSmallStoreDetail::where('code',$code_store_destination)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('total_cump_value');
                $cump = RawMaterialBigStoreDetail::where('code',$code_store_origin)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('cump');
                $quantityStockInitialDestination = RawMaterialSmallStoreDetail::where('code',$code_store_destination)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('quantity');
                $quantityRestantSmallStore = $quantityStockInitialDestination - $data->quantity_transfered;


                $valeurAcquisition = $data->quantity_transfered * $data->price;

                $valeurTotalUnite = $data->quantity_transfered + $quantityStockInitialDestination;
                $cump = $cump;

                $reportMediumStore = array(
                    'r_material_id' => $data->r_material_id,
                    'quantity_stock_initial' => $quantityStockInitialOrigine,
                    'value_stock_initial' => $valeurStockInitialOrigine,
                    'code_store' => $code_store_origin,
                    'quantity_stockout' => $data->quantity_transfered,
                    'value_stockout' => $data->total_value_transfered,
                    'quantity_stock_final' => $quantityStockInitialOrigine - $data->quantity_transfered,
                    'value_stock_final' => $valeurStockInitialOrigine - $data->total_value_transfered,
                    'cump' => $cump,
                    'document_no' => $transfer_no,
                    'type_transaction' => "SORTIE TRANSFERT",
                    'description' => $data->description,
                    'date' => $data->date,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportMediumStoreData[] = $reportMediumStore;


                $reportSmallStore = array(
                    'r_material_id' => $data->r_material_id,
                    'quantity_stock_initial' => $quantityStockInitialDestination,
                    'value_stock_initial' => $valeurStockInitialDestination,
                    'code_store' => $code_store_destination,
                    'quantity_stockin' => $data->quantity_transfered,
                    'value_stockin' => $data->total_value_transfered,
                    'quantity_stock_final' => $quantityStockInitialDestination + $data->quantity_transfered,
                    'value_stock_final' => $valeurStockInitialDestination + $data->total_value_transfered,
                    'cump' => $cump,
                    'document_no' => $transfer_no,
                    'type_transaction' => "ENTREE TRANSFERT",
                    'description' => $data->description,
                    'date' => $data->date,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportSmallStoreData[] = $reportSmallStore;

                    $mdStore = array(
                        'r_material_id' => $data->r_material_id,
                        'quantity' => $quantityRestantBigStore,
                        'total_purchase_value' => $quantityRestantBigStore * $data->price,
                        'total_cump_value' => $quantityRestantBigStore * $data->price,
                        'created_by' => $this->user->name,
                        'verified' => false,
                        'created_at' => \Carbon\Carbon::now()
                    );


                    $smallStoreData = array(
                            'r_material_id' => $data->r_material_id,
                            'quantity' => $data->quantity_transfered,
                            'cump' => $cump,
                            'code' => $code_store_destination,
                            'purchase_price' => $data->price,
                            'total_cump_value' => $cump * ($data->quantity_transfered + $quantityStockInitialDestination),
                            'total_purchase_value' => $data->price * ($data->quantity_transfered + $quantityStockInitialDestination),
                            'verified' => false,
                            'created_by' => $this->user->name,
                            'created_at' => \Carbon\Carbon::now()
                        );
                        $smallStore[] = $smallStoreData;

                        $smStore = array(
                            'r_material_id' => $data->r_material_id,
                            'quantity' => $quantityStockInitialDestination + $data->quantity_transfered,
                            'cump' => $cump,
                            'total_cump_value' => $cump * ($data->quantity_transfered + $quantityStockInitialDestination),
                            'total_purchase_value' => $data->price * ($data->quantity_transfered + $quantityStockInitialDestination),
                            'verified' => false,
                            'created_by' => $this->user->name,
                            'created_at' => \Carbon\Carbon::now()
                        );

                        $raw_material_detail_id = RawMaterialSmallStoreDetail::where('code',$code_store_destination)->where("r_material_id",$data->r_material_id)->value('r_material_id');


                    if ($data->quantity_transfered <= $quantityStockInitialOrigine) {
                        
                        RawMaterialBigStoreDetail::where('code',$code_store_origin)->where('r_material_id',$data->r_material_id)
                        ->update($mdStore);

                        $flag_food_md = 1;

                        if (!empty($raw_material_detail_id)) {

                            RawMaterialSmallStoreDetail::where('code',$code_store_destination)->where('r_material_id',$data->r_material_id)
                        ->update($smStore);

                            $flag_food_sm = 1;

                        }else{
                            session()->flash('error', 'oops! Something goes wrong, check if all items are in all stores');
                            return redirect()->back();
                        }

                        // Status will be updated to 6 after all transfers are processed (see end of method)
                        
                    }else{
                        session()->flash('error', 'Why do you want transfering quantity you do not have in your store?please rewrite a valid quantity!');
                        return redirect()->back();
                    }


                }elseif ($data->type_store == 'bg') {
                    $code_store_origin = RawMaterialExtraBigStore::where('id',$data->origin_extra_store_id)->value('code');
                $code_store_destination = RawMaterialBigStore::where('id',$data->dest_bg_store_id)->value('code');

                $valeurStockInitialOrigine = RawMaterialExtraBigStoreDetail::where('code',$code_store_origin)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('total_cump_value');
                $quantityStockInitialOrigine = RawMaterialExtraBigStoreDetail::where('code',$code_store_origin)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('quantity');
                $quantityRestantBigStore = $quantityStockInitialOrigine - $data->quantity_transfered;

                $valeurStockInitialDestination = RawMaterialBigStoreDetail::where('code',$code_store_destination)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('total_cump_value');

                $cump = RawMaterialExtraBigStoreDetail::where('code',$code_store_origin)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('cump');

                $quantityStockInitialDestination = RawMaterialBigStoreDetail::where('code',$code_store_destination)->where('r_material_id','!=', '')->where('r_material_id', $data->r_material_id)->value('quantity');
                $quantityRestantSmallStore = $quantityStockInitialDestination - $data->quantity_transfered;


                $valeurAcquisition = $data->quantity_transfered * $data->price;

                $valeurTotalUnite = $data->quantity_transfered + $quantityStockInitialDestination;
                $cump = $cump;

                $reportBigStore = array(
                    'r_material_id' => $data->r_material_id,
                    'quantity_stock_initial' => $quantityStockInitialOrigine,
                    'value_stock_initial' => $valeurStockInitialOrigine,
                    'code_store' => $code_store_origin,
                    'quantity_stockout' => $data->quantity_transfered,
                    'value_stockout' => $data->total_value_transfered,
                    'quantity_stock_final' => $quantityStockInitialOrigine - $data->quantity_transfered,
                    'value_stock_final' => $valeurStockInitialOrigine - $data->total_value_transfered,
                    'cump' => $cump,
                    'document_no' => $transfer_no,
                    'type_transaction' => "SORTIE TRANSFERT",
                    'description' => $data->description,
                    'date' => $data->date,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportBigStoreData[] = $reportBigStore;


                $reportMediumStore = array(
                    'r_material_id' => $data->r_material_id,
                    'quantity_stock_initial' => $quantityStockInitialDestination,
                    'value_stock_initial' => $valeurStockInitialDestination,
                    'code_store' => $code_store_destination,
                    'quantity_stockin' => $data->quantity_transfered,
                    'value_stockin' => $data->total_value_transfered,
                    'quantity_stock_final' => $quantityStockInitialDestination + $data->quantity_transfered,
                    'value_stock_final' => $valeurStockInitialDestination + $data->total_value_transfered,
                    'cump' => $cump,
                    'document_no' => $transfer_no,
                    'type_transaction' => "ENTREE TRANSFERT",
                    'description' => $data->description,
                    'date' => $data->date,
                    'created_by' => $this->user->name,
                    'created_at' => \Carbon\Carbon::now()
                );
                $reportMediumStoreData[] = $reportMediumStore;

                    $bigStore = array(
                        'r_material_id' => $data->r_material_id,
                        'quantity' => $quantityRestantBigStore,
                        'total_purchase_value' => $quantityRestantBigStore * $data->price,
                        'total_cump_value' => $quantityRestantBigStore * $data->price,
                        'created_by' => $this->user->name,
                        'verified' => false,
                        'created_at' => \Carbon\Carbon::now()
                    );


                    $mediumStoreData = array(
                            'r_material_id' => $data->r_material_id,
                            'quantity' => $quantityStockInitialDestination + $data->quantity_transfered,
                            'cump' => $cump,
                            'code' => $code_store_destination,
                            'purchase_price' => $data->price,
                            'total_cump_value' => $cump * ($data->quantity_transfered + $quantityStockInitialDestination),
                            'total_purchase_value' => $data->price * ($data->quantity_transfered + $quantityStockInitialDestination),
                            'verified' => false,
                            'created_by' => $this->user->name,
                            'created_at' => \Carbon\Carbon::now()
                        );
                        $mediumStore[] = $mediumStoreData;

                        $mdStore = array(
                            'r_material_id' => $data->r_material_id,
                            'quantity' => $quantityStockInitialDestination + $data->quantity_transfered,
                            'cump' => $cump,
                            'total_cump_value' => $cump * ($data->quantity_transfered + $quantityStockInitialDestination),
                            'total_purchase_value' => $data->price * ($data->quantity_transfered + $quantityStockInitialDestination),
                            'verified' => false,
                            'created_by' => $this->user->name,
                            'created_at' => \Carbon\Carbon::now()
                        );

                        $raw_material_detail_id = RawMaterialBigStoreDetail::where('code',$code_store_destination)->where("r_material_id",$data->r_material_id)->value('r_material_id');


                    if ($data->quantity_transfered <= $quantityStockInitialOrigine) {
                        
                        RawMaterialExtraBigStoreDetail::where('code',$code_store_origin)->where('r_material_id',$data->r_material_id)
                        ->update($bigStore);

                        $flag_food_bg = 1;

if (!empty($raw_material_detail_id)) {

                        RawMaterialBigStoreDetail::where('code',$code_store_destination)->where('r_material_id',$data->r_material_id)
                        ->update($mdStore);

                        $flag_food_md = 1;

                        }else{
                            session()->flash('error', 'oops! Something goes wrong, check if all items are in all stores');
                            return redirect()->back();
                        }

                        // Status will be updated to 6 after all transfers are processed (see end of method)
                        
                    }else{
                        session()->flash('error', 'Why do you want transfering quantity you do not have in your store?please rewrite a valid quantity!');
                        return redirect()->back();
                    }

                }
  
        }

        // Get the requisition_no from the first transfer detail to update requisition status
        $requisition_no_to_update = $datas->first()->requisition_no ?? null;

        RawMaterialTransfer::where('transfer_no', '=', $transfer_no)
                        ->update(['status' => 4,'approuved_by' => $this->user->name]);
        RawMaterialTransferDetail::where('transfer_no', '=', $transfer_no)
                        ->update(['status' => 4,'approuved_by' => $this->user->name]);

        // Update requisition status to 6 (transferred) when transfer is approved
        if ($requisition_no_to_update) {
            RawMaterialRequisition::where('requisition_no', '=', $requisition_no_to_update)
                ->update(['status' => 6]);
            RawMaterialRequisitionDetail::where('requisition_no', '=', $requisition_no_to_update)
                ->update(['status' => 6]);
        }

        if ($flag_food_bg != 0) {
            RawMaterialExtraBigReport::insert($reportBigStoreData);
            RawMaterialBigReport::insert($reportMediumStoreData);
        }elseif ($flag_food_md != 0) {
            RawMaterialBigReport::insert($reportMediumStoreData);
            RawMaterialSmallReport::insert($reportSmallStoreData);
            
            // Accounting entries: Credit intermediate stock (RawMaterialBigStore), Debit small stock (RawMaterialSmallStore)
            if ($totalTransferValue > 0 && $originStoreId && $destinationStoreId) {
                // Get account IDs
                $id_acc_intermediate_stock = RawMaterialBigStore::where('id', $originStoreId)->value('default_account_id');
                $id_acc_small_stock = RawMaterialSmallStore::where('id', $destinationStoreId)->value('default_account_id');

                if ($id_acc_intermediate_stock && $id_acc_small_stock) {
                    // Credit intermediate stock account (decrease stock)
                    $intermediateStockLedgerState = $this->updateLedgerTotals($id_acc_intermediate_stock, 'credit', $totalTransferValue);

                    $journalEntryCreditIntermediateStock = array(
                        'date' => $transferDate ?? Carbon::now(),
                        'document_no' => $transfer_no,
                        'description' => $transferDescription ?? 'Transfer: ' . $transfer_no,
                        'account_id' => $id_acc_intermediate_stock,
                        'libelle' => $transferDescription ?? 'Transfer: ' . $transfer_no,
                        'credit' => $totalTransferValue,
                        'balance' => $intermediateStockLedgerState['balance'],
                        'created_at' => Carbon::now()
                    );
                    AccountJournalEntryDetail::insert([$journalEntryCreditIntermediateStock]);

                    // Debit small stock account (increase stock)
                    $smallStockLedgerState = $this->updateLedgerTotals($id_acc_small_stock, 'debit', $totalTransferValue);

                    $journalEntryDebitSmallStock = array(
                        'date' => $transferDate ?? Carbon::now(),
                        'document_no' => $transfer_no,
                        'description' => $transferDescription ?? 'Transfer: ' . $transfer_no,
                        'account_id' => $id_acc_small_stock,
                        'libelle' => $transferDescription ?? 'Transfer: ' . $transfer_no,
                        'debit' => $totalTransferValue,
                        'balance' => $smallStockLedgerState['balance'],
                        'created_at' => Carbon::now()
                    );
                    AccountJournalEntryDetail::insert([$journalEntryDebitSmallStock]);
                } else {
                    // Rollback if accounts are not configured
                    if (!$id_acc_intermediate_stock) {
                        DB::rollback();
                        session()->flash('error', 'Intermediate stock store default account not configured. Please configure it first !!');
                        return back();
                    }
                    if (!$id_acc_small_stock) {
                        DB::rollback();
                        session()->flash('error', 'Small stock store default account not configured. Please configure it first !!');
                        return back();
                    }
                }
            }
        }

        DB::commit();
            session()->flash('success', 'Transfer has been done successfuly !,from store '.$code_store_origin.' to '.$code_store_destination);
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
        if (is_null($this->user) || !$this->user->can('raw_material_transfer.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any transfer !');
        }

        $transfer = RawMaterialTransfer::where('transfer_no',$transfer_no)->first();
        if (is_null($transfer)) {
            session()->flash('error', 'Transfer not found !!');
            return back();
        }

        // Check if requisition status is 6 (cannot delete when fully transferred)
        $requisition = RawMaterialRequisition::where('requisition_no', $transfer->requisition_no)->first();
        if ($requisition && $requisition->status == 6) {
            session()->flash('error', 'Cannot delete a transfer when requisition status is 6 (Transferred) !!');
            return back();
        }

        try {DB::beginTransaction();

        if (!is_null($transfer)) {
            $transfer->delete();
            RawMaterialTransferDetail::where('transfer_no',$transfer_no)->delete();
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

    /**
     * Update ledger totals for accounting entries
     *
     * @param int $accountId
     * @param string $type ('debit' or 'credit')
     * @param float $amount
     * @return array
     */
}
