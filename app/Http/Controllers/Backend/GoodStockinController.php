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
use App\Models\GoodsStockin;
use App\Models\GoodsStockinDetail;
use App\Models\GoodsStore;
use App\Models\GoodsStoreDetail;
use App\Models\GoodsStoreReport;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountSetting;
use Carbon\Carbon;
use PDF;
use App\Services\Stock\StockCumpCalculator;
use Validator;

class GoodStockinController extends Controller
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
    public function index(\Illuminate\Http\Request $request)
    {
        if (is_null($this->user) || !$this->user->can('goods_stockin.view')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à voir les entrées marchandises.');
        }

        $query = GoodsStockin::with('goodsStore')->orderBy('id','desc');
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = \Carbon\Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = \Carbon\Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('date', [$dateDebut, $dateFin]);
        } else {
            $query->take(100);
        }
        $stockins = $query->get();
        return view('backend.pages.goods_stockin.index', compact('stockins'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('goods_stockin.create')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à créer une entrée marchandises.');
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
        return view('backend.pages.goods_stockin.create', compact('items', 'goods_stores'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('goods_stockin.create')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à créer une entrée marchandises.');
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
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $origin = $request->origin;
            $goods_store_id = $request->goods_store_id;
            $description = $request->description; 
            $item_movement_type = $request->item_movement_type; 
            $quantity = $request->quantity;
            $cost_price = $request->cost_price;

            $latest = GoodsStockin::orderBy('id','desc')->first();
            if ($latest) {
               $stockin_no = 'GS-BE' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $stockin_no = 'GS-BE' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

            $stockin_signature = $stockin_no.'-'.Carbon::parse($date)->format('Y-m-d-H-i-s');
            $created_by = $this->user->name;

            $stockin = new GoodsStockin();
            $stockin->date = $date;
            $stockin->stockin_no = $stockin_no;
            $stockin->stockin_signature = $stockin_signature;
            $stockin->store_type = null;
            $stockin->receptionist = $receptionist;
            $stockin->handingover = $handingover;
            $stockin->origin = $origin;
            $stockin->item_purchase_or_sale_currency = $item_purchase_or_sale_currency;
            $stockin->created_by = $created_by;
            $stockin->goods_store_id = $goods_store_id;
            $stockin->status = 1;
            $stockin->item_movement_type = $item_movement_type;
            $stockin->description = $description;
            $stockin->created_at = Carbon::now();
            $stockin->save();

            for( $count = 0; $count < count($item_id); $count++ ){
                $total_cost_value = $quantity[$count] * $cost_price[$count];
                $total_value = $total_cost_value;

                $stockinDetail = new GoodsStockinDetail();
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
                $stockinDetail->store_type = null;
                $stockinDetail->description = $description;
                $stockinDetail->created_by = $created_by;
                $stockinDetail->goods_store_id = $goods_store_id;
                $stockinDetail->item_id = $item_id[$count];
                $stockinDetail->status = 1;
                $stockinDetail->created_at = Carbon::now();
                $stockinDetail->save();
            }

            DB::commit();
            session()->flash('success', 'Entrée marchandises créée avec succès.');
            return redirect()->route('admin.goods-stockins.index');
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
        $stockin = GoodsStockin::where('stockin_no', $stockin_no)->first();
        if (is_null($stockin)) {
            session()->flash('error', 'Entrée introuvable.');
            return redirect()->route('admin.goods-stockins.index');
        }

        $stockinDetails = GoodsStockinDetail::with(['item'])
            ->where('stockin_no', $stockin_no)->get();
        
        $totalQuantity = $stockinDetails->sum('quantity');
        $totalCostValue = $stockinDetails->sum('total_cost_value');
        
        return view('backend.pages.goods_stockin.show', compact('stockin', 'stockinDetails', 'totalQuantity', 'totalCostValue'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $stockin_no
     * @return \Illuminate\Http\Response
     */
    public function edit($stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_stockin.edit')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à modifier cette entrée.');
        }

        $stockin = GoodsStockin::where('stockin_no', $stockin_no)->first();
        
        if (is_null($stockin)) {
            session()->flash('error', 'Entrée introuvable.');
            return redirect()->route('admin.goods-stockins.index');
        }

        if ($stockin->status == 4) {
            session()->flash('error', 'Impossible de modifier une entrée approuvée.');
            return redirect()->route('admin.goods-stockins.index');
        }

        $stockinDetails = GoodsStockinDetail::with(['item'])
            ->where('stockin_no', $stockin_no)->get();

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

        return view('backend.pages.goods_stockin.edit', compact('stockin', 'stockinDetails', 'items', 'goods_stores'));
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
        if (is_null($this->user) || !$this->user->can('goods_stockin.edit')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à modifier cette entrée.');
        }

        $stockin = GoodsStockin::where('stockin_no', $stockin_no)->first();
        
        if (is_null($stockin)) {
            session()->flash('error', 'Entrée introuvable.');
            return redirect()->route('admin.goods-stockins.index');
        }

        if ($stockin->status == 4) {
            session()->flash('error', 'Impossible de modifier une entrée approuvée.');
            return redirect()->route('admin.goods-stockins.index');
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
            $handingover = $request->handingover;
            $receptionist = $request->receptionist;
            $origin = $request->origin;
            $goods_store_id = $request->goods_store_id;
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
            $stockin->goods_store_id = $goods_store_id;
            $stockin->item_movement_type = $item_movement_type;
            $stockin->description = $description;
            $stockin->updated_at = Carbon::now();
            $stockin->save();

            GoodsStockinDetail::where('stockin_no', $stockin_no)->delete();

            for( $count = 0; $count < count($item_id); $count++ ){
                $total_cost_value = $quantity[$count] * $cost_price[$count];
                $total_value = $total_cost_value;

                $stockinDetail = new GoodsStockinDetail();
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
                $stockinDetail->store_type = null;
                $stockinDetail->description = $description;
                $stockinDetail->created_by = $stockin->created_by;
                $stockinDetail->updated_by = $updated_by;
                $stockinDetail->goods_store_id = $goods_store_id;
                $stockinDetail->item_id = $item_id[$count];
                $stockinDetail->status = $stockin->status;
                $stockinDetail->created_at = Carbon::now();
                $stockinDetail->save();
            }

            DB::commit();
            session()->flash('success', 'Entrée marchandises mise à jour.');
            return redirect()->route('admin.goods-stockins.index');
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
        if (is_null($this->user) || !$this->user->can('goods_stockin.delete')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à supprimer cette entrée.');
        }

        $stockin = GoodsStockin::where('stockin_no',$stockin_no)->first();
        
        if (is_null($stockin)) {
            session()->flash('error', 'Entrée introuvable.');
            return back();
        }

        if ($stockin->status == 4) {
            session()->flash('error', 'Impossible de supprimer une entrée approuvée.');
            return back();
        }

        try {
            DB::beginTransaction();

            if (!is_null($stockin)) {
                $stockin->delete();
                GoodsStockinDetail::where('stockin_no',$stockin_no)->delete();
            }

            DB::commit();
            session()->flash('success', 'Entrée marchandises supprimée.');
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
        if (is_null($this->user) || !$this->user->can('goods_stockin.validate')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à valider cette entrée.');
        }

        $stockin = GoodsStockin::where('stockin_no', $stockin_no)->first();
        if (!$stockin) {
            session()->flash('error', 'Entrée introuvable.');

            return back();
        }
        if ($response = $this->guardStockDocumentValidate($stockin)) {
            return $response;
        }

        try {
            DB::beginTransaction();

            GoodsStockin::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);
            GoodsStockinDetail::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 2,'validated_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Entrée marchandises validée.');
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
        if (is_null($this->user) || !$this->user->can('goods_stockin.confirm')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à confirmer cette entrée.');
        }

        $stockin = GoodsStockin::where('stockin_no', $stockin_no)->first();
        if (!$stockin) {
            session()->flash('error', 'Entrée introuvable.');

            return back();
        }
        if ($response = $this->guardStockDocumentConfirm($stockin)) {
            return $response;
        }

        try {
            DB::beginTransaction();

            GoodsStockin::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);
            GoodsStockinDetail::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 3,'confirmed_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Entrée marchandises confirmée.');
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
        if (is_null($this->user) || !$this->user->can('goods_stockin.approuve')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à approuver cette entrée.');
        }

        try {
            $stockin = GoodsStockin::where('stockin_no', $stockin_no)->first();

            if (is_null($stockin)) {
                session()->flash('error', 'Entrée introuvable.');

                return back();
            }
            if ($response = $this->guardStockDocumentApprove($stockin)) {
                return $response;
            }

            $datas = GoodsStockinDetail::where('stockin_no', $stockin_no)->with('item')->get();
            $goods_store = GoodsStore::find($stockin->goods_store_id);

            if (is_null($goods_store)) {
                session()->flash('error', 'Magasin marchandises introuvable.');

                return back();
            }

            $accountSetting = \App\Models\Accounting\AccountSetting::where('active', 1)->first();
            if (!$goods_store->default_account_id || !$accountSetting || !$accountSetting->f_eaj_account_id) {
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
                    session()->flash('error', 'Article introuvable dans le magasin: ' . ($data->item ? $data->item->name : 'N/A'));

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
                $obrResult = $this->postGoodsStockinObrMovement($row['obr'], $obrToken);
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
                    /** @var GoodsStoreDetail $storeDetail */
                    $storeDetail = $row['storeDetail'];
                    $data = $row['data'];

                    $storeDetail->quantity = $row['quantityTotal'];
                    $storeDetail->cump = $row['newCump'];
                    $storeDetail->selling_price = $data->item->selling_price ?? $storeDetail->selling_price;
                    $storeDetail->updated_by = $this->user->name;
                    $storeDetail->save();

                    $report = new GoodsStoreReport();
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

                $stock_account_id = $goods_store->default_account_id;
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

                GoodsStockin::where('stockin_no', '=', $stockin_no)
                    ->update(['status' => 4, 'approuved_by' => $this->user->name]);
                GoodsStockinDetail::where('stockin_no', '=', $stockin_no)
                    ->update(['status' => 4, 'approuved_by' => $this->user->name]);

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            session()->flash('success', 'Entrée marchandises approuvée. Stock mis à jour: ' . $code_store);

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
        if (is_null($this->user) || !$this->user->can('goods_stockin.reject')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à rejeter cette entrée.');
        }

        try {
            DB::beginTransaction();

            GoodsStockin::where('stockin_no', '=', $stockin_no)
                ->update([
                    'status' => -1,
                    'rejected_by' => $this->user->name,
                    'rejected_motif' => $request->rejected_motif
                ]);
            GoodsStockinDetail::where('stockin_no', '=', $stockin_no)
                ->update([
                    'status' => -1,
                    'rejected_by' => $this->user->name,
                    'rejected_motif' => $request->rejected_motif
                ]);

            DB::commit();
            session()->flash('success', 'Entrée marchandises rejetée.');
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
        if (is_null($this->user) || !$this->user->can('goods_stockin.reset')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à réinitialiser cette entrée.');
        }

        try {
            DB::beginTransaction();

            GoodsStockin::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);
            GoodsStockinDetail::where('stockin_no', '=', $stockin_no)
                ->update(['status' => 1,'reseted_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Entrée marchandises réinitialisée.');
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
    protected function postGoodsStockinObrMovement(array $m, ?string &$cachedToken): array
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

                return ['ok' => false, 'message' => $this->sanitizeGoodsStockinObrErrorMessage($rawMsg)];
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Erreur lors de la transmission à l’OBR. Veuillez réessayer plus tard.'];
        }

        return ['ok' => true, 'message' => ''];
    }

    protected function sanitizeGoodsStockinObrErrorMessage(string $raw): string
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
     * Impression par numéro de document (bon d'entrée produits finis).
     */
    public function print($stockin_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_stockin.view')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à imprimer ce document.');
        }
        $stockin = GoodsStockin::where('stockin_no', $stockin_no)->first();
        if (!$stockin) {
            session()->flash('error', 'Document introuvable.');
            return redirect()->route('admin.goods-stockins.index');
        }
        $setting = DB::table('settings')->orderBy('created_at', 'desc')->first();
        $datas = GoodsStockinDetail::where('stockin_no', $stockin_no)->with('item')->get();
        $totalValue = $datas->sum('total_cost_value');
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.goods_stockin', compact('stockin', 'datas', 'setting', 'totalValue'), [], 'UTF-8');
        return $pdf->download('BON_ENTREE_MARCHANDISES_' . $stockin_no . '.pdf');
    }
}
