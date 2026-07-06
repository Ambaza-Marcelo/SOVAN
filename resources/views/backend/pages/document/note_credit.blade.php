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
        $invoice = $credit;
        $date = $credit->invoice_date ?? null;
        $documentTitle = 'FACTURE D\'AVOIR';
        $documentNumber = $credit->invoice_number ?? '';
        $documentDate = $credit->invoice_date ? \Carbon\Carbon::parse($credit->invoice_date)->format('d/m/Y H:i') : '';
        $customerName = $credit->client->customer_name ?? '';
        $customerAddress = $credit->client->customer_address ?? '';
        $customerPhone = $credit->client->telephone ?? '';
        $customerNif = $credit->client->customer_TIN ?? '';
        $customerSubjectToVat = (optional($credit->client)->vat_customer_payer && !in_array(optional($credit->client)->vat_customer_payer, ['0', 'non', 'no', 'false'], true)) ? 'Oui' : 'Non';
        $details = $credit->details ?? collect();
        $totalAmount = $details->sum('item_total_amount');
        $grandTotal = $totalAmount;
        $hasVat = ((string) ($credit->vat_taxpayer ?? '0')) === '1';
    @endphp
    @include('backend.pages.document.partials._document_header')

    <p class="doc-meta"><strong>@lang('messages.Type (OBR) :')</strong> @lang('messages.FA — Facture d\'avoir / note de crédit &nbsp;|&nbsp;') <strong>@lang('messages.Réf. facture concernée :')</strong> {{ $credit->invoice_ref }} <span style="font-size:9px;">@lang('messages.(facture normale, ex. FN)')</span></p>
    @if($credit->cn_motif == '1')
        <p class="doc-meta"><strong>@lang('messages.Motif :')</strong> @lang('messages.Erreur sur la facture')</p>
    @elseif($credit->cn_motif == '2')
        <p class="doc-meta"><strong>@lang('messages.Motif :')</strong> @lang('messages.Retour marchandises')</p>
    @elseif($credit->cn_motif == '3')
        <p class="doc-meta"><strong>@lang('messages.Motif :')</strong> @lang('messages.Rabais')</p>
    @elseif($credit->cn_motif == '4')
        <p class="doc-meta"><strong>@lang('messages.Motif :')</strong> @lang('messages.Réduction hors facture')</p>
    @endif

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
                    $ptNvat = (float) ($d->item_price_nvat ?? 0);
                    if ($ptNvat <= 0 && $qty > 0) {
                        $ptNvat = ((float) ($d->item_total_amount ?? 0)) - ((float) ($d->vat ?? 0));
                    }
                    $puHt = $qty > 0 ? $ptNvat / $qty : 0;
                    $tva = (float) ($d->vat ?? 0);
                    $ptWvat = (float) ($d->item_price_wvat ?? $d->item_total_amount ?? ($ptNvat + $tva));
                @endphp
                <td class="text-right">{{ number_format($puHt, 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($ptNvat, 2, ',', ' ') }}</td>
                <td class="text-right">{{ (float) (optional($d->item)->vat ?? 0) }}%</td>
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
                <td class="text-right">{{ number_format($details->sum(function($d){ $q = (float)($d->item_quantity ?? 0); $ht = (float)($d->item_price_nvat ?? 0); if ($ht > 0) { return $ht; } return ((float)($d->item_total_amount ?? 0)) - ((float)($d->vat ?? 0)); }), 2, ',', ' ') }}</td>
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
    <p class="doc-meta" style="margin-top:10px;"><strong>@lang('messages.Montant en lettres :')</strong> {{ \App\Helpers\NumberToWordsFrench::convert((int) round($totalAmount ?? 0)) }} francs burundais</p>
    @if($credit->electronic_signature)
        <p class="doc-meta"><strong>@lang('messages.Signature électronique OBR :')</strong> {{ $credit->electronic_signature }}</p>
    @endif

    @include('backend.pages.document.partials._document_totals_signature')
    @include('backend.pages.document.partials._document_footer_bank')
</body>
</html>
