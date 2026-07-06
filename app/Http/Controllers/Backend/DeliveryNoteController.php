<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesStockDocumentApproval;
use App\Http\Controllers\Backend\Concerns\UpdatesLedgerTotals;
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
use App\Models\DeliveryNoteDetail;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\ItemDetail;
use App\Models\RawMaterialBigStore;
use App\Models\RawMaterialBigStoreDetail;
use App\Models\RawMaterialSmallStoreDetail;
use App\Models\EGRClient;
use App\Models\Setting;
use App\Support\BillingChain;
use App\Models\FinishedGoodsStore;
use App\Models\FinishedGoodsStoreDetail;
use App\Models\FinishedGoodReport;
use App\Models\FinishedGoodsSmStore;
use App\Models\FinishedGoodsSmStoreDetail;
use App\Models\FinishedGoodsSmStoreReport;
use App\Models\GoodsStore;
use App\Models\GoodsStoreDetail;
use App\Models\GoodsStoreReport;
use App\Models\Accounting\AccountCostCenter;
use App\Models\Accounting\AccountJournalEntry;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountCurrency;
use App\Models\Accounting\AccountSetting;

class DeliveryNoteController extends Controller
{
    use HandlesStockDocumentApproval;
    use UpdatesLedgerTotals;

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
        if (is_null($this->user) || !$this->user->can('delivery_note.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any delivery note !');
        }

