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
use App\Models\GoodsStockout;
use App\Models\GoodsStockoutDetail;
use App\Models\GoodsStore;
use App\Models\GoodsStoreDetail;
use App\Models\GoodsStoreReport;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountSetting;
use App\Services\Stock\SauStockoutJournalService;
use Carbon\Carbon;
use PDF;
use Validator;

class GoodStockoutController extends Controller
{
    use HandlesStockDocumentApproval;
    use UpdatesLedgerTotals;

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
        if (is_null($this->user) || !$this->user->can('goods_stockout.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any stockout !');
        }

        $query = GoodsStockout::orderBy('id','desc');
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = \Carbon\Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = \Carbon\Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('date', [$dateDebut, $dateFin]);
        } else {
            $query->take(100);
        }
        $stockouts = $query->get();
        return view('backend.pages.goods_stockout.index', compact('stockouts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('goods_stockout.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockout !');
        }

        // Ne proposer que les articles stockables (actifs) qui figurent déjà
        // dans le stock des marchandises (GoodsStoreDetail).
        $items = Item::active()
            ->where('store_type', Item::STORE_TYPE_GOODS)
            ->whereHas('goodsStoreDetail', function ($q) {
                $q->whereNotNull('item_id');
            })
            ->orderBy('name','asc')
            ->get();
        $goods_stores = GoodsStore::all();
        return view('backend.pages.goods_stockout.create', compact('items', 'goods_stores'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('goods_stockout.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any stockout !');
        }

        $rules = array(
            'item_id.*'  => 'required',
            'date'  => 'required',
            'quantity.*'  => 'required',
            'asker'  => 'required',
            'destination'  => 'required',
            'item_movement_type'  => 'nullable|in:SAU,SP,SV,SC,SAJ',
            'goods_store_id'  => 'required',
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
            $asker = $request->asker;
            $destination = $request->destination;
            $goods_store_id = $request->goods_store_id;
            $description = $request->description; 
            $item_movement_type = SauStockoutJournalService::normalizeType($request->item_movement_type); 
            $requisition_no = $request->requisition_no;
            $quantity = $request->quantity;

            // Génération du numéro de sortie marchandises (GS-BS0001, GS-BS0002, ...)
            $latest = GoodsStockout::orderBy('id','desc')->first();
            if ($latest) {
               $stockout_no = 'GS-BS' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $stockout_no = 'GS-BS' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $stockout_signature = $stockout_no.'-'.Carbon::parse($date)->format('Y-m-d-H-i-s');
            $created_by = $this->user->name;

            $stockout = new GoodsStockout();
            $stockout->date = $date;
            $stockout->stockout_no = $stockout_no;
            $stockout->stockout_signature = $stockout_signature;
            $stockout->requisition_no = $requisition_no;
            $stockout->asker = $asker;
            $stockout->destination = $destination;
            $stockout->store_type = null;
            $stockout->item_purchase_or_sale_currency = $item_purchase_or_sale_currency;
            $stockout->created_by = $created_by;
            $stockout->goods_store_id = $goods_store_id;
            $stockout->status = 1;
            $stockout->item_movement_type = $item_movement_type;
            $stockout->description = $description;
            $stockout->created_at = Carbon::now();
            $stockout->save();

            for( $count = 0; $count < count($item_id); $count++ ){
                // Get item to calculate cost price
                $item = Item::find($item_id[$count]);
                $cost_price = $item->cump ?? 0;
                $total_cost_value = $quantity[$count] * $cost_price;

                $stockoutDetail = new GoodsStockoutDetail();
                $stockoutDetail->date = $date;
                $stockoutDetail->quantity = $quantity[$count];
                $stockoutDetail->cost_price = $cost_price;
                $stockoutDetail->total_cost_value = $total_cost_value;
                $stockoutDetail->stockout_no = $stockout_no;
                $stockoutDetail->stockout_signature = $stockout_signature;
                $stockoutDetail->requisition_no = $requisition_no;
                $stockoutDetail->asker = $asker;
                $stockoutDetail->destination = $destination;
                $stockoutDetail->item_movement_type = $item_movement_type;
                $stockoutDetail->store_type = null;
                $stockoutDetail->description = $description;
                $stockoutDetail->created_by = $created_by;
                $stockoutDetail->goods_store_id = $goods_store_id;
                $stockoutDetail->item_id = $item_id[$count];
                $stockoutDetail->status = 1;
                $stockoutDetail->created_at = Carbon::now();
                $stockoutDetail->save();
            }

            DB::commit();
            session()->flash('success', 'Stockout has been created !!');
            return redirect()->route('admin.goods-stockouts.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $stockout_no
     * @return \Illuminate\Http\Response
     */
    public function show($stockout_no)
    {
        $stockout = GoodsStockout::where('stockout_no', $stockout_no)->first();
        if (is_null($stockout)) {
            session()->flash('error', 'Stockout not found !!');
            return redirect()->route('admin.goods-stockouts.index');
        }

        $stockoutDetails = GoodsStockoutDetail::with(['item'])
            ->where('stockout_no', $stockout_no)->get();
        
        // Calculate totals
        $totalQuantity = $stockoutDetails->sum('quantity');
        $totalCostValue = $stockoutDetails->sum('total_cost_value');
        
        return view('backend.pages.goods_stockout.show', compact('stockout', 'stockoutDetails', 'totalQuantity', 'totalCostValue'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $stockout_no
     * @return \Illuminate\Http\Response
     */
    public function edit($stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_stockout.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any stockout !');
        }

        $stockout = GoodsStockout::where('stockout_no', $stockout_no)->first();
        
        if (is_null($stockout)) {
            session()->flash('error', 'Stockout not found !!');
            return redirect()->route('admin.goods-stockouts.index');
        }

        // Prevent editing when status is 4 (Approuvé/Approved)
        if ($stockout->status == 4) {
            session()->flash('error', 'Cannot edit stockout that has been approved (status = 4) !!');
            return redirect()->route('admin.goods-stockouts.index');
        }

        $stockoutDetails = GoodsStockoutDetail::with(['item'])
            ->where('stockout_no', $stockout_no)->get();

        // Ne proposer que les articles stockables (actifs) qui figurent déjà
        // dans le stock des marchandises (GoodsStoreDetail).
        $items = Item::active()
            ->where('store_type', Item::STORE_TYPE_GOODS)
            ->whereHas('goodsStoreDetail', function ($q) {
                $q->whereNotNull('item_id');
            })
            ->orderBy('name','asc')
            ->get();
        $goods_stores = GoodsStore::all();

        return view('backend.pages.goods_stockout.edit', compact('stockout', 'stockoutDetails', 'items', 'goods_stores'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $stockout_no
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_stockout.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any stockout !');
        }

        $stockout = GoodsStockout::where('stockout_no', $stockout_no)->first();
        
        if (is_null($stockout)) {
            session()->flash('error', 'Stockout not found !!');
            return redirect()->route('admin.goods-stockouts.index');
        }

        // Prevent updating when status is 4 (Approuvé/Approved)
        if ($stockout->status == 4) {
            session()->flash('error', 'Cannot update stockout that has been approved (status = 4) !!');
            return redirect()->route('admin.goods-stockouts.index');
        }

        $rules = array(
            'item_id.*'  => 'required',
            'date'  => 'required',
            'quantity.*'  => 'required',
            'asker'  => 'required',
            'destination'  => 'required',
            'item_movement_type'  => 'nullable|in:SAU,SP,SV,SC,SAJ',
            'goods_store_id'  => 'required',
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
            $asker = $request->asker;
            $destination = $request->destination;
            $goods_store_id = $request->goods_store_id;
            $description = $request->description; 
            $item_movement_type = SauStockoutJournalService::normalizeType($request->item_movement_type); 
            $requisition_no = $request->requisition_no;
            $quantity = $request->quantity;

            $stockout_signature = $stockout_no.'-'.Carbon::parse($date)->format('Y-m-d-H-i-s');
            $updated_by = $this->user->name;

            $stockout->date = $date;
            $stockout->stockout_signature = $stockout_signature;
            $stockout->requisition_no = $requisition_no;
            $stockout->asker = $asker;
            $stockout->destination = $destination;
            $stockout->item_purchase_or_sale_currency = $item_purchase_or_sale_currency;
            $stockout->updated_by = $updated_by;
            $stockout->goods_store_id = $goods_store_id;
            $stockout->item_movement_type = $item_movement_type;
            $stockout->description = $description;
            $stockout->updated_at = Carbon::now();
            $stockout->save();

            // Delete existing details
            GoodsStockoutDetail::where('stockout_no', $stockout_no)->delete();

            // Create new details
            for( $count = 0; $count < count($item_id); $count++ ){
                // Get item to calculate cost price
                $item = Item::find($item_id[$count]);
                $cost_price = $item->cump ?? 0;
                $total_cost_value = $quantity[$count] * $cost_price;

                $stockoutDetail = new GoodsStockoutDetail();
                $stockoutDetail->date = $date;
                $stockoutDetail->quantity = $quantity[$count];
                $stockoutDetail->cost_price = $cost_price;
                $stockoutDetail->total_cost_value = $total_cost_value;
                $stockoutDetail->stockout_no = $stockout_no;
                $stockoutDetail->stockout_signature = $stockout_signature;
                $stockoutDetail->requisition_no = $requisition_no;
                $stockoutDetail->asker = $asker;
                $stockoutDetail->destination = $destination;
                $stockoutDetail->item_movement_type = $item_movement_type;
                $stockoutDetail->store_type = null;
                $stockoutDetail->description = $description;
                $stockoutDetail->created_by = $stockout->created_by;
                $stockoutDetail->updated_by = $updated_by;
                $stockoutDetail->goods_store_id = $goods_store_id;
                $stockoutDetail->item_id = $item_id[$count];
                $stockoutDetail->status = $stockout->status;
                $stockoutDetail->created_at = Carbon::now();
                $stockoutDetail->save();
            }

            DB::commit();
            session()->flash('success', 'Stockout has been updated !!');
            return redirect()->route('admin.goods-stockouts.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $stockout_no
     * @return \Illuminate\Http\Response
     */
    public function destroy($stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_stockout.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any stockout !');
        }

        $stockout = GoodsStockout::where('stockout_no',$stockout_no)->first();
        
        if (is_null($stockout)) {
            session()->flash('error', 'Stockout not found !!');
            return back();
        }

        // Prevent deletion when status is 4 (Approuvé/Approved)
        if ($stockout->status == 4) {
            session()->flash('error', 'Cannot delete stockout that has been approved (status = 4) !!');
            return back();
        }

        try {
            DB::beginTransaction();

            if (!is_null($stockout)) {
                $stockout->delete();
                GoodsStockoutDetail::where('stockout_no',$stockout_no)->delete();
            }

            DB::commit();
            session()->flash('success', 'Stockout has been deleted !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Validate the stockout (status = 2)
     *
     * @param  string  $stockout_no
     * @return \Illuminate\Http\Response
     */
    public function validateStockout($stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_stockout.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any stockout !');
        }

        $stockout = GoodsStockout::where('stockout_no', $stockout_no)->first();
        if (!$stockout) {
            session()->flash('error', 'Stockout not found !!');

            return back();
        }
        if ($response = $this->guardStockDocumentValidate($stockout)) {
            return $response;
        }

        try {
            DB::beginTransaction();

            GoodsStockout::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            GoodsStockoutDetail::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Stockout has been validated !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Confirm the stockout (status = 3)
     *
     * @param  string  $stockout_no
     * @return \Illuminate\Http\Response
     */
    public function confirm($stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_stockout.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any stockout !');
        }

        $stockout = GoodsStockout::where('stockout_no', $stockout_no)->first();
        if (!$stockout) {
            session()->flash('error', 'Stockout not found !!');

            return back();
        }
        if ($response = $this->guardStockDocumentConfirm($stockout)) {
            return $response;
        }

        try {
            DB::beginTransaction();

            GoodsStockout::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            GoodsStockoutDetail::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Stockout has been confirmed !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Approve the stockout (status = 4)
     * This will decrease stock, create reports, and make accounting entries
     *
     * @param  string  $stockout_no
     * @return \Illuminate\Http\Response
     */
    public function approuve($stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_stockout.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to approve any stockout !');
        }

        try {
            $stockout = GoodsStockout::where('stockout_no', $stockout_no)->first();

            if (is_null($stockout)) {
                session()->flash('error', 'Stockout not found !!');

                return back();
            }
            if ($response = $this->guardStockDocumentApprove($stockout)) {
                return $response;
            }

            $datas = GoodsStockoutDetail::where('stockout_no', $stockout_no)->with('item')->get();
            $goods_store = GoodsStore::find($stockout->goods_store_id);

            if (is_null($goods_store)) {
                session()->flash('error', ' goods store not found !!');

                return back();
            }

            $sauService = app(SauStockoutJournalService::class);
            $movementType = SauStockoutJournalService::normalizeType($stockout->item_movement_type);
            try {
                $sauService->assertDirectStockoutType($movementType);
            } catch (\RuntimeException $ex) {
                session()->flash('error', $ex->getMessage());

                return back();
            }
            $isSau = $movementType === SauStockoutJournalService::SAU;

            $accountSetting = \App\Models\Accounting\AccountSetting::where('active', 1)->first();
            if (!$goods_store->default_account_id
                || (!$isSau && (!$accountSetting || !$accountSetting->f_saj_account_id))) {
                session()->flash('error', __('messages.approval_accounts_required'));

                return back();
            }

            $code_store = $goods_store->code;
            $prepared = [];

            foreach ($datas as $data) {
                $storeDetail = GoodsStoreDetail::where('code_store', $code_store)
                    ->where('item_id', $data->item_id)
                    ->first();

                if (is_null($storeDetail)) {
                    session()->flash('error', 'Item not found in store: ' . ($data->item ? $data->item->name : 'N/A'));

                    return back();
                }

                if (!$data->item) {
                    session()->flash('error', 'Article manquant sur une ligne du bon de sortie.');

                    return back();
                }

                $quantityStockInitial = $storeDetail->quantity ?? 0;
                $quantityStockout = floatval($data->quantity);

                if ($quantityStockInitial < $quantityStockout) {
                    session()->flash('error', 'Insufficient stock for item: ' . ($data->item ? $data->item->name : 'N/A') . '. Available: ' . $quantityStockInitial . ', Requested: ' . $quantityStockout);

                    return back();
                }

                $valueStockInitial = ($storeDetail->cump ?? 0) * $quantityStockInitial;
                $valueStockout = floatval($data->total_cost_value);
                $quantityTotal = $quantityStockInitial - $quantityStockout;
                $currentCump = (float) ($storeDetail->cump ?? 0);
                if ($isSau && $currentCump > 0) {
                    $valueStockout = round($currentCump * $quantityStockout, 2);
                }

                $currency = $stockout->item_purchase_or_sale_currency ?: 'BIF';
                $movementDate = $data->date
                    ? Carbon::parse($data->date)->toDateTimeString()
                    : Carbon::now()->toDateTimeString();

                $movType = (string) ($data->item_movement_type ?? '');
                $desc = trim((string) ($data->description ?? $stockout->description ?? ''));
                if ($desc === '' && $movType !== 'SV' && $movType !== 'SN') {
                    $desc = 'Mouvement stock ' . ($movType !== '' ? $movType : 'SORTIE') . ' (' . $stockout_no . ')';
                }
                $invRef = $stockout_no;
                if ($movType === 'SC' && preg_match('/\b(FA|FN)\d+\b/i', $desc, $mRef)) {
                    $invRef = strtoupper($mRef[0]);
                }

                $prepared[] = [
                    'storeDetail' => $storeDetail,
                    'quantityStockInitial' => $quantityStockInitial,
                    'quantityStockout' => $quantityStockout,
                    'valueStockInitial' => $valueStockInitial,
                    'valueStockout' => $valueStockout,
                    'quantityTotal' => $quantityTotal,
                    'currentCump' => $currentCump,
                    'data' => $data,
                    'obr' => [
                        'item_code' => (string) ($data->item->code ?? ''),
                        'item_designation' => (string) ($data->item->name ?? ''),
                        'item_quantity' => $data->quantity,
                        'item_measurement_unit' => 'PCS',
                        'item_purchase_or_sale_price' => $currentCump,
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
                $obrResult = $this->postGoodsStockoutObrMovement($row['obr'], $obrToken);
                if (!$obrResult['ok']) {
                    session()->flash('error', $obrResult['message']);

                    return back();
                }
            }
            */

            $resolvedSauLines = [];
            if ($isSau) {
                try {
                    $resolvedSauLines = $sauService->resolveLines(array_map(function ($row) {
                        return [
                            'item' => $row['data']->item,
                            'amount' => $row['valueStockout'],
                            'label' => (string) ($row['data']->item->name ?? ''),
                        ];
                    }, $prepared));
                } catch (\RuntimeException $ex) {
                    session()->flash('error', $ex->getMessage());

                    return back();
                }
            }

            DB::beginTransaction();

            try {
                $totalValue = 0;

                foreach ($prepared as $row) {
                    $storeDetail = $row['storeDetail'];
                    $data = $row['data'];

                    $storeDetail->quantity = $row['quantityTotal'];
                    $storeDetail->updated_by = $this->user->name;
                    $storeDetail->save();

                    $report = new GoodsStoreReport();
                    $report->code_store = $code_store;
                    $report->item_id = $data->item_id;
                    $report->quantity_stock_initial = $row['quantityStockInitial'];
                    $report->value_stock_initial = $row['valueStockInitial'];
                    $report->quantity_stockin = 0;
                    $report->value_stockin = 0;
                    $report->stock_total = $row['quantityStockInitial'];
                    $report->quantity_stockout = $row['quantityStockout'];
                    $report->value_stockout = $row['valueStockout'];
                    $report->quantity_stock_final = $row['quantityTotal'];
                    $report->value_stock_final = $row['currentCump'] * $row['quantityTotal'];
                    $report->cump = $row['currentCump'];
                    $report->date = $data->date;
                    $report->created_by = $this->user->name;
                    $report->type_transaction = $data->item_movement_type;
                    $report->document_no = $stockout_no;
                    $report->description = $data->description;
                    $report->created_at = Carbon::now();
                    $report->save();

                    $totalValue += $row['valueStockout'];
                }

                $stock_account_id = $goods_store->default_account_id;
                if ($isSau) {
                    $sauService->post($stock_account_id, $resolvedSauLines, [
                        'date' => $stockout->date,
                        'document_no' => $stockout_no,
                        'description' => $stockout->description,
                    ]);
                } elseif ($stock_account_id) {
                    $stockLedgerState = $this->updateLedgerTotals($stock_account_id, 'credit', $totalValue);

                    $journalEntryCredit = new AccountJournalEntryDetail();
                    $journalEntryCredit->date = $stockout->date;
                    $journalEntryCredit->document_no = $stockout_no;
                    $journalEntryCredit->description = $stockout->description;
                    $journalEntryCredit->account_id = $stock_account_id;
                    $journalEntryCredit->libelle = $stockout->description;
                    $journalEntryCredit->credit = $totalValue;
                    $journalEntryCredit->balance = $stockLedgerState['balance'];
                    $journalEntryCredit->created_at = Carbon::now();
                    $journalEntryCredit->save();
                }

                $accountSetting = AccountSetting::where('active', 1)->first();
                if (!$isSau && $accountSetting && $accountSetting->f_saj_account_id) {
                    $sajLedgerState = $this->updateLedgerTotals($accountSetting->f_saj_account_id, 'debit', $totalValue);

                    $journalEntryDebit = new AccountJournalEntryDetail();
                    $journalEntryDebit->date = $stockout->date;
                    $journalEntryDebit->document_no = $stockout_no;
                    $journalEntryDebit->description = $stockout->description;
                    $journalEntryDebit->account_id = $accountSetting->f_saj_account_id;
                    $journalEntryDebit->libelle = $stockout->description;
                    $journalEntryDebit->debit = $totalValue;
                    $journalEntryDebit->balance = $sajLedgerState['balance'];
                    $journalEntryDebit->created_at = Carbon::now();
                    $journalEntryDebit->save();
                }

                GoodsStockout::where('stockout_no', '=', $stockout_no)
                    ->update(['status' => 4, 'approuved_by' => $this->user->name]);
                GoodsStockoutDetail::where('stockout_no', '=', $stockout_no)
                    ->update(['status' => 4, 'approuved_by' => $this->user->name]);

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            session()->flash('success', 'Stockout has been approved successfully! Stock decreased in store: ' . $code_store);

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
     * Reject the stockout
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $stockout_no
     * @return \Illuminate\Http\Response
     */
    public function reject(Request $request, $stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_stockout.reject')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any stockout !');
        }

        try {
            DB::beginTransaction();

            GoodsStockout::where('stockout_no', '=', $stockout_no)
                ->update([
                    'status' => -1,
                    'rejected_by' => $this->user->name,
                    'rejected_motif' => $request->rejected_motif
                ]);
            GoodsStockoutDetail::where('stockout_no', '=', $stockout_no)
                ->update([
                    'status' => -1,
                    'rejected_by' => $this->user->name,
                    'rejected_motif' => $request->rejected_motif
                ]);

            DB::commit();
            session()->flash('success', 'Stockout has been rejected !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Reset the stockout
     *
     * @param  string  $stockout_no
     * @return \Illuminate\Http\Response
     */
    public function reset($stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_stockout.reset')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any stockout !');
        }

        try {
            DB::beginTransaction();

            GoodsStockout::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
            GoodsStockoutDetail::where('stockout_no', '=', $stockout_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Stockout has been reseted !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $m
     * @return array{ok: bool, message: string}
     */
    protected function postGoodsStockoutObrMovement(array $m, ?string &$cachedToken): array
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

                return ['ok' => false, 'message' => $this->sanitizeGoodsStockoutObrErrorMessage($rawMsg)];
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Erreur lors de la transmission à l’OBR. Veuillez réessayer plus tard.'];
        }

        return ['ok' => true, 'message' => ''];
    }

    protected function sanitizeGoodsStockoutObrErrorMessage(string $raw): string
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
     * Update ledger totals for accounting
     *
     * @param  int  $accountId
     * @param  string  $type
     * @param  float  $amount
     * @return array
     */
    /**
     * Impression par numéro de document (bon de sortie marchandises).
     */
    public function print($stockout_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_stockout.view')) {
            abort(403, 'Sorry !! You are Unauthorized to print this document !');
        }
        $stockout = GoodsStockout::where('stockout_no', $stockout_no)->first();
        if (!$stockout) {
            session()->flash('error', 'Document not found !!');
            return redirect()->route('admin.goods-stockouts.index');
        }
        $setting = DB::table('settings')->orderBy('created_at', 'desc')->first();
        $datas = GoodsStockoutDetail::where('stockout_no', $stockout_no)->with('item')->get();
        // Utiliser la valeur de coût totale cohérente avec le modèle
        $totalValue = $datas->sum('total_cost_value');
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.goods_stockout', compact('stockout', 'datas', 'setting', 'totalValue'), [], 'UTF-8');
        return $pdf->download('BON_SORTIE_MARCHANDISES_' . $stockout_no . '.pdf');
    }
}
