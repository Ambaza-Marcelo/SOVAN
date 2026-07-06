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
use App\Models\RawMaterialRequisition;
use App\Models\RawMaterialRequisitionDetail;
use App\Models\RawMaterialTransfer;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanDetail;
use Validator;
use PDF;
use Mail;
use Carbon\Carbon;
use App\Mail\OrderMail;
use Excel;

class RawMaterialRequisitionController extends Controller
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
    public function index(\Illuminate\Http\Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_requisition.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any requisition !');
        }

        $query = RawMaterialRequisition::orderBy('id','desc');
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = \Carbon\Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = \Carbon\Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('date', [$dateDebut, $dateFin]);
        } else {
            $query->take(100);
        }
        $requisitions = $query->get();
        // Get transfer information for each requisition
        $transfers = \App\Models\RawMaterialTransfer::whereIn('requisition_no', $requisitions->pluck('requisition_no'))->get()->keyBy('requisition_no');
        return view('backend.pages.raw_material_requisition.index', compact('requisitions', 'transfers'));
    }

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_requisition.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export requisitions !');
        }
        $dateDebut = $request->input('date_debut') ?? $request->input('start_date');
        $dateFin = $request->input('date_fin') ?? $request->input('end_date');
        if (!$dateDebut || !$dateFin) {
            return redirect()->route('admin.raw-material-requisitions.index')
                ->with('error', __('messages.export_select_date_range'));
        }
        $filename = 'requisitions_matieres_premieres_' . Carbon::parse($dateDebut)->format('Y-m-d') . '_' . Carbon::parse($dateFin)->format('Y-m-d') . '.xlsx';
        return Excel::download(new \App\Exports\RawMaterialRequisitionExport(), $filename);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('raw_material_small_inventory.view')) {
            abort(403, 'Sorry !! You are Unauthorized to enter in this stock !,more information contact Marcelo');
        }

        $raw_materials  = RawMaterial::orderBy('name','asc')->get();
        return view('backend.pages.raw_material_requisition.create', compact('raw_materials'));
    }

    public function createFromBig()
    {
        if (is_null($this->user) || !$this->user->can('raw_material_big_inventory.view')) {
            abort(403, 'Sorry !! You are Unauthorized to enter in this stock !,more information contact Marcelo');
        }

        $raw_materials  = RawMaterial::orderBy('name','asc')->get();
        return view('backend.pages.raw_material_requisition.create_from_big', compact('raw_materials'));
    }

    /**
     * Create requisition from an approved production plan (évite doublon avec flag1).
     */
    public function createFromPlan($plan_number)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_requisition.create')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à créer une requête.');
        }

        $plan = ProductionPlan::where('prodution_plan_number', $plan_number)->first();
        if (!$plan) {
            session()->flash('error', 'Plan de production introuvable !!');
            return redirect()->route('admin.production-plan.index');
        }
        if ($plan->status != '4' && $plan->status != 4) {
            session()->flash('error', 'Le plan doit être approuvé pour créer une requête.');
            return redirect()->route('admin.production-plan.show', $plan_number);
        }
        if ($plan->flag1 == '1') {
            session()->flash('error', 'Une requête a déjà été créée à partir de ce plan (flag1).');
            return redirect()->route('admin.production-plan.show', $plan_number);
        }

        $planDetails = ProductionPlanDetail::with('rawMaterial.rawMaterialMeasurement')
            ->where('production_number', $plan_number)
            ->whereNotNull('r_material_id')
            ->get();

        if ($planDetails->isEmpty()) {
            session()->flash('error', 'Aucune matière première dans ce plan.');
            return redirect()->route('admin.production-plan.show', $plan_number);
        }

        $raw_materials = RawMaterial::orderBy('name', 'asc')->get();
        return view('backend.pages.raw_material_requisition.create_from_plan', compact('plan', 'planDetails', 'raw_materials'));
    }

    public function choose()
    {
        if (is_null($this->user) || !$this->user->can('raw_material_requisition.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any requisition !');
        }

        return view('backend.pages.raw_material_requisition.choose');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_requisition.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any requisition !');
        }

        $rules = array(
                'r_material_id.*'  => 'required',
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

            $r_material_id = $request->r_material_id ?? $request->food_id;
            $date = $request->date;
            $quantity_requisitioned = $request->quantity_requisitioned;
            $type_store = $request->type_store;
            $description =$request->description; 
            $latest = RawMaterialRequisition::latest()->first();
            if ($latest) {
               $requisition_no = 'BR' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $requisition_no = 'BR' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $requisition_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$requisition_no;
            $created_by = $this->user->name;

            //create requisition
            $requisition = new RawMaterialRequisition();
            $requisition->date = $date;
            $requisition->requisition_signature = $requisition_signature;
            $requisition->requisition_no = $requisition_no;
            $requisition->type_store = $type_store;
            $requisition->created_by = $created_by;
            $requisition->description = $description;
            if (!empty($request->production_plan_number)) {
                $requisition->production_plan_number = $request->production_plan_number;
            }
            $requisition->created_at = \Carbon\Carbon::now();
            $requisition->save();

            if (!empty($request->production_plan_number)) {
                ProductionPlan::where('prodution_plan_number', $request->production_plan_number)->update(['flag1' => '1']);
            }
            //insert details of requisition No.
            for( $count = 0; $count < count($r_material_id); $count++ ){

                $price = RawMaterial::where('id', $r_material_id[$count])->value('purchase_price');
                $total_value_requisitioned = $quantity_requisitioned[$count] * $price;
                $data = array(
                    'r_material_id' => $r_material_id[$count],
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
       
        RawMaterialRequisitionDetail::insert($insert_data);

        DB::commit();
            session()->flash('success', 'Requisition has been created !!');
            return redirect()->route('admin.raw-material-requisitions.index');
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
        $requisition = RawMaterialRequisition::where('requisition_no', $requisition_no)->first();
        $requisitionDetails = RawMaterialRequisitionDetail::with(['rawMaterial.rawMaterialMeasurement'])
            ->where('requisition_no', $requisition_no)->get();
        
        // Calculate totals
        $totalQuantityRequisitioned = $requisitionDetails->sum('quantity_requisitioned');
        $totalValueRequisitioned = $requisitionDetails->sum('total_value_requisitioned');
        
        // Get transfer information for this requisition
        $transfer = \App\Models\RawMaterialTransfer::where('requisition_no', $requisition_no)->first();
        
        return view('backend.pages.raw_material_requisition.show', compact('requisition', 'requisitionDetails', 'totalQuantityRequisitioned', 'totalValueRequisitioned', 'transfer'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $requisition_no
     * @return \Illuminate\Http\Response
     */
    public function edit($requisition_no)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_requisition.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any requisition !');
        }

        $requisition = RawMaterialRequisition::where('requisition_no', $requisition_no)->first();
        if (is_null($requisition)) {
            session()->flash('error', 'Requisition not found !!');
            return redirect()->route('admin.raw-material-requisitions.index');
        }

        $requisitionDetails = RawMaterialRequisitionDetail::where('requisition_no', $requisition_no)->get();
        $raw_materials = RawMaterial::orderBy('name', 'asc')->get();

        return view('backend.pages.raw_material_requisition.edit', compact('requisition', 'requisitionDetails', 'raw_materials'));
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
        if (is_null($this->user) || !$this->user->can('raw_material_requisition.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any requisition !');
        }

        $requisition = RawMaterialRequisition::where('requisition_no', $requisition_no)->first();
        if (is_null($requisition)) {
            session()->flash('error', 'Requisition not found !!');
            return redirect()->route('admin.raw-material-requisitions.index');
        }

        $rules = array(
            'r_material_id.*'  => 'required',
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

            $r_material_id = $request->r_material_id ?? $request->food_id;
            $date = Carbon::parse($request->date);
            $type_store = $requisition->type_store; // Keep original type_store
            $quantity_requisitioned = $request->quantity_requisitioned;
            $description = $request->description;
            $updated_by = $this->user->name;

            // Delete old details
            RawMaterialRequisitionDetail::where('requisition_no', $requisition_no)->delete();

            // Insert updated details
            $insert_data = [];
            for ($count = 0; $count < count($r_material_id); $count++) {
                $price = RawMaterial::where('id', $r_material_id[$count])->value('purchase_price');
                $total_value_requisitioned = $quantity_requisitioned[$count] * $price;
                
                $data = array(
                    'r_material_id' => $r_material_id[$count],
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

            RawMaterialRequisitionDetail::insert($insert_data);

            // Update main requisition
            $requisition->date = $date;
            $requisition->description = $description;
            $requisition->updated_by = $updated_by;
            $requisition->save();

            DB::commit();
            session()->flash('success', 'Requisition has been updated !!');
            return redirect()->route('admin.raw-material-requisitions.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function validateRequisition($requisition_no)
    {
       if (is_null($this->user) || !$this->user->can('raw_material_requisition.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any requisition !');
        }

        try {DB::beginTransaction();

            RawMaterialRequisition::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            RawMaterialRequisitionDetail::where('requisition_no', '=', $requisition_no)
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
       if (is_null($this->user) || !$this->user->can('raw_material_requisition.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any requisition !');
        }

        try {DB::beginTransaction();

        RawMaterialRequisition::where('requisition_no', '=', $requisition_no)
                ->update(['status' => -1,'rejected_by' => $this->user->name]);
            RawMaterialRequisitionDetail::where('requisition_no', '=', $requisition_no)
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
       if (is_null($this->user) || !$this->user->can('raw_material_requisition.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any requisition !');
        }

        try {DB::beginTransaction();

        RawMaterialRequisition::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
            RawMaterialRequisitionDetail::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);

        DB::commit();
            session()->flash('success', 'Raw Material Requisition has been reseted !!');
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
       if (is_null($this->user) || !$this->user->can('raw_material_requisition.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any requisition !');
        }

        try {DB::beginTransaction();

        RawMaterialRequisition::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            RawMaterialRequisitionDetail::where('requisition_no', '=', $requisition_no)
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
       if (is_null($this->user) || !$this->user->can('raw_material_requisition.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any requisition !');
        }

        try {DB::beginTransaction();

        RawMaterialRequisition::where('requisition_no', '=', $requisition_no)
                ->update(['status' => 4,'approuved_by' => $this->user->name]);
            RawMaterialRequisitionDetail::where('requisition_no', '=', $requisition_no)
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
        if (is_null($this->user) || !$this->user->can('raw_material_requisition.view')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }

        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        $stat = RawMaterialRequisition::where('requisition_no', $requisition_no)->value('status');
        $description = RawMaterialRequisition::where('requisition_no', $requisition_no)->value('description');
        $date = RawMaterialRequisition::where('requisition_no', $requisition_no)->value('date');
        if($stat == 2 && $stat == 3 || $stat == 4 || $stat == 5){
           $requisition_no = RawMaterialRequisition::where('requisition_no', $requisition_no)->value('requisition_no');
           $requisition_signature = RawMaterialRequisition::where('requisition_no', $requisition_no)->value('requisition_signature');
           $totalValue = DB::table('raw_material_requisition_details')
            ->where('requisition_no', '=', $requisition_no)
            ->sum('total_value_requisitioned');

           $datas = RawMaterialRequisitionDetail::with(['rawMaterial.rawMaterialMeasurement'])->where('requisition_no', $requisition_no)->get();
           $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.raw_material_requisition', compact('datas','requisition_no','setting','description','date','requisition_signature','totalValue'), [], 'UTF-8');

           Storage::put('public/pdf/raw_material_requisition/'.'BON_REQUISITION_'.$requisition_no.'.pdf', $pdf->output());

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
        if (is_null($this->user) || !$this->user->can('raw_material_requisition.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any requisition !');
        }

        $requisition = RawMaterialRequisition::where('requisition_no', $requisition_no)->first();
        if (is_null($requisition)) {
            session()->flash('error', 'Requisition not found !!');
            return back();
        }

        // Check if requisition status is 6 (cannot delete when fully transferred)
        if ($requisition->status == 6) {
            session()->flash('error', 'Cannot delete a requisition with status 6 (Transferred) !!');
            return back();
        }

        try {
            DB::beginTransaction();

            $requisition->delete();
            RawMaterialRequisitionDetail::where('requisition_no', $requisition_no)->delete();

            DB::commit();
            session()->flash('success', 'Requisition has been deleted !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
