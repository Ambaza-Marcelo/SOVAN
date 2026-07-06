<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountSetting;
use App\Models\DeliveryNote;
use App\Models\EGRClient;
use App\Models\FinishedGoodReport;
use App\Models\FinishedGoodsSmStore;
use App\Models\FinishedGoodsSmStoreDetail;
use App\Models\FinishedGoodsSmStoreReport;
use App\Models\FinishedGoodsStore;
use App\Models\FinishedGoodsStoreDetail;
use App\Models\GoodsStore;
use App\Models\GoodsStoreDetail;
use App\Models\GoodsStoreReport;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Item;
use App\Models\NoteCredit;
use App\Models\NoteCreditDetail;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PDF;
use Validator;

class NoteCreditController extends Controller
{
    /** @var \App\Models\Admin|null */
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
        if (is_null($this->user) || !$this->user->can('note_credit.view')) {
            abort(403, 'Désolé, vous n\'êtes pas autorisé à consulter les notes de crédit.');
        }

        $credits = NoteCredit::with('client')->orderBy('id', 'desc')->limit(200)->get();

        return view('backend.pages.note_credit.index', compact('credits'));
    }

    public function show(string $invoice_number)
    {
        if (is_null($this->user) || !$this->user->can('note_credit.view')) {
            abort(403);
        }

        $credit = NoteCredit::with(['details.item', 'client'])
            ->where('invoice_number', $invoice_number)
            ->firstOrFail();

        $sourceInvoice = Invoice::where('invoice_number', $credit->invoice_ref)->first();
        $deliveryNote = null;
        if ($credit->document_no) {
            $deliveryNote = DeliveryNote::where('delivery_number', $credit->document_no)->first();
        }

        return view('backend.pages.note_credit.show', compact('credit', 'sourceInvoice', 'deliveryNote'));
    }

    /**
     * Formulaire de création depuis une facture déjà validée (état 1 ou 2).
     */
    public function noteCredit(string $invoice_number)
    {
        if (is_null($this->user) || !$this->user->can('note_credit.create')) {
            abort(403);
        }

        $invoice = Invoice::with(['details.item', 'client'])
            ->where('invoice_number', $invoice_number)
            ->firstOrFail();

        if (!in_array((string) $invoice->etat, ['1', '2'], true)) {
            session()->flash('error', 'Une note de crédit n\'est possible que pour une facture déjà validée (cash ou crédit).');

            return redirect()->route('admin.invoice.show', $invoice_number);
        }

        $invoiceTotal = (float) InvoiceDetail::where('invoice_number', $invoice_number)->sum('item_total_amount');
        $alreadyCredited = (float) NoteCreditDetail::whereHas('noteCredit', function ($q) use ($invoice_number) {
            $q->where('invoice_ref', $invoice_number)->where('statut', '2');
        })->sum('item_total_amount');
        $remaining = max(0, $invoiceTotal - $alreadyCredited);
        $setting = Setting::first();

        return view('backend.pages.note_credit.create_from_invoice', compact('invoice', 'invoiceTotal', 'alreadyCredited', 'remaining', 'setting'));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('note_credit.create')) {
            abort(403);
        }

        $rules = [
            'source_invoice_number' => 'required|string|max:50',
            'cn_motif' => 'required|in:1,2,3,4',
            'item_id' => 'required|array|min:1',
            'item_id.*' => 'required|integer|exists:items,id',
            'item_quantity' => 'required|array',
            'item_quantity.*' => 'nullable|numeric|min:0',
        ];

        $error = Validator::make($request->all(), $rules);
        if ($error->fails()) {
            return redirect()->back()->withErrors($error)->withInput();
        }

        $sourceNo = $request->source_invoice_number;
        $invoice = Invoice::with(['details', 'client'])->where('invoice_number', $sourceNo)->first();
        if (!$invoice || !in_array((string) $invoice->etat, ['1', '2'], true)) {
            session()->flash('error', 'Facture source introuvable ou non validée.');

            return redirect()->back()->withInput();
        }

        $cnMotif = (string) $request->cn_motif;
        $cnMotifDetail = $this->cnMotifLabel($cnMotif);

        $invoiceTotal = (float) InvoiceDetail::where('invoice_number', $sourceNo)->sum('item_total_amount');
        $alreadyCredited = (float) NoteCreditDetail::whereHas('noteCredit', function ($q) use ($sourceNo) {
            $q->where('invoice_ref', $sourceNo)->where('statut', '2');
        })->sum('item_total_amount');
        $remaining = max(0, $invoiceTotal - $alreadyCredited);

        $lines = [];
        $ncTotal = 0.0;
        $ncVat = 0.0;
        $ncNvat = 0.0;

        foreach ($request->item_id as $i => $itemId) {
            $qty = (float) ($request->item_quantity[$i] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $orig = InvoiceDetail::where('invoice_number', $sourceNo)->where('item_id', $itemId)->first();
            if (!$orig) {
                session()->flash('error', 'Article non présent sur la facture source.');

                return redirect()->back()->withInput();
            }
            if ($qty > (float) $orig->item_quantity + 1e-6) {
                session()->flash('error', 'La quantité au crédit ne peut pas dépasser la quantité facturée pour l\'article '.($orig->item_designation ?? '').'.');

                return redirect()->back()->withInput();
            }

            $qtyOrig = max((float) $orig->item_quantity, 1e-9);
            $ratio = $qty / $qtyOrig;
            $lineTotal = round(((float) ($orig->item_total_amount ?? 0)) * $ratio, 2);
            $lineVat = round(((float) ($orig->vat ?? 0)) * $ratio, 2);
            // Facture source : item_price = PU HT ; item_price_nvat = total HT ligne ; item_total_amount = TTC ligne
            $unitHt = (float) ($orig->item_price ?? 0);
            if ($unitHt <= 1e-9) {
                $unitHt = (float) ($orig->item_price_nvat ?? 0) / $qtyOrig;
            }
            $unitTtc = ((float) ($orig->item_total_amount ?? 0)) / $qtyOrig;
            $lineHt = round($unitHt * $qty, 2);
            $lineWvat = $lineTotal;

            $lines[] = [
                'orig' => $orig,
                'item_id' => (int) $itemId,
                'qty' => $qty,
                'line_total' => $lineTotal,
                'line_vat' => $lineVat,
                'line_ht' => $lineHt,
                'line_wvat' => $lineWvat,
                'pu_ttc' => round($unitTtc, 2),
                'ct' => (float) ($orig->item_ct ?? 0) * $ratio,
                'tl' => (float) ($orig->item_tl ?? 0) * $ratio,
            ];
            $ncTotal += $lineTotal;
            $ncVat += $lineVat;
            $ncNvat += $lineHt;
        }

        if ($lines === []) {
            session()->flash('error', 'Veuillez saisir au moins une quantité à créditer.');

            return redirect()->back()->withInput();
        }

        if ($ncTotal - $remaining > 0.02) {
            session()->flash('error', 'Le montant de la note de crédit dépasse le solde disponible sur la facture source (max '.number_format($remaining, 2, ',', ' ').' '.$invoice->invoice_currency.' ).');

            return redirect()->back()->withInput();
        }

        $invoiceDate = Carbon::now();
        $latest = NoteCredit::orderBy('id', 'desc')->first();
        $nextId = $latest ? ((int) $latest->id + 1) : 1;
        $faNumber = 'FA'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);

        $invoiceSignature = ($invoice->tp_TIN ?? '').'/'.config('app.obr_test_username').'/'.$invoiceDate->format('YmdHis').'/'.$faNumber;

        DB::beginTransaction();
        try {
            $header = new NoteCredit;
            $header->invoice_number = $faNumber;
            $header->invoice_date = $invoiceDate;
            $header->type_facture = 'FA';
            $header->invoice_type = 'FA';
            $header->tp_type = $invoice->tp_type;
            $header->tp_name = $invoice->tp_name;
            $header->tp_TIN = $invoice->tp_TIN;
            $header->tp_trade_number = $invoice->tp_trade_number;
            $header->tp_phone_number = $invoice->tp_phone_number;
            $header->tp_address_commune = $invoice->tp_address_commune;
            $header->tp_address_quartier = $invoice->tp_address_quartier;
            $header->tp_address_province = $invoice->tp_address_province ?? null;
            $header->tp_address_avenue = $invoice->tp_address_avenue ?? null;
            $header->tp_address_rue = $invoice->tp_address_rue ?? null;
            $header->tp_address_number = $invoice->tp_address_number ?? null;
            $header->vat_taxpayer = $invoice->vat_taxpayer;
            $header->ct_taxpayer = $invoice->ct_taxpayer;
            $header->tl_taxpayer = $invoice->tl_taxpayer;
            $header->tp_fiscal_center = $invoice->tp_fiscal_center;
            $header->tp_activity_sector = $invoice->tp_activity_sector;
            $header->tp_legal_form = $invoice->tp_legal_form;
            $header->payment_type = $invoice->payment_type;
            $header->invoice_currency = $invoice->invoice_currency ?? 'BIF';
            $header->client_id = $invoice->client_id;
            $header->document_no = $invoice->document_no;
            $header->invoice_ref = $sourceNo;
            $header->cancelled_invoice_ref = $sourceNo;
            $regNo = trim((string) ($invoice->invoice_registered_number ?? ''));
            if ($regNo !== '') {
                $header->invoice_registered_number = $regNo;
            }
            $header->cn_motif = $cnMotif;
            $header->description = $cnMotifDetail;
            $header->invoice_signature = $invoiceSignature;
            $header->invoice_signature_date = $invoiceDate;
            $header->auteur = $this->user->name;
            $header->etat = '0';
            $header->statut = '1';
            $header->save();

            $detailRows = [];
            foreach ($lines as $row) {
                /** @var InvoiceDetail $orig */
                $orig = $row['orig'];
                $item = Item::find($row['item_id']);
                $detailRows[] = [
                    'invoice_number' => $faNumber,
                    'invoice_date' => $invoiceDate,
                    'type_facture' => 'FA',
                    'tp_type' => $header->tp_type,
                    'tp_name' => $header->tp_name,
                    'tp_TIN' => $header->tp_TIN,
                    'invoice_type' => $header->invoice_type,
                    'tp_trade_number' => $header->tp_trade_number,
                    'tp_phone_number' => $header->tp_phone_number,
                    'tp_address_province' => $header->tp_address_province,
                    'tp_address_commune' => $header->tp_address_commune,
                    'tp_address_quartier' => $header->tp_address_quartier,
                    'tp_address_avenue' => $header->tp_address_avenue,
                    'tp_address_rue' => $header->tp_address_rue,
                    'tp_address_number' => $header->tp_address_number,
                    'vat_taxpayer' => $header->vat_taxpayer,
                    'ct_taxpayer' => $header->ct_taxpayer,
                    'tl_taxpayer' => $header->tl_taxpayer,
                    'tp_fiscal_center' => $header->tp_fiscal_center,
                    'tp_activity_sector' => $header->tp_activity_sector,
                    'tp_legal_form' => $header->tp_legal_form,
                    'payment_type' => $header->payment_type,
                    'cancelled_invoice_ref' => $sourceNo,
                    'cancelled_invoice' => '1',
                    'invoice_ref' => $sourceNo,
                    'cn_motif' => $cnMotif,
                    'document_no' => $invoice->document_no,
                    'invoice_currency' => $header->invoice_currency,
                    'invoice_signature' => $invoiceSignature,
                    'invoice_signature_date' => $invoiceDate,
                    'item_designation' => $orig->item_designation ?? ($item->name ?? ''),
                    'item_quantity' => $row['qty'],
                    'item_price' => $row['pu_ttc'],
                    'item_ct' => $row['ct'],
                    'item_tl' => $row['tl'],
                    'item_price_nvat' => $row['line_ht'],
                    'vat' => $row['line_vat'],
                    'item_price_wvat' => $row['line_wvat'],
                    'item_total_amount' => $row['line_total'],
                    'auteur' => $this->user->name,
                    'etat' => '0',
                    'statut' => '1',
                    'client_id' => $invoice->client_id,
                    'item_id' => $row['item_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            NoteCreditDetail::insert($detailRows);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Enregistrement impossible : '.$e->getMessage());

            return redirect()->back()->withInput();
        }

        session()->flash('success', 'Facture d\'avoir '.$faNumber.' enregistrée. Validez-la pour appliquer les écritures comptables et le stock (retour marchandises).');

        return redirect()->route('admin.note-de-credit.show', $faNumber);
    }

    /**
     * Retrouve le compte débité sur la facture (caisse ou créance client) à partir des écritures
     * déjà enregistrées (document_no = n° facture, ou à défaut n° bon de livraison lié).
     */
    protected function resolveCounterpartAccountIdFromSourceInvoice(Invoice $source): int
    {
        $invoiceRef = (string) $source->invoice_number;
        $expectedTtc = (float) InvoiceDetail::where('invoice_number', $invoiceRef)->sum('item_total_amount');
        $tol = max(0.02, abs($expectedTtc) * 1e-6);

        $docNos = array_values(array_unique(array_filter([
            $invoiceRef,
            (string) ($source->document_no ?? ''),
        ])));

        foreach ($docNos as $docNo) {
            $id = $this->findPrimaryDebitAccountIdForJournalDocument($docNo, $expectedTtc, $tol);
            if ($id > 0) {
                return $id;
            }
        }

        if ((int) ($source->account_id ?? 0) > 0) {
            return (int) $source->account_id;
        }
        if ($source->client && (int) ($source->client->account_id ?? 0) > 0) {
            return (int) $source->client->account_id;
        }

        return 0;
    }

    /**
     * Sur une facture validée, le « gros » débit TTC est la caisse (cash) ou la créance client (crédit).
     */
    protected function findPrimaryDebitAccountIdForJournalDocument(string $documentNo, float $expectedTtc, float $tol): int
    {
        if ($documentNo === '' || $expectedTtc <= 0) {
            return 0;
        }

        $candidates = AccountJournalEntryDetail::where('document_no', $documentNo)
            ->where('debit', '>', 0)
            ->orderByDesc('debit')
            ->get();

        foreach ($candidates as $row) {
            if (abs((float) $row->debit - $expectedTtc) <= $tol) {
                return (int) $row->account_id;
            }
        }

        $top = $candidates->first();
        if ($top && (float) $top->debit >= $expectedTtc * 0.5 - $tol) {
            return (int) $top->account_id;
        }

        return 0;
    }

    /**
     * Validation : écritures inverses de la facture + retour stock si cn_motif = 2 et BL approuvé.
     */
    public function validerFacture(Request $request, string $invoice_number)
    {
        if (is_null($this->user) || !$this->user->can('note_credit.validate')) {
            abort(403);
        }

        $nc = NoteCredit::with(['details.item', 'client'])->where('invoice_number', $invoice_number)->firstOrFail();
        if ((string) $nc->statut === '2') {
            session()->flash('error', 'Cette note de crédit est déjà validée (aucune annulation n\'est prévue).');

            return redirect()->back();
        }

        $source = Invoice::with('client')->where('invoice_number', $nc->invoice_ref)->firstOrFail();
        if (!in_array((string) $source->etat, ['1', '2'], true)) {
            session()->flash('error', 'La facture source n\'est plus dans un état compatible.');

            return redirect()->back();
        }

        $activeSetting = AccountSetting::where('active', 1)->first();
        if (!$activeSetting) {
            session()->flash('error', 'Paramètres comptables (AccountSetting) introuvables.');

            return redirect()->back();
        }

        $details = NoteCreditDetail::where('invoice_number', $invoice_number)->get();
        $itemTotal = (float) $details->sum('item_total_amount');
        $vatSum = (float) $details->sum('vat');

        DB::beginTransaction();
        try {
            $counterpartId = $this->resolveCounterpartAccountIdFromSourceInvoice($source);
            if ($counterpartId <= 0) {
                throw new \RuntimeException(
                    'Impossible de retrouver le compte contrepassé : aucune écriture débit TTC trouvée pour la facture '.
                    $source->invoice_number.' (journal document_no). Vérifiez que la facture a bien été validée et que les écritures existent.'
                );
            }

            $counterLabel = (string) $source->etat === '1' ? 'vente cash' : 'créance client';
            $cpState = $this->updateLedgerTotals($counterpartId, 'credit', $itemTotal);
            AccountJournalEntryDetail::insert([[
                'date' => $nc->invoice_date,
                'document_no' => $nc->invoice_number,
                'description' => 'FACTURE D\'AVOIR (contre-passation '.$counterLabel.')',
                'account_id' => $counterpartId,
                'libelle' => 'FACTURE D\'AVOIR',
                'credit' => $itemTotal,
                'balance' => $cpState['balance'],
                'created_at' => Carbon::now(),
            ]]);

            $salesByAccount = [];
            $salesLabel = [];
            foreach ($details as $d) {
                $itemId = (int) ($d->item_id ?? 0);
                if (!$itemId) {
                    continue;
                }
                // item_price_nvat = total HT de la ligne (comme sur invoice_details)
                $lineNvat = (float) ($d->item_price_nvat ?? 0);
                if ($lineNvat <= 0) {
                    $lineNvat = (float) ($d->item_total_amount ?? 0) - (float) ($d->vat ?? 0);
                }
                if ($lineNvat <= 0) {
                    continue;
                }
                $type = $this->resolveSalesTypeForItem($itemId);
                if ($type === 'FG_SM') {
                    $acc = (int) ($activeSetting->f_sm_selling_account_id ?? 0);
                    $label = 'VENTES PF SM (contre-passation avoir)';
                } elseif ($type === 'FG_MAIN') {
                    $acc = (int) ($activeSetting->f_selling_account_id ?? 0);
                    $label = 'VENTES PF (contre-passation avoir)';
                } elseif ($type === 'GOODS') {
                    $acc = (int) ($activeSetting->goods_selling_account_id ?? 0);
                    $label = 'VENTES MARCHANDISES (contre-passation avoir)';
                } else {
                    $acc = (int) ($activeSetting->service_selling_account_id ?? 0);
                    $label = 'VENTES SERVICES (contre-passation avoir)';
                }
                if (!$acc) {
                    throw new \RuntimeException('Compte de vente non configuré pour le type '.$type.'.');
                }
                $salesByAccount[$acc] = ($salesByAccount[$acc] ?? 0) + $lineNvat;
                $salesLabel[$acc] = $label;
            }

            $netTarget = $itemTotal - $vatSum;
            $salesSum = array_sum($salesByAccount);
            if ($salesSum > 0 && abs($salesSum - $netTarget) > 0.05) {
                $keys = array_keys($salesByAccount);
                $lastKey = end($keys);
                if ($lastKey !== false) {
                    $salesByAccount[$lastKey] = ($salesByAccount[$lastKey] ?? 0) + ($netTarget - $salesSum);
                }
            }

            if ($salesByAccount === [] && $itemTotal > $vatSum + 0.02) {
                throw new \RuntimeException('Impossible de ventiler le montant HT sur les comptes de vente (vérifiez les articles).');
            }

            foreach ($salesByAccount as $accId => $amount) {
                if ($amount <= 0) {
                    continue;
                }
                $st = $this->updateLedgerTotals((int) $accId, 'debit', (float) $amount);
                AccountJournalEntryDetail::insert([[
                    'date' => $nc->invoice_date,
                    'document_no' => $nc->invoice_number,
                    'description' => $salesLabel[$accId] ?? 'FACTURE D\'AVOIR',
                    'account_id' => (int) $accId,
                    'libelle' => $salesLabel[$accId] ?? 'FACTURE D\'AVOIR',
                    'debit' => (float) $amount,
                    'balance' => $st['balance'],
                    'created_at' => Carbon::now(),
                ]]);
            }

            $vatAcc = (int) ($activeSetting->vat_collec_account_id ?? 0);
            if ($vatSum > 0 && $vatAcc) {
                $vs = $this->updateLedgerTotals($vatAcc, 'debit', $vatSum);
                AccountJournalEntryDetail::insert([[
                    'date' => $nc->invoice_date,
                    'document_no' => $nc->invoice_number,
                    'description' => 'TVA COLLECTEE (contre-passation avoir)',
                    'account_id' => $vatAcc,
                    'libelle' => 'TVA COLLECTEE AVOIR',
                    'debit' => $vatSum,
                    'balance' => $vs['balance'],
                    'created_at' => Carbon::now(),
                ]]);
            }

            if ((string) $nc->cn_motif === '2' && $source->document_no) {
                $dn = DeliveryNote::where('delivery_number', $source->document_no)->first();
                if ($dn && (string) $dn->etat === '3') {
                    $this->applyCreditNoteStockReturn($nc, $details, $dn);
                }
            }

            $this->assertBalancedJournalForDocument($nc->invoice_number);

            $nc->statut = '2';
            $nc->etat = '1';
            $nc->validated_by = $this->user->name;
            $nc->save();
            NoteCreditDetail::where('invoice_number', $invoice_number)->update([
                'statut' => '2',
                'etat' => '1',
                'validated_by' => $this->user->name,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Validation impossible : '.$e->getMessage());

            return redirect()->back()->withInput();
        }

        session()->flash('success', 'Facture d\'avoir validée : écritures comptables inversées'.((string) $nc->cn_motif === '2' ? ' et stock mis à jour (retour).' : '.'));

        return redirect()->route('admin.note-de-credit.show', $invoice_number);
    }

    /**
     * PDF + transmission OBR au premier téléchargement (comme la facture).
     */
    public function facture(string $invoice_number)
    {
        if (is_null($this->user) || !$this->user->can('note_credit.view')) {
            abort(403);
        }

        $credit = NoteCredit::with(['details.item', 'client'])->where('invoice_number', $invoice_number)->firstOrFail();
        $setting = Setting::first();
        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])
            ->loadView('backend.pages.document.note_credit', compact('credit', 'setting'), [], 'UTF-8');

        if ((string) ($credit->statut_paied ?? '') === '1' || (string) ($credit->electronic_signature ?? '') !== '') {
            return $pdf->download('FA_'.$invoice_number.'.pdf');
        }

        if ((string) $credit->statut !== '2') {
            session()->flash('error', 'Validez d\'abord la note de crédit avant l\'envoi OBR à l\'impression.');

            return redirect()->route('admin.note-de-credit.show', $invoice_number);
        }

        $sourceInvoice = Invoice::where('invoice_number', $credit->invoice_ref)->first();
        $obrOriginalRef = $this->resolveObrOriginalInvoiceReference($sourceInvoice, $credit);

        if (!$this->sourceInvoiceHasObrElectronicSignature($sourceInvoice)) {
            session()->flash('warning', 'La facture « '.$credit->invoice_ref.' » n\'a pas encore d\'accusé OBR (imprimez-la une première fois avec succès depuis les factures). PDF téléchargé sans envoi de la facture d\'avoir à l\'OBR.');

            return $pdf->download('FA_'.$invoice_number.'.pdf');
        }

        try {
            $token = $this->obrLoginToken();
            if ($token === null) {
                session()->flash('error', 'Connexion OBR impossible (token manquant).');

                return redirect()->route('admin.note-de-credit.show', $invoice_number);
            }

            $items = [];
            foreach ($credit->details as $data) {
                if (!$data->item_id) {
                    continue;
                }
                $items[] = [
                    'item_designation' => $data->item_designation ?? optional($data->item)->name,
                    'item_quantity' => $data->item_quantity,
                    'item_price' => $data->item_price,
                    'item_ct' => $data->item_ct,
                    'item_tl' => $data->item_tl,
                    'item_price_nvat' => $data->item_price_nvat,
                    'vat' => $data->vat,
                    'item_price_wvat' => $data->item_price_wvat,
                    'item_total_amount' => $data->item_total_amount,
                ];
            }

            $url = rtrim((string) config('app.guzzle_test_url'), '/').'/ebms_api/addInvoice_confirm';
            $send = function (string $tk) use ($url, $credit, $setting, $obrOriginalRef, $items) {
                return Http::withHeaders([
                    'Authorization' => 'Bearer '.$tk,
                    'Accept' => 'application/json',
                ])->post($url, [
                'invoice_number' => $credit->invoice_number,
                'invoice_date' => $this->formatDateTimeForObr($credit->invoice_date),
                'tp_type' => $credit->tp_type,
                'tp_name' => $credit->tp_name,
                'tp_TIN' => $credit->tp_TIN,
                'tp_trade_number' => $credit->tp_trade_number,
                'tp_phone_number' => $credit->tp_phone_number,
                'tp_address_province' => $credit->tp_address_province,
                'tp_address_commune' => $credit->tp_address_commune,
                'tp_address_quartier' => $credit->tp_address_quartier,
                'tp_address_avenue' => $credit->tp_address_rue,
                'tp_address_rue' => $credit->tp_address_rue,
                'vat_taxpayer' => $credit->vat_taxpayer,
                'ct_taxpayer' => $credit->ct_taxpayer,
                'tl_taxpayer' => $credit->tl_taxpayer,
                'tp_fiscal_center' => $setting->tp_fiscal_center ?? $credit->tp_fiscal_center,
                'tp_activity_sector' => $credit->tp_activity_sector,
                'tp_legal_form' => $credit->tp_legal_form,
                'payment_type' => $credit->payment_type,
                'invoice_type' => 'FA',
                'customer_name' => optional($credit->client)->customer_name,
                'customer_TIN' => optional($credit->client)->customer_TIN,
                'vat_customer_payer' => optional($credit->client)->vat_customer_payer,
                'customer_address' => optional($credit->client)->customer_address,
                'invoice_signature' => $credit->invoice_signature,
                'invoice_identifier' => $credit->invoice_signature,
                'invoice_currency' => $credit->invoice_currency,
                'cancelled_invoice_ref' => $obrOriginalRef,
                'cancelled_invoice' => $credit->cancelled_invoice ?? '1',
                'invoice_ref' => $obrOriginalRef,
                'cn_motif' => (int) $credit->cn_motif,
                'invoice_signature_date' => $this->formatDateTimeForObr($credit->invoice_signature_date ?? $credit->invoice_date),
                'invoice_items' => $items,
                ]);
            };

            $response = $send($token);
            if (method_exists($response, 'unauthorized') && ($response->unauthorized() || $response->forbidden())) {
                $newToken = $this->obrLoginToken();
                if (!empty($newToken)) {
                    $token = $newToken;
                    $response = $send($token);
                }
            } elseif (method_exists($response, 'status') && in_array((int) $response->status(), [401, 403], true)) {
                $newToken = $this->obrLoginToken();
                if (!empty($newToken)) {
                    $token = $newToken;
                    $response = $send($token);
                }
            }

            $json = $response->json() ?? [];
            $successVal = $json['success'] ?? false;
            $ok = $successVal === true || $successVal === 1 || $successVal === '1'
                || strtolower((string) $successVal) === 'true';

            if (!$ok) {
                $apiMsg = (string) ($json['msg'] ?? '');
                if ($this->isObrOriginalInvoiceNotFoundMessage($apiMsg)) {
                    session()->flash('warning', 'L\'OBR ne trouve pas la facture de référence « '.$obrOriginalRef.' » sur cet environnement. PDF téléchargé localement sans accusé OBR pour la facture d\'avoir.');

                    return $pdf->download('FA_'.$invoice_number.'.pdf');
                }
                session()->flash('error', $apiMsg !== '' ? $apiMsg : 'Transmission OBR refusée.');

                return redirect()->route('admin.note-de-credit.show', $invoice_number);
            }

            $sig = $json['electronic_signature'] ?? null;
            DB::beginTransaction();
            $credit->electronic_signature = $sig;
            $credit->statut_paied = '1';
            $credit->save();
            NoteCreditDetail::where('invoice_number', $invoice_number)->update(['electronic_signature' => $sig, 'statut_paied' => '1']);
            DB::commit();
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            session()->flash('error', 'Erreur OBR / PDF : '.$e->getMessage());

            return redirect()->route('admin.note-de-credit.show', $invoice_number);
        }

        return $pdf->download('FA_'.$invoice_number.'.pdf');
    }

    protected function obrLoginToken(): ?string
    {
        $base = rtrim((string) config('app.guzzle_test_url'), '/');
        if ($base === '') {
            return null;
        }
        $resp = Http::post($base.'/ebms_api/login/', [
            'username' => config('app.obr_test_username'),
            'password' => config('app.obr_test_pwd'),
        ]);
        $json = $resp->json() ?? [];

        return $json['result']['token'] ?? null;
    }

    protected function cnMotifLabel(string $code): string
    {
        switch ($code) {
            case '2':
                return 'Retour marchandises';
            case '3':
                return 'Rabais';
            case '4':
                return 'Reduction hors facture';
            default:
                return 'Erreur sur la facture';
        }
    }

    /**
     * L'API OBR rejette les dates sérialisées en JSON (ISO 8601). Le modèle NoteCredit
     * caste invoice_date en datetime : il faut envoyer une chaîne explicite, comme une
     * facture Invoice lue depuis la BDD (Y-m-d H:i:s).
     */
    protected function formatDateTimeForObr($value): string
    {
        if ($value === null || $value === '') {
            return Carbon::now()->format('Y-m-d H:i:s');
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    /**
     * Numéro de facture à indiquer à l'OBR pour lier l'avoir (n° enregistré s'il existe, sinon n° interne FN…).
     */
    protected function resolveObrOriginalInvoiceReference(?Invoice $sourceInvoice, NoteCredit $credit): string
    {
        if ($sourceInvoice) {
            $reg = trim((string) ($sourceInvoice->invoice_registered_number ?? ''));
            if ($reg !== '') {
                return $reg;
            }
        }

        return (string) $credit->invoice_ref;
    }

    /**
     * La facture FN doit avoir été transmise à l'OBR au moins une fois (signature électronique enregistrée).
     */
    protected function sourceInvoiceHasObrElectronicSignature(?Invoice $sourceInvoice): bool
    {
        if (!$sourceInvoice) {
            return false;
        }

        return trim((string) ($sourceInvoice->electronic_signature ?? '')) !== '';
    }

    /**
     * Message OBR : aucune facture normale ne correspond à la référence fournie.
     */
    protected function isObrOriginalInvoiceNotFoundMessage(?string $msg): bool
    {
        if ($msg === null || $msg === '') {
            return false;
        }

        return false !== stripos($msg, 'Aucune facture normale')
            || false !== stripos($msg, 'numero de facture')
            || false !== stripos($msg, 'numéro de facture')
            || false !== stripos($msg, 'référence de la facture')
            || false !== stripos($msg, 'facture valide');
    }

    protected function resolveSalesTypeForItem(int $itemId): string
    {
        if (FinishedGoodsSmStoreDetail::where('item_id', $itemId)->exists()) {
            return 'FG_SM';
        }
        if (FinishedGoodsStoreDetail::where('item_id', $itemId)->exists()) {
            return 'FG_MAIN';
        }
        if (GoodsStoreDetail::where('item_id', $itemId)->exists()) {
            return 'GOODS';
        }

        return 'SERVICE';
    }

    /**
     * @return array{type: string, code_store: ?string, detail: mixed, store: mixed}
     */
    protected function resolveStockContextForItem(int $itemId): array
    {
        $fgSm = FinishedGoodsSmStoreDetail::where('item_id', $itemId)->first();
        if ($fgSm && $fgSm->code_store) {
            $store = FinishedGoodsSmStore::where('code', $fgSm->code_store)->first();

            return ['type' => 'FG_SM', 'code_store' => $fgSm->code_store, 'detail' => $fgSm, 'store' => $store];
        }
        $fg = FinishedGoodsStoreDetail::where('item_id', $itemId)->first();
        if ($fg && $fg->code_store) {
            $store = FinishedGoodsStore::where('code', $fg->code_store)->first();

            return ['type' => 'FG_MAIN', 'code_store' => $fg->code_store, 'detail' => $fg, 'store' => $store];
        }
        $g = GoodsStoreDetail::where('item_id', $itemId)->first();
        if ($g && $g->code_store) {
            $store = GoodsStore::where('code', $g->code_store)->first();

            return ['type' => 'GOODS', 'code_store' => $g->code_store, 'detail' => $g, 'store' => $store];
        }

        return ['type' => 'NONE', 'code_store' => null, 'detail' => null, 'store' => null];
    }

    protected function applyCreditNoteStockReturn(NoteCredit $nc, $details, DeliveryNote $dn): void
    {
        $insertExpense = [];
        $insertStock = [];
        $repFg = [];
        $repSm = [];
        $repGoods = [];
        $date = $nc->invoice_date ?? Carbon::now();

        foreach ($details as $line) {
            if (!$line->item_id) {
                continue;
            }
            $item = Item::find($line->item_id);
            if (!$item) {
                continue;
            }
            $storeType = (string) ($item->store_type ?? '1');
            if ($storeType === '2') {
                $st = $item->status;
                if ($st === 0 || $st === '0' || $st === 'inactive') {
                    continue;
                }
            }

            $ctx = $this->resolveStockContextForItem((int) $line->item_id);
            if ($ctx['type'] === 'NONE' || !$ctx['detail'] || !$ctx['store']) {
                continue;
            }

            $stockDetail = $ctx['detail'];
            $store = $ctx['store'];
            $qty = (float) $line->item_quantity;
            if ($qty <= 0) {
                continue;
            }

            $cump = (float) ($stockDetail->cump ?? $item->cump ?? 0);
            $qtyInitial = (float) $stockDetail->quantity;
            $expenseAmount = $cump * $qty;

            $stockDetail->quantity = $qtyInitial + $qty;
            $stockDetail->updated_by = $this->user->name;
            $stockDetail->save();

            if (!$item->default_account_id) {
                throw new \RuntimeException('Compte de charge article non configuré pour '.$item->name);
            }
            $idStock = (int) ($store->default_account_id ?? 0);
            if (!$idStock) {
                throw new \RuntimeException('Compte stock magasin non configuré.');
            }

            $ledExp = $this->updateLedgerTotals((int) $item->default_account_id, 'credit', $expenseAmount);
            $insertExpense[] = [
                'date' => $date,
                'document_no' => $nc->invoice_number,
                'description' => 'RETOUR STOCK (NOTE DE CREDIT)',
                'account_id' => (int) $item->default_account_id,
                'libelle' => 'RETOUR STOCK AVOIR',
                'credit' => $expenseAmount,
                'balance' => $ledExp['balance'],
                'created_at' => Carbon::now(),
            ];
            $ledSt = $this->updateLedgerTotals($idStock, 'debit', $expenseAmount);
            $insertStock[] = [
                'date' => $date,
                'document_no' => $nc->invoice_number,
                'description' => 'RETOUR STOCK (NOTE DE CREDIT)',
                'account_id' => $idStock,
                'libelle' => 'RETOUR STOCK AVOIR',
                'debit' => $expenseAmount,
                'balance' => $ledSt['balance'],
                'created_at' => Carbon::now(),
            ];

            $row = [
                'item_id' => $line->item_id,
                'code_store' => $ctx['code_store'],
                'quantity_stock_initial' => $qtyInitial,
                'value_stock_initial' => $qtyInitial * $cump,
                'quantity_stockin' => $qty,
                'value_stockin' => $expenseAmount,
                'stock_total' => $stockDetail->quantity,
                'quantity_stockout' => 0,
                'value_stockout' => 0,
                'quantity_stock_final' => $stockDetail->quantity,
                'value_stock_final' => $stockDetail->quantity * $cump,
                'cump' => $cump,
                'date' => $date,
                'type_transaction' => 'NOTE DE CREDIT RETOUR',
                'document_no' => $nc->invoice_number,
                'description' => 'Retour marchandises lié facture '.$nc->invoice_ref,
                'created_by' => $this->user->name,
                'created_at' => Carbon::now(),
            ];
            if ($ctx['type'] === 'FG_SM') {
                $repSm[] = $row;
            } elseif ($ctx['type'] === 'FG_MAIN') {
                $repFg[] = $row;
            } else {
                $repGoods[] = $row;
            }
        }

        if ($insertExpense !== []) {
            AccountJournalEntryDetail::insert($insertExpense);
        }
        if ($insertStock !== []) {
            AccountJournalEntryDetail::insert($insertStock);
        }
        if ($repFg !== []) {
            FinishedGoodReport::insert($repFg);
        }
        if ($repSm !== []) {
            FinishedGoodsSmStoreReport::insert($repSm);
        }
        if ($repGoods !== []) {
            GoodsStoreReport::insert($repGoods);
        }
    }

    protected function updateLedgerTotals(int $accountId, string $type, float $amount): array
    {
        $ledger = AccountLedger::firstOrNew(['account_id' => $accountId]);
        $currentDebit = (float) ($ledger->debit ?? 0);
        $currentCredit = (float) ($ledger->credit ?? 0);
        if ($type === 'debit') {
            $currentDebit += $amount;
        } else {
            $currentCredit += $amount;
        }
        $ledger->debit = $currentDebit;
        $ledger->credit = $currentCredit;
        $ledger->balance = $currentDebit - $currentCredit;
        $ledger->save();

        return [
            'debit' => $currentDebit,
            'credit' => $currentCredit,
            'balance' => $ledger->balance,
        ];
    }

    protected function assertBalancedJournalForDocument(string $documentNo, float $tolerance = 0.02): void
    {
        $row = DB::table('account_journal_entry_details')
            ->where('document_no', $documentNo)
            ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
            ->first();
        $diff = (float) ($row->d ?? 0) - (float) ($row->c ?? 0);
        if (abs($diff) > $tolerance) {
            throw new \RuntimeException('Écritures non équilibrées pour '.$documentNo.' (écart '.$diff.').');
        }
    }
}
