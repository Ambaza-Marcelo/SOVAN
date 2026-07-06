<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesStockDocumentApproval;
use App\Http\Controllers\Backend\Concerns\PostsObrAddStockMovement;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\GoodsStore;
use App\Models\GoodsStoreDetail;
use App\Models\GoodsStoreInventory;
use App\Models\GoodsStoreInventoryDetail;
use App\Models\GoodsStoreReport;
use App\Support\StockInventoryReportBuilder;
use Carbon\Carbon;
use PDF;
use Validator;

class GoodStoreInventoryController extends Controller
{
    use HandlesStockDocumentApproval;
    use PostsObrAddStockMovement;

    public $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }

    public function index()
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.view')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à voir l\'inventaire marchandises.');
        }

        $inventories = GoodsStoreInventory::with('goodsStore')->orderBy('created_at', 'desc')->get();
        return view('backend.pages.goods_store_inventory.index', compact('inventories'));
    }

    public function create($code)
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.create')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à créer un inventaire marchandises.');
        }

        $store = GoodsStore::where('code', $code)->first();
        if (!$store) {
            session()->flash('error', 'Magasin introuvable.');
            return redirect()->route('admin.goods-store.index');
        }

        $datas = GoodsStoreDetail::where('code_store', $code)->whereNotNull('item_id')->where('item_id', '!=', '')->get();
        $item_ids = $datas->pluck('item_id')->unique()->filter()->values()->all();
        $items = $item_ids ? Item::whereIn('id', $item_ids)->orderBy('name', 'asc')->get() : collect();

        return view('backend.pages.goods_store_inventory.create', compact('datas', 'items', 'code', 'store'));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.create')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à créer un inventaire marchandises.');
        }

        $rules = array(
            'item_id.*' => 'required',
            'date' => 'required|date',
            'title' => 'required',
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

            $item_id = $request->item_id;
            $date = $request->date;
            $quantity = $request->quantity ?? [];
            $purchase_price = $request->purchase_price ?? [];
            $new_quantity = $request->new_quantity;
            $new_purchase_price = $request->new_purchase_price;
            $selling_price = $request->selling_price ?? [];
            $new_selling_price = $request->new_selling_price ?? [];
            $title = $request->title;
            $code_store = $request->code_store;
            $store_id = $request->store_id ?: GoodsStore::where('code', $code_store)->value('id');
            $created_by = $this->user->name;
            $description = $request->description;

            $latest = GoodsStoreInventory::latest()->first();
            if ($latest) {
                $inventory_no = 'GSI' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT));
            } else {
                $inventory_no = 'GSI' . (str_pad(1, 4, '0', STR_PAD_LEFT));
            }

            $inventory_signature = config('app.tin_number_company', '') . Carbon::parse(Carbon::now())->format('YmdHis') . "/" . $inventory_no;

            $insert_data = [];
            for ($count = 0; $count < count($item_id); $count++) {
                $qty = !empty($quantity[$count]) ? $quantity[$count] : 0;
                $price = !empty($purchase_price[$count]) ? $purchase_price[$count] : 0;
                $sell_price = !empty($selling_price[$count]) ? $selling_price[$count] : 0;
                $new_qty = !empty($new_quantity[$count]) ? $new_quantity[$count] : 0;
                $new_price = !empty($new_purchase_price[$count]) ? $new_purchase_price[$count] : 0;
                $new_sell = isset($new_selling_price[$count]) && $new_selling_price[$count] !== '' ? $new_selling_price[$count] : $sell_price;
                $total_cump_value = $qty * $price;
                $new_total_cump_value = $new_qty * $new_price;
                $total_selling_value = $qty * $sell_price;
                $new_total_selling_value = $new_qty * $new_sell;
                $relicat = $qty - $new_qty;

                $insert_data[] = [
                    'item_id' => $item_id[$count],
                    'date' => $date,
                    'title' => $title,
                    'code_store' => $code_store,
                    'store_id' => $store_id,
                    'quantity' => $qty,
                    'purchase_price' => $price,
                    'cump' => $price,
                    'selling_price' => $sell_price,
                    'total_purchase_value' => $qty * $price,
                    'total_cump_value' => $total_cump_value,
                    'total_selling_value' => $total_selling_value,
                    'new_quantity' => $new_qty,
                    'new_purchase_price' => $new_price,
                    'new_cump' => $new_price,
                    'new_selling_price' => $new_sell,
                    'new_total_purchase_value' => $new_total_cump_value,
                    'new_total_cump_value' => $new_total_cump_value,
                    'new_total_selling_value' => $new_total_selling_value,
                    'relicat' => $relicat,
                    'inventory_no' => $inventory_no,
                    'inventory_signature' => $inventory_signature,
                    'created_by' => $created_by,
                    'description' => $description,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }

            GoodsStoreInventoryDetail::insert($insert_data);

            $inventory = new GoodsStoreInventory();
            $inventory->date = $date;
            $inventory->title = $title;
            $inventory->inventory_no = $inventory_no;
            $inventory->code_store = $code_store;
            $inventory->store_id = $store_id;
            $inventory->inventory_signature = $inventory_signature;
            $inventory->description = $description;
            $inventory->created_by = $created_by;
            $inventory->save();

            DB::commit();
            session()->flash('success', 'Inventaire marchandises créé.');
            return redirect()->route('admin.goods-store-inventory.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function show($inventory_no)
    {
        $inventories = GoodsStoreInventoryDetail::where('inventory_no', $inventory_no)->get();
        $code = $inventory_no;
        return view('backend.pages.goods_store_inventory.show', compact('inventories', 'code'));
    }

    public function edit($inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.edit')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à modifier cet inventaire.');
        }

        $inventory = GoodsStoreInventory::where('inventory_no', $inventory_no)->first();
        if (!$inventory) {
            session()->flash('error', 'Inventaire introuvable.');
            return redirect()->route('admin.goods-store-inventory.index');
        }

        if ($inventory->status == 2) {
            session()->flash('error', 'Impossible de modifier un inventaire validé.');
            return redirect()->route('admin.goods-store-inventory.index');
        }

        $inventoryDetails = GoodsStoreInventoryDetail::where('inventory_no', $inventory_no)->get();
        $code = $inventory->code_store;
        $item_ids = $inventoryDetails->pluck('item_id')->unique()->filter()->values()->all();
        $items = $item_ids ? Item::whereIn('id', $item_ids)->orderBy('name', 'asc')->get() : collect();
        $allItems = Item::orderBy('name', 'asc')->get();

        return view('backend.pages.goods_store_inventory.edit', compact('inventory', 'inventoryDetails', 'items', 'allItems', 'code'));
    }

    public function update(Request $request, $inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.edit')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à modifier cet inventaire.');
        }

        $inventory = GoodsStoreInventory::where('inventory_no', $inventory_no)->first();
        if (!$inventory) {
            session()->flash('error', 'Inventaire introuvable.');
            return redirect()->route('admin.goods-store-inventory.index');
        }

        if ($inventory->status == 2) {
            session()->flash('error', 'Impossible de modifier un inventaire validé.');
            return redirect()->route('admin.goods-store-inventory.index');
        }

        $rules = array(
            'item_id.*' => 'required',
            'date' => 'required|date',
            'title' => 'required',
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

            $item_id = $request->item_id;
            $date = Carbon::parse($request->date);
            $quantity = $request->quantity ?? [];
            $purchase_price = $request->purchase_price ?? [];
            $selling_price = $request->selling_price ?? [];
            $new_quantity = $request->new_quantity;
            $new_purchase_price = $request->new_purchase_price;
            $new_selling_price = $request->new_selling_price ?? [];
            $title = $request->title;
            $code_store = $request->code_store;
            $description = $request->description;
            $updated_by = $this->user->name;

            GoodsStoreInventoryDetail::where('inventory_no', $inventory_no)->delete();

            $insert_data = [];
            for ($count = 0; $count < count($item_id); $count++) {
                $qty = isset($quantity[$count]) && $quantity[$count] !== '' ? $quantity[$count] : 0;
                $price = isset($purchase_price[$count]) && $purchase_price[$count] !== '' ? $purchase_price[$count] : 0;
                $sell_price = isset($selling_price[$count]) && $selling_price[$count] !== '' ? $selling_price[$count] : 0;
                $new_qty = !empty($new_quantity[$count]) ? $new_quantity[$count] : 0;
                $new_price_val = !empty($new_purchase_price[$count]) ? $new_purchase_price[$count] : 0;
                $new_sell = isset($new_selling_price[$count]) && $new_selling_price[$count] !== '' ? $new_selling_price[$count] : $sell_price;
                $total_cump_value = $qty * $price;
                $new_total_cump_value = $new_qty * $new_price_val;
                $relicat = $qty - $new_qty;

                $insert_data[] = [
                    'item_id' => $item_id[$count],
                    'date' => $date,
                    'title' => $title,
                    'code_store' => $code_store,
                    'store_id' => $inventory->store_id,
                    'quantity' => $qty,
                    'purchase_price' => $price,
                    'cump' => $price,
                    'selling_price' => $sell_price,
                    'total_purchase_value' => $total_cump_value,
                    'total_cump_value' => $total_cump_value,
                    'total_selling_value' => $qty * $sell_price,
                    'new_quantity' => $new_qty,
                    'new_purchase_price' => $new_price_val,
                    'new_cump' => $new_price_val,
                    'new_selling_price' => $new_sell,
                    'new_total_purchase_value' => $new_total_cump_value,
                    'new_total_cump_value' => $new_total_cump_value,
                    'new_total_selling_value' => $new_qty * $new_sell,
                    'relicat' => $relicat,
                    'inventory_no' => $inventory_no,
                    'inventory_signature' => $inventory->inventory_signature,
                    'created_by' => $inventory->created_by,
                    'updated_by' => $updated_by,
                    'description' => $description,
                    'status' => $inventory->status,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }

            GoodsStoreInventoryDetail::insert($insert_data);

            $inventory->date = $date;
            $inventory->title = $title;
            $inventory->description = $description;
            $inventory->updated_by = $updated_by;
            $inventory->save();

            DB::commit();
            session()->flash('success', 'Inventaire marchandises mis à jour.');
            return redirect()->route('admin.goods-store-inventory.index');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function destroy($inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any inventory !');
        }

        try {
            DB::beginTransaction();

            $inventory = GoodsStoreInventory::where('inventory_no', $inventory_no)->first();
            if (!is_null($inventory)) {
                $inventory->delete();
                GoodsStoreInventoryDetail::where('inventory_no', $inventory_no)->delete();
            }

            DB::commit();
            session()->flash('success', 'Inventaire marchandises supprimé.');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bon_inventaire($inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.view')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé.');
        }

        $inv = GoodsStoreInventory::where('inventory_no', $inventory_no)->first();
        if (!$inv) {
            session()->flash('error', 'Inventaire introuvable.');
            return redirect()->route('admin.goods-store-inventory.index');
        }

        $setting = DB::table('settings')->orderBy('created_at', 'desc')->first();
        $datas = GoodsStoreInventoryDetail::with('item')->where('inventory_no', $inventory_no)->get();
        $title = $inv->title;
        $description = $inv->description;
        $date = $inv->date;
        $inventory_signature = $inv->inventory_signature;
        $gestionnaire = $inv->created_by;
        $totalQuantitActuelle = $datas->sum('quantity');
        $totalQuantitNew = $datas->sum('new_quantity');
        $totalValueActuelle = $datas->sum('total_cump_value');
        $totalValueNew = $datas->sum('new_total_cump_value');

        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.goods_store_inventory', compact('datas', 'inventory_no', 'totalValueActuelle', 'totalValueNew', 'gestionnaire', 'setting', 'title', 'description', 'date', 'inventory_signature', 'totalQuantitActuelle', 'totalQuantitNew'), [], 'UTF-8');
        return $pdf->download('BON INVENTAIRE STOCK MARCHANDISES ' . $inventory_no . '.pdf');
    }

    /**
     * Impression par numéro de document (alias pour bon_inventaire).
     */
    public function print($inventory_no)
    {
        return $this->bon_inventaire($inventory_no);
    }

    public function validateInventory($inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.edit')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à valider cet inventaire.');
        }

        $header = GoodsStoreInventory::where('inventory_no', $inventory_no)->first();
        if (!$header) {
            session()->flash('error', 'Inventaire introuvable.');
            return back();
        }
        if ($response = $this->guardInventoryValidate($header)) {
            return $response;
        }

        $datas = GoodsStoreInventoryDetail::with('item')->where('inventory_no', $inventory_no)->get();
        $movementDate = Carbon::parse($header->date ?? Carbon::now())->toDateTimeString();

        // Envoi OBR désactivé temporairement — à réactiver ultérieurement.
        /*
        $obrError = $this->sendStoreInventoryEiMovementsToObr($datas, $inventory_no, $movementDate);
        if ($obrError !== null) {
            session()->flash('error', $obrError);
            return back();
        }
        */

        try {
            DB::beginTransaction();

            foreach ($datas as $data) {
                $cump = $data->new_cump ?? $data->new_purchase_price;
                $valueStockFinal = $data->new_quantity * $cump;

                GoodsStoreDetail::where('code_store', $data->code_store)->where('item_id', $data->item_id)
                    ->update([
                        'quantity' => $data->new_quantity,
                        'cump' => $cump,
                        'selling_price' => $data->new_selling_price ?? $data->selling_price,
                        'total_cump_value' => $valueStockFinal,
                        'updated_by' => $this->user->name,
                    ]);

                $reportData = array_merge(
                    StockInventoryReportBuilder::movementPayload(
                        (float) ($data->quantity ?? 0),
                        (float) ($data->new_quantity ?? 0),
                        (float) $cump,
                        (float) ($data->total_cump_value ?? 0)
                    ),
                    [
                        'item_id' => $data->item_id,
                        'document_no' => $data->inventory_no,
                        'type_transaction' => 'INVENTAIRE',
                        'description' => $data->description ?? '',
                        'code_store' => $data->code_store,
                        'date' => $data->date ?? now(),
                        'created_by' => $this->user->name,
                    ]
                );

                GoodsStoreReport::insert($reportData);
            }

            GoodsStoreInventory::where('inventory_no', $inventory_no)->update(['status' => 2, 'validated_by' => $this->user->name]);
            GoodsStoreInventoryDetail::where('inventory_no', $inventory_no)->update(['status' => 2, 'validated_by' => $this->user->name]);

            DB::commit();
            session()->flash('success', 'Inventaire marchandises validé.');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function rejectInventory($inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to reject any inventory !');
        }

        try {
            DB::beginTransaction();
            GoodsStoreInventory::where('inventory_no', $inventory_no)->update(['status' => 1, 'rejected_by' => $this->user->name]);
            GoodsStoreInventoryDetail::where('inventory_no', $inventory_no)->update(['status' => 1, 'rejected_by' => $this->user->name]);
            DB::commit();
            session()->flash('success', 'Inventaire marchandises rejeté.');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function resetInventory($inventory_no)
    {
        if (is_null($this->user) || !$this->user->can('material_big_inventory.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to reset any inventory !');
        }

        try {
            DB::beginTransaction();
            GoodsStoreInventory::where('inventory_no', $inventory_no)->update(['status' => 0, 'reseted_by' => $this->user->name]);
            GoodsStoreInventoryDetail::where('inventory_no', $inventory_no)->update(['status' => 0, 'reseted_by' => $this->user->name]);
            DB::commit();
            session()->flash('success', 'Inventaire marchandises réinitialisé.');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
