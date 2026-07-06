<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    @include('backend.pages.document.partials._document_styles')
    <style>
        .doc-table td.text-right { white-space: nowrap; }
        .chk-cell { width: 50px; text-align: center; }
        .chk-box { display:inline-block; width: 14px; height: 14px; border: 1px solid #000; }
        .sig-grid { width: 100%; margin-top: 30px; }
        .sig-col { width: 48%; vertical-align: top; }
        .sig-line { margin-top: 45px; border-bottom: 1px solid #000; width: 100%; }
        .sig-label { font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body style="font-family: 'DejaVu Sans', sans-serif;">
    @php
        $date = $date ?? $delivery_note->delivery_date ?? null;
        $documentTitle = 'BON DE LIVRAISON';
        $documentNumber = $delivery_note->delivery_number ?? '';
        $documentDate = $delivery_note->delivery_date ? \Carbon\Carbon::parse($delivery_note->delivery_date)->format('d/m/Y H:i') : '';
        $customerName = $delivery_note->client->customer_name ?? '';
        $customerAddress = $delivery_note->client->customer_address ?? '';
        $customerPhone = $delivery_note->client->telephone ?? '';
        $customerNif = $delivery_note->client->customer_TIN ?? '';
        $customerSubjectToVat = (optional($delivery_note->client)->vat_customer_payer && !in_array(optional($delivery_note->client)->vat_customer_payer, ['0', 'non', 'no', 'false'], true)) ? 'Oui' : 'Non';
        $details = $delivery_note->details ?? collect();
        // Delivery note: quantities only, no monetary totals.
        $totalAmount = 0;
        $grandTotal = 0;
        $hasVat = false;
    @endphp
    @include('backend.pages.document.partials._document_header')

    @include('backend.pages.document.partials._document_customer_date')

    <table class="doc-table">
        <thead>
            <tr>
                <th>N°</th>
                <th>@lang('messages.Désignation')</th>
                <th class="text-right">@lang('messages.Quantité')</th>
                <th class="chk-cell">@lang('messages.Reçu')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $d)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $d->item_designation ?? ($d->item->name ?? 'N/D') }}</td>
                <td class="text-right">{{ number_format($d->item_quantity ?? 0, 0, ',', ' ') }}</td>
                <td class="chk-cell"><span class="chk-box"></span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($delivery_note->description ?? null)
    <p class="doc-meta" style="margin-top:10px;"><strong>@lang('messages.Description:')</strong> {{ $delivery_note->description }}</p>
    @endif

    <table class="sig-grid">
        <tr>
            <td class="sig-col">
                <div class="sig-label">@lang('messages.Remis par (Signature)')</div>
                <div class="sig-line"></div>
                <div class="sig-label" style="margin-top:20px;">@lang('messages.Nom :')</div>
                <div class="sig-line"></div>
            </td>
            <td style="width:4%"></td>
            <td class="sig-col">
                <div class="sig-label">@lang('messages.Réceptionné par (Signature)')</div>
                <div class="sig-line"></div>
                <div class="sig-label" style="margin-top:20px;">@lang('messages.Nom :')</div>
                <div class="sig-line"></div>
            </td>
        </tr>
    </table>
</body>
</html>