        $query = DeliveryNote::with(['client'])->orderBy('id','desc');
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('delivery_date', [$dateDebut, $dateFin]);
        } else {
            $query->take(100);
        }
        $delivery_notes = $query->get();
        return view('backend.pages.delivery_note.index',compact('delivery_notes'));
    }

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('delivery_note.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export delivery notes !');
        }
        $query = DeliveryNote::with(['client'])->orderBy('id','desc');
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('delivery_date', [$dateDebut, $dateFin]);
        }
        $deliveryNumbers = $query->pluck('delivery_number');
        $details = DeliveryNoteDetail::with(['deliveryNote.client', 'item'])
            ->whereIn('delivery_number', $deliveryNumbers)
            ->orderBy('delivery_number')->orderBy('id')
            ->get();
        $rows = [];
        $i = 1;
        foreach ($details as $d) {
            $doc = $d->deliveryNote;
            $etat = $doc && $doc->etat == '0' ? 'Brouillon' : ($doc && $doc->etat == '1' ? 'Validée' : ($doc && $doc->etat == '2' ? 'Confirmée' : ($doc && $doc->etat == '3' ? 'Approuvée' : ($doc->etat ?? ''))));
            $rows[] = [
                $i++,
                $d->delivery_number,
                $doc && $doc->delivery_date ? Carbon::parse($doc->delivery_date)->format('d/m/Y') : '',
                $doc->delivery_signature ?? '',
                $doc && $doc->client ? $doc->client->customer_name : 'N/A',
                $doc->document_no ?? '',
                $etat,
                $d->item_designation ?? ($d->item ? $d->item->name : ''),
                (float) ($d->item_quantity ?? 0),
                (float) ($d->item_price_nvat ?? $d->item_price ?? 0),
                (float) ($d->vat ?? 0),
                (float) ($d->item_total_amount ?? 0),
                $doc->auteur ?? '',
            ];
        }
        $headings = ['#', 'Numéro BL', 'Date', 'Signature', 'Customer', 'Facture Proforma', 'Etat', 'Désignation', 'Quantité', 'PU HTVA', 'TVA', 'Total ligne', 'Auteur'];
        return Excel::download(new \App\Exports\GenericIndexExport($rows, $headings), 'delivery_notes.xlsx');
    }

    public function create($proforma_number)
    {
        if (is_null($this->user) || !$this->user->can('delivery_note.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any delivery note !');
        }

        // Get proforma invoice
        $proforma_invoice = ProformaInvoice::where('proforma_number', $proforma_number)
            ->with(['client'])
            ->firstOrFail();

        // Check if proforma invoice is approved (etat=3)
        if ($proforma_invoice->etat != '3') {
            session()->flash('error', 'La facture proforma doit être approuvée (etat=3) pour créer un bon de livraison.');
            return redirect()->route('admin.proforma-invoice.index');
        }

        // Check if delivery note already exists (flag1 = 1 means already delivered)
        if ($proforma_invoice->flag1 == '1') {
            $existing = DeliveryNote::where('document_no', $proforma_number)->first();
            if ($existing) {
                session()->flash('info', 'Un bon de livraison existe déjà pour cette facture proforma.');
                return redirect()->route('admin.delivery-note.show', $existing->delivery_number);
            }
        }

        // Get proforma invoice details from proforma_invoice_details table where proforma_number matches
        $datas = ProformaInvoiceDetail::where('proforma_number', $proforma_number)
            ->with(['item'])
            ->get();

        if ($datas->isEmpty()) {
            session()->flash('error', 'Aucun détail trouvé pour cette facture proforma.');
            return redirect()->route('admin.proforma-invoice.index');
        }

        $clients = EGRClient::orderBy('customer_name')->get();
        return view('backend.pages.delivery_note.create', compact('clients', 'datas', 'proforma_invoice', 'proforma_number'));
    }

    public function createFromInvoice($invoice_number)
    {
        if (is_null($this->user) || !$this->user->can('delivery_note.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any delivery note !');
        }
        $invoice = Invoice::where('invoice_number', $invoice_number)->with(['client'])->firstOrFail();
        if (!in_array($invoice->etat, ['0', '1', '2'])) {
            session()->flash('error', 'La facture doit exister pour créer un bon de livraison.');
            return redirect()->route('admin.invoice.index');
        }
        $existing = DeliveryNote::where('document_no', $invoice_number)->first();
        if ($existing) {
            session()->flash('info', 'Un bon de livraison existe déjà pour cette facture.');
            return redirect()->route('admin.delivery-note.show', $existing->delivery_number);
        }
        $datas = \App\Models\InvoiceDetail::where('invoice_number', $invoice_number)->with(['item'])->get();
        if ($datas->isEmpty()) {
            session()->flash('error', 'Aucun détail trouvé pour cette facture.');
            return redirect()->route('admin.invoice.index');
        }
        $clients = EGRClient::orderBy('customer_name')->get();
        return view('backend.pages.delivery_note.create_from_invoice', compact('clients', 'datas', 'invoice', 'invoice_number'));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('delivery_note.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any delivery note !');
        }

        $rules = array(
            'client_id' => 'required',
            'item_id.*' => 'required',
            'item_quantity.*' => 'required|numeric|min:1'
        );

        $error = Validator::make($request->all(), $rules);

        if($error->fails()){
            return redirect()->back()->withErrors($error)->withInput();
        }

        try {
            DB::beginTransaction();

            $item_ids = $request->item_id ?? [];
            $quantities = $request->item_quantity ?? [];
            $client_id = $request->client_id;
            $proforma_number = $request->proforma_number ?? null;
            $invoice_number = $request->invoice_number ?? null;
            $document_no = $request->document_no ?? $invoice_number ?? $proforma_number;
            
            if (empty($item_ids) || empty($quantities)) {
                return redirect()->back()->withErrors(['error' => 'Veuillez ajouter au moins un article.'])->withInput();
            }
            
            $latest = DeliveryNote::orderBy('id','desc')->first();
            if ($latest) {
                $delivery_number = 'BL' . str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT); 
            } else {
                $delivery_number = 'BL' . str_pad(1, 4, '0', STR_PAD_LEFT);  
            }

            $delivery_date = Carbon::now();
            $delivery_signature = "BL/" . Carbon::parse($delivery_date)->format('YmdHis') . "/" . $delivery_number;

            $client = EGRClient::where('id', $client_id)->first();
            
            // Get Setting to check vat_taxpayer
            $setting = Setting::first();
            $is_vat_taxpayer = $setting && $setting->vat_taxpayer == 1;

            // Create main delivery note
            $delivery_note = new DeliveryNote();
            $delivery_note->delivery_number = $delivery_number;
            $delivery_note->delivery_date = $delivery_date;
            $delivery_note->delivery_signature = $delivery_signature;
            $delivery_note->delivery_signature_date = $delivery_date;
            $delivery_note->client_id = $client_id;
            $delivery_note->document_no = $document_no;
            $delivery_note->description = $request->description ?? '';
            $delivery_note->auteur = $this->user->name;
            $delivery_note->etat = '0';
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                if (in_array(strtolower($file->getClientOriginalExtension()), ['pdf', 'jpg', 'jpeg', 'png'])) {
                    $delivery_note->document = $file->store('documents/delivery_note', 'public');
                }
            }
            $delivery_note->save();

            // Create details
            $data1 = [];
            for($count = 0; $count < count($item_ids); $count++) {
                $item = Item::where('id', $item_ids[$count])->first();
                if (!$item) continue;

                $item_price_input = $request->item_price[$count] ?? $item->selling_price ?? 0;
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
                    'delivery_number' => $delivery_number,
                    'delivery_date' => $delivery_date,
                    'delivery_signature' => $delivery_signature,
                    'delivery_signature_date' => $delivery_date,
                    'client_id' => $client_id,
                    'document_no' => $document_no, // Save proforma_number value in document_no
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
                DeliveryNoteDetail::insert($data1);
            }

            // Update ProformaInvoice flag1 = 1 to indicate it has been delivered
            if ($proforma_number) {
                ProformaInvoice::where('proforma_number', $proforma_number)->update(['flag1' => '1']);
            }

            DB::commit();
            session()->flash('success', 'Le bon de livraison est créé avec succès!!');
            return redirect()->route('admin.delivery-note.index');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', __('messages.error_creation') . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function show($delivery_number)
    {
        if (is_null($this->user) || !$this->user->can('delivery_note.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any delivery note !');
        }

        $delivery_note = DeliveryNote::where('delivery_number', $delivery_number)
            ->with(['client', 'details.item', 'proformaInvoice'])
            ->firstOrFail();

        return view('backend.pages.delivery_note.show', compact('delivery_note'));
    }

    public function edit($delivery_number)
    {
        if (is_null($this->user) || !$this->user->can('delivery_note.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any delivery note !');
        }

        $delivery_note = DeliveryNote::where('delivery_number', $delivery_number)
            ->with(['client'])
            ->firstOrFail();

        // Check if delivery note can be edited (etat = 0, 1, 2, or 3)
        if (!in_array($delivery_note->etat, ['0', '1', '2', '3'])) {
            session()->flash('error', 'Ce bon de livraison ne peut pas être modifié dans son état actuel.');
            return redirect()->route('admin.delivery-note.index');
        }

        if (BillingChain::hasValidatedInvoiceForDocument($delivery_number)) {
            $invoice = Invoice::where('document_no', $delivery_number)
                ->whereIn('etat', ['1', '2'])
                ->first();
            session()->flash('error', 'Ce bon de livraison ne peut pas être modifié car une facture validée (état ' . ($invoice->etat ?? '') . ') existe déjà.');
            return redirect()->route('admin.delivery-note.index');
        }

        $document_no = $delivery_note->document_no;
        if (!$document_no) {
            session()->flash('error', 'Aucun document source associé trouvé.');
            return redirect()->route('admin.delivery-note.index');
        }

        $sync = BillingChain::deliveryNoteEditFromPrevious($delivery_note);
        $proforma_details = $sync['lines'];

        if ($proforma_details->isEmpty()) {
            session()->flash('error', 'Aucun détail trouvé pour ce bon de livraison.');
            return redirect()->route('admin.delivery-note.index');
        }

        $proforma_invoice = $sync['proforma_invoice'];
        $proforma_number = $sync['proforma_number'];
        $invoice_source = $sync['invoice_source'];
        $formClient = $sync['client'];
        $formClientId = $sync['client_id'];
        $formDescription = $sync['description'];
        $syncedFromPrevious = $sync['synced_from_previous'];
        $readonlyForm = BillingChain::hasValidatedInvoiceForDocument($delivery_number);

        $items = Item::orderBy('name')->get();
        $clients = EGRClient::orderBy('customer_name')->get();
        $defaultClient = EGRClient::where('mail', 'clientordinaire@gmail.com')->first();

        return view('backend.pages.delivery_note.edit', compact(
            'delivery_note',
            'proforma_details',
            'proforma_invoice',
            'invoice_source',
            'items',
            'clients',
            'proforma_number',
            'document_no',
            'readonlyForm',
            'defaultClient',
            'formClient',
            'formClientId',
            'formDescription',
            'syncedFromPrevious'
        ));
    }

    public function update(Request $request, $delivery_number)
    {
        if (is_null($this->user) || !$this->user->can('delivery_note.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any delivery note !');
        }

        $delivery_note = DeliveryNote::where('delivery_number', $delivery_number)->firstOrFail();

        if (BillingChain::hasValidatedInvoiceForDocument($delivery_number)) {
            session()->flash('error', 'Ce bon de livraison ne peut pas être modifié car une facture validée existe déjà.');
            return redirect()->route('admin.delivery-note.index');
        }

        $rules = array(
            'client_id' => 'required',
            'item_id.*' => 'required',
            'item_quantity.*' => 'required|numeric|min:1'
        );

        $error = Validator::make($request->all(), $rules);

        if($error->fails()){
            return redirect()->back()->withErrors($error)->withInput();
        }

        try {
            DB::beginTransaction();

            $item_ids = $request->item_id ?? [];
            $quantities = $request->item_quantity ?? [];
            $client_id = $request->client_id;
            $document_no = $request->document_no ?? $delivery_note->document_no;

            if (empty($item_ids) || empty($quantities)) {
                return redirect()->back()->withErrors(['error' => 'Veuillez ajouter au moins un article.'])->withInput();
            }

            // Get Setting to check vat_taxpayer
            $setting = Setting::first();
            $is_vat_taxpayer = $setting && $setting->vat_taxpayer == 1;

            // Update main delivery note
            $delivery_note->client_id = $client_id;
            $delivery_note->document_no = $document_no;
            $delivery_note->description = $request->description ?? '';
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                if (in_array(strtolower($file->getClientOriginalExtension()), ['pdf', 'jpg', 'jpeg', 'png'])) {
                    if ($delivery_note->document) {
                        Storage::disk('public')->delete($delivery_note->document);
                    }
                    $delivery_note->document = $file->store('documents/delivery_note', 'public');
                }
            }
            $delivery_note->save();

            // Delete old details
            DeliveryNoteDetail::where('delivery_number', $delivery_number)->delete();

            // Create new details
            $data1 = [];
            for($count = 0; $count < count($item_ids); $count++) {
                $item = Item::where('id', $item_ids[$count])->first();
                if (!$item) continue;

                $item_price_input = $request->item_price[$count] ?? $item->selling_price ?? 0;
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
                    'delivery_number' => $delivery_number,
                    'delivery_date' => $delivery_note->delivery_date,
                    'delivery_signature' => $delivery_note->delivery_signature,
                    'delivery_signature_date' => $delivery_note->delivery_signature_date,
                    'client_id' => $client_id,
                    'document_no' => $document_no, // Save proforma_number value in document_no
                    'item_id' => $item_ids[$count],
                    'item_designation' => $item->name, // Save item name from items table
                    'item_quantity' => $quantity,
                    'item_price' => $unitPriceHt,
                    'item_price_nvat' => $item_price_nvat,
                    'vat' => $vat,
                    'item_price_wvat' => $item_price_wvat,
                    'item_total_amount' => $item_total_amount,
                    'auteur' => $this->user->name,
                    'etat' => $delivery_note->etat,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }

            if (!empty($data1)) {
                DeliveryNoteDetail::insert($data1);
            }

            DB::commit();
            session()->flash('success', 'Le bon de livraison est mis à jour avec succès!!');
            return redirect()->route('admin.delivery-note.index');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', __('messages.error_update') . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function destroy($delivery_number)
    {
        if (is_null($this->user) || !$this->user->can('delivery_note.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any delivery note !');
        }

        try {
            $delivery_note = DeliveryNote::where('delivery_number', $delivery_number)->firstOrFail();
            
            // Prevent deletion if flag1=1 (means invoice is validated with etat=1 or 2)
            if ($delivery_note->flag1 == '1') {
                $invoice = Invoice::where('document_no', $delivery_number)
                    ->whereIn('etat', ['1', '2'])
                    ->first();
                if ($invoice) {
                    session()->flash('error', 'Ce bon de livraison ne peut pas être supprimé car une facture validée (état ' . $invoice->etat . ') existe déjà.');
                    return redirect()->route('admin.delivery-note.index');
                }
            }

            DB::beginTransaction();
            
            // Delete details first
            DeliveryNoteDetail::where('delivery_number', $delivery_number)->delete();
            
            // Delete main delivery note
            $delivery_note->delete();

            DB::commit();
            session()->flash('success', 'Le bon de livraison est supprimé avec succès!!');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', __('messages.error_delete') . $e->getMessage());
        }

        return redirect()->route('admin.delivery-note.index');
    }

    public function validateInvoice($delivery_number)
    {
        if (is_null($this->user) || !$this->user->can('delivery_note.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any delivery note !');
        }

        try {
            DB::beginTransaction();

            $delivery_note = DeliveryNote::where('delivery_number', $delivery_number)->firstOrFail();
            
            if ($delivery_note->etat == '0') {
                $delivery_note->etat = '1';
                $delivery_note->validated_by = $this->user->name;
                $delivery_note->save();

                // Update details
                DeliveryNoteDetail::where('delivery_number', $delivery_number)->update(['etat' => '1']);

                DB::commit();
                session()->flash('success', 'Le bon de livraison est validé avec succès!!');
            } else {
                session()->flash('error', 'Ce bon de livraison ne peut pas être validé dans son état actuel.');
            }
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', __('messages.error_validation') . $e->getMessage());
        }

        return redirect()->back();
    }

    public function confirm($delivery_number)
    {
        if (is_null($this->user) || !$this->user->can('delivery_note.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any delivery note !');
        }

        $delivery_note = DeliveryNote::where('delivery_number', $delivery_number)->firstOrFail();

        if ((string) ($delivery_note->etat ?? '') !== '1') {
            session()->flash('error', 'Ce bon de livraison doit être validé (etat=1) avant d\'être confirmé.');

            return redirect()->back();
        }

        try {
            DB::beginTransaction();

            $delivery_note->etat = '2';
            $delivery_note->confirmed_by = $this->user->name;
            $delivery_note->save();

            DeliveryNoteDetail::where('delivery_number', $delivery_number)->update(['etat' => '2']);

            DB::commit();
            session()->flash('success', 'Le bon de livraison est confirmé avec succès!!');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', __('messages.error_confirmation') . $e->getMessage());
        }

        return redirect()->back();
    }

    public function approve($delivery_number)
    {
        if (is_null($this->user) || !$this->user->can('delivery_note.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to approve any delivery note !');
        }

        try {
            $delivery_note = DeliveryNote::where('delivery_number', $delivery_number)->firstOrFail();

            if ($response = $this->guardDeliveryNoteApprove($delivery_note)) {
                return $response;
            }

            // Envoi OBR désactivé temporairement — à réactiver ultérieurement.
            /*
            $obrStock = $this->sendDeliveryNoteStockMovementsToObr($delivery_note);
            if (!$obrStock['ok']) {
                session()->flash('error', $obrStock['message']);

                return redirect()->back();
            }
            */

            DB::beginTransaction();

            try {
                $delivery_note = DeliveryNote::where('delivery_number', $delivery_number)->lockForUpdate()->firstOrFail();

                if (!in_array((string) ($delivery_note->etat ?? ''), ['1', '2'], true) || (string) ($delivery_note->flag2 ?? '') === '1') {
                    DB::rollBack();
                    session()->flash('error', 'Ce bon de livraison ne peut pas être approuvé dans son état actuel.');

                    return redirect()->back();
                }

                $delivery_note->etat = '3';
                $delivery_note->approuved_by = $this->user->name;
                $delivery_note->save();

                DeliveryNoteDetail::where('delivery_number', $delivery_number)->update(['etat' => '3']);

                if ($delivery_note->flag2 != '1') {
                    $this->processStockAndAccounting($delivery_note);
                    $delivery_note->update(['flag2' => '1']);
                }

                $this->assertBalancedJournalForDocument($delivery_number);

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            session()->flash('success', 'Le bon de livraison est approuvé avec succès!!');
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            session()->flash('error', __('messages.error_approval') . $e->getMessage());
        }

        return redirect()->back();
    }

    public function print($delivery_number)
    {
        if (is_null($this->user) || !$this->user->can('delivery_note.view')) {
            abort(403, 'Sorry !! You are Unauthorized to print this delivery note !');
        }
        $delivery_note = DeliveryNote::where('delivery_number', $delivery_number)->with(['client', 'details.item'])->firstOrFail();
        $setting = Setting::first();
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.delivery_note', compact('delivery_note', 'setting'), [], 'UTF-8');
        return $pdf->download('Bon_Livraison_' . $delivery_number . '.pdf');
    }

    protected function processStockAndAccounting(DeliveryNote $delivery_note)
    {
        $delivery_number = $delivery_note->delivery_number;
        $details = DeliveryNoteDetail::where('delivery_number', $delivery_number)
            ->with(['item' => function ($q) {
                // On charge aussi le statut pour distinguer stockable / non stockable
                $q->select('id', 'name', 'code', 'cump', 'default_account_id', 'store_type', 'status');
            }])
            ->get();

        $insert_expense = [];
        $insert_stock_credit = [];
        $reportDataFinished = [];
        $reportDataFinishedSm = [];
        $reportDataGoods = [];

        $accountSetting = AccountSetting::where('active', 1)->first();
        $libelleStockVente = 'COUT DU STOCK VENDU (BL)';
        $libelleProductionVendue = 'production vendue';

        foreach ($details as $detail) {
            if (!$detail->item || !$detail->item_id) continue;

            $store_type = $detail->item->store_type ?? '1';

            // Pour les articles non stockables du stock des marchandises (services, etc.),
            // on ne touche ni au stock ni à la comptabilité.
            if ($store_type === '2') {
                $status = $detail->item->status;
                if ($status === 0 || $status === '0' || $status === 'inactive') {
                    continue;
                }
            }

            if ($store_type === '1') {
                // Produits finis: MAIN ou SM selon l'endroit réel de stockage
                $ctx = $this->resolveStockContextForItem((int)$detail->item_id);
                if ($ctx['type'] !== 'FG_MAIN' && $ctx['type'] !== 'FG_SM') {
                    throw new \Exception('Item "' . ($detail->item->name ?? 'N/A') . '" not found in finished goods stores (MAIN/SM).');
                }

                $code_store = $ctx['code_store'];
                $stockDetail = $ctx['detail'];
                $store = $ctx['store'];

                if ($stockDetail->quantity < $detail->item_quantity) {
                    throw new \Exception('Insufficient stock for item "' . ($detail->item->name ?? 'N/A') . '".');
                }
                $cump = $stockDetail->cump ?? $detail->item->cump ?? 0;
                $expenseAmount = $cump * $detail->item_quantity;
                $qtyInitial = $stockDetail->quantity;
                $stockDetail->quantity = $stockDetail->quantity - $detail->item_quantity;
                $stockDetail->updated_by = $this->user->name;
                $stockDetail->save();

                $productionOffsetId = $ctx['type'] === 'FG_SM'
                    ? ($accountSetting ? ($accountSetting->prod_sm_account_id ?? null) : null)
                    : ($accountSetting ? ($accountSetting->prod_bg_account_id ?? null) : null);
                if ($productionOffsetId) {
                    $productionOffsetId = (int) $productionOffsetId;
                }
                if (!$productionOffsetId) {
                    throw new \Exception(
                        'Paramètres comptables actifs : renseigner prod_sm_account_id (petite production) ou prod_bg_account_id (stock principal PF) selon le magasin de l\'article "' . ($detail->item->name ?? 'N/A') . '".'
                    );
                }
                $ledgerExpense = $this->updateLedgerTotals($productionOffsetId, 'debit', $expenseAmount);
                $insert_expense[] = [
                    'date' => $delivery_note->delivery_date,
                    'document_no' => $delivery_number,
                    'description' => $libelleProductionVendue,
                    'account_id' => $productionOffsetId,
                    'libelle' => $libelleProductionVendue,
                    'debit' => $expenseAmount,
                    'credit' => 0,
                    'balance' => $ledgerExpense['balance'],
                    'created_at' => Carbon::now(),
                ];
                $id_acc_stock = (int)($store->default_account_id ?? 0);
                if (!$id_acc_stock) throw new \Exception('Finished goods store default account not configured.');
                $ledgerStock = $this->updateLedgerTotals($id_acc_stock, 'credit', $expenseAmount);
                $insert_stock_credit[] = [
                    'date' => $delivery_note->delivery_date,
                    'document_no' => $delivery_number,
                    'description' => $libelleProductionVendue,
                    'account_id' => $id_acc_stock,
                    'libelle' => $libelleProductionVendue,
                    'debit' => 0,
                    'credit' => $expenseAmount,
                    'balance' => $ledgerStock['balance'],
                    'created_at' => Carbon::now(),
                ];
                $reportRow = [
                    'item_id' => $detail->item_id,
                    'code_store' => $code_store,
                    'quantity_stock_initial' => $qtyInitial,
                    'value_stock_initial' => $qtyInitial * $cump,
                    'quantity_stockout' => $detail->item_quantity,
                    'value_stockout' => $expenseAmount,
                    'quantity_stock_final' => $stockDetail->quantity,
                    'value_stock_final' => $stockDetail->quantity * $cump,
                    'cump' => $cump,
                    'date' => $delivery_note->delivery_date,
                    'type_transaction' => 'VENTE',
                    'document_no' => $delivery_number,
                    'description' => 'Bon de livraison: ' . $delivery_number,
                    'created_by' => $this->user->name,
                    'created_at' => Carbon::now(),
                ];
                if ($ctx['type'] === 'FG_SM') {
                    $reportDataFinishedSm[] = $reportRow;
                } else {
                    $reportDataFinished[] = $reportRow;
                }
            } else {
                // store_type == 2: Stock des marchandises
                $ctx = $this->resolveStockContextForItem((int)$detail->item_id);
                if ($ctx['type'] !== 'GOODS') {
                    throw new \Exception('Item "' . ($detail->item->name ?? 'N/A') . '" not found in goods store.');
                }
                $code_store = $ctx['code_store'];
                $stockDetail = $ctx['detail'];
                $store = $ctx['store'];
                if ($stockDetail->quantity < $detail->item_quantity) {
                    throw new \Exception('Insufficient stock for item "' . ($detail->item->name ?? 'N/A') . '".');
                }
                $cump = $stockDetail->cump ?? $detail->item->cump ?? 0;
                $expenseAmount = $cump * $detail->item_quantity;
                $qtyInitial = $stockDetail->quantity;
                $stockDetail->quantity = $stockDetail->quantity - $detail->item_quantity;
                $stockDetail->updated_by = $this->user->name;
                $stockDetail->save();

                if (!$detail->item->default_account_id) {
                    throw new \Exception('Item "' . ($detail->item->name ?? 'N/A') . '" default account not configured.');
                }
                $ledgerExpense = $this->updateLedgerTotals($detail->item->default_account_id, 'debit', $expenseAmount);
                $insert_expense[] = [
                    'date' => $delivery_note->delivery_date,
                    'document_no' => $delivery_number,
                    'description' => $libelleStockVente,
                    'account_id' => $detail->item->default_account_id,
                    'libelle' => $libelleStockVente,
                    'debit' => $expenseAmount,
                    'credit' => 0,
                    'balance' => $ledgerExpense['balance'],
                    'created_at' => Carbon::now(),
                ];
                $id_acc_stock = (int)($store->default_account_id ?? 0);
                if (!$id_acc_stock) throw new \Exception('Goods store default account not configured.');
                $ledgerStock = $this->updateLedgerTotals($id_acc_stock, 'credit', $expenseAmount);
                $insert_stock_credit[] = [
                    'date' => $delivery_note->delivery_date,
                    'document_no' => $delivery_number,
                    'description' => $libelleStockVente,
                    'account_id' => $id_acc_stock,
                    'libelle' => $libelleStockVente,
                    'debit' => 0,
                    'credit' => $expenseAmount,
                    'balance' => $ledgerStock['balance'],
                    'created_at' => Carbon::now(),
                ];
                $reportDataGoods[] = [
                    'item_id' => $detail->item_id,
                    'code_store' => $code_store,
                    'quantity_stock_initial' => $qtyInitial,
                    'value_stock_initial' => $qtyInitial * $cump,
                    'quantity_stockout' => $detail->item_quantity,
                    'value_stockout' => $expenseAmount,
                    'quantity_stock_final' => $stockDetail->quantity,
                    'value_stock_final' => $stockDetail->quantity * $cump,
                    'cump' => $cump,
                    'date' => $delivery_note->delivery_date,
                    'type_transaction' => 'VENTE',
                    'document_no' => $delivery_number,
                    'description' => 'Bon de livraison: ' . $delivery_number,
                    'created_by' => $this->user->name,
                    'created_at' => Carbon::now(),
                ];
            }
        }

        if (!empty($insert_expense)) {
            AccountJournalEntryDetail::insert($insert_expense);
        }
        if (!empty($insert_stock_credit)) {
            AccountJournalEntryDetail::insert($insert_stock_credit);
        }
        if (!empty($reportDataFinished)) {
            FinishedGoodReport::insert($reportDataFinished);
        }
        if (!empty($reportDataFinishedSm)) {
            FinishedGoodsSmStoreReport::insert($reportDataFinishedSm);
        }
        if (!empty($reportDataGoods)) {
            GoodsStoreReport::insert($reportDataGoods);
        }
    }

    /**
     * Resolve stock context based on the store detail where the item exists.
     *
     * Returns:
     * - type: FG_SM | FG_MAIN | GOODS | NONE
     * - code_store
     * - detail (model instance)
     * - store (model instance with default_account_id)
     */
    protected function resolveStockContextForItem(int $itemId): array
    {
        $fgSmDetail = FinishedGoodsSmStoreDetail::where('item_id', $itemId)->first();
        if ($fgSmDetail && $fgSmDetail->code_store) {
            $store = FinishedGoodsSmStore::where('code', $fgSmDetail->code_store)->first();
            return [
                'type' => 'FG_SM',
                'code_store' => $fgSmDetail->code_store,
                'detail' => $fgSmDetail,
                'store' => $store,
            ];
        }

        $fgDetail = FinishedGoodsStoreDetail::where('item_id', $itemId)->first();
        if ($fgDetail && $fgDetail->code_store) {
            $store = FinishedGoodsStore::where('code', $fgDetail->code_store)->first();
            return [
                'type' => 'FG_MAIN',
                'code_store' => $fgDetail->code_store,
                'detail' => $fgDetail,
                'store' => $store,
            ];
        }

        $goodsDetail = GoodsStoreDetail::where('item_id', $itemId)->first();
        if ($goodsDetail && $goodsDetail->code_store) {
            $store = GoodsStore::where('code', $goodsDetail->code_store)->first();
            return [
                'type' => 'GOODS',
                'code_store' => $goodsDetail->code_store,
                'detail' => $goodsDetail,
                'store' => $store,
            ];
        }

        return [
            'type' => 'NONE',
            'code_store' => null,
            'detail' => null,
            'store' => null,
        ];
    }

    /**
     * Ensure journal entry details for a document are balanced (sum debit == sum credit).
     */
    protected function assertBalancedJournalForDocument(string $documentNo, float $tolerance = 0.01): void
    {
        $row = DB::table('account_journal_entry_details')
            ->where('document_no', $documentNo)
            ->selectRaw('COALESCE(SUM(debit),0) as debit_sum, COALESCE(SUM(credit),0) as credit_sum')
            ->first();

        $debit = (float)($row->debit_sum ?? 0);
        $credit = (float)($row->credit_sum ?? 0);
        $diff = $debit - $credit;

        if (abs($diff) > $tolerance) {
            throw new \RuntimeException("Ecritures non équilibrées pour {$documentNo}: Débit={$debit}, Crédit={$credit}, Diff={$diff}");
        }
    }

    /**
     * Construit les mouvements de stock OBR pour un BL (mêmes lignes que processStockAndAccounting).
     * Appeler après processStockAndAccounting : le CUMP en magasin est inchangé.
     */
    protected function buildDeliveryNoteObrStockMovements(DeliveryNote $delivery_note): array
    {
        $delivery_number = $delivery_note->delivery_number;
        $details = DeliveryNoteDetail::where('delivery_number', $delivery_number)
            ->with(['item' => function ($q) {
                $q->select('id', 'name', 'code', 'cump', 'default_account_id', 'store_type', 'status');
            }])
            ->get();

        $currency = $delivery_note->delivery_currency ?: 'BIF';
        $movementDate = Carbon::now()->toDateTimeString();

        $movements = [];
        foreach ($details as $detail) {
            if (!$detail->item || !$detail->item_id) {
                continue;
            }

            $store_type = $detail->item->store_type ?? '1';
            if ($store_type === '2') {
                $status = $detail->item->status;
                if ($status === 0 || $status === '0' || $status === 'inactive') {
                    continue;
                }
            }

            if ($store_type === '1') {
                $ctx = $this->resolveStockContextForItem((int) $detail->item_id);
                if ($ctx['type'] !== 'FG_MAIN' && $ctx['type'] !== 'FG_SM') {
                    continue;
                }
            } else {
                $ctx = $this->resolveStockContextForItem((int) $detail->item_id);
                if ($ctx['type'] !== 'GOODS') {
                    continue;
                }
            }

            $stockDetail = $ctx['detail'];
            $cump = (float) ($stockDetail->cump ?? $detail->item->cump ?? 0);

            $code = trim((string) ($detail->item->code ?? ''));
            if ($code === '') {
                // On refuse d'émettre un mouvement OBR sans code article (risque de doublons / refus silencieux côté OBR)
                throw new \RuntimeException('Code article OBR manquant pour : ' . ($detail->item->name ?? ('#' . ($detail->item_id ?? ''))));
            }

            $movements[] = [
                'item_code' => $code,
                'item_designation' => (string) ($detail->item->name ?? ''),
                'item_quantity' => $detail->item_quantity,
                'item_measurement_unit' => 'PCS',
                'item_purchase_or_sale_price' => $cump,
                'item_cost_price' => $cump,
                'item_purchase_or_sale_currency' => $currency,
                // Sortie vente (aligné sur les types utilisés dans GoodStockoutController)
                'item_movement_type' => 'SN',
                'item_movement_invoice_ref' => $delivery_number,
                'item_movement_description' => 'SORTIES NORMALES DE VENTE DES MARCHANDISES (BL)',
                'item_movement_date' => $movementDate,
            ];
        }

        return $movements;
    }

    /**
     * Connexion OBR + envoi AddStockMovement par ligne (comme InvoiceController + retry token).
     *
     * @return array{ok: bool, message: string}
     */
    protected function sendDeliveryNoteStockMovementsToObr(DeliveryNote $delivery_note): array
    {
        $base = rtrim((string) config('app.guzzle_test_url'), '/');
        if ($base === '') {
            return ['ok' => false, 'message' => 'URL du service OBR non configurée (guzzle_test_url).'];
        }

        try {
            $movements = $this->buildDeliveryNoteObrStockMovements($delivery_note);
            if ($movements === []) {
                return ['ok' => true, 'message' => ''];
            }

            $login = function () use ($base): ?string {
                $loginResponse = Http::post($base . '/ebms_api/login/', [
                    'username' => config('app.obr_test_username'),
                    'password' => config('app.obr_test_pwd'),
                ]);
                $loginJson = $loginResponse->json() ?? [];

                return $loginJson['result']['token'] ?? null;
            };

            $token = $login();
            if (empty($token)) {
                return ['ok' => false, 'message' => 'Connexion au service OBR impossible (token manquant).'];
            }

            $movementUrl = $base . '/ebms_api/AddStockMovement';

            foreach ($movements as $m) {
                $payload = [
                    'system_or_device_id' => config('app.obr_test_username'),
                    'item_code' => $m['item_code'] ?? '',
                    'item_designation' => $m['item_designation'] ?? '',
                    'item_quantity' => $m['item_quantity'] ?? 0,
                    'item_measurement_unit' => ($m['item_measurement_unit'] ?? 'PCS') ?: 'PCS',
                    'item_cost_price' => $m['item_purchase_or_sale_price'] ?? ($m['item_cost_price'] ?? 0),
                    'item_purchase_or_sale_currency' => $m['item_purchase_or_sale_currency'] ?? 'BIF',
                    'item_movement_type' => $m['item_movement_type'] ?? 'SN',
                    'item_movement_invoice_ref' => $m['delivery_number'] ?? '',
                    'item_movement_description' => $m['item_movement_description'] ?? '',
                    'item_movement_date' => $m['item_movement_date'] ?? Carbon::now()->toDateTimeString(),
                ];

               

                $send = function (string $tk) use ($movementUrl, $payload) {
                    return Http::withHeaders([
                        'Authorization' => 'Bearer ' . $tk,
                        'Accept' => 'application/json',
                    ])->post($movementUrl, $payload);
                };

                $resp = $send($token);

                if (method_exists($resp, 'unauthorized') && ($resp->unauthorized() || $resp->forbidden())) {
                    $newToken = $login();
                    if (!empty($newToken)) {
                        $token = $newToken;
                        $resp = $send($token);
                    }
                } elseif (method_exists($resp, 'status') && in_array((int) $resp->status(), [401, 403], true)) {
                    $newToken = $login();
                    if (!empty($newToken)) {
                        $token = $newToken;
                        $resp = $send($token);
                    }
                }

                $json = $resp->json() ?? [];
                $ok = $json['success'] ?? false;

                if (!$ok) {
                    $status = method_exists($resp, 'status') ? (int) $resp->status() : 0;
                    $rawMsg = (string) ($json['msg'] ?? '');
                    if (trim($rawMsg) === '' && method_exists($resp, 'body')) {
                        $rawMsg = (string) $resp->body();
                    }
                    if (trim($rawMsg) === '') {
                        $rawMsg = 'Transmission stock OBR refusée.';
                    }

                    // Si HTTP != 200/201 et msg vide, on remonte le code pour diagnostic.
                    if ($status !== 0 && trim((string) $rawMsg) === 'Transmission stock OBR refusée.') {
                        $rawMsg = 'Transmission stock OBR refusée (HTTP ' . $status . ').';
                    }

                    return ['ok' => false, 'message' => $this->sanitizeDeliveryNoteObrErrorMessage($rawMsg)];
                }
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $this->sanitizeDeliveryNoteObrErrorMessage($e->getMessage() ?: 'Erreur lors de la transmission à l’OBR. Veuillez réessayer plus tard.')];
        }

        return ['ok' => true, 'message' => ''];
    }

    protected function sanitizeDeliveryNoteObrErrorMessage(string $raw): string
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

}
