<?php

namespace App\Support;

use App\Models\DeliveryNote;
use App\Models\DeliveryNoteDetail;
use App\Models\EGRClient;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceDetail;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use Illuminate\Support\Collection;

class BillingChain
{
    public static function hasValidatedInvoiceForDocument(?string $documentNo): bool
    {
        if (!$documentNo) {
            return false;
        }

        return Invoice::where('document_no', $documentNo)
            ->whereIn('etat', ['1', '2'])
            ->exists();
    }

    public static function linkedInvoice(?string $documentNo): ?Invoice
    {
        if (!$documentNo) {
            return null;
        }

        return Invoice::where('document_no', $documentNo)->first();
    }

    public static function proformaHasValidatedInvoice(string $proformaNumber): bool
    {
        $deliveryNote = DeliveryNote::where('document_no', $proformaNumber)->first();
        if ($deliveryNote && self::hasValidatedInvoiceForDocument($deliveryNote->delivery_number)) {
            return true;
        }

        $salesOrder = SalesOrder::where('document_no', $proformaNumber)->first();
        if ($salesOrder && self::hasValidatedInvoiceForDocument($salesOrder->sales_order_number)) {
            return true;
        }

        return false;
    }

    /**
     * Charge les lignes du bon de commande depuis la proforma (document précédent).
     *
     * @return array{lines: Collection, synced_from_previous: bool, client: ?EGRClient, client_id: ?int, description: ?string}
     */
    public static function salesOrderEditFromPrevious(SalesOrder $salesOrder): array
    {
        $fallback = [
            'lines' => $salesOrder->relationLoaded('details') ? $salesOrder->details : $salesOrder->details()->with('item')->get(),
            'synced_from_previous' => false,
            'client' => $salesOrder->client,
            'client_id' => $salesOrder->client_id,
            'description' => $salesOrder->description,
        ];

        if (!$salesOrder->document_no) {
            return $fallback;
        }

        $proforma = ProformaInvoice::where('proforma_number', $salesOrder->document_no)
            ->with(['client', 'details.item'])
            ->first();

        if (!$proforma || $proforma->details->isEmpty()) {
            return $fallback;
        }

        return [
            'lines' => $proforma->details,
            'synced_from_previous' => true,
            'client' => $proforma->client,
            'client_id' => $proforma->client_id,
            'description' => $proforma->description,
        ];
    }

    /**
     * Charge les lignes de la facture depuis le BC ou le BL (document précédent).
     *
     * @return array{lines: Collection, synced_from_previous: bool, source_number: ?string, source_type: ?string, client: ?EGRClient, client_id: ?int, description: ?string, sales_order: ?SalesOrder, delivery_note: ?DeliveryNote}
     */
    public static function invoiceEditFromPrevious(Invoice $invoice): array
    {
        $sourceNumber = $invoice->document_no;
        $fallbackLines = InvoiceDetail::where('invoice_number', $invoice->invoice_number)
            ->with(['item'])
            ->get();

        $fallback = [
            'lines' => $fallbackLines,
            'synced_from_previous' => false,
            'source_number' => $sourceNumber,
            'source_type' => null,
            'client' => $invoice->client,
            'client_id' => $invoice->client_id,
            'description' => $invoice->description,
            'sales_order' => null,
            'delivery_note' => null,
        ];

        if (!$sourceNumber) {
            return $fallback;
        }

        $salesOrder = SalesOrder::where('sales_order_number', $sourceNumber)
            ->with(['client', 'details.item'])
            ->first();
        if ($salesOrder && $salesOrder->details->isNotEmpty()) {
            return [
                'lines' => $salesOrder->details,
                'synced_from_previous' => true,
                'source_number' => $sourceNumber,
                'source_type' => 'sales_order',
                'client' => $salesOrder->client,
                'client_id' => $salesOrder->client_id,
                'description' => $salesOrder->description,
                'sales_order' => $salesOrder,
                'delivery_note' => null,
            ];
        }

        $deliveryNote = DeliveryNote::where('delivery_number', $sourceNumber)
            ->with(['client', 'details.item'])
            ->first();
        if ($deliveryNote && $deliveryNote->details->isNotEmpty()) {
            return [
                'lines' => $deliveryNote->details,
                'synced_from_previous' => true,
                'source_number' => $sourceNumber,
                'source_type' => 'delivery_note',
                'client' => $deliveryNote->client,
                'client_id' => $deliveryNote->client_id,
                'description' => $deliveryNote->description,
                'sales_order' => null,
                'delivery_note' => $deliveryNote,
            ];
        }

        $salesOrder = SalesOrder::where('sales_order_number', $sourceNumber)->with('client')->first();
        $deliveryNote = DeliveryNote::where('delivery_number', $sourceNumber)->with('client')->first();

        return array_merge($fallback, [
            'sales_order' => $salesOrder,
            'delivery_note' => $deliveryNote,
        ]);
    }

    /**
     * Charge les lignes du BL depuis la proforma ou la facture (document précédent).
     *
     * @return array{lines: Collection, synced_from_previous: bool, proforma_number: ?string, proforma_invoice: ?ProformaInvoice, invoice_source: ?Invoice, client: ?EGRClient, client_id: ?int, description: ?string}
     */
    public static function deliveryNoteEditFromPrevious(DeliveryNote $deliveryNote): array
    {
        $documentNo = $deliveryNote->document_no;
        $fallbackLines = DeliveryNoteDetail::where('delivery_number', $deliveryNote->delivery_number)
            ->with(['item'])
            ->get();

        $fallback = [
            'lines' => $fallbackLines,
            'synced_from_previous' => false,
            'proforma_number' => null,
            'proforma_invoice' => null,
            'invoice_source' => null,
            'client' => $deliveryNote->client,
            'client_id' => $deliveryNote->client_id,
            'description' => $deliveryNote->description,
        ];

        if (!$documentNo) {
            return $fallback;
        }

        $proforma = ProformaInvoice::where('proforma_number', $documentNo)
            ->with(['client', 'details.item'])
            ->first();
        if ($proforma && $proforma->details->isNotEmpty()) {
            return [
                'lines' => $proforma->details,
                'synced_from_previous' => true,
                'proforma_number' => $documentNo,
                'proforma_invoice' => $proforma,
                'invoice_source' => null,
                'client' => $proforma->client,
                'client_id' => $proforma->client_id,
                'description' => $proforma->description,
            ];
        }

        $invoice = Invoice::where('invoice_number', $documentNo)
            ->with(['client', 'details.item'])
            ->first();
        if ($invoice && $invoice->details->isNotEmpty()) {
            return [
                'lines' => $invoice->details,
                'synced_from_previous' => true,
                'proforma_number' => null,
                'proforma_invoice' => null,
                'invoice_source' => $invoice,
                'client' => $invoice->client,
                'client_id' => $invoice->client_id,
                'description' => $invoice->description,
            ];
        }

        if ($proforma) {
            $fallback['proforma_number'] = $documentNo;
            $fallback['proforma_invoice'] = $proforma;
        } elseif ($invoice) {
            $fallback['invoice_source'] = $invoice;
        }

        return $fallback;
    }
}
