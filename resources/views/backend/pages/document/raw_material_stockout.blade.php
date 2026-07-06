<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    @include('backend.pages.document.partials._document_styles')
</head>
<body style="font-family: 'DejaVu Sans', sans-serif;">
    @include('backend.pages.document.partials._document_header')

    <h3 class="doc-title">BON DE SORTIE MATIÈRES PREMIÈRES N° {{ $stockout_no ?? $code ?? '' }}</h3>
    <div class="doc-meta">
        <strong>@lang('messages.Date:')</strong> {{ $date ? \Carbon\Carbon::parse($date)->format('d/m/Y H:i') : 'N/A' }} &nbsp;|&nbsp;
        <strong>@lang('messages.Signature:')</strong> {{ $stockout_signature ?? 'N/A' }}
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
                <td>{{ $d->rawMaterial->name ?? $d->r_material_id ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($d->quantity ?? 0, 0, ',', ' ') }}</td>
                <td>{{ $d->unit ?? optional($d->rawMaterial)->unit ?? optional(optional($d->rawMaterial)->rawMaterialMeasurement)->stockout_unit ?? optional(optional($d->rawMaterial)->rawMaterialMeasurement)->purchase_unit ?? '-' }}</td>
                <td class="text-right">{{ number_format($d->price ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($d->total_purchase_value ?? $d->total_value ?? 0, 0, ',', ' ') }} BIF</td>
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

    @if(!empty($description))
    <p class="doc-meta" style="margin-top:10px;"><strong>@lang('messages.Description:')</strong> {{ $description }}</p>
    @endif

    <table style="width:100%; margin-top:28px; font-size:10px;">
        <tr>
            <td style="width:50%; padding-right:20px; vertical-align:bottom;">@lang('messages.Nom et signature : _______________________')</td>
            <td style="width:50%; vertical-align:bottom;">@lang('messages.Nom et signature : _______________________')</td>
        </tr>
    </table>

</body>
</html>
