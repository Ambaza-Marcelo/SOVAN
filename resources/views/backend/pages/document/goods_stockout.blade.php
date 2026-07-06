<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    @include('backend.pages.document.partials._document_styles')
</head>
<body style="font-family: 'DejaVu Sans', sans-serif;">
    @include('backend.pages.document.partials._document_header', [
        'documentTitle' => "BON DE SORTIE MARCHANDISES",
        'documentNumber' => $stockout->stockout_no ?? null,
        'date' => $stockout->date ?? null
    ])

    <h3 class="doc-title">BON DE SORTIE MARCHANDISES N° {{ $stockout->stockout_no }}</h3>
    <div class="doc-meta">
        <strong>@lang('messages.Date:')</strong> {{ $stockout->date ? \Carbon\Carbon::parse($stockout->date)->format('d/m/Y H:i') : 'N/A' }} &nbsp;|&nbsp;
        <strong>@lang('messages.Signature:')</strong> {{ $stockout->stockout_signature ?? 'N/A' }}<br>
        <strong>@lang('messages.Demandeur:')</strong> {{ $stockout->asker ?? 'N/A' }} &nbsp;|&nbsp;
        <strong>@lang('messages.Destination:')</strong> {{ $stockout->destination ?? 'N/A' }}<br>
        <strong>@lang('messages.Magasin d\'origine:')</strong> {{ optional($stockout->goodsStore)->code ?? '-' }} - {{ optional($stockout->goodsStore)->name ?? '' }}
    </div>

    <table class="doc-table">
        <thead>
            <tr>
                <th>#</th>
                <th>@lang('messages.Désignation')</th>
                <th class="text-right">@lang('messages.Quantité')</th>
                <th>@lang('messages.Unité')</th>
                <th class="text-right">@lang('messages.Prix unit.')</th>
                <th class="text-right">@lang('messages.Total')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datas ?? [] as $d)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $d->item->name ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($d->quantity ?? 0, 0, ',', ' ') }}</td>
                <td>{{ $d->unit ?? optional($d->item)->unit ?? '-' }}</td>
                <td class="text-right">{{ number_format($d->cost_price ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($d->total_cost_value ?? 0, 0, ',', ' ') }} BIF</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:bold;">
                <td colspan="5" class="text-right">@lang('messages.TOTAL')</td>
                <td class="text-right">{{ number_format($totalValue ?? 0, 0, ',', ' ') }} BIF</td>
            </tr>
        </tfoot>
    </table>

    @if($stockout->description)
    <p class="doc-meta" style="margin-top:10px;"><strong>@lang('messages.Description:')</strong> {{ $stockout->description }}</p>
    @endif

    <table style="width:100%; margin-top:28px; font-size:10px;">
        <tr>
            <td style="width:50%; padding-right:20px; vertical-align:bottom;">@lang('messages.Nom et signature demandeur : _______________________')</td>
            <td style="width:50%; vertical-align:bottom;">@lang('messages.Nom et signature magasinier : _______________________')</td>
        </tr>
    </table>

    {{-- ATTENTION: pas de coordonnées bancaires sur ce document --}}

</body>
</html>

