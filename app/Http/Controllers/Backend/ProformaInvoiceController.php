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
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use PDF;
use Excel;
use Mail;
use Validator;
use GuzzleHttp\Client;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceDetail;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\ItemDetail;
use App\Models\RawMaterialBigStore;
use App\Models\RawMaterialBigStoreDetail;
use App\Models\RawMaterialSmallStoreDetail;
use App\Models\EGRClient;
use App\Models\Setting;
use App\Support\BillingChain;
use App\Exports\GenericIndexExport;

class ProformaInvoiceController extends Controller
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

    public function index(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('proforma_invoice.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any proforma invoice !');
        }

        $query = ProformaInvoice::with(['client', 'details'])->orderBy('id','desc');
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('proforma_date', [$dateDebut, $dateFin]);
        } else {
            $query->take(100);
        }
        $proforma_invoices = $query->get();
        return view('backend.pages.proforma_invoice.index',compact('proforma_invoices'));
    }

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('proforma_invoice.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export proforma invoices !');
        }
        $query = ProformaInvoice::with(['client', 'details'])->orderBy('id','desc');
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('proforma_date', [$dateDebut, $dateFin]);
        }
        $proformaNumbers = $query->pluck('proforma_number');
        $details = ProformaInvoiceDetail::with(['proformaInvoice.client', 'item'])
            ->whereIn('proforma_number', $proformaNumbers)
            ->orderBy('proforma_number')
            ->orderBy('id')
            ->get();
        $rows = [];
        $i = 1;
        foreach ($details as $d) {
            $doc = $d->proformaInvoice;
            $etat = $doc && $doc->etat == '0' ? 'Brouillon' : ($doc && $doc->etat == '1' ? 'Validée' : ($doc && $doc->etat == '2' ? 'Confirmée' : ($doc && $doc->etat == '3' ? 'Approuvée' : ($doc->etat ?? ''))));
            $rows[] = [
                $i++,
                $d->proforma_number,
                $doc && $doc->proforma_date ? Carbon::parse($doc->proforma_date)->format('d/m/Y') : '',
                $doc->proforma_signature ?? '',
                $doc && $doc->client ? $doc->client->customer_name : 'N/A',
                $etat,
                $d->item_designation ?? ($d->item ? $d->item->name : ''),
                (float) ($d->item_quantity ?? 0),
                (float) ($d->item_price_nvat ?? $d->item_price ?? 0),
                (float) ($d->vat ?? 0),
                (float) ($d->item_total_amount ?? 0),
                $doc->description ?? '',
                $doc->auteur ?? '',
            ];
        }
        $headings = ['#', 'Proforma Number', 'Date', 'Signature', 'Customer', 'Etat', 'Désignation', 'Quantité', 'PU HTVA', 'TVA', 'Total ligne', 'Description', 'Auteur'];
        return Excel::download(new GenericIndexExport($rows, $headings), 'proforma_invoices.xlsx');
    }

    public function create()
    {
        if (is_null($this->user) || !$this->user->can('proforma_invoice.create')) {
            abort(403, 'Sorry !! You are Unauthorized to view any proforma invoice !');
        }

        $clients = EGRClient::orderBy('customer_name')->get();
        $defaultClient = EGRClient::where('mail', 'clientordinaire@gmail.com')->first();
        return view('backend.pages.proforma_invoice.create', compact('clients', 'defaultClient'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('proforma_invoice.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any proforma invoice !');
        }

        $error = Validator::make($request->all(), [
            'client_id' => 'required',
            'item_id' => 'required|array',
            'quantity' => 'required|array',
            'quantity.*' => 'nullable|numeric|min:0.0001',
        ]);

        if ($error->fails()) {
            return redirect()->back()->withErrors($error)->withInput();
        }

        try {
            DB::beginTransaction();

            $lines = $this->normalizeProformaLines($request);
            if ($lines === null) {
                return redirect()->back()->withErrors(['error' => 'Veuillez sélectionner au moins un article dans la liste (produit fini ou marchandise).'])->withInput();
            }

            $item_ids = $lines['item_ids'];
            $quantities = $lines['quantities'];
            $item_prices = $lines['item_prices'];
            $client_id = $request->client_id;

            $latest = ProformaInvoice::orderBy('id','desc')->first();
            if ($latest) {
                $proforma_number = str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT); 
            } else {
                $proforma_number = str_pad(1, 4, '0', STR_PAD_LEFT);  
            }

            $proforma_date = $request->filled('proforma_date')
                ? Carbon::parse($request->proforma_date)->startOfDay()
                : Carbon::now();
            $proforma_signature = "PROF/" . Carbon::parse($proforma_date)->format('YmdHis') . "/" . $proforma_number;

            $client = EGRClient::where('id', $client_id)->first();
            
            // Get Setting to check vat_taxpayer
            $setting = Setting::first();
            $is_vat_taxpayer = $setting && $setting->vat_taxpayer == 1;

            // Create main proforma invoice
            $proforma_invoice = new ProformaInvoice();
            $proforma_invoice->proforma_number = $proforma_number;
            $proforma_invoice->proforma_date = $proforma_date;
            $proforma_invoice->proforma_signature = $proforma_signature;
            $proforma_invoice->proforma_signature_date = $proforma_date;
            $proforma_invoice->client_id = $client_id;
            $proforma_invoice->description = $request->description ?? '';
            $proforma_invoice->auteur = $this->user->name;
            $proforma_invoice->etat = '0';
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
                if (in_array(strtolower($file->getClientOriginalExtension()), $allowed)) {
                    $path = $file->store('documents/proforma', 'public');
                    $proforma_invoice->document = $path;
                }
            }
            $proforma_invoice->save();

            // Create details
            $data1 = [];
            for ($count = 0; $count < count($item_ids); $count++) {
                $item = Item::billable()->where('id', $item_ids[$count])->first();
                if (!$item) {
                    DB::rollBack();
                    return redirect()->back()->withErrors([
                        'error' => 'Article invalide ou non facturable à la ligne ' . ($count + 1) . '.',
                    ])->withInput();
                }

                $item_price_input = $item_prices[$count] ?? $item->selling_price ?? 0;
                $unitPriceHt = round((float) $item_price_input, 2);
                $quantity = $quantities[$count] ?? 0;

                // item_price en BDD = PU HT ; la TVA est calculée par article via items.vat
                if ($is_vat_taxpayer) {
                    $vatRate = (float) ($item->vat_rate ?? $item->vat ?? 0); // %
                    $item_price_nvat = (float) $quantity * (float) $unitPriceHt;
                    $vat = $item_price_nvat * $vatRate / 100;
                    $item_total_amount = $item_price_nvat + $vat;
                    $item_price_wvat = $item_total_amount;
                } else {
                    $vat = 0;
                    $item_price_nvat = (float) $quantity * (float) $unitPriceHt;
                    $item_price_wvat = $item_total_amount = $item_price_nvat;
                }

                $data1[] = [
                    'proforma_number' => $proforma_number,
                    'proforma_date' => $proforma_date,
                    'proforma_signature' => $proforma_signature,
                    'proforma_signature_date' => $proforma_date,
                    'client_id' => $client_id,
                    'item_id' => $item_ids[$count],
                    'item_designation' => $item->name, // Save item name from items table
                    'item_quantity' => $quantity,
                    'item_price' => $unitPriceHt,
                    'item_price_nvat' => $item_price_nvat,
                    'vat' => $vat,
                    'item_price_wvat' => $item_price_wvat,
                    'item_total_amount' => $item_total_amount,
                    'auteur' => $this->user->name,
                    'etat' => '0',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }

            if (!empty($data1)) {
                ProformaInvoiceDetail::insert($data1);
            }

            DB::commit();
            session()->flash('success', 'La facture proforma est créée avec succès!!');
            return redirect()->route('admin.proforma-invoice.index');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', __('messages.error_creation') . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function show($proforma_number)
    {
        if (is_null($this->user) || !$this->user->can('proforma_invoice.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any proforma invoice !');
        }

        $proforma_invoice = ProformaInvoice::where('proforma_number', $proforma_number)
            ->with(['client', 'details.item'])
            ->firstOrFail();

        return view('backend.pages.proforma_invoice.show', compact('proforma_invoice'));
    }

    public function edit($proforma_number)
    {
        if (is_null($this->user) || !$this->user->can('proforma_invoice.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any proforma invoice !');
        }

        $proforma_invoice = ProformaInvoice::where('proforma_number', $proforma_number)
            ->with(['client', 'details.item'])
            ->firstOrFail();

        // Check if editing is allowed based on etat
        $allowed_etats = ['0', '1', '2', '3', '4'];
        if (!in_array($proforma_invoice->etat, $allowed_etats)) {
            session()->flash('error', 'Cette facture proforma ne peut pas être modifiée dans son état actuel.');
            return redirect()->route('admin.proforma-invoice.index');
        }

        if (BillingChain::proformaHasValidatedInvoice($proforma_number)) {
            session()->flash('error', 'Cette facture proforma ne peut pas être modifiée car une facture validée existe déjà.');
            return redirect()->route('admin.proforma-invoice.index');
        }

        $clients = EGRClient::orderBy('customer_name')->get();
        $defaultClient = EGRClient::where('mail', 'clientordinaire@gmail.com')->first();

        return view('backend.pages.proforma_invoice.edit', compact('proforma_invoice', 'clients', 'defaultClient'));
    }

    public function update(Request $request, $proforma_number)
    {
        if (is_null($this->user) || !$this->user->can('proforma_invoice.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any proforma invoice !');
        }

        $proforma_invoice = ProformaInvoice::where('proforma_number', $proforma_number)->firstOrFail();

        // Check if editing is allowed based on etat
        $allowed_etats = ['0', '1', '2', '3', '4'];
        if (!in_array($proforma_invoice->etat, $allowed_etats)) {
            session()->flash('error', 'Cette facture proforma ne peut pas être modifiée dans son état actuel.');
            return redirect()->route('admin.proforma-invoice.index');
        }

        if (BillingChain::proformaHasValidatedInvoice($proforma_number)) {
            session()->flash('error', 'Cette facture proforma ne peut pas être modifiée car une facture validée existe déjà.');
            return redirect()->route('admin.proforma-invoice.index');
        }

        $error = Validator::make($request->all(), [
            'client_id' => 'required',
            'item_id' => 'required|array',
            'quantity' => 'required|array',
            'quantity.*' => 'nullable|numeric|min:0.0001',
        ]);

        if ($error->fails()) {
            return redirect()->back()->withErrors($error)->withInput();
        }

        try {
            DB::beginTransaction();

            $lines = $this->normalizeProformaLines($request);
            if ($lines === null) {
                return redirect()->back()->withErrors(['error' => 'Veuillez sélectionner au moins un article dans la liste (produit fini ou marchandise).'])->withInput();
            }

            $item_ids = $lines['item_ids'];
            $quantities = $lines['quantities'];
            $item_prices = $lines['item_prices'];
            $client_id = $request->client_id;

            if ($request->filled('proforma_date')) {
                $proforma_invoice->proforma_date = Carbon::parse($request->proforma_date)->startOfDay();
            }

            // Get Setting to check vat_taxpayer
            $setting = Setting::first();
            $is_vat_taxpayer = $setting && $setting->vat_taxpayer == 1;

            // Update main proforma invoice
            $proforma_invoice->client_id = $client_id;
            $proforma_invoice->description = $request->description ?? '';
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
                if (in_array(strtolower($file->getClientOriginalExtension()), $allowed)) {
                    if ($proforma_invoice->document) {
                        Storage::disk('public')->delete($proforma_invoice->document);
                    }
                    $path = $file->store('documents/proforma', 'public');
                    $proforma_invoice->document = $path;
                }
            }
            $proforma_invoice->save();

            // Delete old details
            ProformaInvoiceDetail::where('proforma_number', $proforma_number)->delete();

            // Create new details
            $data1 = [];
            for ($count = 0; $count < count($item_ids); $count++) {
                $item = Item::billable()->where('id', $item_ids[$count])->first();
                if (!$item) {
                    DB::rollBack();
                    return redirect()->back()->withErrors([
                        'error' => 'Article invalide ou non facturable à la ligne ' . ($count + 1) . '.',
                    ])->withInput();
                }

                $item_price_input = $item_prices[$count] ?? $item->selling_price ?? 0;
                $unitPriceHt = round((float) $item_price_input, 2);
                $quantity = $quantities[$count] ?? 0;

                // item_price en BDD = PU HT ; la TVA est calculée par article via items.vat
                if ($is_vat_taxpayer) {
                    $vatRate = (float) ($item->vat_rate ?? $item->vat ?? 0); // %
                    $item_price_nvat = (float) $quantity * (float) $unitPriceHt;
                    $vat = $item_price_nvat * $vatRate / 100;
                    $item_total_amount = $item_price_nvat + $vat;
                    $item_price_wvat = $item_total_amount;
                } else {
                    $vat = 0;
                    $item_price_nvat = (float) $quantity * (float) $unitPriceHt;
                    $item_price_wvat = $item_total_amount = $item_price_nvat;
                }

                $data1[] = [
                    'proforma_number' => $proforma_number,
                    'proforma_date' => $proforma_invoice->proforma_date,
                    'proforma_signature' => $proforma_invoice->proforma_signature,
                    'proforma_signature_date' => $proforma_invoice->proforma_signature_date,
                    'client_id' => $client_id,
                    'item_id' => $item_ids[$count],
                    'item_designation' => $item->name, // Save item name from items table
                    'item_quantity' => $quantity,
                    'item_price' => $unitPriceHt,
                    'item_price_nvat' => $item_price_nvat,
                    'vat' => $vat,
                    'item_price_wvat' => $item_price_wvat,
                    'item_total_amount' => $item_total_amount,
                    'auteur' => $this->user->name,
                    'etat' => $proforma_invoice->etat,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }

            if (!empty($data1)) {
                ProformaInvoiceDetail::insert($data1);
            }

            DB::commit();
            session()->flash('success', 'La facture proforma est mise à jour avec succès!!');
            return redirect()->route('admin.proforma-invoice.index');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', __('messages.error_update') . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function destroy($proforma_number)
    {
        if (is_null($this->user) || !$this->user->can('proforma_invoice.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any proforma invoice !');
        }

        try {
            $proforma_invoice = ProformaInvoice::where('proforma_number', $proforma_number)->firstOrFail();
            
            // Prevent deletion if flag1=1 (means invoice is validated with etat=1 or 2)
            if ($proforma_invoice->flag1 == '1') {
                // Check if there's a delivery note with flag1=1 and a validated invoice
                $delivery_note = DeliveryNote::where('document_no', $proforma_number)
                    ->where('flag1', '1')
                    ->first();
                
                if ($delivery_note) {
                    $invoice = Invoice::where('document_no', $delivery_note->delivery_number)
                        ->whereIn('etat', ['1', '2'])
                        ->first();
                    if ($invoice) {
                        session()->flash('error', 'Cette facture proforma ne peut pas être supprimée car une facture validée (état ' . $invoice->etat . ') existe déjà.');
                        return redirect()->route('admin.proforma-invoice.index');
                    }
                }
            }

            DB::beginTransaction();
            
            // Delete details first
            ProformaInvoiceDetail::where('proforma_number', $proforma_number)->delete();
            
            // Delete main invoice
            $proforma_invoice->delete();

            DB::commit();
            session()->flash('success', 'La facture proforma est supprimée avec succès!!');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', __('messages.error_delete') . $e->getMessage());
        }

        return redirect()->route('admin.proforma-invoice.index');
    }

    public function validateInvoice($proforma_number)
    {
        if (is_null($this->user) || !$this->user->can('proforma_invoice.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any proforma invoice !');
        }

        try {
            DB::beginTransaction();

            $proforma_invoice = ProformaInvoice::where('proforma_number', $proforma_number)->firstOrFail();
            
            if ($proforma_invoice->etat == '0') {
                $proforma_invoice->etat = '1';
                $proforma_invoice->validated_by = $this->user->name;
                $proforma_invoice->save();

                // Update details
                ProformaInvoiceDetail::where('proforma_number', $proforma_number)->update(['etat' => '1']);

                DB::commit();
                session()->flash('success', 'La facture proforma est validée avec succès!!');
            } else {
                session()->flash('error', 'Cette facture proforma ne peut pas être validée dans son état actuel.');
            }
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', __('messages.error_validation') . $e->getMessage());
        }

        return redirect()->route('admin.proforma-invoice.index');
    }

    public function confirm($proforma_number)
    {
        if (is_null($this->user) || !$this->user->can('proforma_invoice.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any proforma invoice !');
        }

        try {
            DB::beginTransaction();

            $proforma_invoice = ProformaInvoice::where('proforma_number', $proforma_number)->firstOrFail();
            
            if ($proforma_invoice->etat == '1') {
                $proforma_invoice->etat = '2';
                $proforma_invoice->confirmed_by = $this->user->name;
                $proforma_invoice->save();

                // Update details
                ProformaInvoiceDetail::where('proforma_number', $proforma_number)->update(['etat' => '2']);

                DB::commit();
                session()->flash('success', 'La facture proforma est confirmée avec succès!!');
            } else {
                session()->flash('error', 'Cette facture proforma doit être validée (etat=1) avant d\'être confirmée.');
            }
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', __('messages.error_confirmation') . $e->getMessage());
        }

        return redirect()->route('admin.proforma-invoice.index');
    }

    public function approve($proforma_number)
    {
        if (is_null($this->user) || !$this->user->can('proforma_invoice.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to approve any proforma invoice !');
        }

        try {
            DB::beginTransaction();

            $proforma_invoice = ProformaInvoice::where('proforma_number', $proforma_number)->firstOrFail();
            
            if (in_array($proforma_invoice->etat, ['1', '2', '3'])) {
                $proforma_invoice->etat = '3';
                $proforma_invoice->approuved_by = $this->user->name;
                $proforma_invoice->save();

                // Update details
                ProformaInvoiceDetail::where('proforma_number', $proforma_number)->update(['etat' => '3']);

                DB::commit();
                session()->flash('success', 'La facture proforma est approuvée avec succès!!');
            } else {
                session()->flash('error', 'Cette facture proforma ne peut pas être approuvée dans son état actuel.');
            }
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', __('messages.error_approval') . $e->getMessage());
        }

        return redirect()->route('admin.proforma-invoice.index');
    }

    public function print($proforma_number)
    {
        if (is_null($this->user) || !$this->user->can('proforma_invoice.view')) {
            abort(403, 'Sorry !! You are Unauthorized to print this proforma invoice !');
        }
        $proforma_invoice = ProformaInvoice::where('proforma_number', $proforma_number)
            ->with(['client', 'details.item'])
            ->firstOrFail();
        $setting = Setting::first();
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.proforma_invoice', compact('proforma_invoice', 'setting'), [], 'UTF-8');
        return $pdf->download('Facture_Proforma_' . $proforma_number . '.pdf');
    }

    /**
     * PDF proforma en USD (conversion BIF → USD selon le taux à la date de la proforma).
     */
    public function printUsd($proforma_number)
    {
        if (is_null($this->user) || !$this->user->can('proforma_invoice.view')) {
            abort(403, 'Sorry !! You are Unauthorized to print this proforma invoice !');
        }
        $proforma_invoice = ProformaInvoice::where('proforma_number', $proforma_number)
            ->with(['client', 'details.item'])
            ->firstOrFail();
        $setting = Setting::first();
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])
            ->loadView('backend.pages.document.proforma_invoice_usd', compact('proforma_invoice', 'setting'), [], 'UTF-8');

        return $pdf->download('Facture_Proforma_USD_' . $proforma_number . '.pdf');
    }

    /**
     * @return array{item_ids: array, quantities: array, item_prices: array}|null
     */
    protected function normalizeProformaLines(Request $request): ?array
    {
        $rawIds = $request->input('item_id', []);
        $rawQty = $request->input('quantity', []);
        $rawPrices = $request->input('item_price', []);

        $item_ids = [];
        $quantities = [];
        $item_prices = [];

        foreach ($rawIds as $index => $id) {
            if ($id === null || $id === '') {
                continue;
            }

            $qty = $rawQty[$index] ?? null;
            if ($qty === null || $qty === '' || (float) $qty <= 0) {
                continue;
            }

            $item_ids[] = (int) $id;
            $quantities[] = (float) $qty;
            $item_prices[] = isset($rawPrices[$index]) && $rawPrices[$index] !== ''
                ? (float) $rawPrices[$index]
                : null;
        }

        if (empty($item_ids)) {
            return null;
        }

        return compact('item_ids', 'quantities', 'item_prices');
    }

}
