<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    @include('backend.pages.document.partials._document_styles')
    <style>
        /* Keep amounts on a single line */
        .doc-table td.text-right { white-space: nowrap; }
    </style>
</head>
<body style="font-family: 'DejaVu Sans', sans-serif;">
    @php
        $date = $date ?? $sales_order->sales_order_date ?? null;
        $documentTitle = 'BON DE COMMANDE';
        $documentNumber = $sales_order->sales_order_number ?? '';
        $documentDate = $sales_order->sales_order_date ? \Carbon\Carbon::parse($sales_order->sales_order_date)->format('d/m/Y H:i') : '';
        $customerName = $sales_order->client->customer_name ?? '';
        $customerAddress = $sales_order->client->customer_address ?? '';
        $customerPhone = $sales_order->client->telephone ?? '';
        $customerNif = $sales_order->client->customer_TIN ?? '';
        $customerSubjectToVat = (optional($sales_order->client)->vat_customer_payer && !in_array(optional($sales_order->client)->vat_customer_payer, ['0', 'non', 'no', 'false'], true)) ? 'Oui' : 'Non';
        $details = $sales_order->details ?? collect();
        $totalAmount = $details->sum('item_total_amount');
        $grandTotal = $totalAmount;
        $hasVat = ((string) ($sales_order->vat_taxpayer ?? '0')) === '1' || $details->sum('vat') > 0;
    @endphp
    @include('backend.pages.document.partials._document_header')

    @include('backend.pages.document.partials._document_customer_date')

    <table class="doc-table">
        <thead>
            <tr>
                <th>N°</th>
                <th>@lang('messages.Désignation')</th>
                <th class="text-right">@lang('messages.Quantité')</th>
                @if($hasVat)
                <th class="text-right">@lang('messages.PU HTVA')</th>
                <th class="text-right">@lang('messages.PT HTVA')</th>
                <th class="text-right">@lang('messages.Taux TVA')</th>
                <th class="text-right">@lang('messages.TVA')</th>
                <th class="text-right">@lang('messages.TVAC')</th>
                @else
                <th class="text-right">@lang('messages.Prix unitaire')</th>
                <th class="text-right">@lang('messages.Montant')</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($details as $d)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $d->item_designation ?? ($d->item->name ?? 'N/D') }}</td>
                <td class="text-right">{{ number_format($d->item_quantity ?? 0, 0, ',', ' ') }}</td>
                @if($hasVat)
                @php
                    $qty = (float) ($d->item_quantity ?? 0);
                    // item_price_nvat = total HT ligne ; item_price = PU HT
                    $ptNvat = (float) ($d->item_price_nvat ?? 0);
                    if ($ptNvat <= 0 && $qty > 0) {
                        $ptNvat = ((float) ($d->item_price ?? 0)) * $qty;
                    }
                    $puHt = $qty > 0 ? $ptNvat / $qty : (float) ($d->item_price ?? 0);
                    $tva = (float) ($d->vat ?? 0);
                    $ptWvat = (float) ($d->item_price_wvat ?? $d->item_total_amount ?? ($ptNvat + $tva));
                @endphp
                <td class="text-right">{{ number_format($puHt, 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($ptNvat, 2, ',', ' ') }}</td>
                <td class="text-right">{{ (float) (optional($d->item)->vat_rate ?? optional($d->item)->vat ?? 0) }}%</td>
                <td class="text-right">{{ number_format($tva, 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($ptWvat, 2, ',', ' ') }}</td>
                @else
                <td class="text-right">{{ number_format($d->item_price ?? 0, 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($d->item_total_amount ?? 0, 2, ',', ' ') }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:bold;">
                @if($hasVat)
                <td colspan="4" class="text-right">@lang('messages.TOTAL')</td>
                <td class="text-right">{{ number_format($details->sum(function($d){ $q = (float)($d->item_quantity ?? 0); $ht = (float)($d->item_price_nvat ?? 0); if ($ht > 0) { return $ht; } return ((float)($d->item_price ?? 0)) * $q; }), 2, ',', ' ') }}</td>
                <td></td>
                <td class="text-right">{{ number_format($details->sum('vat'), 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($totalAmount, 2, ',', ' ') }}</td>
                @else
                <td colspan="4" class="text-right">@lang('messages.TOTAL')</td>
                <td class="text-right">{{ number_format($totalAmount, 2, ',', ' ') }}</td>
                @endif
            </tr>
        </tfoot>
    </table>
    @if($sales_order->description)
    <p class="doc-meta" style="margin-top:10px;"><strong>@lang('messages.Description:')</strong> {{ $sales_order->description }}</p>
    @endif

    @include('backend.pages.document.partials._document_totals_signature')
    @include('backend.pages.document.partials._document_footer_bank')
</body>
</html>
