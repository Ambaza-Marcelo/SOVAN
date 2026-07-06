<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    @include('backend.pages.document.partials._document_styles')
</head>
<body style="font-family: 'DejaVu Sans', sans-serif;">
    @include('backend.pages.document.partials._document_header')

    <h3 class="doc-title">@lang('messages.INVENTAIRE AU PETIT STOCK DES MATIÈRES PREMIÈRES')</h3>
    <div class="doc-meta">
        <strong>@lang('messages.N° inventaire:')</strong> {{ $inventory_no ?? '' }} &nbsp;|&nbsp;
        <strong>@lang('messages.Date:')</strong> {{ $date ? \Carbon\Carbon::parse($date)->format('d/m/Y H:i') : 'N/A' }} &nbsp;|&nbsp;
        <strong>@lang('messages.Gestionnaire:')</strong> {{ $gestionnaire ?? 'N/A' }} &nbsp;|&nbsp;
        <strong>@lang('messages.Signature:')</strong> {{ $inventory_signature ?? 'N/A' }}
    </div>
    @if(!empty($title))<p class="doc-meta"><strong>@lang('messages.Titre:')</strong> {{ $title }}</p>@endif

    <table class="doc-table">
        <thead>
            <tr>
                <th>#</th>
                <th>@lang('messages.Désignation')</th>
                <th class="text-right">@lang('messages.Qté théorique')</th>
                <th class="text-right">@lang('messages.Qté physique')</th>
                <th>@lang('messages.Unité')</th>
                <th class="text-right">@lang('messages.Valeur actuelle')</th>
                <th class="text-right">@lang('messages.Nouvelle valeur')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datas ?? [] as $d)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $d->rawMaterial->name ?? $d->r_material_id ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($d->quantity ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($d->new_quantity ?? 0, 0, ',', ' ') }}</td>
                <td>{{ $d->unit ?? $d->new_unit ?? optional($d->rawMaterial)->unit ?? optional(optional($d->rawMaterial)->rawMaterialMeasurement)->purchase_unit ?? '-' }}</td>
                <td class="text-right">{{ number_format($d->total_purchase_value ?? $d->total_cump_value ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($d->new_total_purchase_value ?? $d->new_total_cump_value ?? 0, 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:bold;">
                <td colspan="2" class="text-right">@lang('messages.TOTAL (qté théorique / physique)')</td>
                <td class="text-right">{{ number_format($totalQuantiteTheorique ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($totalQuantitePhysique ?? 0, 0, ',', ' ') }}</td>
                <td></td>
                <td class="text-right">{{ number_format($totalValueActuelle ?? 0, 0, ',', ' ') }} BIF</td>
                <td class="text-right">{{ number_format($totalValueNew ?? 0, 0, ',', ' ') }} BIF</td>
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
