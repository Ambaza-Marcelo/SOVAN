<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    @include('backend.pages.document.partials._document_styles')
    <style>
        .doc-table td.text-right { white-space: nowrap; }
    </style>
</head>
<body style="font-family: 'DejaVu Sans', sans-serif;">
    @php
        $date = $date ?? $proforma_invoice->proforma_date ?? null;
        $documentTitle = 'FACTURE PROFORMA (USD)';
        $documentNumber = $proforma_invoice->proforma_number ?? '';
        $documentDate = $proforma_invoice->proforma_date ? \Carbon\Carbon::parse($proforma_invoice->proforma_date)->format('d/m/Y H:i') : '';
        $customerName = $proforma_invoice->client->customer_name ?? '';
        $customerAddress = $proforma_invoice->client->customer_address ?? '';
        $customerPhone = $proforma_invoice->client->telephone ?? '';
        $customerNif = $proforma_invoice->client->customer_TIN ?? '';
        $customerSubjectToVat = (optional($proforma_invoice->client)->vat_customer_payer && !in_array(optional($proforma_invoice->client)->vat_customer_payer, ['0', 'non', 'no', 'false'], true)) ? 'Oui' : 'Non';
        $details = $proforma_invoice->details ?? collect();
        $totalAmount = $details->sum('item_total_amount');
        $grandTotal = $totalAmount;
        $hasVat = $details->contains(function($d) { return isset($d->item_price_nvat) || isset($d->vat); });

        $fxInfo = \App\Models\ExchangeRate::bifPerUsdForDate($proforma_invoice->proforma_date ?? null);
        $bifPerUsd = $fxInfo['bif_per_usd'] ?? null;
        $fxRateDateStr = !empty($fxInfo['rate_date']) ? \Carbon\Carbon::parse($fxInfo['rate_date'])->format('d/m/Y') : null;

        $moneyUsd = function ($amountBif) use ($bifPerUsd) {
            if ($bifPerUsd === null || (float) $bifPerUsd <= 0) {
                return '—';
            }
            return number_format(((float) $amountBif) / (float) $bifPerUsd, 2, ',', ' ');
        };
    @endphp
    @include('backend.pages.document.partials._document_header')

    @if($bifPerUsd === null || (float) $bifPerUsd <= 0)
        <p class="doc-meta" style="color:#a00;"><strong>{{ \App\Models\ExchangeRate::missingBifUsdRateMessage() }}</strong></p>
    @else
        <p class="doc-meta"><strong>@lang('messages.Conversion :')</strong> 1 USD = {{ number_format((float) $bifPerUsd, 0, ',', ' ') }} BIF (taux en vigueur au {{ $fxRateDateStr }} ; date du document : {{ $documentDate }}).</p>
    @endif

    @include('backend.pages.document.partials._document_customer_date')

    <table class="doc-table">
        <thead>
            <tr>
                <th>N°</th>
                <th>@lang('messages.Désignation')</th>
                <th class="text-right">@lang('messages.Quantité')</th>
                @if($hasVat)
                <th class="text-right">@lang('messages.PU HTVA (USD)')</th>
                <th class="text-right">@lang('messages.PT HTVA (USD)')</th>
                <th class="text-right">@lang('messages.Taux TVA')</th>
                <th class="text-right">@lang('messages.TVA (USD)')</th>
                <th class="text-right">@lang('messages.TVAC (USD)')</th>
                @else
                <th class="text-right">@lang('messages.Prix unitaire (USD)')</th>
                <th class="text-right">@lang('messages.Montant (USD)')</th>
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
                    $ptNvat = (float) ($d->item_price_nvat ?? 0);
                    $puHt = $qty > 0 ? $ptNvat / $qty : 0;
                    $tva = (float) ($d->vat ?? 0);
                    $ptWvat = (float) ($d->item_price_wvat ?? $d->item_total_amount ?? ($ptNvat + $tva));
                @endphp
                <td class="text-right">{{ $moneyUsd($puHt) }}</td>
                <td class="text-right">{{ $moneyUsd($ptNvat) }}</td>
                <td class="text-right">{{ $d->item->vat_rate ?? 0 }}%</td>
                <td class="text-right">{{ $moneyUsd($tva) }}</td>
                <td class="text-right">{{ $moneyUsd($ptWvat) }}</td>
                @else
                <td class="text-right">{{ $moneyUsd($d->item_price ?? 0) }}</td>
                <td class="text-right">{{ $moneyUsd($d->item_total_amount ?? 0) }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:bold;">
                @if($hasVat)
                <td colspan="4" class="text-right">@lang('messages.TOTAL (USD)')</td>
                <td class="text-right">{{ $moneyUsd($details->sum(function($d){ return ($d->item_price_nvat ?? $d->item_price ?? 0) * ($d->item_quantity ?? 0); })) }}</td>
                <td></td>
                <td class="text-right">{{ $moneyUsd($details->sum('vat')) }}</td>
                <td class="text-right">{{ $moneyUsd($totalAmount) }}</td>
                @else
                <td colspan="4" class="text-right">@lang('messages.TOTAL (USD)')</td>
                <td class="text-right">{{ $moneyUsd($totalAmount) }}</td>
                @endif
            </tr>
        </tfoot>
    </table>
    @if($bifPerUsd && (float) $bifPerUsd > 0)
        <p class="doc-meta" style="margin-top:10px;"><strong>@lang('messages.Montant total (USD) :')</strong> {{ $moneyUsd($totalAmount) }} USD</p>
        <p class="doc-meta"><strong>@lang('messages.Équivalent (BIF) :')</strong> {{ number_format($totalAmount, 0, ',', ' ') }} francs burundais (taux 1 USD = {{ number_format((float) $bifPerUsd, 0, ',', ' ') }} BIF).</p>
    @else
        <p class="doc-meta" style="margin-top:10px;"><strong>@lang('messages.Montant (BIF, sans conversion USD) :')</strong> {{ number_format($totalAmount, 0, ',', ' ') }} francs burundais.</p>
    @endif
    <p class="doc-meta" style="margin-top:6px;"><strong>@lang('messages.Montant en lettres (BIF) :')</strong> {{ \App\Helpers\NumberToWordsFrench::convert((int) round($totalAmount ?? 0)) }} francs burundais</p>
    @if($proforma_invoice->description)
    <p class="doc-meta" style="margin-top:10px;"><strong>@lang('messages.Description:')</strong> {{ $proforma_invoice->description }}</p>
    @endif

    @include('backend.pages.document.partials._document_totals_signature')
    @include('backend.pages.document.partials._document_footer_bank')
</body>
</html>
