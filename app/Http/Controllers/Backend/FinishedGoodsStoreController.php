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
use App\Models\Item;
use App\Models\FinishedGoodsStore;
use App\Models\FinishedGoodsStoreDetail;
use App\Exports\FinishedGoodsStoreExport;
use App\Models\Accounting\AccountAccount;
use Carbon\Carbon;
use Excel;
use PDF;

class FinishedGoodsStoreController extends Controller
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
        if (is_null($this->user) || !$this->user->can('finished_product_stock.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any finished goods store !');
        }

        $finished_goods = FinishedGoodsStore::with('defaultAccount')->get();
        $hasRecord = FinishedGoodsStore::count() > 0;
        return view('backend.pages.finished_goods_store.index', compact('finished_goods', 'hasRecord'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('finished_product_stock.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any finished goods store !');
        }

        // Check if a record already exists
        if (FinishedGoodsStore::count() > 0) {
            session()->flash('error', 'Only one Finished Goods Store record is allowed. Please edit the existing record instead.');
            return redirect()->route('admin.finished-goods-store.index');
        }

        return view('backend.pages.finished_goods_store.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_stock.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any finished goods store !');
        }

        // Check if a record already exists
        if (FinishedGoodsStore::count() > 0) {
            session()->flash('error', 'Only one Finished Goods Store record is allowed. Please edit the existing record instead.');
            return redirect()->route('admin.finished-goods-store.index');
        }

        // Validation Data
        $request->validate([
            'name' => 'required|max:255',
            'emplacement' => 'required|max:255',
            'manager' => 'nullable|max:255',
            'description' => 'nullable',
            'default_account_id' => 'required'
        ]);

        // Create New store
        try {
            DB::beginTransaction();

            $finished_goods_store = new FinishedGoodsStore();
            $finished_goods_store->name = $request->name;
            $reference = strtoupper(substr($request->name, 0, 3));
            $finished_goods_store->code = $reference.date("y").substr(number_format(time() * mt_rand(), 0, '', ''), 0, 6);
            $store_signature = config('app.tin_number_company').Carbon::parse(Carbon::now())->format('YmdHis')."/".$finished_goods_store->code;
            $finished_goods_store->store_signature = $store_signature;
            $finished_goods_store->emplacement = $request->emplacement;
            $finished_goods_store->manager = $request->manager;
            $finished_goods_store->description = $request->description;
            $finished_goods_store->default_account_id = $request->default_account_id;
            $finished_goods_store->created_by = $this->user->name;
            $finished_goods_store->save();

            DB::commit();
            session()->flash('success', 'Finished Goods Store has been created !!');
            return redirect()->route('admin.finished-goods-store.index');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Something went wrong !!');
            return redirect()->back();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $code
     * @return \Illuminate\Http\Response
     */
    public function show($code)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_big_store.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any finished goods store !');
        }

        $finished_goods_store = FinishedGoodsStore::where('code', $code)->first();
        if (!$finished_goods_store) {
            abort(404, 'Finished Goods Store not found');
        }

        // Get details and filter out items that are inactive
        $finished_goods_store_details = FinishedGoodsStoreDetail::where('code_store', $code)
            ->with('item')
            ->get()
            ->filter(function($detail) {
                // Only show details where item is active or has no item_id (legacy records)
                return !$detail->item_id || ($detail->item && $detail->item->isActive());
            });
        
        $hasRecord = FinishedGoodsStore::count() > 0;
        
        return view('backend.pages.finished_goods_store.show', compact(
            'finished_goods_store', 'finished_goods_store_details', 'hasRecord'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_stock.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any finished goods store !');
        }

        $finished_goods_store = FinishedGoodsStore::findOrFail($id);
        return view('backend.pages.finished_goods_store.edit', compact('finished_goods_store'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_stock.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any finished goods store !');
        }

        // Validation Data
        $request->validate([
            'name' => 'required|max:255',
            'emplacement' => 'required|max:255',
            'manager' => 'nullable|max:255',
            'description' => 'nullable',
            'default_account_id' => 'required'
        ]);

        try {
            DB::beginTransaction();

            $finished_goods_store = FinishedGoodsStore::findOrFail($id);
            $finished_goods_store->name = $request->name;
            $finished_goods_store->emplacement = $request->emplacement;
            $finished_goods_store->manager = $request->manager;
            $finished_goods_store->description = $request->description;
            $finished_goods_store->default_account_id = $request->default_account_id;
            $finished_goods_store->updated_by = $this->user->name;
            $finished_goods_store->save();

            DB::commit();
            session()->flash('success', 'Finished Goods Store has been updated !!');
            return redirect()->route('admin.finished-goods-store.index');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Something went wrong !!');
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_stock.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any finished goods store !');
        }

        try {
            $finished_goods_store = FinishedGoodsStore::findOrFail($id);
            $finished_goods_store->delete();
            session()->flash('success', 'Finished Goods Store has been deleted !!');
            return redirect()->route('admin.finished-goods-store.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Something went wrong !!');
            return redirect()->back();
        }
    }

    /**
     * Remove the specified detail record from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyDetail($id)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_stock.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any finished goods store detail !');
        }

        try {
            $finished_goods_store_detail = FinishedGoodsStoreDetail::findOrFail($id);
            $code = $finished_goods_store_detail->code_store;
            $finished_goods_store_detail->delete();
            session()->flash('success', 'Finished Goods Store Detail has been deleted !!');
            return redirect()->route('admin.finished-goods-store.show', $code);
        } catch (\Exception $e) {
            session()->flash('error', 'Something went wrong !!');
            return redirect()->back();
        }
    }

    /**
     * Impression par code du magasin (état du stock produits finis).
     */
    public function print($code)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_stock.view')) {
            abort(403, 'Sorry !! You are Unauthorized to print this document !');
        }
        $finished_goods_store = FinishedGoodsStore::where('code', $code)->first();
        if (!$finished_goods_store) {
            session()->flash('error', 'Store not found !!');
            return redirect()->route('admin.finished-goods-store.index');
        }
        $datas = FinishedGoodsStoreDetail::where('code_store', $code)->with('item')->get();
        $setting = DB::table('settings')->orderBy('created_at', 'desc')->first();
        $totalValue = $datas->sum('total_cump_value');
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.finished_goods_store', compact('finished_goods_store', 'datas', 'setting', 'totalValue'), [], 'UTF-8');
        return $pdf->download('ETAT_STOCK_PRODUITS_FINIS_' . $code . '.pdf');
    }
}
