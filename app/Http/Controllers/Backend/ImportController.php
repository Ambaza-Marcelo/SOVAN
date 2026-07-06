<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesStockDocumentApproval;
use App\Http\Controllers\Backend\Concerns\UpdatesLedgerTotals;
use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountSetting;
use App\Models\FinishedGoodReport;
use App\Models\FinishedGoodsSmStore;
use App\Models\FinishedGoodsSmStoreDetail;
use App\Models\FinishedGoodsSmStoreReport;
use App\Models\FinishedGoodsStore;
use App\Models\FinishedGoodsStoreDetail;
use App\Models\GoodsStore;
use App\Models\GoodsStoreDetail;
use App\Models\GoodsStoreReport;
use App\Models\Import;
use App\Models\ImportDetail;
use App\Models\Item;
use App\Services\Stock\StockCumpCalculator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Validator;

class ImportController extends Controller
{
    use HandlesStockDocumentApproval;
    use UpdatesLedgerTotals;

    /** @var \App\Models\Admin|null */
    public $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('goods_import.view')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à voir les importations marchandises.');
        }

        $query = Import::with(['goodsStore', 'finishedGoodsStore', 'finishedGoodsSmStore'])->orderBy('id', 'desc');
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('date', [$dateDebut, $dateFin]);
        } else {
            $query->take(100);
        }
        $imports = $query->get();

        return view('backend.pages.import.index', compact('imports'));
    }

    public function create()
    {
        if (is_null($this->user) || !$this->user->can('goods_import.create')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à créer une importation marchandises.');
        }

        $goods_stores = GoodsStore::orderBy('code')->get();
        $finished_goods_stores = FinishedGoodsStore::orderBy('code')->get();
        $finished_goods_sm_stores = FinishedGoodsSmStore::orderBy('code')->get();

        return view('backend.pages.import.create', compact(
            'goods_stores',
            'finished_goods_stores',
            'finished_goods_sm_stores'
        ));
    }

    /**
     * Recherche d'articles (autocomplete) selon le magasin : marchandises, produits finis, ou PF SM.
     */
    public function searchItems(Request $request)
    {
        if (is_null($this->user)
            || (!$this->user->can('goods_import.create') && !$this->user->can('goods_import.edit'))) {
            abort(403, 'Désolé !! Action non autorisée.');
        }

        $validated = Validator::make($request->all(), [
            'q' => 'nullable|string|max:100',
            'store_kind' => 'required|in:' . Import::STORE_KIND_GOODS . ',' . Import::STORE_KIND_FINISHED_GOODS . ',' . Import::STORE_KIND_FINISHED_GOODS_SM,
            'store_id' => 'required|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:50',
        ])->validate();

        $q = trim((string) ($validated['q'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 20);
        $kind = (string) $validated['store_kind'];
        $storeId = (int) $validated['store_id'];

        $codeStore = null;
        if ($kind === Import::STORE_KIND_GOODS) {
            $codeStore = optional(GoodsStore::select('id', 'code')->find($storeId))->code;
            $detailsQuery = GoodsStoreDetail::query()->where('code_store', $codeStore)->whereNotNull('item_id');
        } elseif ($kind === Import::STORE_KIND_FINISHED_GOODS) {
            $codeStore = optional(FinishedGoodsStore::select('id', 'code')->find($storeId))->code;
            $detailsQuery = FinishedGoodsStoreDetail::query()->where('code_store', $codeStore)->whereNotNull('item_id');
        } else {
            $codeStore = optional(FinishedGoodsSmStore::select('id', 'code')->find($storeId))->code;
            $detailsQuery = FinishedGoodsSmStoreDetail::query()->where('code_store', $codeStore)->whereNotNull('item_id');
        }

        if (!$codeStore) {
            return response()->json(['results' => []]);
        }

        if ($q !== '') {
            $detailsQuery->where(function ($qq) use ($q) {
                $qq->where('name', 'like', '%' . $q . '%')
                    ->orWhere('code', 'like', '%' . $q . '%');
            });
        }

        $rows = $detailsQuery
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->get(['item_id', 'name', 'code', 'cump']);

        $results = $rows->map(function ($r) {
            return [
                'id' => (int) $r->item_id,
                'label' => trim(($r->name ?? '') . '/' . number_format($r->cump ?? 0, 0, ',', ' ') . '/' . ($r->code ?? '')),
                'cump' => (float) ($r->cump ?? 0),
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('goods_import.create')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à créer une importation marchandises.');
        }

        $rules = [
            'item_id' => 'required|array|min:1',
            'item_id.*' => 'required|integer|min:1',
            'item_quantity.*' => 'required|numeric|min:0',
            'item_measurement_unit.*' => 'required|string|max:80',
            'purchase_price.*' => 'required|numeric|min:0',
            'date' => 'required|date',
            'handingover' => 'required|string|max:190',
            'origin' => 'required|string|max:190',
            'receptionist' => 'required|string|max:190',
            'import_store' => ['required', 'regex:/^(goods|finished_goods|finished_goods_sm):\d+$/'],
            'description' => 'required|string|max:490',
            'item_purchase_or_sale_currency' => 'nullable|string|max:10',
            'reference_dmc' => 'nullable|string|max:190',
            'rubrique_tarifaire' => 'nullable|string|max:190',
            'nombre_par_paquet.*' => 'nullable|numeric',
            'description_paquet.*' => 'nullable|string|max:190',
            'transport_cost' => 'nullable|numeric|min:0',
            'clearance_cost' => 'nullable|numeric|min:0',
        ];

        $error = Validator::make($request->all(), $rules);
        if ($error->fails()) {
            return response()->json([
                'error' => $error->errors()->all(),
            ]);
        }

        $totals = $this->computeLineAndDocumentTotals($request);

        try {
            DB::beginTransaction();

            $date = $request->date;
            $created_by = $this->user->name;

            $latest = Import::orderBy('id', 'desc')->first();
            $import_no = $latest
                ? 'IMP-BE' . str_pad((string) ((int) $latest->id + 1), 4, '0', STR_PAD_LEFT)
                : 'IMP-BE' . str_pad('1', 4, '0', STR_PAD_LEFT);

            $import_signature = $import_no . '-' . Carbon::parse($date)->format('Y-m-d-H-i-s');
            $currency = $request->item_purchase_or_sale_currency ?: 'BIF';

            $import = new Import;
            $import->date = $date;
            $import->import_no = $import_no;
            $import->import_signature = $import_signature;
            $import->store_type = Item::STORE_TYPE_GOODS;
            $import->receptionist = $request->receptionist;
            $import->handingover = $request->handingover;
            $import->origin = $request->origin;
            $import->item_purchase_or_sale_currency = $currency;
            $import->item_movement_type = 'EN';
            $import->description = $request->description;
            $import->reference_dmc = $request->reference_dmc;
            $import->rubrique_tarifaire = $request->rubrique_tarifaire;
            $import->nombre_par_paquet = null;
            $import->description_paquet = null;
            $import->transport_cost = $request->transport_cost;
            $import->clearance_cost = $request->clearance_cost;
            $import->total_amount = $totals['total_amount'];
            $this->applyImportStoreSelectionToModel($import, (string) $request->import_store);
            $import->status = '1';
            $import->created_by = $created_by;
            $import->save();

            $this->persistDetailRows($import, $request, $totals['line_totals'], $created_by, null);

            DB::commit();
            session()->flash('success', 'Importation marchandises enregistrée avec succès.');

            return redirect()->route('admin.imports.index');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show($import_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_import.view')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à voir ce document.');
        }

        $import = Import::with(['goodsStore', 'finishedGoodsStore', 'finishedGoodsSmStore'])->where('import_no', $import_no)->first();
        if (is_null($import)) {
            session()->flash('error', 'Document introuvable.');

            return redirect()->route('admin.imports.index');
        }

        $importDetails = ImportDetail::with('item')
            ->where('import_no', $import_no)
            ->orderBy('id')
            ->get();

        $totalQuantity = $importDetails->sum('item_quantity');
        $sumTotalPurchaseValue = $importDetails->sum('total_purchase_value');
        $transportCost = (float) ($import->transport_cost ?? 0);
        $clearanceCost = (float) ($import->clearance_cost ?? 0);
        $grandTotalAmount = (float) ($import->total_amount ?? ($sumTotalPurchaseValue + $transportCost + $clearanceCost));

        return view('backend.pages.import.show', compact(
            'import',
            'importDetails',
            'totalQuantity',
            'sumTotalPurchaseValue',
            'transportCost',
            'clearanceCost',
            'grandTotalAmount'
        ));
    }

    public function edit($import_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_import.edit')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à modifier ce document.');
        }

        $import = Import::where('import_no', $import_no)->first();
        if (is_null($import)) {
            session()->flash('error', 'Document introuvable.');

            return redirect()->route('admin.imports.index');
        }

        if ((string) $import->status === '4') {
            session()->flash('error', 'Impossible de modifier une importation approuvée.');

            return redirect()->route('admin.imports.index');
        }

        $importDetails = ImportDetail::with('item')
            ->where('import_no', $import_no)
            ->orderBy('id')
            ->get();

        $goods_stores = GoodsStore::orderBy('code')->get();
        $finished_goods_stores = FinishedGoodsStore::orderBy('code')->get();
        $finished_goods_sm_stores = FinishedGoodsSmStore::orderBy('code')->get();
        $selected_import_store = $this->formatImportStoreSelectionValue($import);

        return view('backend.pages.import.edit', compact(
            'import',
            'importDetails',
            'goods_stores',
            'finished_goods_stores',
            'finished_goods_sm_stores',
            'selected_import_store'
        ));
    }

    public function update(Request $request, $import_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_import.edit')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à modifier ce document.');
        }

        $import = Import::where('import_no', $import_no)->first();
        if (is_null($import)) {
            session()->flash('error', 'Document introuvable.');

            return redirect()->route('admin.imports.index');
        }

        if ((string) $import->status === '4') {
            session()->flash('error', 'Impossible de modifier une importation approuvée.');

            return redirect()->route('admin.imports.index');
        }

        $rules = [
            'item_id' => 'required|array|min:1',
            'item_id.*' => 'required|integer|min:1',
            'item_quantity.*' => 'required|numeric|min:0',
            'item_measurement_unit.*' => 'required|string|max:80',
            'purchase_price.*' => 'required|numeric|min:0',
            'date' => 'required|date',
            'handingover' => 'required|string|max:190',
            'origin' => 'required|string|max:190',
            'receptionist' => 'required|string|max:190',
            'import_store' => ['required', 'regex:/^(goods|finished_goods|finished_goods_sm):\d+$/'],
            'description' => 'required|string|max:490',
            'item_purchase_or_sale_currency' => 'nullable|string|max:10',
            'reference_dmc' => 'nullable|string|max:190',
            'rubrique_tarifaire' => 'nullable|string|max:190',
            'nombre_par_paquet.*' => 'nullable|numeric',
            'description_paquet.*' => 'nullable|string|max:190',
            'transport_cost' => 'nullable|numeric|min:0',
            'clearance_cost' => 'nullable|numeric|min:0',
        ];

        $error = Validator::make($request->all(), $rules);
        if ($error->fails()) {
            return response()->json([
                'error' => $error->errors()->all(),
            ]);
        }

        $totals = $this->computeLineAndDocumentTotals($request);

        try {
            DB::beginTransaction();

            $date = $request->date;
            $updated_by = $this->user->name;
            $currency = $request->item_purchase_or_sale_currency ?: 'BIF';
            $import_signature = $import_no . '-' . Carbon::parse($date)->format('Y-m-d-H-i-s');

            $import->date = $date;
            $import->import_signature = $import_signature;
            $import->receptionist = $request->receptionist;
            $import->handingover = $request->handingover;
            $import->origin = $request->origin;
            $import->item_purchase_or_sale_currency = $currency;
            $import->item_movement_type = 'EN';
            $import->description = $request->description;
            $import->reference_dmc = $request->reference_dmc;
            $import->rubrique_tarifaire = $request->rubrique_tarifaire;
            $import->nombre_par_paquet = null;
            $import->description_paquet = null;
            $import->transport_cost = $request->transport_cost;
            $import->clearance_cost = $request->clearance_cost;
            $import->total_amount = $totals['total_amount'];
            $this->applyImportStoreSelectionToModel($import, (string) $request->import_store);
            $import->updated_by = $updated_by;
            $import->save();

            ImportDetail::where('import_no', $import_no)->delete();
            $this->persistDetailRows($import, $request, $totals['line_totals'], $import->created_by, $updated_by);

            DB::commit();
            session()->flash('success', 'Importation marchandises mise à jour.');

            return redirect()->route('admin.imports.index');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy($import_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_import.delete')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à supprimer ce document.');
        }

        $import = Import::where('import_no', $import_no)->first();
        if (is_null($import)) {
            session()->flash('error', 'Document introuvable.');

            return back();
        }

        if ((string) $import->status === '4') {
            session()->flash('error', 'Impossible de supprimer une importation approuvée.');

            return back();
        }

        try {
            DB::beginTransaction();
            ImportDetail::where('import_no', $import_no)->delete();
            $import->delete();
            DB::commit();
            session()->flash('success', 'Importation marchandises supprimée.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return back();
    }

    public function validateImport($import_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_import.validate')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à valider ce document.');
        }

        $import = Import::where('import_no', $import_no)->first();
        if (!$import) {
            session()->flash('error', 'Document introuvable.');

            return back();
        }
        if ($response = $this->guardStockDocumentValidate($import)) {
            return $response;
        }

        try {
            DB::beginTransaction();
            Import::where('import_no', $import_no)->update([
                'status' => '2',
                'validated_by' => $this->user->name,
            ]);
            ImportDetail::where('import_no', $import_no)->update([
                'status' => '2',
                'validated_by' => $this->user->name,
            ]);
            DB::commit();
            session()->flash('success', 'Importation validée.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return back();
    }

    public function confirm($import_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_import.confirm')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à confirmer ce document.');
        }

        $import = Import::where('import_no', $import_no)->first();
        if (!$import) {
            session()->flash('error', 'Document introuvable.');

            return back();
        }
        if ($response = $this->guardStockDocumentConfirm($import)) {
            return $response;
        }

        try {
            DB::beginTransaction();
            Import::where('import_no', $import_no)->update([
                'status' => '3',
                'confirmed_by' => $this->user->name,
            ]);
            ImportDetail::where('import_no', $import_no)->update([
                'status' => '3',
                'confirmed_by' => $this->user->name,
            ]);
            DB::commit();
            session()->flash('success', 'Importation confirmée.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return back();
    }

    public function approuve($import_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_import.approuve')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à approuver ce document.');
        }

        try {
            $import = Import::where('import_no', $import_no)->first();
            if (is_null($import)) {
                session()->flash('error', 'Document introuvable.');

                return back();
            }
            if ($response = $this->guardStockDocumentApprove($import)) {
                return $response;
            }

            $datas = ImportDetail::where('import_no', $import_no)->with('item')->get();
            if ($datas->isEmpty()) {
                session()->flash('error', 'Aucune ligne trouvée pour cette importation.');

                return back();
            }

            $ledgerStore = $this->resolveLedgerStoreForImport($import);
            if (!$ledgerStore || trim((string) ($ledgerStore->code ?? '')) === '') {
                session()->flash('error', 'Magasin introuvable ou code magasin manquant.');

                return back();
            }
            $code_store = (string) $ledgerStore->code;
            $kind = $this->resolveImportStoreKindFromImport($import);

            // Prépare les calculs stock/valeurs (appliqués seulement si l’OBR accepte toutes les lignes).
            $prepared = [];
            foreach ($datas as $data) {
                $storeDetail = $this->findStoreDetailLineForImport($kind, $code_store, (int) $data->item_id);
                if (is_null($storeDetail)) {
                    session()->flash('error', 'Article introuvable dans le magasin: ' . ($data->item ? $data->item->name : 'N/A'));

                    return back();
                }
                if (!$data->item) {
                    session()->flash('error', 'Article manquant sur une ligne de l\'importation.');

                    return back();
                }

                $quantityStockInitial = (float) ($storeDetail->quantity ?? 0);
                $quantityStockin = (float) ($data->item_quantity ?? 0);
                $purchasePrice = (float) ($data->purchase_price ?? 0);
                $valueStockInitial = StockCumpCalculator::initialValueFromStore(
                    $quantityStockInitial,
                    isset($storeDetail->cump) ? (float) $storeDetail->cump : null,
                    isset($storeDetail->total_cump_value) ? (float) $storeDetail->total_cump_value : null
                );
                $cumpResult = StockCumpCalculator::weightedAverageOnStockIn(
                    $quantityStockInitial,
                    $valueStockInitial,
                    $quantityStockin,
                    $purchasePrice,
                    $purchasePrice
                );
                $quantityTotal = $cumpResult['quantity_total'];
                $newCump = $cumpResult['cump'];
                $valueStockin = (float) ($data->total_purchase_value ?? $cumpResult['value_in']);

                $prepared[] = [
                    'storeDetail' => $storeDetail,
                    'quantityStockInitial' => $quantityStockInitial,
                    'valueStockInitial' => $valueStockInitial,
                    'quantityStockin' => $quantityStockin,
                    'valueStockin' => $valueStockin,
                    'quantityTotal' => $quantityTotal,
                    'newCump' => $newCump,
                    'data' => $data,
                ];
            }

            // Envoi OBR désactivé temporairement — à réactiver ultérieurement.
            /*
            // 1) Envoi OBR. Ne change pas les attributs du payload existant.
            $base = rtrim((string) config('app.guzzle_test_url'), '/');
            if ($base === '') {
                session()->flash('error', 'URL du service OBR non configurée (guzzle_test_url).');

                return back();
            }
            $loginResponse = Http::post($base . '/ebms_api/login/', [
                'username' => config('app.obr_test_username'),
                'password' => config('app.obr_test_pwd'),
            ]);
            $loginJson = $loginResponse->json() ?? [];
            $token = $loginJson['result']['token'] ?? null;
            if (!$token) {
                session()->flash('error', 'Connexion au service OBR impossible (token manquant).');

                return back();
            }

            $movementUrl = $base . '/ebms_api/AddStockMovementImporters';
            foreach ($datas as $data) {
                $payload = [
                    'system_or_device_id' => config('app.obr_test_username'),
                    'item_code' => (string) ($data->item->code ?? ''),
                    'item_designation' => (string) ($data->item->name ?? ''),
                    'item_quantity' => (string) ($data->item_quantity ?? ''),
                    'item_measurement_unit' => (string) ($data->item_measurement_unit ?? ''),
                    'item_cost_price' => (string) ($data->purchase_price ?? ''),
                    'item_cost_price_currency' => "BIF",
                    'item_movement_type' => "EN",
                    'item_movement_invoice_ref' => "",
                    'item_movement_description' => (string) ($data->description ?? ''),
                    'item_movement_date' => Carbon::now()->format('Y-m-d H:i:s'),
                    'reference_dmc' => (string) ($data->reference_dmc ?? ''),
                    'rubrique_tarifaire' => (string) ($data->rubrique_tarifaire ?? ''),
                    'nombre_par_paquet' => (string) ($data->nombre_par_paquet ?? ''),
                    'description_paquet' => (string) ($data->description_paquet ?? ''),
                ];

                // Pas de null
                foreach ($payload as $k => $v) {
                    $payload[$k] = $v === null ? '' : (string) $v;
                }

                $resp = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])->post($movementUrl, $payload);

                $json = $resp->json() ?? [];
                $successVal = $json['success'] ?? false;
                $ok = $successVal === true || $successVal === 1 || $successVal === '1'
                    || strtolower((string) $successVal) === 'true';

                if (!$ok) {
                    $rawMsg = (string) ($json['msg'] ?? '');
                    if (trim($rawMsg) === '' && isset($json['result']) && is_array($json['result'])) {
                        $rawMsg = (string) ($json['result']['msg'] ?? $json['result']['message'] ?? '');
                    }
                    if (trim($rawMsg) === '' && method_exists($resp, 'body')) {
                        $rawMsg = (string) $resp->body();
                    }
                    if (trim($rawMsg) === '') {
                        $rawMsg = 'Transmission import OBR refusée.';
                    }
                    session()->flash('error', $rawMsg);

                    return back();
                }
            }
            */

            // Mise à jour stock + rapports + statuts (sans transmission OBR pour l'instant).
            DB::beginTransaction();
            try {
                foreach ($prepared as $row) {
                    $storeDetail = $row['storeDetail'];
                    /** @var \App\Models\ImportDetail $data */
                    $data = $row['data'];

                    $storeDetail->quantity = $row['quantityTotal'];
                    $storeDetail->cump = $row['newCump'];
                    $storeDetail->updated_by = $this->user->name;
                    if ($storeDetail instanceof FinishedGoodsSmStoreDetail) {
                        $storeDetail->total_cump_value = $row['quantityTotal'] * $row['newCump'];
                    }
                    $storeDetail->save();

                    $this->saveImportStockReport($kind, $code_store, $row, $import_no, $import);
                }

                Import::where('import_no', $import_no)->update([
                    'status' => '4',
                    'approuved_by' => $this->user->name,
                ]);
                ImportDetail::where('import_no', $import_no)->update([
                    'status' => '4',
                    'approuved_by' => $this->user->name,
                ]);

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            session()->flash('success', 'Importation approuvée. Stock mis à jour: ' . $code_store);

            return back();
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            session()->flash('error', __('messages.error_approval') . $e->getMessage());

            return back();
        }
    }

    public function reject(Request $request, $import_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_import.reject')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à rejeter ce document.');
        }

        try {
            DB::beginTransaction();
            Import::where('import_no', $import_no)->update([
                'status' => '-1',
                'rejected_by' => $this->user->name,
                'rejected_motif' => $request->rejected_motif,
            ]);
            ImportDetail::where('import_no', $import_no)->update([
                'status' => '-1',
                'rejected_by' => $this->user->name,
                'rejected_motif' => $request->rejected_motif,
            ]);
            DB::commit();
            session()->flash('success', 'Importation rejetée.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return back();
    }

    public function reset($import_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_import.reset')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à réinitialiser ce document.');
        }

        try {
            DB::beginTransaction();
            Import::where('import_no', $import_no)->update([
                'status' => '1',
                'reseted_by' => $this->user->name,
            ]);
            ImportDetail::where('import_no', $import_no)->update([
                'status' => '1',
                'reseted_by' => $this->user->name,
            ]);
            DB::commit();
            session()->flash('success', 'Importation réinitialisée.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return back();
    }

    /**
     * @return array{line_totals: array<int, float>, sum_lines: float, total_amount: float}
     */
    protected function computeLineAndDocumentTotals(Request $request): array
    {
        $qty = $request->item_quantity ?? [];
        $price = $request->purchase_price ?? [];
        $lineTotals = [];
        $sum = 0.0;
        foreach ($qty as $i => $q) {
            $pv = (float) $q * (float) ($price[$i] ?? 0);
            $lineTotals[$i] = $pv;
            $sum += $pv;
        }
        $transport = (float) ($request->transport_cost ?? 0);
        $clearance = (float) ($request->clearance_cost ?? 0);

        return [
            'line_totals' => $lineTotals,
            'sum_lines' => $sum,
            'total_amount' => $sum + $transport + $clearance,
        ];
    }

    protected function persistDetailRows(
        Import $import,
        Request $request,
        array $lineTotals,
        ?string $createdBy,
        ?string $updatedBy
    ): void {
        $itemIds = $request->item_id ?? [];
        $qty = $request->item_quantity ?? [];
        $units = $request->item_measurement_unit ?? [];
        $prices = $request->purchase_price ?? [];
        $nbPaquets = $request->nombre_par_paquet ?? [];
        $descPaquets = $request->description_paquet ?? [];
        $currency = $request->item_purchase_or_sale_currency ?: 'BIF';
        $date = $import->date;

        foreach ($itemIds as $count => $itemId) {
            $totalPurchase = $lineTotals[$count] ?? ((float) ($qty[$count] ?? 0) * (float) ($prices[$count] ?? 0));

            $detail = new ImportDetail;
            $detail->date = $date;
            $detail->import_no = $import->import_no;
            $detail->import_signature = $import->import_signature;
            $detail->store_type = $import->store_type;
            $detail->receptionist = $import->receptionist;
            $detail->handingover = $import->handingover;
            $detail->origin = $import->origin;
            $detail->item_purchase_or_sale_currency = $currency;
            $detail->item_movement_type = 'EN';
            $detail->description = $import->description;
            $detail->reference_dmc = $import->reference_dmc;
            $detail->rubrique_tarifaire = $import->rubrique_tarifaire;
            $rawNb = $nbPaquets[$count] ?? null;
            $detail->nombre_par_paquet = ($rawNb !== null && $rawNb !== '') ? $rawNb : null;
            $rawDesc = isset($descPaquets[$count]) ? trim((string) $descPaquets[$count]) : '';
            $detail->description_paquet = $rawDesc !== '' ? $rawDesc : null;
            $detail->status = $import->status;
            $detail->item_quantity = $qty[$count];
            $detail->item_measurement_unit = $units[$count] ?? '';
            $detail->purchase_price = $prices[$count];
            $detail->total_purchase_value = $totalPurchase;
            $detail->transport_cost = null;
            $detail->clearance_cost = null;
            $detail->total_amount = $totalPurchase;
            $detail->goods_store_id = $import->goods_store_id;
            $detail->finished_g_store_id = $import->finished_g_store_id;
            $detail->finished_g_sm_store_id = $import->finished_g_sm_store_id;
            $detail->item_id = $itemId;
            $detail->created_by = $createdBy;
            $detail->updated_by = $updatedBy;
            $detail->save();
        }
    }


    protected function applyImportStoreSelectionToModel(Import $import, string $importStore): void
    {
        $parsed = $this->parseImportStoreSelection($importStore);
        $import->store_type = $parsed['kind'];
        $import->goods_store_id = null;
        $import->finished_g_store_id = null;
        $import->finished_g_sm_store_id = null;
        if ($parsed['kind'] === Import::STORE_KIND_GOODS) {
            $import->goods_store_id = $parsed['id'];
        } elseif ($parsed['kind'] === Import::STORE_KIND_FINISHED_GOODS) {
            $import->finished_g_store_id = $parsed['id'];
        } elseif ($parsed['kind'] === Import::STORE_KIND_FINISHED_GOODS_SM) {
            $import->finished_g_sm_store_id = $parsed['id'];
        }
    }

    /**
     * @return array{kind: string, id: int}
     */
    protected function parseImportStoreSelection(string $value): array
    {
        if (!preg_match('/^(goods|finished_goods|finished_goods_sm):(\d+)$/', $value, $m)) {
            throw new \InvalidArgumentException('Magasin invalide.');
        }

        return ['kind' => $m[1], 'id' => (int) $m[2]];
    }

    protected function formatImportStoreSelectionValue(Import $import): string
    {
        if ($import->finished_g_sm_store_id) {
            return Import::STORE_KIND_FINISHED_GOODS_SM . ':' . $import->finished_g_sm_store_id;
        }
        if ($import->finished_g_store_id) {
            return Import::STORE_KIND_FINISHED_GOODS . ':' . $import->finished_g_store_id;
        }
        if ($import->goods_store_id) {
            return Import::STORE_KIND_GOODS . ':' . $import->goods_store_id;
        }

        return Import::STORE_KIND_GOODS . ':0';
    }

    protected function resolveImportStoreKindFromImport(Import $import): string
    {
        if ($import->finished_g_sm_store_id) {
            return Import::STORE_KIND_FINISHED_GOODS_SM;
        }
        if ($import->finished_g_store_id) {
            return Import::STORE_KIND_FINISHED_GOODS;
        }

        return Import::STORE_KIND_GOODS;
    }

    /**
     * @return GoodsStore|FinishedGoodsStore|FinishedGoodsSmStore|null
     */
    protected function resolveLedgerStoreForImport(Import $import)
    {
        if ($import->finished_g_sm_store_id) {
            return FinishedGoodsSmStore::find($import->finished_g_sm_store_id);
        }
        if ($import->finished_g_store_id) {
            return FinishedGoodsStore::find($import->finished_g_store_id);
        }
        if ($import->goods_store_id) {
            return GoodsStore::find($import->goods_store_id);
        }

        return null;
    }

    /**
     * @return GoodsStoreDetail|FinishedGoodsStoreDetail|FinishedGoodsSmStoreDetail|null
     */
    protected function findStoreDetailLineForImport(string $kind, string $code_store, int $item_id)
    {
        if ($kind === Import::STORE_KIND_GOODS) {
            return GoodsStoreDetail::where('code_store', $code_store)->where('item_id', $item_id)->first();
        }
        if ($kind === Import::STORE_KIND_FINISHED_GOODS) {
            return FinishedGoodsStoreDetail::where('code_store', $code_store)->where('item_id', $item_id)->first();
        }

        return FinishedGoodsSmStoreDetail::where('code_store', $code_store)->where('item_id', $item_id)->first();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function saveImportStockReport(string $kind, string $code_store, array $row, string $import_no, Import $import): void
    {
        $data = $row['data'];
        $attrs = [
            'code_store' => $code_store,
            'item_id' => $data->item_id,
            'quantity_stock_initial' => $row['quantityStockInitial'],
            'value_stock_initial' => $row['valueStockInitial'],
            'quantity_stockin' => $row['quantityStockin'],
            'value_stockin' => $row['valueStockin'],
            'stock_total' => $row['quantityTotal'],
            'quantity_stockout' => 0,
            'value_stockout' => 0,
            'quantity_stock_final' => $row['quantityTotal'],
            'value_stock_final' => $row['newCump'] * $row['quantityTotal'],
            'cump' => $row['newCump'],
            'date' => $data->date,
            'created_by' => $this->user->name,
            'type_transaction' => $data->item_movement_type ?? 'EN',
            'document_no' => $import_no,
            'description' => $data->description ?? $import->description,
        ];

        if ($kind === Import::STORE_KIND_GOODS) {
            GoodsStoreReport::create($attrs);

            return;
        }
        if ($kind === Import::STORE_KIND_FINISHED_GOODS) {
            FinishedGoodReport::create($attrs);

            return;
        }
        FinishedGoodsSmStoreReport::create($attrs);
    }

    public function print($import_no)
    {
        if (is_null($this->user) || !$this->user->can('goods_import.view')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à imprimer ce document.');
        }

        session()->flash('info', 'Document PDF importation : à définir si besoin.');

        return redirect()->route('admin.imports.show', $import_no);
    }
}
