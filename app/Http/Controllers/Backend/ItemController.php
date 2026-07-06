<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Item;
use App\Models\ItemDetail;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterial;
use App\Models\FinishedGoodsStore;
use App\Models\FinishedGoodsStoreDetail;
use App\Models\FinishedGoodsSmStore;
use App\Models\FinishedGoodsSmStoreDetail;
use App\Models\GoodsStore;
use App\Models\GoodsStoreDetail;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountCurrency;
use App\Models\Accounting\AccountSetting;
use Validator;
use Carbon\Carbon;
use Excel;
use PDF;

class ItemController extends Controller
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
    public function index()
    {
        if (is_null($this->user) || !$this->user->can('item.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any item !');
        }

        $items = Item::with('defaultAccount')->get();
        $finishedGoodsStores = FinishedGoodsStore::select('id', 'name', 'code')->get();
        $finishedGoodsSmStores = FinishedGoodsSmStore::select('id', 'name', 'code')->get();
        $goodsStores = GoodsStore::select('id', 'name', 'code')->get();

        $storeNameByCode = [];
        foreach ($finishedGoodsStores as $s) {
            $storeNameByCode[$s->code] = $s->name;
        }
        foreach ($finishedGoodsSmStores as $s) {
            $storeNameByCode[$s->code] = $s->name;
        }
        foreach ($goodsStores as $s) {
            $storeNameByCode[$s->code] = $s->name;
        }

        return view('backend.pages.item.index', compact('items', 'storeNameByCode'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('item.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any item !');
        }
        $categories = RawMaterialCategory::all();
        $raw_materials = RawMaterial::with('rawMaterialMeasurement')->get();

        $accounts = AccountAccount::all();

        $finishedGoodsStores = FinishedGoodsStore::select('id', 'name', 'code')->get();
        $finishedGoodsSmStores = FinishedGoodsSmStore::select('id', 'name', 'code')->get();
        return view('backend.pages.item.create',compact('categories','raw_materials','accounts','finishedGoodsStores','finishedGoodsSmStores'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('item.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any item !');
        }

        $rules = array(
            'name' => 'required|max:255',
            'default_account_id' => 'required',
            'selling_price' => 'required',
            'cump' => 'required',
            'store_type' => 'required|in:1,2',
        );
        if ($request->store_type == '1') {
            $rules['quantity.*'] = 'required';
            $rules['r_material_id.*'] = 'required';
            $rules['finished_goods_store_selection'] = 'required|string';
        } elseif ($request->store_type == '2') {
            // Pour le stock des marchandises, on choisit si l'article est stockable ou non (1 / 0)
            $rules['status'] = 'required|in:0,1';
        }

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {DB::beginTransaction();

            $store_type = $request->store_type;
            $name = $request->name;
            $cump = $request->cump;
            $selling_price = $request->selling_price;
            $specification = $request->specification;
            $vat = $request->vat;
            $default_account_id = $request->default_account_id;
            $created_by = $this->user->name;

            $artCode = strtoupper(substr($name, 0, 3));
            $code = $artCode.date("y").substr(number_format(time() * mt_rand(), 0, '', ''), 0, 6);

            if ($store_type == '1') {
                $r_material_id = $request->r_material_id ?? $request->food_id ?? [];
                $quantity = $request->quantity ?? [];
                $insert_data = [];
                for ($count = 0; $count < count($r_material_id); $count++) {
                    $insert_data[] = array(
                        'r_material_id' => $r_material_id[$count],
                        'name' => $name,
                        'quantity' => $quantity[$count] ?? 0,
                        'cump' => $cump,
                        'selling_price' => $selling_price,
                        'specification' => $specification,
                        'vat' => $vat,
                        'code' => $code,
                        'default_account_id' => $default_account_id,
                        'created_by' => $created_by,
                        'created_at' => \Carbon\Carbon::now()
                    );
                }
                if (!empty($insert_data)) {
                    ItemDetail::insert($insert_data);
                }
            }

        // Create New Item
        $item = new Item();
        $item->name = $name;
        $item->code = $code;
        $item->cump = $cump;
        $item->selling_price = $selling_price;
        $item->vat = $request->vat;
        $item->default_account_id = $request->default_account_id;
        $item->store_type = $store_type;
        $item->threshold_quantity = 0;
        $item->code_store = $this->resolveItemCodeStore($request, $store_type);
        if ($store_type === Item::STORE_TYPE_FINISHED_GOODS && !$item->code_store) {
            return response()->json(['error' => [__('messages.finished_goods_store_selection_help')]], 422);
        }
        // Pour les produits finis (store_type = 1), toujours stockable.
        // Pour le stock des marchandises (store_type = 2), on utilise la valeur choisie (1 = stockable, 0 = non stockable).
        $item->status = $store_type == '2'
            ? (string)($request->input('status', '1'))
            : '1';
        $item->created_by = $this->user->name;
        $item->save();

        // Sync item to the correct store only (produits finis OR marchandises, not both)
        $this->syncItemToStoreDetail($item, $name, $code, $vat, $selling_price, $cump, $default_account_id, $specification, $created_by, $request->expiration_date ?? null);

        DB::commit();
            session()->flash('success', 'Item has been created !!');
            return redirect()->route('admin.items.index');
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
    public function show($code)
    {
        //
        if (is_null($this->user) || !$this->user->can('item.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any item !');
        }

        $datas = ItemDetail::with('rawMaterial.rawMaterialMeasurement')->where('code',$code)->get();
        return view('backend.pages.item.show', compact('datas'));
    }

    /**
     * Display the technical sheet (fiche technique) for an item.
     *
     * @param  string  $code
     * @return \Illuminate\Http\Response
     */
    public function fiche($code)
    {
        if (is_null($this->user) || !$this->user->can('item.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any item !');
        }

        $datas = Item::where('code', $code)->get();
        $item_details = ItemDetail::with('rawMaterial.rawMaterialMeasurement')->where('code', $code)->get();
        return view('backend.pages.document.fiche_technique', compact('datas', 'item_details'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($code)
    {
        if (is_null($this->user) || !$this->user->can('item.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any item !');
        }
        $categories = RawMaterialCategory::all();
        $item = Item::where('code',$code)->first();
        if (!$item) {
            abort(404, 'Article introuvable.');
        }
        $raw_materials = RawMaterial::with('rawMaterialMeasurement')->get();
        $accounts = AccountAccount::all();
        $datas = ItemDetail::with('rawMaterial.rawMaterialMeasurement')->where('code',$code)->get();
        // Lock store_type only if the item already exists in one of the store details (produits finis or marchandises)
        $store_type_locked = FinishedGoodsStoreDetail::where('item_id', $item->id)->exists()
            || FinishedGoodsSmStoreDetail::where('item_id', $item->id)->exists()
            || GoodsStoreDetail::where('item_id', $item->id)->exists();
        $finishedGoodsStores = FinishedGoodsStore::select('id', 'name', 'code')->get();
        $finishedGoodsSmStores = FinishedGoodsSmStore::select('id', 'name', 'code')->get();
        return view('backend.pages.item.edit', compact(
            'item','categories','raw_materials','datas','accounts','store_type_locked','finishedGoodsStores','finishedGoodsSmStores'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $code)
    {
        if (is_null($this->user) || !$this->user->can('item.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any item !');
        }
        

        // Validation Data
        $rules = array(
            'name' => 'required|max:255',
            'default_account_id' => 'required',
            'selling_price' => 'required',
            'cump' => 'required',
            'store_type' => 'required|in:1,2',
        );
        if ($request->store_type == '1') {
            $rules['quantity.*'] = 'required';
            $rules['r_material_id.*'] = 'required';
            $rules['finished_goods_store_selection'] = 'required|string';
        } elseif ($request->store_type == '2') {
            // Pour le stock des marchandises, on choisit si l'article est stockable ou non (1 / 0)
            $rules['status'] = 'required|in:0,1';
        }

            $error = Validator::make($request->all(),$rules);

            if($error->fails()){
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            $existingItem = Item::where('code', $code)->first();
            if (!$existingItem) {
                return response()->json(['error' => ['Item not found.']], 404);
            }
            // Refuse changing store_type only if the item already exists in one of the store details
            $existsInFinishedGoods = FinishedGoodsStoreDetail::where('item_id', $existingItem->id)->exists()
                || FinishedGoodsSmStoreDetail::where('item_id', $existingItem->id)->exists();
            $existsInGoods = GoodsStoreDetail::where('item_id', $existingItem->id)->exists();
            if (($existsInFinishedGoods || $existsInGoods) && $request->store_type != $existingItem->store_type) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['error' => [__('messages.item_store_type_cannot_change')]], 422);
                }
                session()->flash('error', __('messages.item_store_type_cannot_change'));
                return redirect()->back()->withInput();
            }

            try {DB::beginTransaction();

            $name = $request->name;
            $cump = $request->cump;
            $selling_price = $request->selling_price;
            $specification = $request->specification;
            $default_account_id = $request->default_account_id;
            $store_type = $request->store_type;
            $vat = $request->vat;
            $created_by = $this->user->name;

            ItemDetail::where('code', $code)->delete();

            if ($store_type == '1') {
                $r_material_id = $request->r_material_id ?? $request->food_id ?? [];
                $quantity = $request->quantity ?? [];
                $insert_data = [];
                for ($count = 0; $count < count($r_material_id); $count++) {
                    $insert_data[] = array(
                        'r_material_id' => $r_material_id[$count],
                        'name' => $name,
                        'quantity' => $quantity[$count] ?? 0,
                        'cump' => $cump,
                        'selling_price' => $selling_price,
                        'specification' => $specification,
                        'vat' => $vat,
                        'code' => $code,
                        'default_account_id' => $default_account_id,
                        'created_by' => $created_by,
                        'created_at' => \Carbon\Carbon::now()
                    );
                }
                if (!empty($insert_data)) {
                    ItemDetail::insert($insert_data);
                }
            }

        $item = Item::where('code',$code)->first();

        $item->name = $name;
        $item->cump = $cump;
        $item->selling_price = $selling_price;
        $item->vat = $request->vat;
        $item->default_account_id = $request->default_account_id;
        $item->store_type = $store_type;
        $item->threshold_quantity = 0;
        $item->code_store = $this->resolveItemCodeStore($request, $store_type);
        if ($store_type === Item::STORE_TYPE_FINISHED_GOODS && !$item->code_store) {
            return response()->json(['error' => [__('messages.finished_goods_store_selection_help')]], 422);
        }
        // Mise à jour du statut :
        // - store_type = 1 : toujours stockable (1)
        // - store_type = 2 : valeur choisie dans le formulaire (1 / 0)
        $item->status = $store_type == '2'
            ? (string)($request->input('status', (string)($item->status ?? '1')))
            : '1';
        $item->created_by = $this->user->name;
        $item->save();

        // Sync item to the correct store only (remove from other store, add/update in chosen store)
        $this->syncItemToStoreDetail($item, $name, $code, $vat, $selling_price, $cump, $default_account_id, $specification, $created_by, $request->expiration_date ?? null);

        DB::commit();
            session()->flash('success', 'Item has been updated !!');
            return redirect()->route('admin.items.index');
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
     * @param  int  $code
     * @return \Illuminate\Http\Response
     */
    public function destroy($code)
    {
        if (is_null($this->user) || !$this->user->can('item.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any item !');
        }

        try {DB::beginTransaction();

        $item = Item::where('code',$code)->first();
        if (!is_null($item)) {
            // Remove from both store types so the item does not remain in any stock
            FinishedGoodsStoreDetail::where('item_id', $item->id)->delete();
            FinishedGoodsSmStoreDetail::where('item_id', $item->id)->delete();
            GoodsStoreDetail::where('item_id', $item->id)->delete();
            $item->delete();
            ItemDetail::where('code',$code)->delete();
        }

        DB::commit();
            session()->flash('success', 'Item has been deleted !!');
            return back();
        } catch (\Exception $e) {
            // An error occured; cancel the transaction...

            DB::rollback();

            // and throw the error again.

            throw $e;
        }

    }

    /**
     * Toggle item status (activate/deactivate)
     *
     * @param  string  $code
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus($code)
    {
        if (is_null($this->user) || !$this->user->can('item.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to change item status !');
        }

        try {
            $item = Item::where('code', $code)->firstOrFail();
            
            // Toggle status
            if ($item->isActive()) {
                $item->status = 'inactive';
                $message = 'Item has been deactivated !!';
                // Remove from both store types when deactivated
                FinishedGoodsStoreDetail::where('item_id', $item->id)->delete();
                FinishedGoodsSmStoreDetail::where('item_id', $item->id)->delete();
                GoodsStoreDetail::where('item_id', $item->id)->delete();
            } else {
                $item->status = 'active';
                $message = 'Item has been activated !!';
                // Re-add only to the store that matches store_type (produits finis or marchandises)
                $this->syncItemToStoreDetail(
                    $item,
                    $item->name,
                    $item->code,
                    $item->vat ?? 0,
                    $item->selling_price ?? 0,
                    $item->cump ?? 0,
                    $item->default_account_id,
                    $item->specification,
                    $this->user->name,
                    $item->expiration_date ?? null
                );
            }
            
            $item->updated_by = $this->user->name;
            $item->save();
            
            session()->flash('success', $message);
            return redirect()->route('admin.items.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Something went wrong !!');
            return redirect()->back();
        }
    }

    /**
     * Sync item to the correct store detail only (produits finis OR marchandises).
     * Removes from the other store so the item appears in exactly one stock.
     */
    protected function syncItemToStoreDetail(Item $item, string $name, string $code, $vat, $selling_price, $cump, $default_account_id, $specification, string $created_by, $expiration_date = null): void
    {
        $store_type = $item->store_type ?? null;
        if (!$store_type || !in_array($store_type, ['1', '2'], true)) {
            return;
        }

        // HARD GUARANTEE: an item must exist in ONE stock only.
        // We delete all existing store-detail rows for this item first, then re-create in the chosen store.
        FinishedGoodsStoreDetail::where('item_id', $item->id)->delete();
        FinishedGoodsSmStoreDetail::where('item_id', $item->id)->delete();
        GoodsStoreDetail::where('item_id', $item->id)->delete();

        if (!$item->isActive()) {
            return;
        }

        if ($store_type === Item::STORE_TYPE_FINISHED_GOODS) {
            // Determine which finished goods store to target using item.code_store:
            // - If code_store exists in finished_goods_sm_stores => use FinishedGoodsSmStoreDetail
            // - Else => use FinishedGoodsStoreDetail
            $targetCode = $item->code_store;
            $targetIsSm = $targetCode ? FinishedGoodsSmStore::where('code', $targetCode)->exists() : false;

            if ($targetIsSm) {
                $store = $targetCode ? FinishedGoodsSmStore::where('code', $targetCode)->first() : FinishedGoodsSmStore::first();
                if (!$store) return;
                $detail = new FinishedGoodsSmStoreDetail();
                $detail->name = $name;
                $detail->code = $code;
                $detail->code_store = $store->code;
                $detail->item_id = $item->id;
                $detail->quantity = 0;
                $detail->created_by = $created_by;
                $detail->vat = $vat;
                $detail->selling_price = $selling_price;
                $detail->cump = $cump;
                $detail->total_cump_value = 0;
                $detail->expiration_date = $expiration_date;
                $detail->default_account_id = $default_account_id;
                $detail->specification = $specification;
                $detail->updated_by = $this->user->name;
                $detail->save();
                return;
            }

            $store = $targetCode ? FinishedGoodsStore::where('code', $targetCode)->first() : FinishedGoodsStore::first();
            if (!$store) return;
            $detail = new FinishedGoodsStoreDetail();
            $detail->name = $name;
            $detail->code = $code;
            $detail->code_store = $store->code;
            $detail->item_id = $item->id;
            $detail->quantity = 0;
            $detail->created_by = $created_by;
            $detail->vat = $vat;
            $detail->selling_price = $selling_price;
            $detail->cump = $cump;
            $detail->expiration_date = $expiration_date;
            $detail->default_account_id = $default_account_id;
            $detail->specification = $specification;
            $detail->updated_by = $this->user->name;
            $detail->save();
            return;
        }

        // store_type == 2: GoodsStoreDetail (stock des marchandises)
        $store = GoodsStore::first();
        if (!$store) return;
        $detail = new GoodsStoreDetail();
        $detail->name = $name;
        $detail->code = $code;
        $detail->code_store = $store->code;
        $detail->item_id = $item->id;
        $detail->quantity = 0;
        $detail->created_by = $created_by;
        $detail->vat = $vat;
        // Pour les articles non stockables (status = 0) dans le stock des marchandises,
        // on laisse les quantités/valeurs à 0 pour indiquer qu'ils ne sont pas suivis en stock.
        if ((string)$item->status === '0') {
            $detail->quantity = 0;
            $detail->selling_price = 0;
            $detail->cump = 0;
        } else {
            $detail->selling_price = $selling_price;
            $detail->cump = $cump;
        }
        $detail->expiration_date = $expiration_date;
        $detail->default_account_id = $default_account_id;
        $detail->specification = $specification;
        $detail->status = (string)$item->status;
        $detail->updated_by = $this->user->name;
        $detail->save();
    }

    /**
     * Resolve the selected store code (items.code_store).
     * - store_type=1 => user must choose between FinishedGoodsStore and FinishedGoodsSmStore
     * - store_type=2 => use the (unique) GoodsStore code
     */
    protected function resolveItemCodeStore(Request $request, string $store_type): ?string
    {
        if ($store_type === Item::STORE_TYPE_GOODS) {
            $store = GoodsStore::first();
            return $store ? $store->code : null;
        }

        $selection = (string)($request->input('finished_goods_store_selection', ''));
        if (!$selection) {
            return null;
        }

        // format: fg:{code} or fgsm:{code}
        $parts = explode(':', $selection, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $prefix = $parts[0];
        $code = $parts[1] ?: null;
        if (!$code) {
            return null;
        }

        if ($prefix === 'fgsm') {
            return FinishedGoodsSmStore::where('code', $code)->exists() ? $code : null;
        }

        if ($prefix === 'fg') {
            return FinishedGoodsStore::where('code', $code)->exists() ? $code : null;
        }

        return null;
    }
}
