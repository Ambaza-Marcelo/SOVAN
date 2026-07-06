<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesStockDocumentApproval;
use App\Http\Controllers\Backend\Concerns\UpdatesLedgerTotals;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Item;
use App\Models\FinishedGoodsStockin;
use App\Models\FinishedGoodsStockinDetail;
use App\Models\FinishedGoodsStore;
use App\Models\FinishedGoodsStoreDetail;
use App\Models\FinishedGoodsSmStore;
use App\Models\FinishedGoodsSmStoreDetail;
use App\Models\FinishedGoodReport;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountSetting;
use App\Services\Stock\StockCumpCalculator;
use Carbon\Carbon;
use PDF;
use Validator;

class FinishedGoodsStockinController extends Controller
{
    use HandlesStockDocumentApproval;
    use UpdatesLedgerTotals;

    public $user;

    private const STORE_TYPE_MAIN = 'MAIN';
    private const STORE_TYPE_SM = 'SM';

    /**
     * Ajax search: finished goods items within selected store (MAIN or SM).
     */
    public function searchItems(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('finished_goods_stockin.create')) {
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
        if ($storeType === self::STORE_TYPE_SM) {
            $store = FinishedGoodsSmStore::select('id', 'code')->find($storeId);
            $storeCode = $store ? $store->code : null;
        } else {
            $store = FinishedGoodsStore::select('id', 'code')->find($storeId);
            $storeCode = $store ? $store->code : null;
        }

        if (!$storeCode) {
            return response()->json(['results' => []]);
        }

        $detailsQuery = $storeType === self::STORE_TYPE_SM
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
                'label' => trim(($r->name ?? '') . '/' . number_format($r->cump ?? 0, 0, ',', ' ') . '/' . ($r->code ?? '')),
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
    public function index(\Illuminate\Http\Request $request)
    {
        if (is_null($this->user) || !$this->user->can('finished_goods_stockin.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any stockin !');
        }

        $query = FinishedGoodsStockin::orderBy('id','desc');
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = \Carbon\Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = \Carbon\Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('date', [$dateDebut, $dateFin]);
        } else {
            $query->take(100);
        }
        $stockins = $query->get();
        return view('backend.pages.finished_goods_stockin.index', compact('stockins'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('finished_goods_stockin.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockin !');
        }

        // Ne proposer que les articles stockables (actifs) qui figurent déjà
        // dans l'un des stocks des produits finis (Principal ou SM).
        $items = Item::active()
            ->where('store_type', Item::STORE_TYPE_FINISHED_GOODS)
            ->where(function ($q) {
                $q->whereHas('finishedGoodsStoreDetail', function ($qq) {
                    $qq->whereNotNull('item_id');
                })->orWhereHas('finishedGoodsSmStoreDetail', function ($qq) {
                    $qq->whereNotNull('item_id');
                });
            })
            ->orderBy('name','asc')
            ->get();
        $finished_goods_stores = FinishedGoodsStore::all();
        $finished_goods_sm_stores = FinishedGoodsSmStore::all();
        return view('backend.pages.finished_goods_stockin.create', compact('items', 'finished_goods_stores', 'finished_goods_sm_stores'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('finished_goods_stockin.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockin !');
        }

        $rules = array(
            'item_id.*'  => 'required',
            'date'  => 'required',
            'quantity.*'  => 'required',
            'cost_price.*'  => 'required',
            'handingover'  => 'required',
            'origin'  => 'required',
            'receptionist'  => 'required',
            'item_movement_type'  => 'required|in:EN,ER,EAJ,EAU',
            'store_type' => 'required|in:MAIN,SM',
            'finished_g_store_id'  => 'required_if:store_type,MAIN',
            'finished_g_sm_store_id'  => 'required_if:store_type,SM',
            'description'  => 'required|max:490'
        );

        $error = Validator::make($request->all(),$rules);

        if($error->fails()){
            return response()->json([
                'error' => $error->errors()->all(),
            ]);
        }

        try {
            DB::beginTransaction();

            $item_id = $request->item_id;
            $date = $request->date;
            $item_purchase_or_sale_currency = $request->item_purchase_or_sale_currency;
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $origin = $request->origin;
            $store_type = $request->store_type;
            $finished_g_store_id = $request->finished_g_store_id;
            $finished_g_sm_store_id = $request->finished_g_sm_store_id;
            $description = $request->description; 
            $item_movement_type = $request->item_movement_type; 
            $quantity = $request->quantity;
            $cost_price = $request->cost_price;

            $latest = FinishedGoodsStockin::orderBy('id','desc')->first();
            if ($latest) {
               $stockin_no = 'FG-BE' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $stockin_no = 'FG-BE' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $stockin_signature = $stockin_no.'-'.Carbon::parse($date)->format('Y-m-d-H-i-s');
            $created_by = $this->user->name;

            $stockin = new FinishedGoodsStockin();
            $stockin->date = $date;
            $stockin->stockin_no = $stockin_no;
            $stockin->stockin_signature = $stockin_signature;
            $stockin->store_type = $store_type;
            $stockin->receptionist = $receptionist;
            $stockin->handingover = $handingover;
            $stockin->origin = $origin;
            $stockin->item_purchase_or_sale_currency = $item_purchase_or_sale_currency;
            $stockin->created_by = $created_by;
            $stockin->finished_g_store_id = $store_type === self::STORE_TYPE_MAIN ? $finished_g_store_id : null;
            $stockin->finished_g_sm_store_id = $store_type === self::STORE_TYPE_SM ? $finished_g_sm_store_id : null;
            $stockin->status = 1;
            $stockin->item_movement_type = $item_movement_type;
            $stockin->description = $description;
            $stockin->created_at = Carbon::now();
            $stockin->save();

            for( $count = 0; $count < count($item_id); $count++ ){
                $total_cost_value = $quantity[$count] * $cost_price[$count];
                $total_value = $total_cost_value;

                $stockinDetail = new FinishedGoodsStockinDetail();
                $stockinDetail->date = $date;
                $stockinDetail->quantity = $quantity[$count];
                $stockinDetail->cost_price = $cost_price[$count];
                $stockinDetail->total_cost_value = $total_cost_value;
                $stockinDetail->total_value = $total_value;
                $stockinDetail->stockin_no = $stockin_no;
                $stockinDetail->stockin_signature = $stockin_signature;
                $stockinDetail->receptionist = $receptionist;
                $stockinDetail->handingover = $handingover;
                $stockinDetail->origin = $origin;
                $stockinDetail->item_purchase_or_sale_currency = $item_purchase_or_sale_currency;
                $stockinDetail->item_movement_type = $item_movement_type;
                $stockinDetail->store_type = $store_type;
                $stockinDetail->description = $description;
                $stockinDetail->created_by = $created_by;
                $stockinDetail->finished_g_store_id = $store_type === self::STORE_TYPE_MAIN ? $finished_g_store_id : null;
                $stockinDetail->finished_g_sm_store_id = $store_type === self::STORE_TYPE_SM ? $finished_g_sm_store_id : null;
                $stockinDetail->item_id = $item_id[$count];
                $stockinDetail->status = 1;
                $stockinDetail->created_at = Carbon::now();
                $stockinDetail->save();
            }

            DB::commit();
            session()->flash('success', 'Stockin has been created !!');
            return redirect()->route('admin.finished-goods-stockins.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $stockin_no
     * @return \Illuminate\Http\Response
     */
    public function show($stockin_no)
    {
        $stockin = FinishedGoodsStockin::where('stockin_no', $stockin_no)->first();
        if (is_null($stockin)) {
            session()->flash('error', 'Stockin not found !!');
            return redirect()->route('admin.finished-goods-stockins.index');
        }

        $stockinDetails = FinishedGoodsStockinDetail::with(['item'])
            ->where('stockin_no', $stockin_no)->get();
        
        // Calculate totals
        $totalQuantity = $stockinDetails->sum('quantity');
        $totalCostValue = $stockinDetails->sum('total_cost_value');
        
        return view('backend.pages.finished_goods_stockin.show', compact('stockin', 'stockinDetails', 'totalQuantity', 'totalCostValue'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $stockin_no
     * @return \Illuminate\Http\Response
     */
    public function edit($stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('finished_goods_stockin.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any stockin !');
        }

        $stockin = FinishedGoodsStockin::where('stockin_no', $stockin_no)->first();
        
        if (is_null($stockin)) {
            session()->flash('error', 'Stockin not found !!');
            return redirect()->route('admin.finished-goods-stockins.index');
        }

        // Prevent editing when status is 4 (Approuvé/Approved)
        if ($stockin->status == 4) {
            session()->flash('error', 'Cannot edit stockin that has been approved (status = 4) !!');
            return redirect()->route('admin.finished-goods-stockins.index');
        }

        $stockinDetails = FinishedGoodsStockinDetail::with(['item'])
            ->where('stockin_no', $stockin_no)->get();

        // Ne proposer que les articles stockables (actifs) qui figurent déjà
        // dans l'un des stocks des produits finis (Principal ou SM).
        $items = Item::active()
            ->where('store_type', Item::STORE_TYPE_FINISHED_GOODS)
            ->where(function ($q) {
                $q->whereHas('finishedGoodsStoreDetail', function ($qq) {
                    $qq->whereNotNull('item_id');
                })->orWhereHas('finishedGoodsSmStoreDetail', function ($qq) {
                    $qq->whereNotNull('item_id');
                });
            })
            ->orderBy('name','asc')
            ->get();
        $finished_goods_stores = FinishedGoodsStore::all();
        $finished_goods_sm_stores = FinishedGoodsSmStore::all();

        return view('backend.pages.finished_goods_stockin.edit', compact('stockin', 'stockinDetails', 'items', 'finished_goods_stores', 'finished_goods_sm_stores'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $stockin_no
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('finished_goods_stockin.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any stockin !');
        }

        $stockin = FinishedGoodsStockin::where('stockin_no', $stockin_no)->first();
        
        if (is_null($stockin)) {
            session()->flash('error', 'Stockin not found !!');
            return redirect()->route('admin.finished-goods-stockins.index');
        }

        // Prevent updating when status is 4 (Approuvé/Approved)
        if ($stockin->status == 4) {
            session()->flash('error', 'Cannot update stockin that has been approved (status = 4) !!');
            return redirect()->route('admin.finished-goods-stockins.index');
        }

        $rules = array(
            'item_id.*'  => 'required',
            'date'  => 'required',
            'quantity.*'  => 'required',
            'cost_price.*'  => 'required',
            'handingover'  => 'required',
            'origin'  => 'required',
            'receptionist'  => 'required',
            'item_movement_type'  => 'required|in:EN,ER,EAJ,EAU',
            'store_type' => 'required|in:MAIN,SM',
            'finished_g_store_id'  => 'required_if:store_type,MAIN',
            'finished_g_sm_store_id'  => 'required_if:store_type,SM',
            'description'  => 'required|max:490'
        );

        $error = Validator::make($request->all(),$rules);

        if($error->fails()){
            return response()->json([
                'error' => $error->errors()->all(),
            ]);
        }

        try {
            DB::beginTransaction();

            $item_id = $request->item_id;
            $date = $request->date;
            $item_purchase_or_sale_currency = $request->item_purchase_or_sale_currency;
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $origin = $request->origin;
            $store_type = $request->store_type;
            $finished_g_store_id = $request->finished_g_store_id;
            $finished_g_sm_store_id = $request->finished_g_sm_store_id;
            $description = $request->description; 
            $item_movement_type = $request->item_movement_type; 
            $quantity = $request->quantity;
            $cost_price = $request->cost_price;

            $stockin_signature = $stockin_no.'-'.Carbon::parse($date)->format('Y-m-d-H-i-s');
            $updated_by = $this->user->name;

            $stockin->date = $date;
            $stockin->stockin_signature = $stockin_signature;
            $stockin->receptionist = $receptionist;
            $stockin->handingover = $handingover;
            $stockin->origin = $origin;
            $stockin->item_purchase_or_sale_currency = $item_purchase_or_sale_currency;
            $stockin->updated_by = $updated_by;
            $stockin->store_type = $store_type;
            $stockin->finished_g_store_id = $store_type === self::STORE_TYPE_MAIN ? $finished_g_store_id : null;
            $stockin->finished_g_sm_store_id = $store_type === self::STORE_TYPE_SM ? $finished_g_sm_store_id : null;
            $stockin->item_movement_type = $item_movement_type;
            $stockin->description = $description;
            $stockin->updated_at = Carbon::now();
            $stockin->save();

            // Delete existing details
            FinishedGoodsStockinDetail::where('stockin_no', $stockin_no)->delete();

            // Create new details
            for( $count = 0; $count < count($item_id); $count++ ){
                $total_cost_value = $quantity[$count] * $cost_price[$count];
                $total_value = $total_cost_value;

                $stockinDetail = new FinishedGoodsStockinDetail();
                $stockinDetail->date = $date;
                $stockinDetail->quantity = $quantity[$count];
                $stockinDetail->cost_price = $cost_price[$count];
                $stockinDetail->total_cost_value = $total_cost_value;
                $stockinDetail->total_value = $total_value;
                $stockinDetail->stockin_no = $stockin_no;
                $stockinDetail->stockin_signature = $stockin_signature;
                $stockinDetail->receptionist = $receptionist;
                $stockinDetail->handingover = $handingover;
                $stockinDetail->origin = $origin;
                $stockinDetail->item_purchase_or_sale_currency = $item_purchase_or_sale_currency;
                $stockinDetail->item_movement_type = $item_movement_type;
                $stockinDetail->store_type = $store_type;
                $stockinDetail->description = $description;
                $stockinDetail->created_by = $stockin->created_by;
                $stockinDetail->updated_by = $updated_by;
                $stockinDetail->finished_g_store_id = $store_type === self::STORE_TYPE_MAIN ? $finished_g_store_id : null;
                $stockinDetail->finished_g_sm_store_id = $store_type === self::STORE_TYPE_SM ? $finished_g_sm_store_id : null;
                $stockinDetail->item_id = $item_id[$count];
                $stockinDetail->status = $stockin->status;
                $stockinDetail->created_at = Carbon::now();
                $stockinDetail->save();
            }

            DB::commit();
            session()->flash('success', 'Stockin has been updated !!');
            return redirect()->route('admin.finished-goods-stockins.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $stockin_no
     * @return \Illuminate\Http\Response
     */
    public function destroy($stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('finished_goods_stockin.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any stockin !');
        }

        $stockin = FinishedGoodsStockin::where('stockin_no',$stockin_no)->first();
        
        if (is_null($stockin)) {
            session()->flash('error', 'Stockin not found !!');
            return back();
        }

        // Prevent deletion when status is 4 (Approuvé/Approved)
        if ($stockin->status == 4) {
            session()->flash('error', 'Cannot delete stockin that has been approved (status = 4) !!');
            return back();
        }

        try {
            DB::beginTransaction();

            if (!is_null($stockin)) {
                $stockin->delete();
                FinishedGoodsStockinDetail::where('stockin_no',$stockin_no)->delete();
            }

            DB::commit();
            session()->flash('success', 'Stockin has been deleted !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Validate the stockin (status = 2)
     *
     * @param  string  $stockin_no
     * @return \Illuminate\Http\Response
     */
    public function validateStockin($stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('finished_goods_stockin.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any stockin !');
        }

        $stockin = FinishedGoodsStockin::where('stockin_no', $stockin_no)->first();
        if (!$stockin) {
            session()->flash('error', 'Stockin not found !!');

            return back();
        }
        if ($response = $this->guardStockDocumentValidate($stockin)) {
            return $response;
        }

        try {
            DB::beginTransaction();

            FinishedGoodsStockin::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            FinishedGoodsStockinDetail::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Stockin has been validated !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Confirm the stockin (status = 3)
     *
     * @param  string  $stockin_no
     * @return \Illuminate\Http\Response
     */
    public function confirm($stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('finished_goods_stockin.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any stockin !');
        }

        $stockin = FinishedGoodsStockin::where('stockin_no', $stockin_no)->first();
        if (!$stockin) {
            session()->flash('error', 'Stockin not found !!');

            return back();
        }
        if ($response = $this->guardStockDocumentConfirm($stockin)) {
            return $response;
        }

        try {
            DB::beginTransaction();

            FinishedGoodsStockin::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            FinishedGoodsStockinDetail::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Stockin has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Approve the stockin (status = 4)
     * This will update stock, create reports, and make accounting entries
     *
     * @param  string  $stockin_no
     * @return \Illuminate\Http\Response
     */
    public function approuve($stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('finished_goods_stockin.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to approve any stockin !');
        }

        try {
            $stockin = FinishedGoodsStockin::where('stockin_no', $stockin_no)->first();

            if (is_null($stockin)) {
                session()->flash('error', 'Stockin not found !!');

                return back();
            }
            if ($response = $this->guardStockDocumentApprove($stockin)) {
                return $response;
            }

            $datas = FinishedGoodsStockinDetail::where('stockin_no', $stockin_no)->with('item')->get();
            $storeType = $stockin->store_type ?: self::STORE_TYPE_MAIN;
            $finished_goods_store = $storeType === self::STORE_TYPE_SM
                ? FinishedGoodsSmStore::find($stockin->finished_g_sm_store_id)
                : FinishedGoodsStore::find($stockin->finished_g_store_id);

            if (is_null($finished_goods_store)) {
                session()->flash('error', 'Finished goods store not found !!');

                return back();
            }

            $accountSetting = \App\Models\Accounting\AccountSetting::where('active', 1)->first();
            if (!$finished_goods_store->default_account_id || !$accountSetting || !$accountSetting->f_eaj_account_id) {
                session()->flash('error', __('messages.approval_accounts_required'));

                return back();
            }

            $code_store = $finished_goods_store->code;
            $prepared = [];

            foreach ($datas as $data) {
                $storeDetail = $storeType === self::STORE_TYPE_SM
                    ? FinishedGoodsSmStoreDetail::where('code_store', $code_store)->where('item_id', $data->item_id)->first()
                    : FinishedGoodsStoreDetail::where('code_store', $code_store)->where('item_id', $data->item_id)->first();

                if (is_null($storeDetail)) {
                    session()->flash('error', 'Item not found in store: ' . ($data->item ? $data->item->name : 'N/A'));

                    return back();
                }

                if (!$data->item) {
                    session()->flash('error', 'Article manquant sur une ligne du bon d\'entrée.');

                    return back();
                }

                $quantityStockInitial = (float) ($storeDetail->quantity ?? 0);
                $quantityStockin = (float) $data->quantity;
                $costPrice = (float) $data->cost_price;
                $valueStockInitial = StockCumpCalculator::initialValueFromStore(
                    $quantityStockInitial,
                    isset($storeDetail->cump) ? (float) $storeDetail->cump : null,
                    isset($storeDetail->total_cump_value) ? (float) $storeDetail->total_cump_value : null
                );
                $cumpResult = StockCumpCalculator::weightedAverageOnStockIn(
                    $quantityStockInitial,
                    $valueStockInitial,
                    $quantityStockin,
                    $costPrice,
                    $costPrice
                );
                $quantityTotal = $cumpResult['quantity_total'];
                $newCump = $cumpResult['cump'];
                $valueStockin = (float) ($data->total_cost_value ?: $cumpResult['value_in']);

                $currency = $data->item_purchase_or_sale_currency
                    ?: ($stockin->item_purchase_or_sale_currency ?: 'BIF');
                $movementDate = $data->date
                    ? Carbon::parse($data->date)->toDateTimeString()
                    : Carbon::now()->toDateTimeString();

                $movType = (string) ($data->item_movement_type ?? '');
                $desc = trim((string) ($data->description ?? $stockin->description ?? ''));
                if ($desc === '' && $movType !== 'EN') {
                    $desc = 'Mouvement stock ' . ($movType !== '' ? $movType : 'ENTREE') . ' (' . $stockin_no . ')';
                }
                $invRef = $stockin_no;
                if ($movType === 'ER' && preg_match('/\b(FA|FN)\d+\b/i', $desc, $mRef)) {
                    $invRef = strtoupper($mRef[0]);
                }

                $prepared[] = [
                    'storeDetail' => $storeDetail,
                    'storeType' => $storeType,
                    'quantityStockInitial' => $quantityStockInitial,
                    'valueStockInitial' => $valueStockInitial,
                    'quantityStockin' => $quantityStockin,
                    'valueStockin' => $valueStockin,
                    'quantityTotal' => $quantityTotal,
                    'newCump' => $newCump,
                    'data' => $data,
                    'obr' => [
                        'item_code' => (string) ($data->item->code ?? ''),
                        'item_designation' => (string) ($data->item->name ?? ''),
                        'item_quantity' => $data->quantity,
                        'item_measurement_unit' => 'PCS',
                        'item_purchase_or_sale_price' => (float) $data->cost_price,
                        'item_purchase_or_sale_currency' => $currency,
                        'item_movement_type' => $movType,
                        'item_movement_invoice_ref' => $invRef,
                        'item_movement_description' => $desc,
                        'item_movement_date' => $movementDate,
                    ],
                ];
            }

            // Envoi OBR désactivé temporairement — à réactiver ultérieurement.
            /*
            $obrToken = null;
            foreach ($prepared as $row) {
                $obrResult = $this->postFinishedGoodsStockinObrMovement($row['obr'], $obrToken);
                if (!$obrResult['ok']) {
                    session()->flash('error', $obrResult['message']);

                    return back();
                }
            }
            */

            DB::beginTransaction();

            try {
                $totalValue = 0;

                foreach ($prepared as $row) {
                    $storeDetail = $row['storeDetail'];
                    $data = $row['data'];
                    $rowStoreType = $row['storeType'];

                    $storeDetail->quantity = $row['quantityTotal'];
                    $storeDetail->cump = $row['newCump'];
                    $storeDetail->selling_price = $data->item->selling_price ?? $storeDetail->selling_price;
                    $storeDetail->updated_by = $this->user->name;
                    if ($rowStoreType === self::STORE_TYPE_SM) {
                        $storeDetail->total_cump_value = $row['quantityTotal'] * $row['newCump'];
                    }
                    $storeDetail->save();

                    $report = new FinishedGoodReport();
                    $report->code_store = $code_store;
                    $report->item_id = $data->item_id;
                    $report->quantity_stock_initial = $row['quantityStockInitial'];
                    $report->value_stock_initial = $row['valueStockInitial'];
                    $report->quantity_stockin = $row['quantityStockin'];
                    $report->value_stockin = $row['valueStockin'];
                    $report->stock_total = $row['quantityTotal'];
                    $report->quantity_stockout = 0;
                    $report->value_stockout = 0;
                    $report->quantity_stock_final = $row['quantityTotal'];
                    $report->value_stock_final = $row['newCump'] * $row['quantityTotal'];
                    $report->cump = $row['newCump'];
                    $report->date = $data->date;
                    $report->created_by = $this->user->name;
                    $report->type_transaction = $data->item_movement_type;
                    $report->document_no = $stockin_no;
                    $report->description = $data->description;
                    $report->created_at = Carbon::now();
                    $report->save();

                    $totalValue += $row['valueStockin'];
                }

                $stock_account_id = $finished_goods_store->default_account_id;
                if ($stock_account_id) {
                    $stockLedgerState = $this->updateLedgerTotals($stock_account_id, 'debit', $totalValue);

                    $journalEntryDebit = new AccountJournalEntryDetail();
                    $journalEntryDebit->date = $stockin->date;
                    $journalEntryDebit->document_no = $stockin_no;
                    $journalEntryDebit->description = $stockin->description;
                    $journalEntryDebit->account_id = $stock_account_id;
                    $journalEntryDebit->libelle = $stockin->description;
                    $journalEntryDebit->debit = $totalValue;
                    $journalEntryDebit->balance = $stockLedgerState['balance'];
                    $journalEntryDebit->created_at = Carbon::now();
                    $journalEntryDebit->save();
                }

                $accountSetting = AccountSetting::where('active', 1)->first();
                if ($accountSetting && $accountSetting->f_eaj_account_id) {
                    $eajLedgerState = $this->updateLedgerTotals($accountSetting->f_eaj_account_id, 'credit', $totalValue);

                    $journalEntryCredit = new AccountJournalEntryDetail();
                    $journalEntryCredit->date = $stockin->date;
                    $journalEntryCredit->document_no = $stockin_no;
                    $journalEntryCredit->description = $stockin->description;
                    $journalEntryCredit->account_id = $accountSetting->f_eaj_account_id;
                    $journalEntryCredit->libelle = $stockin->description;
                    $journalEntryCredit->credit = $totalValue;
                    $journalEntryCredit->balance = $eajLedgerState['balance'];
                    $journalEntryCredit->created_at = Carbon::now();
                    $journalEntryCredit->save();
                }

                FinishedGoodsStockin::where('stockin_no', '=', $stockin_no)
                    ->update(['status' => 4, 'approuved_by' => $this->user->name]);
                FinishedGoodsStockinDetail::where('stockin_no', '=', $stockin_no)
                    ->update(['status' => 4, 'approuved_by' => $this->user->name]);

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            session()->flash('success', 'Stockin has been approved successfully! Stock updated in store: ' . $code_store);

            return back();
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            session()->flash('error', __('messages.error_approval') . $e->getMessage());

            return back();
        }
    }

    /**
     * Reject the stockin
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $stockin_no
     * @return \Illuminate\Http\Response
     */
    public function reject(Request $request, $stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('finished_goods_stockin.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any stockin !');
        }

        try {
            DB::beginTransaction();

            FinishedGoodsStockin::where('stockin_no', '=', $stockin_no)
                ->update([
                    'status' => -1,
                    'rejected_by' => $this->user->name,
                    'rejected_motif' => $request->rejected_motif
                ]);
            FinishedGoodsStockinDetail::where('stockin_no', '=', $stockin_no)
                ->update([
                    'status' => -1,
                    'rejected_by' => $this->user->name,
                    'rejected_motif' => $request->rejected_motif
                ]);

            DB::commit();
            session()->flash('success', 'Stockin has been rejected !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Reset the stockin
     *
     * @param  string  $stockin_no
     * @return \Illuminate\Http\Response
     */
    public function reset($stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('finished_goods_stockin.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any stockin !');
        }

        try {
            DB::beginTransaction();

            FinishedGoodsStockin::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
            FinishedGoodsStockinDetail::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Stockin has been reseted !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Update ledger totals for accounting
     *
     * @param  int  $accountId
     * @param  string  $type
     * @param  float  $amount
     * @return array
     */
    /**
     * Envoie un mouvement de stock vers l’OBR (login + token réutilisé, retry 401/403).
     *
     * @param  array<string, mixed>  $m
     * @return array{ok: bool, message: string}
     */
    protected function postFinishedGoodsStockinObrMovement(array $m, ?string &$cachedToken): array
    {
        $base = rtrim((string) config('app.guzzle_test_url'), '/');
        if ($base === '') {
            return ['ok' => false, 'message' => 'URL du service OBR non configurée (guzzle_test_url).'];
        }

        try {
            $login = function () use ($base): ?string {
                $loginResponse = Http::post($base . '/ebms_api/login/', [
                    'username' => config('app.obr_test_username'),
                    'password' => config('app.obr_test_pwd'),
                ]);
                $loginJson = $loginResponse->json() ?? [];

                return $loginJson['result']['token'] ?? null;
            };

            if ($cachedToken === null || $cachedToken === '') {
                $cachedToken = $login();
            }
            if (empty($cachedToken)) {
                return ['ok' => false, 'message' => 'Connexion au service OBR impossible (token manquant).'];
            }

            $movementUrl = $base . '/ebms_api/AddStockMovement';
            $payload = [
                'system_or_device_id' => config('app.obr_test_username'),
                'item_code' => $m['item_code'] ?? '',
                'item_designation' => $m['item_designation'] ?? '',
                'item_quantity' => $m['item_quantity'] ?? 0,
                'item_measurement_unit' => ($m['item_measurement_unit'] ?? 'PCS') ?: 'PCS',
                'item_cost_price' => $m['item_purchase_or_sale_price'] ?? ($m['item_cost_price'] ?? 0),
                'item_purchase_or_sale_currency' => $m['item_purchase_or_sale_currency'] ?? 'BIF',
                'item_movement_type' => $m['item_movement_type'] ?? '',
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

            $resp = $send($cachedToken);

            if (method_exists($resp, 'unauthorized') && ($resp->unauthorized() || $resp->forbidden())) {
                $cachedToken = $login();
                if (!empty($cachedToken)) {
                    $resp = $send($cachedToken);
                }
            } elseif (method_exists($resp, 'status') && in_array((int) $resp->status(), [401, 403], true)) {
                $cachedToken = $login();
                if (!empty($cachedToken)) {
                    $resp = $send($cachedToken);
                }
            }

            $json = $resp->json() ?? [];
            $ok = $json['success'] ?? false;

            if (!$ok) {
                $rawMsg = (string) ($json['msg'] ?? '');
                if (trim($rawMsg) === '' && method_exists($resp, 'body')) {
                    $rawMsg = (string) $resp->body();
                }
                if (trim($rawMsg) === '') {
                    $rawMsg = 'Transmission stock OBR refusée.';
                }

                if ($this->isObrAlreadyRegisteredMessage($rawMsg)) {
                    return ['ok' => true, 'message' => ''];
                }

                return ['ok' => false, 'message' => $this->sanitizeFinishedGoodsStockinObrErrorMessage($rawMsg)];
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Erreur lors de la transmission à l’OBR. Veuillez réessayer plus tard.'];
        }

        return ['ok' => true, 'message' => ''];
    }

    protected function sanitizeFinishedGoodsStockinObrErrorMessage(string $raw): string
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
     * Impression par numéro de document (bon d'entrée produits finis).
     */
    public function print($stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('finished_goods_stockin.view')) {
            abort(403, 'Sorry !! You are Unauthorized to print this document !');
        }
        $stockin = FinishedGoodsStockin::where('stockin_no', $stockin_no)->first();
        if (!$stockin) {
            session()->flash('error', 'Document not found !!');
            return redirect()->route('admin.finished-goods-stockins.index');
        }
        $setting = DB::table('settings')->orderBy('created_at', 'desc')->first();
        $datas = FinishedGoodsStockinDetail::where('stockin_no', $stockin_no)->with('item')->get();
        $totalValue = $datas->sum('total_cost_value');
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.finished_goods_stockin', compact('stockin', 'datas', 'setting', 'totalValue'), [], 'UTF-8');
        return $pdf->download('BON_ENTREE_PRODUITS_FINIS_' . $stockin_no . '.pdf');
    }
}
