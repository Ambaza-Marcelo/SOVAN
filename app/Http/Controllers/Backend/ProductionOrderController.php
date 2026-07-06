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
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use PDF;
use Excel;
use Mail;
use Validator;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\RawMaterial;
use App\Models\Item;
use App\Models\ItemDetail;
use App\Models\FinishedGoodsStore;
use App\Models\FinishedGoodsStoreDetail;
use App\Models\FinishedGoodsSmStore;
use App\Models\FinishedGoodsSmStoreDetail;
use App\Models\RawMaterialSmallStore;
use App\Models\RawMaterialSmallStoreDetail;
use App\Models\RawMaterialSmallReport;
use App\Models\FinishedGoodReport;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountSetting;

class ProductionOrderController extends Controller
{
    use HandlesStockDocumentApproval;
    use UpdatesLedgerTotals;

    //
    public $user;

    private const FINISHED_GOODS_STORE_TYPE_MAIN = 'MAIN';
    private const FINISHED_GOODS_STORE_TYPE_SM = 'SM';

    /**
     * Ajax search: finished goods items within selected store (MAIN or SM).
     */
    public function searchItems(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('production_order.create')) {
            abort(403, 'Sorry !! You are Unauthorized to search items !');
        }

        $validated = Validator::make($request->all(), [
            'q' => 'nullable|string|max:100',
            'store_type' => 'required|in:MAIN,SM',
            'store_id' => 'required|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:50',
        ])->validate();

        $q = trim((string)($validated['q'] ?? ''));
        $storeType = (string)$validated['store_type'];
        $storeId = (int)$validated['store_id'];
        $limit = (int)($validated['limit'] ?? 20);

        $storeCode = null;
        if ($storeType === self::FINISHED_GOODS_STORE_TYPE_SM) {
            $store = FinishedGoodsSmStore::select('id', 'code')->find($storeId);
            $storeCode = $store ? $store->code : null;
        } else {
            $store = FinishedGoodsStore::select('id', 'code')->find($storeId);
            $storeCode = $store ? $store->code : null;
        }

        if (!$storeCode) {
            return response()->json(['results' => []]);
        }

        $detailsQuery = $storeType === self::FINISHED_GOODS_STORE_TYPE_SM
            ? FinishedGoodsSmStoreDetail::query()->where('code_store', $storeCode)
            : FinishedGoodsStoreDetail::query()->where('code_store', $storeCode);

        if ($q !== '') {
            $detailsQuery->where(function ($qq) use ($q) {
                $qq->where('name', 'like', '%' . $q . '%')
                    ->orWhere('code', 'like', '%' . $q . '%');
            });
        }

        $rows = $detailsQuery
            ->whereNotNull('item_id')
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->get(['item_id', 'name', 'code', 'cump']);

