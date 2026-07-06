<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\FinishedGoodsSmStore;
use App\Models\FinishedGoodsSmStoreDetail;
use App\Models\Accounting\AccountAccount;
use Carbon\Carbon;
use PDF;

class FinishedGoodsSmStoreController extends Controller
{
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
        if (is_null($this->user) || !$this->user->can('finished_product_sm_stock.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any finished goods SM store !');
        }

        $finished_goods = FinishedGoodsSmStore::with('defaultAccount')->get();
        $hasRecord = FinishedGoodsSmStore::count() > 0;

        return view('backend.pages.finished_goods_sm_store.index', compact('finished_goods', 'hasRecord'));
    }

    public function create()
    {
        if (is_null($this->user) || !$this->user->can('finished_product_sm_stock.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any finished goods SM store !');
        }

        // même règle que le premier stock : un seul magasin SM
        if (FinishedGoodsSmStore::count() > 0) {
            session()->flash('error', 'Only one Finished Goods SM Store record is allowed. Please edit the existing record instead.');
            return redirect()->route('admin.finished-goods-sm-store.index');
        }

        return view('backend.pages.finished_goods_sm_store.create');
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_sm_stock.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any finished goods SM store !');
        }

        if (FinishedGoodsSmStore::count() > 0) {
            session()->flash('error', 'Only one Finished Goods SM Store record is allowed. Please edit the existing record instead.');
            return redirect()->route('admin.finished-goods-sm-store.index');
        }

        $request->validate([
            'name' => 'required|max:255',
            'emplacement' => 'required|max:255',
            'manager' => 'nullable|max:255',
            'description' => 'nullable',
            'default_account_id' => 'required'
        ]);

        try {
            DB::beginTransaction();

            $store = new FinishedGoodsSmStore();
            $store->name = $request->name;
            $reference = strtoupper(substr($request->name, 0, 3));
            $store->code = $reference . date("y") . substr(number_format(time() * mt_rand(), 0, '', ''), 0, 6);
            $store->store_signature = config('app.tin_number_company') . Carbon::parse(Carbon::now())->format('YmdHis') . "/" . $store->code;
            $store->emplacement = $request->emplacement;
            $store->manager = $request->manager;
            $store->description = $request->description;
            $store->default_account_id = $request->default_account_id;
            $store->created_by = $this->user->name;
            $store->save();

            DB::commit();
            session()->flash('success', 'Finished Goods SM Store has been created !!');
            return redirect()->route('admin.finished-goods-sm-store.index');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Something went wrong !!');
            return redirect()->back();
        }
    }

    public function show($code)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_sm_stock.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any finished goods SM store !');
        }

        $finished_goods_store = FinishedGoodsSmStore::where('code', $code)->first();
        if (!$finished_goods_store) {
            abort(404, 'Finished Goods SM Store not found');
        }

        $finished_goods_store_details = FinishedGoodsSmStoreDetail::where('code_store', $code)
            ->with('item')
            ->get();

        $hasRecord = FinishedGoodsSmStore::count() > 0;

        return view('backend.pages.finished_goods_sm_store.show', compact(
            'finished_goods_store',
            'finished_goods_store_details',
            'hasRecord'
        ));
    }

    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_sm_stock.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any finished goods SM store !');
        }

        $finished_goods_store = FinishedGoodsSmStore::findOrFail($id);

        return view('backend.pages.finished_goods_sm_store.edit', compact('finished_goods_store'));
    }

    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_sm_stock.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any finished goods SM store !');
        }

        $request->validate([
            'name' => 'required|max:255',
            'emplacement' => 'required|max:255',
            'manager' => 'nullable|max:255',
            'description' => 'nullable',
            'default_account_id' => 'required'
        ]);

        try {
            DB::beginTransaction();

            $store = FinishedGoodsSmStore::findOrFail($id);
            $store->name = $request->name;
            $store->emplacement = $request->emplacement;
            $store->manager = $request->manager;
            $store->description = $request->description;
            $store->default_account_id = $request->default_account_id;
            $store->updated_by = $this->user->name;
            $store->save();

            DB::commit();
            session()->flash('success', 'Finished Goods SM Store has been updated !!');
            return redirect()->route('admin.finished-goods-sm-store.index');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Something went wrong !!');
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_sm_stock.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any finished goods SM store !');
        }

        try {
            $store = FinishedGoodsSmStore::findOrFail($id);
            $store->delete();
            session()->flash('success', 'Finished Goods SM Store has been deleted !!');
            return redirect()->route('admin.finished-goods-sm-store.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Something went wrong !!');
            return redirect()->back();
        }
    }

    public function destroyDetail($id)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_sm_stock.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any finished goods SM store detail !');
        }

        try {
            $detail = FinishedGoodsSmStoreDetail::findOrFail($id);
            $code = $detail->code_store;
            $detail->delete();
            session()->flash('success', 'Finished Goods SM Store Detail has been deleted !!');
            return redirect()->route('admin.finished-goods-sm-store.show', $code);
        } catch (\Exception $e) {
            session()->flash('error', 'Something went wrong !!');
            return redirect()->back();
        }
    }

    public function storeStatus($code)
    {
        return $this->print($code);
    }

    public function print($code)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_sm_stock.view')) {
            abort(403, 'Sorry !! You are Unauthorized to print this document !');
        }

        $store = FinishedGoodsSmStore::where('code', $code)->first();
        if (!$store) {
            session()->flash('error', 'Store not found !!');
            return redirect()->route('admin.finished-goods-sm-store.index');
        }

        $datas = FinishedGoodsSmStoreDetail::where('code_store', $code)->with('item')->get();
        $setting = DB::table('settings')->orderBy('created_at', 'desc')->first();
        $totalValue = $datas->sum('total_cump_value');

        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])
            ->loadView('backend.pages.document.finished_goods_sm_store', compact('store', 'datas', 'setting', 'totalValue'), [], 'UTF-8');

        return $pdf->download('ETAT_STOCK_PRODUITS_FINIS_SM_' . $code . '.pdf');
    }
}
