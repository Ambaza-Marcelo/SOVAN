<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\GoodsStore;
use App\Models\GoodsStoreDetail;
use App\Models\Accounting\AccountAccount;
use Carbon\Carbon;
use PDF;

class GoodStoreController extends Controller
{
    public $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource (stock des marchandises).
     */
    public function index()
    {
        if (is_null($this->user) || !$this->user->can('goods_stock.view')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à voir le stock des marchandises.');
        }

        $goods_stores = GoodsStore::with('defaultAccount')->get();
        $hasRecord = GoodsStore::count() > 0;
        return view('backend.pages.goods_store.index', compact('goods_stores', 'hasRecord'));
    }

    /**
     * Show the form for creating a new resource.
     * Only one goods store is allowed.
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('goods_stock.create')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à créer un magasin de marchandises.');
        }

        if (GoodsStore::count() > 0) {
            session()->flash('info', __('messages.goods_store_one_only'));
            return redirect()->route('admin.goods-store.index');
        }

        return view('backend.pages.goods_store.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('goods_stock.create')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à créer un magasin de marchandises.');
        }

        if (GoodsStore::count() > 0) {
            session()->flash('error', __('messages.goods_store_one_only'));
            return redirect()->route('admin.goods-store.index');
        }

        $request->validate([
            'name' => 'required|max:255',
            'emplacement' => 'required|max:255',
            'manager' => 'nullable|max:255',
            'description' => 'nullable',
            'default_account_id' => 'required',
        ]);

        try {
            DB::beginTransaction();

            $store = new GoodsStore();
            $store->name = $request->name;
            $reference = strtoupper(substr($request->name, 0, 3));
            $store->code = $reference . date('y') . substr(number_format(time() * mt_rand(), 0, '', ''), 0, 6);
            $store->store_signature = config('app.tin_number_company', '') . Carbon::now()->format('YmdHis') . '/' . $store->code;
            $store->emplacement = $request->emplacement;
            $store->manager = $request->manager;
            $store->description = $request->description;
            $store->default_account_id = $request->default_account_id;
            $store->created_by = $this->user->name;
            $store->save();

            DB::commit();
            session()->flash('success', 'Magasin de marchandises créé avec succès.');
            return redirect()->route('admin.goods-store.index');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur s\'est produite.');
            return redirect()->back();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($code)
    {
        if (is_null($this->user) || !$this->user->can('goods_stock.view')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à voir ce magasin.');
        }

        $goods_store = GoodsStore::where('code', $code)->first();
        if (!$goods_store) {
            abort(404, 'Magasin de marchandises introuvable.');
        }

        $goods_store_details = GoodsStoreDetail::where('code_store', $code)
            ->with('item')
            ->get();

        $hasRecord = GoodsStore::count() > 0;
        return view('backend.pages.goods_store.show', compact('goods_store', 'goods_store_details', 'hasRecord'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('goods_stock.edit')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à modifier ce magasin.');
        }

        $goods_store = GoodsStore::findOrFail($id);
        return view('backend.pages.goods_store.edit', compact('goods_store'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('goods_stock.edit')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à modifier ce magasin.');
        }

        $request->validate([
            'name' => 'required|max:255',
            'emplacement' => 'required|max:255',
            'manager' => 'nullable|max:255',
            'description' => 'nullable',
            'default_account_id' => 'required',
        ]);

        try {
            DB::beginTransaction();

            $store = GoodsStore::findOrFail($id);
            $store->name = $request->name;
            $store->emplacement = $request->emplacement;
            $store->manager = $request->manager;
            $store->description = $request->description;
            $store->default_account_id = $request->default_account_id;
            $store->updated_by = $this->user->name;
            $store->save();

            DB::commit();
            session()->flash('success', 'Magasin de marchandises mis à jour avec succès.');
            return redirect()->route('admin.goods-store.index');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur s\'est produite.');
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('goods_stock.delete')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à supprimer ce magasin.');
        }

        try {
            $store = GoodsStore::findOrFail($id);
            $store->delete();
            session()->flash('success', 'Magasin de marchandises supprimé.');
            return redirect()->route('admin.goods-store.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Une erreur s\'est produite.');
            return redirect()->back();
        }
    }

    /**
     * Remove a store detail (article) from the store.
     */
    public function destroyDetail($id)
    {
        if (is_null($this->user) || !$this->user->can('goods_stock.delete')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à supprimer ce détail.');
        }

        try {
            $detail = GoodsStoreDetail::findOrFail($id);
            $code = $detail->code_store;
            $detail->delete();
            session()->flash('success', 'Détail du magasin supprimé.');
            return redirect()->route('admin.goods-store.show', $code);
        } catch (\Exception $e) {
            session()->flash('error', 'Une erreur s\'est produite.');
            return redirect()->back();
        }
    }

    /**
     * Print stock state (PDF).
     */
    public function print($code)
    {
        if (is_null($this->user) || !$this->user->can('goods_stock.view')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à imprimer ce document.');
        }

        $goods_store = GoodsStore::where('code', $code)->first();
        if (!$goods_store) {
            session()->flash('error', 'Magasin introuvable.');
            return redirect()->route('admin.goods-store.index');
        }

        $datas = GoodsStoreDetail::where('code_store', $code)->with('item')->get();
        $setting = DB::table('settings')->orderBy('created_at', 'desc')->first();
        $totalValue = $datas->sum(function ($d) {
            return is_numeric($d->total_cump_value) ? (float) $d->total_cump_value : 0;
        });
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.goods_store', compact('goods_store', 'datas', 'setting', 'totalValue'), [], 'UTF-8');
        return $pdf->download('ETAT_STOCK_MARCHANDISES_' . $code . '.pdf');
    }
}
