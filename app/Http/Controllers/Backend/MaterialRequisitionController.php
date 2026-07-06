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
use App\Models\MaterialRequisition;
use App\Models\MaterialRequisitionDetail;
use App\Models\MaterialBigStore;
use App\Models\MaterialSmallStore;
use App\Models\MaterialExtraBigStore;
use Validator;
use PDF;
use Mail;
use Carbon\Carbon;
use App\Mail\OrderMail;

class MaterialRequisitionController extends Controller
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
        if (is_null($this->user) || !$this->user->can('material_requisition.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any requisition !');
        }

        $query = MaterialRequisition::query();
        
        // Si des dates sont fournies, filtrer par période
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('created_at', [$dateDebut, $dateFin]);
        } else {
            // Par défaut, afficher les 100 lignes les plus récentes
            $query->take(100);
        }
        
        $requisitions = $query->orderBy('id','desc')->get();
        return view('backend.pages.material_requisition.index', compact('requisitions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('material_requisition.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any requisition !');
        }

        $materials  = Material::orderBy('name','asc')->get();
        return view('backend.pages.material_requisition.create', compact('materials'));
    }

    public function createFromBig()
    {
        if (is_null($this->user) || !$this->user->can('material_requisition.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any requisition !');
        }

        $materials  = Material::orderBy('name','asc')->get();
        return view('backend.pages.material_requisition.create_from_big', compact('materials'));
    }

    public function choose()
    {
        if (is_null($this->user) || !$this->user->can('material_requisition.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any requisition !');
        }

        return view('backend.pages.material_requisition.choose');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('material_requisition.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any requisition !');
        }

        $rules = array(
                'material_id.*'  => 'required',
                'date'  => 'required',
                'quantity_requisitioned.*'  => 'required',
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
            $quantity_requisitioned = $request->quantity_requisitioned;
            $type_store = $request->type_store;
            $description =$request->description; 
            $latest = MaterialRequisition::latest()->first();
            if ($latest) {
               $requisition_no = 'BR' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $requisition_no = 'BR' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $requisition_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$requisition_no;
            $created_by = $this->user->name;

            //create requisition
            $requisition = new MaterialRequisition();
            $requisition->date = $date;
            $requisition->requisition_signature = $requisition_signature;
            $requisition->requisition_no = $requisition_no;
            $requisition->type_store = $type_store;
            $requisition->code_store = $request->code_store ?? null; // Store code_store if provided
            $requisition->created_by = $created_by;
            $requisition->description = $description;
            $requisition->created_at = \Carbon\Carbon::now();
            $requisition->save();
            //insert details of requisition No.
            for( $count = 0; $count < count($material_id); $count++ ){

                $price = Material::where('id', $material_id[$count])->value('purchase_price');
                $total_value_requisitioned = $quantity_requisitioned[$count] * $price;
                $data = array(
                    'material_id' => $material_id[$count],
                    'date' => $date,
                    'quantity_requisitioned' => $quantity_requisitioned[$count],
                    'price' => $price,
                    'description' => $description,
                    'total_value_requisitioned' => $total_value_requisitioned,
                    'created_by' => $created_by,
                    'requisition_no' => $requisition_no,
                    'type_store' => $type_store,
                    'requisition_signature' => $requisition_signature,
                    'created_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }
       
        MaterialRequisitionDetail::insert($insert_data);

        DB::commit();
            session()->flash('success', 'Requisition has been created !!');
            return redirect()->route('admin.material-requisitions.index');
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
    public function show($requisition_no)
    {
        //
        $requisition = MaterialRequisition::where('requisition_no', $requisition_no)->first();
        $requisitionDetails = MaterialRequisitionDetail::with(['material.materialMeasurement'])
            ->where('requisition_no', $requisition_no)->get();
        
        // Calculate totals
        $totalQuantityRequisitioned = $requisitionDetails->sum('quantity_requisitioned');
        $totalValueRequisitioned = $requisitionDetails->sum('total_value_requisitioned');
        
        return view('backend.pages.material_requisition.show', compact('requisition', 'requisitionDetails', 'totalQuantityRequisitioned', 'totalValueRequisitioned'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $requisition_no
     * @return \Illuminate\Http\Response
     */
    public function edit($requisition_no)
    {
        if (is_null($this->user) || !$this->user->can('material_requisition.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any requisition !');
        }

        $requisition = MaterialRequisition::where('requisition_no', $requisition_no)->first();
        if (is_null($requisition)) {
            session()->flash('error', 'Requisition not found !!');
            return redirect()->route('admin.material-requisitions.index');
        }

        // Check if requisition status is 6 (closed, cannot edit)
        if ($requisition->status == 6) {
            session()->flash('error', 'Cannot edit a requisition with status 6 (Closed) !!');
            return redirect()->route('admin.material-requisitions.index');
        }

        $requisitionDetails = MaterialRequisitionDetail::where('requisition_no', $requisition_no)->get();
        $materials = Material::orderBy('name', 'asc')->get();
        
        // Get all stores for type_store selection
        $origin_stores = MaterialBigStore::all();
        $destination_stores = MaterialSmallStore::all();
        $origin_extra_stores = MaterialExtraBigStore::all();
        $destination_bg_stores = MaterialBigStore::all();

        return view('backend.pages.material_requisition.edit', compact('requisition', 'requisitionDetails', 'materials', 'origin_stores', 'destination_stores', 'origin_extra_stores', 'destination_bg_stores'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $requisition_no
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $requisition_no)
    {
        if (is_null($this->user) || !$this->user->can('material_requisition.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any requisition !');
        }

        $requisition = MaterialRequisition::where('requisition_no', $requisition_no)->first();
        if (is_null($requisition)) {
            session()->flash('error', 'Requisition not found !!');
            return redirect()->route('admin.material-requisitions.index');
        }

        // Check if requisition status is 6 (closed, cannot edit)
        if ($requisition->status == 6) {
            session()->flash('error', 'Cannot edit a requisition with status 6 (Closed) !!');
            return redirect()->route('admin.material-requisitions.index');
        }

        $rules = array(
            'material_id.*'  => 'required',
            'date'  => 'required',
            'quantity_requisitioned.*'  => 'required',
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
            $type_store = $request->type_store ?? $requisition->type_store; // Allow type_store modification
            $quantity_requisitioned = $request->quantity_requisitioned;
            $description = $request->description;
            $updated_by = $this->user->name;

            // Delete old details
            MaterialRequisitionDetail::where('requisition_no', $requisition_no)->delete();

            // Insert updated details
            $insert_data = [];
            for ($count = 0; $count < count($material_id); $count++) {
                $price = Material::where('id', $material_id[$count])->value('purchase_price');
                $total_value_requisitioned = $quantity_requisitioned[$count] * $price;
                
                $data = array(
                    'material_id' => $material_id[$count],
                    'date' => $date,
                    'quantity_requisitioned' => $quantity_requisitioned[$count],
                    'price' => $price,
                    'description' => $description,
                    'total_value_requisitioned' => $total_value_requisitioned,
                    'created_by' => $requisition->created_by, // Keep original creator
                    'updated_by' => $updated_by,
                    'requisition_no' => $requisition_no,
                    'type_store' => $type_store,
                    'requisition_signature' => $requisition->requisition_signature, // Keep original signature
                    'status' => $requisition->status, // Keep original status
                    'created_at' => $requisition->created_at, // Keep original created_at
                    'updated_at' => \Carbon\Carbon::now()
                );
                $insert_data[] = $data;
            }

            MaterialRequisitionDetail::insert($insert_data);

            // Update main requisition
            $requisition->date = $date;
            $requisition->type_store = $type_store;
            $requisition->code_store = $request->code_store ?? $requisition->code_store;
            $requisition->description = $description;
            $requisition->updated_by = $updated_by;
            $requisition->save();

            DB::commit();
            session()->flash('success', 'Requisition has been updated !!');
            return redirect()->route('admin.material-requisitions.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function validateRequisition($requisition_no)
    {
       if (is_null($this->user) || !$this->user->can('material_requisition.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any requisition !');
        }

        try {DB::beginTransaction();

            MaterialRequisition::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            MaterialRequisitionDetail::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'requisition has been validated !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function reject($requisition_no)
    {
       if (is_null($this->user) || !$this->user->can('material_requisition.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any requisition !');
        } 

        try {DB::beginTransaction();

            MaterialRequisition::where('requisition_no', '=', $requisition_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);
            MaterialRequisitionDetail::where('requisition_no', '=', $requisition_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Requisition has been rejected !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function reset($requisition_no)
    {
       if (is_null($this->user) || !$this->user->can('material_requisition.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any requisition !');
        }

        try {DB::beginTransaction();

        MaterialRequisition::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
            MaterialRequisitionDetail::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'MaterialRequisition has been reseted !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function confirm($requisition_no)
    {
       if (is_null($this->user) || !$this->user->can('material_requisition.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any requisition !');
        }

        try {DB::beginTransaction();

        MaterialRequisition::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            MaterialRequisitionDetail::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Requisition has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function approuve($requisition_no)
    {
       if (is_null($this->user) || !$this->user->can('material_requisition.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any requisition !');
        }

        try {DB::beginTransaction();

        MaterialRequisition::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 4,'approuved_by' => $this->user->name]);
            MaterialRequisitionDetail::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 4,'approuved_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Requisition has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    public function demande_requisition($requisition_no)
    {
        if (is_null($this->user) || !$this->user->can('material_requisition.create')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }

        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $stat = MaterialRequisition::where('requisition_no', $requisition_no)->value('status');
        $description = MaterialRequisition::where('requisition_no', $requisition_no)->value('description');
        $date = MaterialRequisition::where('requisition_no', $requisition_no)->value('date');
        if($stat == 2 && $stat == 3 || $stat == 4 || $stat == 5){
           $requisition_no = MaterialRequisition::where('requisition_no', $requisition_no)->value('requisition_no');
           $requisition_signature = MaterialRequisition::where('requisition_no', $requisition_no)->value('requisition_signature');
           $totalValue = DB::table('material_requisition_details')
            ->where('requisition_no', '=', $requisition_no)
            ->sum('total_value_requisitioned');

           $datas = MaterialRequisitionDetail::where('requisition_no', $requisition_no)->get();
           $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.material_requisition', compact('datas','requisition_no','setting','description','date','requisition_signature','totalValue'), [], 'UTF-8');

           Storage::put('public/pdf/material_requisition/'.'BON_REQUISITION_'.$requisition_no.'.pdf', $pdf->output());

           // download pdf file
           return $pdf->download('BON_REQUISITION_'.$requisition_no.'.pdf'); 
           
        }else if ($stat == -1) {
            session()->flash('error', 'Requisition has been rejected !!');
            return back();
        }else{
            session()->flash('error', 'wait until requisition will be validated !!');
            return back();
        }
        
    }

    public function print($requisition_no) { return $this->demande_requisition($requisition_no); }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($requisition_no)
    {
        $requisition = MaterialRequisition::where('requisition_no', $requisition_no)->first();
        if (is_null($requisition)) {
            session()->flash('error', 'Requisition not found !!');
            return back();
        }

        // Check if requisition status is 6 (closed, cannot delete)
        if ($requisition->status == 6) {
            session()->flash('error', 'Cannot delete a requisition with status 6 (Closed) !!');
            return back();
        }

        // Deletion is not allowed for requisitions
        session()->flash('error', 'Deletion of requisitions is not allowed !!');
        return back();
    }
}
