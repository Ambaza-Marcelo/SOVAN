<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    @include('backend.pages.document.partials._document_styles')
</head>
<body style="font-family: 'DejaVu Sans', sans-serif;">
    @include('backend.pages.document.partials._document_header')

    <h3 class="doc-title">@lang('messages.ÉTAT DE GROS STOCK DES MATIÈRES PREMIÈRES')</h3>
    <div class="doc-meta">
        <strong>@lang('messages.Date:')</strong> {{ $dateTime ? str_replace('_', ' ', $dateTime) : 'N/A' }} &nbsp;|&nbsp;
        <strong>@lang('messages.Signature magasin:')</strong> {{ $store_signature ?? 'N/A' }}
    </div>

    <table class="doc-table">
        <thead>
            <tr>
                <th>#</th>
                <th>@lang('messages.Désignation')</th>
                <th>@lang('messages.Code')</th>
                <th class="text-right">@lang('messages.Quantité')</th>
                <th>@lang('messages.Unité')</th>
                <th class="text-right">@lang('messages.Valeur achat')</th>
                <th class="text-right">@lang('messages.Valeur vente')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datas ?? [] as $d)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $d->rawMaterial->name ?? $d->r_material_id ?? 'N/A' }}</td>
                <td>{{ $d->rawMaterial->code ?? $d->r_material_id ?? '-' }}</td>
                <td class="text-right">{{ number_format($d->quantity ?? 0, 0, ',', ' ') }}</td>
                <td>{{ $d->unit ?? optional($d->rawMaterial)->unit ?? optional(optional($d->rawMaterial)->rawMaterialMeasurement)->stockout_unit ?? optional(optional($d->rawMaterial)->rawMaterialMeasurement)->purchase_unit ?? '-' }}</td>
                <td class="text-right">{{ number_format($d->total_cump_value ?? $d->total_purchase_value ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($d->total_selling_value ?? 0, 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:bold;">
                <td colspan="5" class="text-right">@lang('messages.TOTAL')</td>
                <td class="text-right">{{ number_format($totalPrixAchat ?? 0, 0, ',', ' ') }} BIF</td>
                <td class="text-right">{{ number_format($totalPrixVente ?? 0, 0, ',', ' ') }} BIF</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
