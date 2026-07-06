<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PDF;
use Validator;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceDetail;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\Invoice;
use App\Models\DeliveryNote;
use App\Models\Item;
use App\Models\EGRClient;
use App\Models\Setting;
use App\Support\BillingChain;

class SalesOrderController extends Controller
{
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
        if (is_null($this->user) || !$this->user->can('sales_order.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any sales order !');
        }
        $query = SalesOrder::with('client')->orderBy('id', 'desc');
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('sales_order_date', [$dateDebut, $dateFin]);
        } else {
            $query->take(100);
        }
        $sales_orders = $query->get();
        return view('backend.pages.sales_order.index', compact('sales_orders'));
    }

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('sales_order.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export sales orders !');
        }
        $query = SalesOrder::with('client')->orderBy('id', 'desc');
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->whereBetween('sales_order_date', [$dateDebut, $dateFin]);
        }
        $orderNumbers = $query->pluck('sales_order_number');
        $details = SalesOrderDetail::with(['salesOrder.client', 'item'])
            ->whereIn('sales_order_number', $orderNumbers)
            ->orderBy('sales_order_number')->orderBy('id')
            ->get();
        $rows = [];
        $i = 1;
        foreach ($details as $d) {
            $doc = $d->salesOrder;
            $etat = $doc && $doc->etat == '0' ? 'Brouillon' : ($doc && $doc->etat == '1' ? 'Validé' : ($doc && $doc->etat == '2' ? 'Confirmé' : ($doc && $doc->etat == '3' ? 'Approuvé' : ($doc->etat ?? ''))));
            $rows[] = [
                $i++,
                $d->sales_order_number,
                $doc && $doc->sales_order_date ? Carbon::parse($doc->sales_order_date)->format('d/m/Y') : '',
                $doc->sales_order_signature ?? '',
                $doc && $doc->client ? $doc->client->customer_name : 'N/A',
                $etat,
                $d->item_designation ?? ($d->item ? $d->item->name : ''),
                (float) ($d->item_quantity ?? 0),
                (float) ($d->item_price_nvat ?? $d->item_price ?? 0),
                (float) ($d->vat ?? 0),
                (float) ($d->item_total_amount ?? 0),
                $doc->auteur ?? '',
            ];
        }
        $headings = ['#', 'N° Bon de commande', 'Date', 'Signature', 'Client', 'Etat', 'Désignation', 'Quantité', 'PU HTVA', 'TVA', 'Total ligne', 'Auteur'];
        return \Excel::download(new \App\Exports\GenericIndexExport($rows, $headings), 'sales_orders.xlsx');
    }

    public function create($proforma_number = null)
    {
        if (is_null($this->user) || !$this->user->can('sales_order.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any sales order !');
        }
        $items = Item::orderBy('name')->get();
        $clients = EGRClient::orderBy('customer_name')->get();
        $proforma_invoice = null;
        $datas = collect();
        if ($proforma_number) {
            $proforma_invoice = ProformaInvoice::where('proforma_number', $proforma_number)->with(['client'])->firstOrFail();
            if ($proforma_invoice->etat != '3') {
                session()->flash('error', 'La facture proforma doit être approuvée (etat=3) pour créer un bon de commande.');
                return redirect()->route('admin.proforma-invoice.index');
            }
            $existing = SalesOrder::where('document_no', $proforma_number)->first();
            if ($existing) {
                session()->flash('info', 'Un bon de commande existe déjà pour cette facture proforma.');
                return redirect()->route('admin.sales-order.show', $existing->sales_order_number);
            }
            $datas = ProformaInvoiceDetail::where('proforma_number', $proforma_number)->with(['item'])->get();
        }
        $defaultClient = EGRClient::where('mail', 'clientordinaire@gmail.com')->first();
        return view('backend.pages.sales_order.create', compact('items', 'clients', 'proforma_invoice', 'proforma_number', 'datas', 'defaultClient'));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('sales_order.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any sales order !');
        }
        $rules = [
            'client_id' => 'required',
            'item_id.*' => 'required',
            'quantity.*' => 'required|numeric|min:1'
        ];
        $error = Validator::make($request->all(), $rules);
        if ($error->fails()) {
            return redirect()->back()->withErrors($error)->withInput();
        }
        try {
            DB::beginTransaction();
            $item_ids = $request->item_id ?? [];
            $quantities = $request->quantity ?? [];
            $client_id = $request->client_id;
            $proforma_number = $request->proforma_number ?? null;
            $document_no = $request->document_no ?? $proforma_number;
            if (empty($item_ids) || empty($quantities)) {
                return redirect()->back()->withErrors(['error' => 'Veuillez ajouter au moins un article.'])->withInput();
            }
            $latest = SalesOrder::orderBy('id', 'desc')->first();
            $sales_order_number = $latest ? str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT) : str_pad(1, 4, '0', STR_PAD_LEFT);
            $sales_order_date = Carbon::now();
            $sales_order_signature = "BC/" . Carbon::parse($sales_order_date)->format('YmdHis') . "/" . $sales_order_number;
            $setting = Setting::first();
            $is_vat_taxpayer = $setting && $setting->vat_taxpayer == 1;

            $sales_order = new SalesOrder();
            $sales_order->sales_order_number = $sales_order_number;
            $sales_order->sales_order_date = $sales_order_date;
            $sales_order->sales_order_signature = $sales_order_signature;
            $sales_order->sales_order_signature_date = $sales_order_date;
            $sales_order->client_id = $client_id;
            $sales_order->document_no = $document_no;
            $sales_order->description = $request->description ?? '';
            $sales_order->auteur = $this->user->name;
            $sales_order->etat = '0';
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                if (in_array(strtolower($file->getClientOriginalExtension()), ['pdf', 'jpg', 'jpeg', 'png'])) {
                    $sales_order->document = $file->store('documents/sales_order', 'public');
                }
            }
            $sales_order->save();

            $data1 = [];
            foreach ($item_ids as $count => $item_id) {
                $item = Item::find($item_ids[$count]);
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
                    'sales_order_number' => $sales_order_number,
                    'sales_order_date' => $sales_order_date,
                    'sales_order_signature' => $sales_order_signature,
                    'sales_order_signature_date' => $sales_order_date,
                    'client_id' => $client_id,
                    'document_no' => $document_no,
                    'item_id' => $item_ids[$count],
                    'item_designation' => $item->name,
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
                SalesOrderDetail::insert($data1);
            }
            DB::commit();
            session()->flash('success', 'Le bon de commande est créé avec succès !!');
            return redirect()->route('admin.sales-order.index');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Erreur: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function show($sales_order_number)
    {
        if (is_null($this->user) || !$this->user->can('sales_order.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any sales order !');
        }
        $sales_order = SalesOrder::where('sales_order_number', $sales_order_number)
            ->with(['client', 'details.item'])
            ->firstOrFail();
        return view('backend.pages.sales_order.show', compact('sales_order'));
    }

    public function edit($sales_order_number)
    {
        if (is_null($this->user) || !$this->user->can('sales_order.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any sales order !');
        }
        $sales_order = SalesOrder::where('sales_order_number', $sales_order_number)->with(['client', 'details.item'])->firstOrFail();
        if (!in_array($sales_order->etat, ['0', '1', '2', '3'])) {
            session()->flash('error', 'Ce bon de commande ne peut pas être modifié.');
            return redirect()->route('admin.sales-order.index');
        }
        if (BillingChain::hasValidatedInvoiceForDocument($sales_order_number)) {
            session()->flash('error', 'Ce bon de commande ne peut pas être modifié car une facture validée existe déjà.');
            return redirect()->route('admin.sales-order.index');
        }
        $proforma_invoice = $sales_order->document_no
            ? ProformaInvoice::where('proforma_number', $sales_order->document_no)->with(['client'])->first()
            : null;

        $sync = BillingChain::salesOrderEditFromPrevious($sales_order);
        $sales_order->setRelation('details', $sync['lines']);
        $formClient = $sync['client'];
        $formClientId = $sync['client_id'];
        $formDescription = $sync['description'];
        $syncedFromPrevious = $sync['synced_from_previous'];

        $clients = EGRClient::orderBy('customer_name')->get();
        $defaultClient = EGRClient::where('mail', 'clientordinaire@gmail.com')->first();
        return view('backend.pages.sales_order.edit', compact(
            'sales_order',
            'proforma_invoice',
            'clients',
            'defaultClient',
            'formClient',
            'formClientId',
            'formDescription',
            'syncedFromPrevious'
        ));
    }

    public function update(Request $request, $sales_order_number)
    {
        if (is_null($this->user) || !$this->user->can('sales_order.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any sales order !');
        }
        $sales_order = SalesOrder::where('sales_order_number', $sales_order_number)->firstOrFail();
        if (!in_array($sales_order->etat, ['0', '1', '2', '3'])) {
            session()->flash('error', 'Ce bon de commande ne peut pas être modifié.');
            return redirect()->route('admin.sales-order.index');
        }
        if (BillingChain::hasValidatedInvoiceForDocument($sales_order_number)) {
            session()->flash('error', 'Ce bon de commande ne peut pas être modifié car une facture validée existe déjà.');
            return redirect()->route('admin.sales-order.index');
        }
        $rules = ['client_id' => 'required', 'item_id.*' => 'required', 'quantity.*' => 'required|numeric|min:1'];
        $error = Validator::make($request->all(), $rules);
        if ($error->fails()) {
            return redirect()->back()->withErrors($error)->withInput();
        }
        try {
            DB::beginTransaction();
            $item_ids = $request->item_id ?? [];
            $quantities = $request->quantity ?? [];
            $client_id = $request->client_id;
            $document_no = $request->document_no ?? $sales_order->document_no;
            if (empty($item_ids) || empty($quantities)) {
                return redirect()->back()->withErrors(['error' => 'Veuillez ajouter au moins un article.'])->withInput();
            }
            $setting = Setting::first();
            $is_vat_taxpayer = $setting && $setting->vat_taxpayer == 1;

            $sales_order->client_id = $client_id;
            $sales_order->document_no = $document_no;
            $sales_order->description = $request->description ?? '';
            if ($request->filled('sales_order_date')) {
                $sales_order->sales_order_date = Carbon::parse($request->sales_order_date);
            }
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                if (in_array(strtolower($file->getClientOriginalExtension()), ['pdf', 'jpg', 'jpeg', 'png'])) {
                    if ($sales_order->document) {
                        Storage::disk('public')->delete($sales_order->document);
                    }
                    $sales_order->document = $file->store('documents/sales_order', 'public');
                }
            }
            $sales_order->save();

            SalesOrderDetail::where('sales_order_number', $sales_order_number)->delete();
            $data1 = [];
            foreach ($item_ids as $count => $item_id) {
                $item = Item::find($item_ids[$count]);
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
                    'sales_order_number' => $sales_order_number,
                    'sales_order_date' => $sales_order->sales_order_date,
                    'sales_order_signature' => $sales_order->sales_order_signature,
                    'sales_order_signature_date' => $sales_order->sales_order_signature_date,
                    'client_id' => $client_id,
                    'document_no' => $document_no,
                    'item_id' => $item_ids[$count],
                    'item_designation' => $item->name,
                    'item_quantity' => $quantity,
                    'item_price' => $unitPriceHt,
                    'item_price_nvat' => $item_price_nvat,
                    'vat' => $vat,
                    'item_price_wvat' => $item_price_wvat,
                    'item_total_amount' => $item_total_amount,
                    'auteur' => $this->user->name,
                    'etat' => $sales_order->etat,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            if (!empty($data1)) {
                SalesOrderDetail::insert($data1);
            }
            DB::commit();
            session()->flash('success', 'Le bon de commande est mis à jour avec succès !!');
            return redirect()->route('admin.sales-order.index');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Erreur: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function destroy($sales_order_number)
    {
        if (is_null($this->user) || !$this->user->can('sales_order.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any sales order !');
        }
        try {
            $sales_order = SalesOrder::where('sales_order_number', $sales_order_number)->firstOrFail();
            if (Invoice::where('document_no', $sales_order_number)->exists()) {
                session()->flash('error', 'Impossible de supprimer: une facture existe pour ce bon de commande.');
                return redirect()->route('admin.sales-order.index');
            }
            DB::beginTransaction();
            SalesOrderDetail::where('sales_order_number', $sales_order_number)->delete();
            if ($sales_order->document) {
                Storage::disk('public')->delete($sales_order->document);
            }
            $sales_order->delete();
            DB::commit();
            session()->flash('success', 'Le bon de commande est supprimé avec succès !!');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Erreur: ' . $e->getMessage());
        }
        return redirect()->route('admin.sales-order.index');
    }

    public function validateInvoice($sales_order_number)
    {
        if (is_null($this->user) || !$this->user->can('sales_order.validate')) {
            abort(403, 'Sorry !! You are Unauthorized to validate any sales order !');
        }
        try {
            $sales_order = SalesOrder::where('sales_order_number', $sales_order_number)->firstOrFail();
            if ($sales_order->etat == '0') {
                $sales_order->etat = '1';
                $sales_order->validated_by = $this->user->name;
                $sales_order->save();
                SalesOrderDetail::where('sales_order_number', $sales_order_number)->update(['etat' => '1']);
                session()->flash('success', 'Le bon de commande est validé avec succès !!');
            } else {
                session()->flash('error', 'Ce bon de commande ne peut pas être validé dans son état actuel.');
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
        return redirect()->route('admin.sales-order.index');
    }

    public function confirm($sales_order_number)
    {
        if (is_null($this->user) || !$this->user->can('sales_order.confirm')) {
            abort(403, 'Sorry !! You are Unauthorized to confirm any sales order !');
        }
        try {
            $sales_order = SalesOrder::where('sales_order_number', $sales_order_number)->firstOrFail();
            if ($sales_order->etat == '1') {
                $sales_order->etat = '2';
                $sales_order->confirmed_by = $this->user->name;
                $sales_order->save();
                SalesOrderDetail::where('sales_order_number', $sales_order_number)->update(['etat' => '2']);
                session()->flash('success', 'Le bon de commande est confirmé avec succès !!');
            } else {
                session()->flash('error', 'Le bon de commande doit être validé (etat=1) avant d\'être confirmé.');
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
        return redirect()->route('admin.sales-order.index');
    }

    public function approve($sales_order_number)
    {
        if (is_null($this->user) || !$this->user->can('sales_order.approuve')) {
            abort(403, 'Sorry !! You are Unauthorized to approve any sales order !');
        }
        try {
            $sales_order = SalesOrder::where('sales_order_number', $sales_order_number)->firstOrFail();
            if (in_array($sales_order->etat, ['1', '2', '3'])) {
                $sales_order->etat = '3';
                $sales_order->approuved_by = $this->user->name;
                $sales_order->save();
                SalesOrderDetail::where('sales_order_number', $sales_order_number)->update(['etat' => '3']);
                session()->flash('success', 'Le bon de commande est approuvé avec succès !!');
            } else {
                session()->flash('error', 'Ce bon de commande ne peut pas être approuvé dans son état actuel.');
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
        return redirect()->route('admin.sales-order.index');
    }

    public function print($sales_order_number)
    {
        if (is_null($this->user) || !$this->user->can('sales_order.view')) {
            abort(403, 'Sorry !! You are Unauthorized to print this sales order !');
        }
        $sales_order = SalesOrder::where('sales_order_number', $sales_order_number)->with(['client', 'details.item'])->firstOrFail();
        $setting = Setting::first();
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])->loadView('backend.pages.document.sales_order', compact('sales_order', 'setting'), [], 'UTF-8');
        return $pdf->download('Bon_Commande_' . $sales_order_number . '.pdf');
    }
}