        $results = $rows->map(function ($r) {
            return [
                'id' => (int)$r->item_id,
                'label' => trim(($r->code ?? '') . ' - ' . ($r->name ?? '')),
                'code' => (string)($r->code ?? ''),
                'cump' => (float)($r->cump ?? 0),
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

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
        if (is_null($this->user) || !$this->user->can('production_order.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any production order !');
        }
        $query = ProductionOrder::orderBy('id','desc');
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('production_date', [$dateDebut, $dateFin]);
        } else {
            $query->take(100);
        }
        $production_orders = $query->get();
        return view('backend.pages.production_order.index', compact('production_orders'));
    }

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('production_order.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export production orders !');
        }
        $dateDebut = $request->input('date_debut') ?? $request->input('start_date');
        $dateFin = $request->input('date_fin') ?? $request->input('end_date');
        if (!$dateDebut || !$dateFin) {
            return redirect()->route('admin.production-order.index')
                ->with('error', __('messages.export_select_date_range'));
        }
        $filename = 'ordres_production_' . Carbon::parse($dateDebut)->format('Y-m-d') . '_' . Carbon::parse($dateFin)->format('Y-m-d') . '.xlsx';
        return Excel::download(new \App\Exports\ProductionOrderExport(), $filename);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('production_order.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any production order !');
        }

        $raw_materials = RawMaterial::where('status', 'active')->orderBy('name','asc')->get();
        $items = Item::where('status', 'active')->orderBy('name','asc')->get();
        // Load all ItemDetails with their raw material relationships
        // ItemDetail.code corresponds to Item.code
        $itemDetails = \App\Models\ItemDetail::with(['rawMaterial.rawMaterialMeasurement'])
            ->whereNotNull('r_material_id')
            ->where('r_material_id', '>', 0)
            ->get();
        $finished_goods_stores = FinishedGoodsStore::all();
        $finished_goods_sm_stores = FinishedGoodsSmStore::all();

        return view('backend.pages.production_order.create', compact(
            'raw_materials',
            'items',
            'itemDetails',
            'finished_goods_stores',
            'finished_goods_sm_stores'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('production_order.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any production order !');
        }

        $rules = array(
            'production_date' => 'required',
            'item_id.*' => 'required',
            'item_quantity.*' => 'required',
            'r_material_id.*' => 'required',
            'material_quantity.*' => 'required',
            'finished_goods_store_type' => 'required|in:MAIN,SM',
            'finished_g_store_id' => 'required_if:finished_goods_store_type,MAIN',
            'finished_g_sm_store_id' => 'required_if:finished_goods_store_type,SM',
            'description' => 'required'
        );

        $error = Validator::make($request->all(), $rules);

        if($error->fails()){
            return response()->json([
                'error' => $error->errors()->all(),
            ]);
        }

        try {
            DB::beginTransaction();

            $production_date = Carbon::parse($request->production_date);
            $start_date = $request->start_date ? Carbon::parse($request->start_date) : $production_date;
            $end_date = $request->end_date ? Carbon::parse($request->end_date) : $production_date;
            $planned_start_time = $request->planned_start_time ? Carbon::parse($request->planned_start_time) : null;
            $planned_finish_time = $request->planned_finish_time ? Carbon::parse($request->planned_finish_time) : null;
            
            $description = $request->description;
            $work_center = $request->work_center;
            $operation_sequence = $request->operation_sequence;
            $routing_notes = $request->routing_notes;
            $estimated_labor_cost = $request->estimated_labor_cost ?? 0;
            $estimated_overhead_cost = $request->estimated_overhead_cost ?? 0;
            $finishedGoodsStoreType = $request->finished_goods_store_type;
            $finishedGoodsStoreId = $request->finished_g_store_id;
            $finishedGoodsSmStoreId = $request->finished_g_sm_store_id;

            // Generate production number
            $latest = ProductionOrder::latest()->first();
            if ($latest) {
                $production_no = 'OF-' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT));
            } else {
                $production_no = 'OF-' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));
            }

            $tin_number = config('app.tin_number_company', '');
            if (empty($tin_number)) {
                $tin_number = 'TIN';
            }
            $production_signature = $tin_number . Carbon::parse(Carbon::now())->format('YmdHis') . "/" . $production_no;
            $created_by = $this->user->name;

            // Calculate total estimated material cost using actual costs from RawMaterialSmallStoreDetail
            $r_material_id = $request->r_material_id ?? [];
            $material_quantity = $request->material_quantity ?? [];
            $material_cost = $request->material_cost ?? [];
            $unit = $request->unit ?? [];
            $lot_number = $request->lot_number ?? [];
            $batch_number = $request->batch_number ?? [];
            
            // Validate arrays have same length
            if (count($r_material_id) != count($material_quantity) || count($r_material_id) != count($material_cost)) {
                throw new \Exception('Mismatch in raw materials data arrays. Please check your input.');
            }
            
            // Get RawMaterialSmallStore to access actual costs
            $rawMaterialSmallStore = RawMaterialSmallStore::first();
            
            $total_material_cost = 0;
            for ($count = 0; $count < count($r_material_id); $count++) {
                if (empty($r_material_id[$count])) {
                    continue; // Skip empty entries
                }
                
                $qty = isset($material_quantity[$count]) ? floatval($material_quantity[$count]) : 0;
                $cost = isset($material_cost[$count]) ? floatval($material_cost[$count]) : 0;
                
                // If cost is not provided or zero, get actual cost from RawMaterialSmallStoreDetail
                if ($cost == 0 && !empty($r_material_id[$count]) && $rawMaterialSmallStore && $rawMaterialSmallStore->code) {
                    $rawMaterialSmallStoreDetail = RawMaterialSmallStoreDetail::where('code', $rawMaterialSmallStore->code)
                        ->where('r_material_id', $r_material_id[$count])
                        ->first();
                    
                    if ($rawMaterialSmallStoreDetail && isset($rawMaterialSmallStoreDetail->cump) && $rawMaterialSmallStoreDetail->cump > 0) {
                        $cost = floatval($rawMaterialSmallStoreDetail->cump);
                    } else {
                        // Fallback to RawMaterial model cump
                        $raw_material = RawMaterial::find($r_material_id[$count]);
                        if ($raw_material && $raw_material->cump) {
                            $cost = floatval($raw_material->cump);
                        }
                    }
                }
                
                $total_material_cost += $qty * $cost;
            }

            $total_estimated_cost = $estimated_labor_cost + $total_material_cost + $estimated_overhead_cost;

            // Create production order
            $productionOrder = new ProductionOrder();
            $productionOrder->prodution_number = $production_no;
            $productionOrder->production_date = $production_date;
            $productionOrder->start_date = $start_date;
            $productionOrder->end_date = $end_date;
            $productionOrder->production_signature = $production_signature;
            $productionOrder->finished_goods_store_type = $finishedGoodsStoreType;
            $productionOrder->finished_g_store_id = $finishedGoodsStoreType === self::FINISHED_GOODS_STORE_TYPE_MAIN ? $finishedGoodsStoreId : null;
            $productionOrder->finished_g_sm_store_id = $finishedGoodsStoreType === self::FINISHED_GOODS_STORE_TYPE_SM ? $finishedGoodsSmStoreId : null;
            $productionOrder->status = '0';
            $productionOrder->description = $description;
            $productionOrder->work_center = $work_center;
            $productionOrder->operation_sequence = $operation_sequence;
            $productionOrder->routing_notes = $routing_notes;
            $productionOrder->estimated_labor_cost = $estimated_labor_cost;
            $productionOrder->estimated_material_cost = $total_material_cost;
            $productionOrder->estimated_overhead_cost = $estimated_overhead_cost;
            $productionOrder->total_estimated_cost = $total_estimated_cost;
            $productionOrder->planned_start_time = $planned_start_time;
            $productionOrder->planned_finish_time = $planned_finish_time;
            $productionOrder->created_by = $created_by;
            $productionOrder->save();

            // Create raw materials details
            // Get RawMaterialSmallStore to access actual costs
            $rawMaterialSmallStore = RawMaterialSmallStore::first();
            
            for ($count = 0; $count < count($r_material_id); $count++) {
                if (empty($r_material_id[$count])) {
                    continue; // Skip empty entries
                }
                
                $qty = isset($material_quantity[$count]) ? floatval($material_quantity[$count]) : 0;
                
                if ($qty <= 0) {
                    throw new \Exception('Material quantity must be greater than 0 for raw material ID: ' . $r_material_id[$count]);
                }
                
                // Auto-fill cost from RawMaterialSmallStoreDetail cump if not provided or empty
                $cost = isset($material_cost[$count]) && !empty($material_cost[$count]) 
                    ? floatval($material_cost[$count]) 
                    : 0;
                
                // If cost is not provided, get it from RawMaterialSmallStoreDetail (actual stock cost)
                if ($cost == 0 && !empty($r_material_id[$count]) && $rawMaterialSmallStore && $rawMaterialSmallStore->code) {
                    $rawMaterialSmallStoreDetail = RawMaterialSmallStoreDetail::where('code', $rawMaterialSmallStore->code)
                        ->where('r_material_id', $r_material_id[$count])
                        ->first();
                    
                    if ($rawMaterialSmallStoreDetail && isset($rawMaterialSmallStoreDetail->cump) && $rawMaterialSmallStoreDetail->cump > 0) {
                        $cost = floatval($rawMaterialSmallStoreDetail->cump);
                    } else {
                        // Fallback to RawMaterial model cump
                        $raw_material = RawMaterial::find($r_material_id[$count]);
                        if ($raw_material && $raw_material->cump) {
                            $cost = floatval($raw_material->cump);
                        }
                    }
                }
                
                if ($cost <= 0) {
                    $raw_material = RawMaterial::find($r_material_id[$count]);
                    $rawMaterialName = $raw_material ? $raw_material->name : 'Unknown';
                    throw new \Exception("Cost is required for material: {$rawMaterialName} (ID: {$r_material_id[$count]})");
                }
                
                $total_cost = $qty * $cost;
                $lot = isset($lot_number[$count]) ? $lot_number[$count] : null;
                $batch = isset($batch_number[$count]) ? $batch_number[$count] : null;

                $detail = new ProductionOrderDetail();
                $detail->production_number = $production_no;
                $detail->production_date = $production_date;
                $detail->start_date = $start_date;
                $detail->end_date = $end_date;
                $detail->production_signature = $production_signature;
                $detail->status = '0';
                $detail->description = $description;
                $detail->r_material_id = $r_material_id[$count];
                $detail->material_quantity = $qty;
                // Removed unit assignment - column doesn't exist in database
                $detail->material_cost = $cost;
                $detail->total_material_cost = $total_cost;
                $detail->lot_number = $lot;
                $detail->batch_number = $batch;
                $detail->created_by = $created_by;
                $detail->save();
            }

            // Create finished goods details
            // Use Total Estimated Cost as cost_price for finished goods (as per user requirement)
            $item_id = $request->item_id ?? [];
            $item_quantity = $request->item_quantity ?? [];
            $cost_price = $request->cost_price ?? [];
            
            // Validate arrays have same length
            if (count($item_id) != count($item_quantity)) {
                throw new \Exception('Mismatch in finished goods data arrays. Please check your input.');
            }
            
            $total_item_quantity = 0;
            foreach ($item_quantity as $qty) {
                $total_item_quantity += floatval($qty ?? 0);
            }
            
            if ($total_item_quantity <= 0) {
                throw new \Exception('Total finished goods quantity must be greater than 0.');
            }
            
            // Use Total Estimated Cost as cost_price (as per user requirement: cost_price = Total Estimated Cost)
            $production_cost_per_unit = $total_estimated_cost;

            for ($count = 0; $count < count($item_id); $count++) {
                if (empty($item_id[$count])) {
                    continue; // Skip empty entries
                }
                
                $qty = isset($item_quantity[$count]) ? floatval($item_quantity[$count]) : 0;
                
                if ($qty <= 0) {
                    throw new \Exception('Item quantity must be greater than 0 for item ID: ' . $item_id[$count]);
                }
                
                // Use Total Estimated Cost as cost_price (as per user requirement)
                $cost = $total_estimated_cost;
                
                $total_cost = $qty * $cost;

                $detail = new ProductionOrderDetail();
                $detail->production_number = $production_no;
                $detail->production_date = $production_date;
                $detail->start_date = $start_date;
                $detail->end_date = $end_date;
                $detail->production_signature = $production_signature;
                $detail->status = '0';
                $detail->description = $description;
                $detail->item_id = $item_id[$count];
                $detail->item_quantity = $qty;
                $detail->cost_price = $cost;
                $detail->total_item_cost = $total_cost;
                $detail->created_by = $created_by;
                $detail->save();
            }

            DB::commit();
            session()->flash('success', 'Production order has been created !!');
            return redirect()->route('admin.production-order.index');
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Production Order Creation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            session()->flash('error', 'Error creating production order: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $production_no
     * @return \Illuminate\Http\Response
     */
    public function show($production_no)
    {
        if (is_null($this->user) || !$this->user->can('production_order.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any production order !');
        }

        $productionOrder = ProductionOrder::where('prodution_number', $production_no)->first();
        
        if (is_null($productionOrder)) {
            session()->flash('error', 'Production order not found !!');
            return redirect()->route('admin.production-order.index');
        }

        $rawMaterials = ProductionOrderDetail::with('rawMaterial.rawMaterialMeasurement')
            ->where('production_number', $production_no)
            ->whereNotNull('r_material_id')
            ->get();

        $finishedGoods = ProductionOrderDetail::with('item')
            ->where('production_number', $production_no)
            ->whereNotNull('item_id')
            ->get();

        // Calculate totals
        $totalMaterialCost = $rawMaterials->sum('total_material_cost');
        $totalItemCost = $finishedGoods->sum('total_item_cost');
        $totalMaterialQuantity = $rawMaterials->sum('material_quantity');
        $totalItemQuantity = $finishedGoods->sum('item_quantity');

        return view('backend.pages.production_order.show', compact(
            'productionOrder',
            'rawMaterials',
            'finishedGoods',
            'totalMaterialCost',
            'totalItemCost',
            'totalMaterialQuantity',
            'totalItemQuantity'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $production_no
     * @return \Illuminate\Http\Response
     */
    public function edit($production_no)
    {
        if (is_null($this->user) || !$this->user->can('production_order.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any production order !');
        }

        $productionOrder = ProductionOrder::where('prodution_number', $production_no)->first();
        
        if (is_null($productionOrder)) {
            session()->flash('error', 'Production order not found !!');
            return redirect()->route('admin.production-order.index');
        }

        // Check if order can be edited (not approved)
        if ($productionOrder->status == '4' || $productionOrder->status == 4) {
            session()->flash('error', 'Cannot edit approved production order !!');
            return redirect()->route('admin.production-order.index');
        }

        $raw_materials = RawMaterial::where('status', 'active')->orderBy('name','asc')->get();
        $items = Item::where('status', 'active')->orderBy('name','asc')->get();
        // Load all ItemDetails with their raw material relationships
        // ItemDetail.code corresponds to Item.code - this ensures all raw materials are loaded
        $itemDetails = \App\Models\ItemDetail::with(['rawMaterial.rawMaterialMeasurement'])
            ->whereNotNull('r_material_id')
            ->where('r_material_id', '>', 0)
            ->get();
        $finished_goods_stores = FinishedGoodsStore::all();
        $finished_goods_sm_stores = FinishedGoodsSmStore::all();

        $rawMaterials = ProductionOrderDetail::with('rawMaterial.rawMaterialMeasurement')
            ->where('production_number', $production_no)
            ->whereNotNull('r_material_id')
            ->get();

        $finishedGoods = ProductionOrderDetail::with('item')
            ->where('production_number', $production_no)
            ->whereNotNull('item_id')
            ->get();

        return view('backend.pages.production_order.edit', compact(
            'productionOrder',
            'raw_materials',
            'items',
            'itemDetails',
            'finished_goods_stores',
            'finished_goods_sm_stores',
            'rawMaterials',
            'finishedGoods'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $production_no
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $production_no)
    {
        if (is_null($this->user) || !$this->user->can('production_order.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any production order !');
        }

        $productionOrder = ProductionOrder::where('prodution_number', $production_no)->first();
        
        if (is_null($productionOrder)) {
            session()->flash('error', 'Production order not found !!');
            return redirect()->route('admin.production-order.index');
        }

        // Check if order can be edited (not approved)
        if ($productionOrder->status == '4' || $productionOrder->status == 4) {
            session()->flash('error', 'Cannot edit approved production order !!');
            return redirect()->route('admin.production-order.index');
        }

        $rules = array(
            'production_date' => 'required',
            'item_id.*' => 'required',
            'item_quantity.*' => 'required',
            'r_material_id.*' => 'required',
            'material_quantity.*' => 'required',
            'finished_goods_store_type' => 'required|in:MAIN,SM',
            'finished_g_store_id' => 'required_if:finished_goods_store_type,MAIN',
            'finished_g_sm_store_id' => 'required_if:finished_goods_store_type,SM',
            'description' => 'required'
        );

        $error = Validator::make($request->all(), $rules);

        if($error->fails()){
            return response()->json([
                'error' => $error->errors()->all(),
            ]);
        }

        try {
            DB::beginTransaction();

            $production_date = Carbon::parse($request->production_date);
            $start_date = $request->start_date ? Carbon::parse($request->start_date) : $production_date;
            $end_date = $request->end_date ? Carbon::parse($request->end_date) : $production_date;
            $planned_start_time = $request->planned_start_time ? Carbon::parse($request->planned_start_time) : null;
            $planned_finish_time = $request->planned_finish_time ? Carbon::parse($request->planned_finish_time) : null;
            
            $description = $request->description;
            $work_center = $request->work_center;
            $operation_sequence = $request->operation_sequence;
            $routing_notes = $request->routing_notes;
            $estimated_labor_cost = $request->estimated_labor_cost ?? 0;
            $estimated_overhead_cost = $request->estimated_overhead_cost ?? 0;
            $finishedGoodsStoreType = $request->finished_goods_store_type;
            $finishedGoodsStoreId = $request->finished_g_store_id;
            $finishedGoodsSmStoreId = $request->finished_g_sm_store_id;

            // Calculate total estimated material cost using actual costs from RawMaterialSmallStoreDetail
            $r_material_id = $request->r_material_id ?? [];
            $material_quantity = $request->material_quantity ?? [];
            $material_cost = $request->material_cost ?? [];
            $unit = $request->unit ?? [];
            $lot_number = $request->lot_number ?? [];
            $batch_number = $request->batch_number ?? [];
            
            // Validate arrays have same length
            if (count($r_material_id) != count($material_quantity) || count($r_material_id) != count($material_cost)) {
                throw new \Exception('Mismatch in raw materials data arrays. Please check your input.');
            }
            
            // Get RawMaterialSmallStore to access actual costs
            $rawMaterialSmallStore = RawMaterialSmallStore::first();
            
            $total_material_cost = 0;
            for ($count = 0; $count < count($r_material_id); $count++) {
                if (empty($r_material_id[$count])) {
                    continue; // Skip empty entries
                }
                
                $qty = isset($material_quantity[$count]) ? floatval($material_quantity[$count]) : 0;
                $cost = isset($material_cost[$count]) ? floatval($material_cost[$count]) : 0;
                
                // If cost is not provided or zero, get actual cost from RawMaterialSmallStoreDetail
                if ($cost == 0 && !empty($r_material_id[$count]) && $rawMaterialSmallStore && $rawMaterialSmallStore->code) {
                    $rawMaterialSmallStoreDetail = RawMaterialSmallStoreDetail::where('code', $rawMaterialSmallStore->code)
                        ->where('r_material_id', $r_material_id[$count])
                        ->first();
                    
                    if ($rawMaterialSmallStoreDetail && isset($rawMaterialSmallStoreDetail->cump) && $rawMaterialSmallStoreDetail->cump > 0) {
                        $cost = floatval($rawMaterialSmallStoreDetail->cump);
                    } else {
                        // Fallback to RawMaterial model cump
                        $raw_material = RawMaterial::find($r_material_id[$count]);
                        if ($raw_material && $raw_material->cump) {
                            $cost = floatval($raw_material->cump);
                        }
                    }
                }
                
                $total_material_cost += $qty * $cost;
            }

            $total_estimated_cost = $estimated_labor_cost + $total_material_cost + $estimated_overhead_cost;

            // Update production order
            $productionOrder->production_date = $production_date;
            $productionOrder->start_date = $start_date;
            $productionOrder->end_date = $end_date;
            $productionOrder->description = $description;
            $productionOrder->work_center = $work_center;
            $productionOrder->operation_sequence = $operation_sequence;
            $productionOrder->routing_notes = $routing_notes;
            $productionOrder->estimated_labor_cost = $estimated_labor_cost;
            $productionOrder->estimated_material_cost = $total_material_cost;
            $productionOrder->estimated_overhead_cost = $estimated_overhead_cost;
            $productionOrder->total_estimated_cost = $total_estimated_cost;
            $productionOrder->planned_start_time = $planned_start_time;
            $productionOrder->planned_finish_time = $planned_finish_time;
            $productionOrder->finished_goods_store_type = $finishedGoodsStoreType;
            $productionOrder->finished_g_store_id = $finishedGoodsStoreType === self::FINISHED_GOODS_STORE_TYPE_MAIN ? $finishedGoodsStoreId : null;
            $productionOrder->finished_g_sm_store_id = $finishedGoodsStoreType === self::FINISHED_GOODS_STORE_TYPE_SM ? $finishedGoodsSmStoreId : null;
            $productionOrder->updated_by = $this->user->name;
            $productionOrder->save();

            // Delete existing details
            ProductionOrderDetail::where('production_number', $production_no)->delete();

            // Create raw materials details
            // Get RawMaterialSmallStore to access actual costs
            $rawMaterialSmallStore = RawMaterialSmallStore::first();
            
            for ($count = 0; $count < count($r_material_id); $count++) {
                if (empty($r_material_id[$count])) {
                    continue; // Skip empty entries
                }
                
                $qty = isset($material_quantity[$count]) ? floatval($material_quantity[$count]) : 0;
                
                if ($qty <= 0) {
                    throw new \Exception('Material quantity must be greater than 0 for raw material ID: ' . $r_material_id[$count]);
                }
                
                // Auto-fill cost from RawMaterialSmallStoreDetail cump if not provided or empty
                $cost = isset($material_cost[$count]) && !empty($material_cost[$count]) 
                    ? floatval($material_cost[$count]) 
                    : 0;
                
                // If cost is not provided, get it from RawMaterialSmallStoreDetail (actual stock cost)
                if ($cost == 0 && !empty($r_material_id[$count]) && $rawMaterialSmallStore && $rawMaterialSmallStore->code) {
                    $rawMaterialSmallStoreDetail = RawMaterialSmallStoreDetail::where('code', $rawMaterialSmallStore->code)
                        ->where('r_material_id', $r_material_id[$count])
                        ->first();
                    
                    if ($rawMaterialSmallStoreDetail && isset($rawMaterialSmallStoreDetail->cump) && $rawMaterialSmallStoreDetail->cump > 0) {
                        $cost = floatval($rawMaterialSmallStoreDetail->cump);
                    } else {
                        // Fallback to RawMaterial model cump
                        $raw_material = RawMaterial::find($r_material_id[$count]);
                        if ($raw_material && $raw_material->cump) {
                            $cost = floatval($raw_material->cump);
                        }
                    }
                }
                
                if ($cost <= 0) {
                    $raw_material = RawMaterial::find($r_material_id[$count]);
                    $rawMaterialName = $raw_material ? $raw_material->name : 'Unknown';
                    throw new \Exception("Cost is required for material: {$rawMaterialName} (ID: {$r_material_id[$count]})");
                }
                
                $total_cost = $qty * $cost;
                $lot = isset($lot_number[$count]) ? $lot_number[$count] : null;
                $batch = isset($batch_number[$count]) ? $batch_number[$count] : null;

                $detail = new ProductionOrderDetail();
                $detail->production_number = $production_no;
                $detail->production_date = $production_date;
                $detail->start_date = $start_date;
                $detail->end_date = $end_date;
                $detail->production_signature = $productionOrder->production_signature;
                $detail->status = $productionOrder->status;
                $detail->description = $description;
                $detail->r_material_id = $r_material_id[$count];
                $detail->material_quantity = $qty;
                // Removed unit assignment - column doesn't exist in database
                $detail->material_cost = $cost;
                $detail->total_material_cost = $total_cost;
                $detail->lot_number = $lot;
                $detail->batch_number = $batch;
                $detail->created_by = $productionOrder->created_by;
                $detail->updated_by = $this->user->name;
                $detail->save();
            }

            // Create finished goods details
            // Use Total Estimated Cost as cost_price for finished goods (as per user requirement)
            $item_id = $request->item_id ?? [];
            $item_quantity = $request->item_quantity ?? [];
            $cost_price = $request->cost_price ?? [];
            
            // Validate arrays have same length
            if (count($item_id) != count($item_quantity)) {
                throw new \Exception('Mismatch in finished goods data arrays. Please check your input.');
            }
            
            $total_item_quantity = 0;
            foreach ($item_quantity as $qty) {
                $total_item_quantity += floatval($qty ?? 0);
            }
            
            if ($total_item_quantity <= 0) {
                throw new \Exception('Total finished goods quantity must be greater than 0.');
            }
            
            // Use Total Estimated Cost as cost_price (as per user requirement: cost_price = Total Estimated Cost)
            $production_cost_per_unit = $total_estimated_cost;

            for ($count = 0; $count < count($item_id); $count++) {
                if (empty($item_id[$count])) {
                    continue; // Skip empty entries
                }
                
                $qty = isset($item_quantity[$count]) ? floatval($item_quantity[$count]) : 0;
                
                if ($qty <= 0) {
                    throw new \Exception('Item quantity must be greater than 0 for item ID: ' . $item_id[$count]);
                }
                
                // Use Total Estimated Cost as cost_price (as per user requirement)
                $cost = $total_estimated_cost;
                
                $total_cost = $qty * $cost;

                $detail = new ProductionOrderDetail();
                $detail->production_number = $production_no;
                $detail->production_date = $production_date;
                $detail->start_date = $start_date;
                $detail->end_date = $end_date;
                $detail->production_signature = $productionOrder->production_signature;
                $detail->status = $productionOrder->status;
                $detail->description = $description;
                $detail->item_id = $item_id[$count];
                $detail->item_quantity = $qty;
                $detail->cost_price = $cost;
                $detail->total_item_cost = $total_cost;
                $detail->created_by = $productionOrder->created_by;
                $detail->updated_by = $this->user->name;
                $detail->save();
            }

            DB::commit();
            session()->flash('success', 'Production order has been updated !!');
            return redirect()->route('admin.production-order.index');
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Production Order Update Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'production_no' => $production_no,
                'request' => $request->all()
            ]);
            session()->flash('error', 'Error updating production order: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $production_no
     * @return \Illuminate\Http\Response
     */
    public function destroy($production_no)
    {
        if (is_null($this->user) || !$this->user->can('production_order.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any production order !');
        }

        $productionOrder = ProductionOrder::where('prodution_number', $production_no)->first();
        
        if (is_null($productionOrder)) {
            session()->flash('error', 'Production order not found !!');
            return redirect()->route('admin.production-order.index');
        }

        // Check if order can be deleted (not approved)
        if ($productionOrder->status == '4' || $productionOrder->status == 4) {
            session()->flash('error', 'Cannot delete approved production order !!');
            return redirect()->route('admin.production-order.index');
        }

        try {
            DB::beginTransaction();

            // Delete details first
            ProductionOrderDetail::where('production_number', $production_no)->delete();
            
            // Delete production order
            $productionOrder->delete();

            DB::commit();
            session()->flash('success', 'Production order has been deleted !!');
            return redirect()->route('admin.production-order.index');
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Production Order Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            session()->flash('error', 'Error: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Validate production order
     *
     * @param  string  $production_no
     * @return \Illuminate\Http\Response
     */
    public function validateOrder($production_no)
    {
        if (is_null($this->user) || !$this->user->can('production_order.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any production order !');
        }

        $productionOrder = ProductionOrder::where('prodution_number', $production_no)->first();
        if (is_null($productionOrder)) {
            session()->flash('error', 'Production order not found !!');
            return back();
        }
        if ($response = $this->guardProductionOrderValidate($productionOrder)) {
            return $response;
        }

        try {
            DB::beginTransaction();

            ProductionOrder::where('prodution_number', '=', $production_no)
                ->update(['status' => '1', 'validated_by' => $this->user->name]);
            
            ProductionOrderDetail::where('production_number', '=', $production_no)
                ->update(['status' => '1', 'validated_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Production order has been validated !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Production Order Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            session()->flash('error', 'Error: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Confirm production order
     *
     * @param  string  $production_no
     * @return \Illuminate\Http\Response
     */
    public function confirm($production_no)
    {
        if (is_null($this->user) || !$this->user->can('production_order.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any production order !');
        }

        $productionOrder = ProductionOrder::where('prodution_number', $production_no)->first();
        if (is_null($productionOrder)) {
            session()->flash('error', 'Production order not found !!');
            return back();
        }
        if ($response = $this->guardProductionOrderConfirm($productionOrder)) {
            return $response;
        }

        try {
            DB::beginTransaction();

            ProductionOrder::where('prodution_number', '=', $production_no)
                ->update(['status' => '2', 'confirmed_by' => $this->user->name]);
            
            ProductionOrderDetail::where('production_number', '=', $production_no)
                ->update(['status' => '2', 'confirmed_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Production order has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Production Order Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            session()->flash('error', 'Error: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Approve production order and update stocks
     *
     * @param  string  $production_no
     * @return \Illuminate\Http\Response
     */
    public function approve($production_no)
    {
        if (is_null($this->user) || !$this->user->can('production_order.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to approve any production order !');
        }

        try {
            $dbStarted = false;
            $productionOrder = ProductionOrder::where('prodution_number', $production_no)->first();
            
            if (is_null($productionOrder)) {
                session()->flash('error', 'Production order not found !!');
                return back();
            }
            if ($response = $this->guardProductionOrderApprove($productionOrder)) {
                return $response;
            }

            // Get selected finished goods store
            $storeType = $productionOrder->finished_goods_store_type ?: self::FINISHED_GOODS_STORE_TYPE_MAIN;
            $finishedGoodsStore = $storeType === self::FINISHED_GOODS_STORE_TYPE_SM
                ? FinishedGoodsSmStore::find($productionOrder->finished_g_sm_store_id)
                : FinishedGoodsStore::find($productionOrder->finished_g_store_id);
            if (is_null($finishedGoodsStore)) {
                session()->flash('error', 'Finished goods store not found. Please select/create it first !!');
                return back();
            }

            // Get all details
            $rawMaterials = ProductionOrderDetail::where('production_number', $production_no)
                ->whereNotNull('r_material_id')
                ->get();

            $finishedGoods = ProductionOrderDetail::where('production_number', $production_no)
                ->whereNotNull('item_id')
                ->get();

            // Get RawMaterialSmallStore to get the code
            $rawMaterialSmallStore = RawMaterialSmallStore::first();
            if (is_null($rawMaterialSmallStore)) {
                session()->flash('error', 'Raw material small store not found. Please create it first !!');
                return back();
            }

            // Get the store code
            $storeCode = $rawMaterialSmallStore->code;
            if (empty($storeCode)) {
                session()->flash('error', 'Raw material small store code not found. Please update the store first !!');
                return back();
            }

            // IMPORTANT:
            // Before sending to OBR, we only validate availability (no DB writes).
            // We will decrease stock & create reports ONLY after OBR success, inside a DB transaction.
            foreach ($rawMaterials as $material) {
                $rawMaterialSmallStoreDetail = RawMaterialSmallStoreDetail::where('code', $storeCode)
                    ->where('r_material_id', $material->r_material_id)
                    ->first();
                if (!$rawMaterialSmallStoreDetail) {
                    $raw_material = RawMaterial::find($material->r_material_id);
                    $rawMaterialName = $raw_material ? $raw_material->name : 'Unknown';
                    session()->flash('error', "Raw material '{$rawMaterialName}' not found in RawMaterialSmallStoreDetail. Please add it to the store first !!");
                    return back();
                }

                $currentQuantity = floatval($rawMaterialSmallStoreDetail->quantity ?? 0);
                $quantityToDeduct = floatval($material->material_quantity ?? 0);
                if ($currentQuantity < $quantityToDeduct) {
                    $raw_material = RawMaterial::find($material->r_material_id);
                    $rawMaterialName = $raw_material ? $raw_material->name : 'Unknown';
                    session()->flash('error', "Insufficient stock for {$rawMaterialName}. Available: {$currentQuantity}, Required: {$quantityToDeduct} !!");
                    return back();
                }
            }

            // Calculate actual production cost
            // Total cost = sum of all raw materials costs (using actual stock costs) + labor + overhead
            $actualTotalMaterialCost = 0;
            foreach ($rawMaterials as $material) {
                $rawMaterialSmallStoreDetail = RawMaterialSmallStoreDetail::where('code', $storeCode)
                    ->where('r_material_id', $material->r_material_id)
                    ->first();
                
                if ($rawMaterialSmallStoreDetail) {
                    // Use cump from RawMaterialSmallStoreDetail if available, otherwise use material_cost from ProductionOrderDetail
                    $actualCost = isset($rawMaterialSmallStoreDetail->cump) && $rawMaterialSmallStoreDetail->cump > 0 
                        ? floatval($rawMaterialSmallStoreDetail->cump) 
                        : floatval($material->material_cost ?? 0);
                } else {
                    $actualCost = floatval($material->material_cost ?? 0);
                }
                
                $actualTotalMaterialCost += floatval($material->material_quantity ?? 0) * $actualCost;
            }
            
            $actualLaborCost = floatval($productionOrder->estimated_labor_cost ?? 0);
            $actualOverheadCost = floatval($productionOrder->estimated_overhead_cost ?? 0);
            $actualTotalProductionCost = $actualTotalMaterialCost + $actualLaborCost + $actualOverheadCost;
            
            // Calculate total quantity of finished goods produced
            $totalFinishedGoodsQuantity = 0;
            foreach ($finishedGoods as $good) {
                $totalFinishedGoodsQuantity += floatval($good->item_quantity ?? 0);
            }
            
            // Calculate production cost per unit
            $productionCostPerUnit = 0;
            if ($totalFinishedGoodsQuantity > 0) {
                $productionCostPerUnit = $actualTotalProductionCost / $totalFinishedGoodsQuantity;
            }

            // Envoi OBR désactivé temporairement — à réactiver ultérieurement.
            /*
            // 1) Send finished goods stock movements to OBR first.
            // If transmission fails, do NOT touch stocks, reports or any other models.
            $movements = [];
            foreach ($finishedGoods as $good) {
                $item = Item::find($good->item_id);
                if (!$item) {
                    continue;
                }
                $quantityToAdd = floatval($good->item_quantity ?? 0);
                if ($quantityToAdd <= 0) {
                    continue;
                }
                $costPrice = $productionCostPerUnit > 0 ? $productionCostPerUnit : floatval($good->cost_price ?? 0);
                if ($costPrice == 0) {
                    $costPrice = floatval($item->cump ?? 0);
                }

                $movements[] = [
                    'item_code' => $item->code,
                    'item_designation' => $item->name,
                    'item_quantity' => $quantityToAdd,
                    'item_cost_price' => $costPrice,
                    'item_measurement_unit' => 'PCS',
                    'item_purchase_or_sale_price' => $costPrice,
                    'item_purchase_or_sale_currency' => 'BIF',
                    'item_movement_invoice_ref' => $productionOrder->prodution_number,
                    'item_movement_description' => 'Production order: ' . $productionOrder->prodution_number,
                    'item_movement_date' => $productionOrder->production_date ? Carbon::parse($productionOrder->production_date)->toDateTimeString() : Carbon::now()->toDateTimeString(),
                ];
            }

            $obrSend = $this->sendStockMovementsToObr($movements, 'EAU');
            if (!$obrSend['ok']) {
                session()->flash('error', $obrSend['message']);
                return back();
            }
            */

            // Mise à jour stock & rapports (sans transmission OBR pour l'instant).
            DB::beginTransaction();
            $dbStarted = true;

            // Decrease raw materials stock (RawMaterialSmallStoreDetail) + reports
            foreach ($rawMaterials as $material) {
                // Find RawMaterialSmallStoreDetail by store code and r_material_id
                $rawMaterialSmallStoreDetail = RawMaterialSmallStoreDetail::where('code', $storeCode)
                    ->where('r_material_id', $material->r_material_id)
                    ->first();

                if ($rawMaterialSmallStoreDetail) {
                    $currentQuantity = floatval($rawMaterialSmallStoreDetail->quantity ?? 0);
                    $quantityToDeduct = floatval($material->material_quantity ?? 0);
                    $currentCump = floatval($rawMaterialSmallStoreDetail->cump ?? 0);

                    // Safety check (should already be validated before OBR)
                    if ($currentQuantity < $quantityToDeduct) {
                        throw new \RuntimeException("Insufficient raw material stock during approval.");
                    }

                    // Stock initial (before deduction)
                    $stockInitial = $currentQuantity;
                    $valueStockInitial = $stockInitial * $currentCump;

                    // Stock final (after deduction)
                    $newQuantity = max(0, $currentQuantity - $quantityToDeduct);
                    $valueStockFinal = $newQuantity * $currentCump;

                    // Value of stockout
                    $valueStockout = $quantityToDeduct * $currentCump;

                    // Update quantity and total_cump_value
                    $rawMaterialSmallStoreDetail->quantity = $newQuantity;
                    if ($currentCump > 0) {
                        $rawMaterialSmallStoreDetail->total_cump_value = $newQuantity * $currentCump;
                    }
                    $rawMaterialSmallStoreDetail->updated_by = $this->user->name;
                    $rawMaterialSmallStoreDetail->save();

                    // Create RawMaterialSmallReport for raw material movement
                    $rawMaterialReport = new RawMaterialSmallReport();
                    $rawMaterialReport->r_material_id = $material->r_material_id;
                    $rawMaterialReport->code_store = $storeCode;
                    $rawMaterialReport->quantity_stock_initial = $stockInitial;
                    $rawMaterialReport->value_stock_initial = $valueStockInitial;
                    $rawMaterialReport->quantity_stockout = $quantityToDeduct;
                    $rawMaterialReport->value_stockout = $valueStockout;
                    $rawMaterialReport->quantity_stock_final = $newQuantity;
                    $rawMaterialReport->value_stock_final = $valueStockFinal;
                    $rawMaterialReport->cump = $currentCump;
                    $rawMaterialReport->date = Carbon::now();
                    $rawMaterialReport->type_transaction = 'PRODUCTION';
                    $rawMaterialReport->document_no = $productionOrder->prodution_number;
                    $rawMaterialReport->description = 'Production order: ' . $productionOrder->prodution_number;
                    $rawMaterialReport->created_by = $this->user->name;
                    $rawMaterialReport->save();
                } else {
                    throw new \RuntimeException("Raw material store detail missing during approval.");
                }
            }

            // Increase finished goods stock (FinishedGoodsStoreDetail) and create reports
            foreach ($finishedGoods as $good) {
                $item = Item::find($good->item_id);
                if (!$item) {
                    continue;
                }

                $finishedGoodsStoreDetail = $storeType === self::FINISHED_GOODS_STORE_TYPE_SM
                    ? FinishedGoodsSmStoreDetail::where('code_store', $finishedGoodsStore->code)->where('item_id', $good->item_id)->first()
                    : FinishedGoodsStoreDetail::where('code_store', $finishedGoodsStore->code)->where('item_id', $good->item_id)->first();

                $quantityToAdd = floatval($good->item_quantity ?? 0);
                
                // Use production cost per unit (actual cost) or cost_price from detail
                $costPrice = $productionCostPerUnit > 0 ? $productionCostPerUnit : floatval($good->cost_price ?? 0);
                if ($costPrice == 0) {
                    $costPrice = floatval($item->cump ?? 0);
                }

                // Stock initial (before adding)
                $stockInitial = $finishedGoodsStoreDetail ? floatval($finishedGoodsStoreDetail->quantity ?? 0) : 0;
                $cumpInitial = $finishedGoodsStoreDetail ? floatval($finishedGoodsStoreDetail->cump ?? 0) : 0;
                $valueStockInitial = $stockInitial * $cumpInitial;

                if ($finishedGoodsStoreDetail) {
                    // Update existing detail - calculate weighted average cump
                    $currentQuantity = floatval($finishedGoodsStoreDetail->quantity ?? 0);
                    $currentCump = floatval($finishedGoodsStoreDetail->cump ?? 0);
                    $currentTotalValue = $currentQuantity * $currentCump;
                    
                    // Calculate new weighted average cump
                    $newQuantity = $currentQuantity + $quantityToAdd;
                    $newItemValue = $quantityToAdd * $costPrice;
                    $newTotalValue = $currentTotalValue + $newItemValue;
                    $newCump = $newQuantity > 0 ? ($newTotalValue / $newQuantity) : $costPrice;
                    
                    $finishedGoodsStoreDetail->quantity = $newQuantity;
                    $finishedGoodsStoreDetail->cump = $newCump;
                    $finishedGoodsStoreDetail->updated_by = $this->user->name;
                    if ($storeType === self::FINISHED_GOODS_STORE_TYPE_SM) {
                        $finishedGoodsStoreDetail->total_cump_value = $newQuantity * $newCump;
                    }
                    $finishedGoodsStoreDetail->save();
                    
                    // Update Item cump with weighted average
                    $item->cump = $newCump;
                    $item->updated_by = $this->user->name;
                    $item->save();
                    
                    // Stock final (after adding)
                    $stockFinal = $newQuantity;
                    $valueStockFinal = $newQuantity * $newCump;
                    $valueStockin = $quantityToAdd * $costPrice;
                } else {
                    // Create new detail if doesn't exist
                    $newDetail = $storeType === self::FINISHED_GOODS_STORE_TYPE_SM ? new FinishedGoodsSmStoreDetail() : new FinishedGoodsStoreDetail();
                    $newDetail->code_store = $finishedGoodsStore->code;
                    $newDetail->item_id = $good->item_id;
                    $newDetail->name = $item->name;
                    $newDetail->code = $item->code;
                    $newDetail->quantity = $quantityToAdd;
                    $newDetail->selling_price = $item->selling_price ?? 0;
                    $newDetail->cump = $costPrice;
                    if ($storeType === self::FINISHED_GOODS_STORE_TYPE_SM) {
                        $newDetail->total_cump_value = $quantityToAdd * $costPrice;
                    }
                    $newDetail->specification = $item->specification ?? null;
                    $newDetail->vat = $item->vat ?? null;
                    $newDetail->default_account_id = $item->default_account_id ?? null;
                    $newDetail->created_by = $this->user->name;
                    $newDetail->save();
                    
                    // Update Item cump
                    $item->cump = $costPrice;
                    $item->updated_by = $this->user->name;
                    $item->save();
                    
                    // Stock final (after adding)
                    $stockFinal = $quantityToAdd;
                    $valueStockFinal = $quantityToAdd * $costPrice;
                    $valueStockin = $quantityToAdd * $costPrice;
                }
                
                // Create FinishedGoodReport for finished goods movement
                $finishedGoodReport = new FinishedGoodReport();
                $finishedGoodReport->item_id = $good->item_id;
                $finishedGoodReport->code_store = $finishedGoodsStore->code;
                $finishedGoodReport->quantity_stock_initial = $stockInitial;
                $finishedGoodReport->value_stock_initial = $valueStockInitial;
                $finishedGoodReport->quantity_stockin = $quantityToAdd;
                $finishedGoodReport->value_stockin = $valueStockin;
                $finishedGoodReport->quantity_stock_final = $stockFinal;
                $finishedGoodReport->value_stock_final = $valueStockFinal;
                $finishedGoodReport->cump = $costPrice;
                $finishedGoodReport->date = Carbon::now();
                $finishedGoodReport->type_transaction = 'PRODUCTION';
                $finishedGoodReport->document_no = $productionOrder->prodution_number;
                $finishedGoodReport->description = 'Production order: ' . $productionOrder->prodution_number;
                $finishedGoodReport->created_by = $this->user->name;
                $finishedGoodReport->save();
            }

            // Calculate total value of finished goods produced
            $totalFinishedGoodsValue = 0;
            foreach ($finishedGoods as $good) {
                $item = Item::find($good->item_id);
                if (!$item) {
                    continue;
                }
                $finishedGoodsStoreDetail = $storeType === self::FINISHED_GOODS_STORE_TYPE_SM
                    ? FinishedGoodsSmStoreDetail::where('code_store', $finishedGoodsStore->code)->where('item_id', $good->item_id)->first()
                    : FinishedGoodsStoreDetail::where('code_store', $finishedGoodsStore->code)->where('item_id', $good->item_id)->first();
                
                if ($finishedGoodsStoreDetail) {
                    $quantityToAdd = floatval($good->item_quantity ?? 0);
                    $costPrice = $productionCostPerUnit > 0 ? $productionCostPerUnit : floatval($good->cost_price ?? 0);
                    if ($costPrice == 0) {
                        $costPrice = floatval($item->cump ?? 0);
                    }
                    $totalFinishedGoodsValue += $quantityToAdd * $costPrice;
                }
            }

            // Calculate total value of raw materials used
            $totalRawMaterialsValue = 0;
            foreach ($rawMaterials as $material) {
                $rawMaterialSmallStoreDetail = RawMaterialSmallStoreDetail::where('code', $storeCode)
                    ->where('r_material_id', $material->r_material_id)
                    ->first();
                
                if ($rawMaterialSmallStoreDetail) {
                    $quantityUsed = floatval($material->material_quantity ?? 0);
                    $currentCump = floatval($rawMaterialSmallStoreDetail->cump ?? 0);
                    $totalRawMaterialsValue += $quantityUsed * $currentCump;
                }
            }

            // Accounting (production): RM — credit stock origine, débit compte classe 6 par matière (default_account_id).
            // PF — débit stock destination (ligne magasin / article / magasin), crédit prod_bg (stock principal) ou prod_sm (petite prod) selon AccountSetting.
            $accountSetting = AccountSetting::where('active', 1)->first();
            $idRmStockOrigin = $rawMaterialSmallStore->default_account_id ? (int) $rawMaterialSmallStore->default_account_id : null;
            $productionOffsetAccountId = null;
            if ($accountSetting) {
                $productionOffsetAccountId = $storeType === self::FINISHED_GOODS_STORE_TYPE_SM
                    ? ($accountSetting->prod_sm_account_id ?: null)
                    : ($accountSetting->prod_bg_account_id ?: null);
                if ($productionOffsetAccountId !== null) {
                    $productionOffsetAccountId = (int) $productionOffsetAccountId;
                }
            }

            $journalRows = [];
            $docLabel = 'Production order: ' . $productionOrder->prodution_number;
            $jDate = $productionOrder->production_date;

            if ($totalRawMaterialsValue > 0) {
                if (!$idRmStockOrigin) {
                    throw new \RuntimeException('Compte de stock matières premières (magasin petit stock) non configuré.');
                }
                foreach ($rawMaterials as $material) {
                    $rmsd = RawMaterialSmallStoreDetail::where('code', $storeCode)
                        ->where('r_material_id', $material->r_material_id)
                        ->first();
                    if (!$rmsd) {
                        continue;
                    }
                    $qtyUsed = (float) ($material->material_quantity ?? 0);
                    $cumpRm = (float) ($rmsd->cump ?? 0);
                    $lineVal = $qtyUsed * $cumpRm;
                    if ($lineVal <= 0) {
                        continue;
                    }
                    $rawMat = RawMaterial::find($material->r_material_id);
                    if (!$rawMat || !$rawMat->default_account_id) {
                        $rmLabel = $rawMat ? ($rawMat->name ?? '#' . $material->r_material_id) : ('#' . $material->r_material_id);
                        throw new \RuntimeException(
                            'Compte de consommation (classe 6) manquant pour la matière première : ' . $rmLabel . '. Renseignez default_account_id sur la matière.'
                        );
                    }
                }

                $rmStockLedger = $this->updateLedgerTotals($idRmStockOrigin, 'credit', $totalRawMaterialsValue);
                // Toujours fournir debit ET credit : le batch insert Laravel trie les clés (ksort) et les
                // colonnes SQL suivent la 1re ligne — sinon les montants se décalent vers la mauvaise colonne.
                $journalRows[] = [
                    'date' => $jDate,
                    'document_no' => $productionOrder->prodution_number,
                    'description' => $docLabel,
                    'account_id' => $idRmStockOrigin,
                    'libelle' => $docLabel . ' — sortie stock MP',
                    'debit' => 0,
                    'credit' => $totalRawMaterialsValue,
                    'balance' => $rmStockLedger['balance'],
                    'created_at' => Carbon::now(),
                ];

                foreach ($rawMaterials as $material) {
                    $rmsd = RawMaterialSmallStoreDetail::where('code', $storeCode)
                        ->where('r_material_id', $material->r_material_id)
                        ->first();
                    if (!$rmsd) {
                        continue;
                    }
                    $qtyUsed = (float) ($material->material_quantity ?? 0);
                    $cumpRm = (float) ($rmsd->cump ?? 0);
                    $lineVal = $qtyUsed * $cumpRm;
                    if ($lineVal <= 0) {
                        continue;
                    }
                    $rawMat = RawMaterial::find($material->r_material_id);
                    $expenseAccId = (int) $rawMat->default_account_id;
                    $expLedger = $this->updateLedgerTotals($expenseAccId, 'debit', $lineVal);
                    $journalRows[] = [
                        'date' => $jDate,
                        'document_no' => $productionOrder->prodution_number,
                        'description' => $docLabel,
                        'account_id' => $expenseAccId,
                        'libelle' => $docLabel . ' — consommation MP ' . ($rawMat->name ?? ''),
                        'debit' => $lineVal,
                        'credit' => 0,
                        'balance' => $expLedger['balance'],
                        'created_at' => Carbon::now(),
                    ];
                }
            }

            if ($totalFinishedGoodsValue > 0) {
                if (!$productionOffsetAccountId) {
                    throw new \RuntimeException(
                        'Compte de production (prod_bg_account_id ou prod_sm_account_id) manquant dans les paramètres comptables actifs.'
                    );
                }
                foreach ($finishedGoods as $good) {
                    $item = Item::find($good->item_id);
                    if (!$item) {
                        continue;
                    }
                    $qtyFg = (float) ($good->item_quantity ?? 0);
                    $costPrice = $productionCostPerUnit > 0 ? $productionCostPerUnit : (float) ($good->cost_price ?? 0);
                    if ($costPrice == 0) {
                        $costPrice = (float) ($item->cump ?? 0);
                    }
                    $lineVal = $qtyFg * $costPrice;
                    if ($lineVal <= 0) {
                        continue;
                    }
                    $fgStockId = $this->resolveFinishedGoodsDestinationStockAccountId($storeType, $finishedGoodsStore, (int) $good->item_id, $item);
                    if (!$fgStockId) {
                        throw new \RuntimeException(
                            'Compte de stock produits finis (destination) manquant pour l\'article : ' . ($item->name ?? $item->code ?? '#' . $good->item_id) . '.'
                        );
                    }
                }

                foreach ($finishedGoods as $good) {
                    $item = Item::find($good->item_id);
                    if (!$item) {
                        continue;
                    }
                    $qtyFg = (float) ($good->item_quantity ?? 0);
                    $costPrice = $productionCostPerUnit > 0 ? $productionCostPerUnit : (float) ($good->cost_price ?? 0);
                    if ($costPrice == 0) {
                        $costPrice = (float) ($item->cump ?? 0);
                    }
                    $lineVal = $qtyFg * $costPrice;
                    if ($lineVal <= 0) {
                        continue;
                    }
                    $fgStockId = (int) $this->resolveFinishedGoodsDestinationStockAccountId($storeType, $finishedGoodsStore, (int) $good->item_id, $item);

                    $fgStockLedger = $this->updateLedgerTotals($fgStockId, 'debit', $lineVal);
                    $journalRows[] = [
                        'date' => $jDate,
                        'document_no' => $productionOrder->prodution_number,
                        'description' => $docLabel,
                        'account_id' => $fgStockId,
                        'libelle' => $docLabel . ' — entrée stock PF ' . ($item->name ?? ''),
                        'debit' => $lineVal,
                        'credit' => 0,
                        'balance' => $fgStockLedger['balance'],
                        'created_at' => Carbon::now(),
                    ];

                    $prodLedger = $this->updateLedgerTotals($productionOffsetAccountId, 'credit', $lineVal);
                    $journalRows[] = [
                        'date' => $jDate,
                        'document_no' => $productionOrder->prodution_number,
                        'description' => $docLabel,
                        'account_id' => $productionOffsetAccountId,
                        'libelle' => $docLabel . ' — production (offset)',
                        'debit' => 0,
                        'credit' => $lineVal,
                        'balance' => $prodLedger['balance'],
                        'created_at' => Carbon::now(),
                    ];
                }
            }

            if (!empty($journalRows)) {
                AccountJournalEntryDetail::insert($journalRows);
            }

            // Update production order status
            $productionOrder->status = '4';
            $productionOrder->approuved_by = $this->user->name;
            $productionOrder->actual_start_time = Carbon::now();
            $productionOrder->save();

            ProductionOrderDetail::where('production_number', '=', $production_no)
                ->update(['status' => '4', 'approuved_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Production order has been approved and stocks have been updated !!');
            return back();
        } catch (\Exception $e) {
            if (isset($dbStarted) && $dbStarted) {
                DB::rollback();
            }
            \Log::error('Production order approval: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            session()->flash('error', 'Quelque chose a mal tourné. ' . $e->getMessage());
            return back();
        }
    }

    /**
     * Send stock movements to OBR via API.
     * Returns ['ok' => bool, 'message' => string]
     */
    protected function sendStockMovementsToObr(array $movements, string $movementType): array
    {
        if (empty($movements)) {
            return ['ok' => true, 'message' => 'No stock movement to send.'];
        }

        try {
            $login = function (): ?string {
                $loginUrl = rtrim((string) config('app.guzzle_test_url'), '/') . '/ebms_api/login/';
                $loginResponse = Http::post($loginUrl, [
                    'username' => config('app.obr_test_username'),
                    'password' => config('app.obr_test_pwd'),
                ]);
                $loginJson = $loginResponse->json() ?? [];
                return $loginJson['result']['token'] ?? null;
            };

            $token = $login();
            if (empty($token)) {
                return ['ok' => false, 'message' => 'Connexion au service OBR impossible (token manquant).'];
            }
            
            $movementUrl = rtrim((string) config('app.guzzle_test_url'), '/') . '/ebms_api/AddStockMovement';
            foreach ($movements as $m) {
                $payload = [
                    'system_or_device_id' => config('app.obr_test_username'),
                    'item_code' => $m['item_code'] ?? '',
                    'item_designation' => $m['item_designation'] ?? '',
                    'item_quantity' => $m['item_quantity'] ?? 0,
                    'item_measurement_unit' => ($m['item_measurement_unit'] ?? 'PCS') ?: 'PCS',
                    // OBR required fields
                    'item_cost_price' => $m['item_purchase_or_sale_price'] ?? ($m['item_cost_price'] ?? 0),
                    'item_purchase_or_sale_currency' => $m['item_purchase_or_sale_currency'] ?? 'BIF',
                    'item_movement_type' => $movementType,
                    'item_movement_invoice_ref' => $m['item_movement_invoice_ref'] ?? '',
                    'item_movement_description' => $m['item_movement_description'] ?? '',
                    'item_movement_date' => $m['item_movement_date'] ?? Carbon::now()->toDateTimeString(),
                ];

                $send = function (string $token) use ($movementUrl, $payload) {
                    return Http::withHeaders([
                        'Authorization' => 'Bearer ' . $token,
                        'Accept' => 'application/json',
                    ])->post($movementUrl, $payload);
                };

                $resp = $send($token);

                // Token in OBR expires quickly (60s). If unauthorized/forbidden, re-login once and retry.
                if (method_exists($resp, 'unauthorized') && ($resp->unauthorized() || $resp->forbidden())) {
                    $newToken = $login();
                    if (!empty($newToken)) {
                        $token = $newToken;
                        $resp = $send($token);
                    }
                } elseif (method_exists($resp, 'status') && in_array((int)$resp->status(), [401, 403], true)) {
                    $newToken = $login();
                    if (!empty($newToken)) {
                        $token = $newToken;
                        $resp = $send($token);
                    }
                }

                $json = $resp->json() ?? [];
                $ok = $json['success'] ?? false;

                if (!$ok) {
                    // If OBR responds with HTML/text (not JSON), use body as message source.
                    $rawMsg = (string) ($json['msg'] ?? '');
                    if (trim($rawMsg) === '' && method_exists($resp, 'body')) {
                        $rawMsg = (string) $resp->body();
                    }
                    if (trim($rawMsg) === '') {
                        $rawMsg = 'Transmission stock OBR refusée.';
                    }

                    if ($this->isObrAlreadyRegisteredMessage($rawMsg)) {
                        continue;
                    }
                    return ['ok' => false, 'message' => $this->sanitizeObrErrorMessage($rawMsg)];
                }
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Erreur lors de la transmission à l’OBR. Veuillez réessayer plus tard.'];
        }

        return ['ok' => true, 'message' => 'OBR stock movements sent successfully.'];
    }

    protected function sanitizeObrErrorMessage(string $raw): string
    {
        $lower = strtolower($raw);
        if (str_contains($lower, 'sqlstate')
            || str_contains($lower, 'syntax error')
            || str_contains($lower, 'access violation')
            || str_contains($lower, 'command denied')
        ) {
            return 'Transmission à l’OBR refusée (droits ou configuration côté OBR). Veuillez contacter l’administrateur OBR.';
        }
        $raw = trim($raw);
        return $raw !== '' ? $raw : 'Transmission à l’OBR refusée.';
    }

    protected function isObrAlreadyRegisteredMessage(string $raw): bool
    {
        $lower = strtolower(trim($raw));
        if ($lower === '') return false;

        if (str_contains($lower, 'mouvement de stock')
            && (str_contains($lower, 'deja ete enregistre') || str_contains($lower, 'déjà été enregistré') || str_contains($lower, 'deja enregistre') || str_contains($lower, 'déjà enregistré'))
        ) {
            return true;
        }

        return str_contains($lower, 'movement')
            && str_contains($lower, 'already')
            && str_contains($lower, 'registered');
    }

    /**
     * Compte de stock valorisé des produits finis sur le magasin de destination (ligne de stock → article → entrepôt).
     */
    protected function resolveFinishedGoodsDestinationStockAccountId(
        string $storeType,
        $finishedGoodsStore,
        int $itemId,
        ?Item $item
    ): ?int {
        if (!$finishedGoodsStore) {
            return null;
        }
        if ($storeType === self::FINISHED_GOODS_STORE_TYPE_SM) {
            $detail = FinishedGoodsSmStoreDetail::where('code_store', $finishedGoodsStore->code)
                ->where('item_id', $itemId)
                ->first();
        } else {
            $detail = FinishedGoodsStoreDetail::where('code_store', $finishedGoodsStore->code)
                ->where('item_id', $itemId)
                ->first();
        }
        if ($detail && $detail->default_account_id) {
            return (int) $detail->default_account_id;
        }
        if ($item && $item->default_account_id) {
            return (int) $item->default_account_id;
        }
        if ($finishedGoodsStore->default_account_id) {
            return (int) $finishedGoodsStore->default_account_id;
        }

        return null;
    }

    /**
     * Update ledger totals for accounting
     *
     * @param int $accountId
     * @param string $type ('debit' or 'credit')
     * @param float $amount
     * @return array
     */
    /**
     * Reject production order
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $production_no
     * @return \Illuminate\Http\Response
     */
    public function reject(Request $request, $production_no)
    {
        if (is_null($this->user) || !$this->user->can('production_order.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any production order !');
        }

        $rules = array(
            'rejected_motif' => 'required'
        );

        $error = Validator::make($request->all(), $rules);

        if($error->fails()){
            return response()->json([
                'error' => $error->errors()->all(),
            ]);
        }

        try {
            DB::beginTransaction();

            ProductionOrder::where('prodution_number', '=', $production_no)
                ->update([
                    'status' => '-1',
                    'rejected_by' => $this->user->name,
                    'rejected_motif' => $request->rejected_motif
                ]);
            
            ProductionOrderDetail::where('production_number', '=', $production_no)
                ->update(['status' => '-1', 'rejected_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Production order has been rejected !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Production Order Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            session()->flash('error', 'Error: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Impression par numéro de document (ordre de fabrication).
     */
    public function print($production_no)
    {
        if (is_null($this->user) || !$this->user->can('production_order.view')) {
            abort(403, 'Sorry !! You are Unauthorized to print this document !');
        }
        $productionOrder = ProductionOrder::where('prodution_number', $production_no)->first();
        if (!$productionOrder) {
            session()->flash('error', 'Production order not found !!');
            return redirect()->route('admin.production-order.index');
        }
        $rawMaterials = ProductionOrderDetail::with('rawMaterial.rawMaterialMeasurement')
            ->where('production_number', $production_no)
            ->whereNotNull('r_material_id')
            ->get();
        $finishedGoods = ProductionOrderDetail::with('item')
            ->where('production_number', $production_no)
            ->whereNotNull('item_id')
            ->get();
        $setting = \DB::table('settings')->orderBy('created_at', 'desc')->first();
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.production_order', compact('productionOrder', 'rawMaterials', 'finishedGoods', 'setting'), [], 'UTF-8');
        return $pdf->download('ORDRE_FABRICATION_' . $production_no . '.pdf');
    }
}
