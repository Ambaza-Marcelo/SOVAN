<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    @include('backend.pages.document.partials._document_styles')
</head>
<body style="font-family: 'DejaVu Sans', sans-serif;">
    @php $date = $date ?? $productionOrder->production_date ?? null; @endphp
    @include('backend.pages.document.partials._document_header')

    <h3 class="doc-title">ORDRE DE FABRICATION N° {{ $productionOrder->prodution_number }}</h3>
    <div class="doc-meta">
        <strong>@lang('messages.Date:')</strong> {{ $productionOrder->production_date ? \Carbon\Carbon::parse($productionOrder->production_date)->format('d/m/Y') : 'N/A' }} &nbsp;|&nbsp;
        <strong>@lang('messages.Signature:')</strong> {{ $productionOrder->production_signature ?? 'N/A' }}
    </div>

    <h4 style="text-decoration:underline; margin-top:14px;">@lang('messages.Matières premières')</h4>
    <table class="doc-table">
        <thead>
            <tr>
                <th>#</th>
                <th>@lang('messages.Désignation')</th>
                <th class="text-right">@lang('messages.Quantité')</th>
                <th>@lang('messages.Unité')</th>
                <th class="text-right">@lang('messages.Coût total')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rawMaterials ?? [] as $d)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $d->rawMaterial->name ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($d->material_quantity ?? 0, 2, ',', ' ') }}</td>
                <td>{{ $d->unit ?? optional($d->rawMaterial)->unit ?? optional(optional($d->rawMaterial)->rawMaterialMeasurement)->production_unit ?? optional(optional($d->rawMaterial)->rawMaterialMeasurement)->purchase_unit ?? '-' }}</td>
                <td class="text-right">{{ number_format($d->total_material_cost ?? 0, 0, ',', ' ') }} BIF</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h4 style="text-decoration:underline; margin-top:18px;">@lang('messages.Produits finis attendus')</h4>
    <table class="doc-table">
        <thead>
            <tr>
                <th>#</th>
                <th>@lang('messages.Désignation')</th>
                <th class="text-right">@lang('messages.Quantité')</th>
                <th class="text-right">@lang('messages.Coût unit.')</th>
                <th class="text-right">@lang('messages.Coût total')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($finishedGoods ?? [] as $d)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $d->item->name ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($d->item_quantity ?? 0, 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($d->cost_price ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($d->total_item_cost ?? 0, 0, ',', ' ') }} BIF</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($productionOrder->description)
    <p class="doc-meta" style="margin-top:10px;"><strong>@lang('messages.Description:')</strong> {{ $productionOrder->description }}</p>
    @endif

    <table style="width:100%; margin-top:28px; font-size:10px;">
        <tr>
            <td style="width:50%; padding-right:20px; vertical-align:bottom;">@lang('messages.Nom et signature : _______________________')</td>
            <td style="width:50%; vertical-align:bottom;">@lang('messages.Nom et signature : _______________________')</td>
        </tr>
    </table>

</body>
</html>
