<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    @include('backend.pages.document.partials._document_styles')
</head>
<body style="font-family: 'DejaVu Sans', sans-serif;">
    @php $date = $date ?? $plan->production_plan_date ?? null; @endphp
    @include('backend.pages.document.partials._document_header')

    <h3 class="doc-title">PLAN DE PRODUCTION N° {{ $plan->prodution_plan_number }}</h3>
    <div class="doc-meta">
        <strong>@lang('messages.Date:')</strong> {{ $plan->production_plan_date ? \Carbon\Carbon::parse($plan->production_plan_date)->format('d/m/Y') : 'N/A' }} &nbsp;|&nbsp;
        <strong>@lang('messages.Signature:')</strong> {{ $plan->production_signature ?? 'N/A' }}
    </div>

    <h4 style="text-decoration:underline; margin-top:14px;">@lang('messages.Produits finis')</h4>
    <table class="doc-table">
        <thead>
            <tr>
                <th>#</th>
                <th>@lang('messages.Désignation')</th>
                <th class="text-right">@lang('messages.Quantité')</th>
                <th class="text-right">@lang('messages.Prix unit.')</th>
                <th class="text-right">@lang('messages.Total')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($finishedGoods ?? [] as $g)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $g->item ? $g->item->code . ' - ' . $g->item->name : 'N/A' }}</td>
                <td class="text-right">{{ number_format($g->item_quantity ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($g->cost_price ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($g->total_item_cost ?? 0, 0, ',', ' ') }} BIF</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h4 style="text-decoration:underline; margin-top:18px;">@lang('messages.Matières premières')</h4>
    <table class="doc-table">
        <thead>
            <tr>
                <th>#</th>
                <th>@lang('messages.Désignation')</th>
                <th class="text-right">@lang('messages.Quantité')</th>
                <th class="text-right">@lang('messages.Coût unit.')</th>
                <th class="text-right">@lang('messages.Total')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rawMaterials ?? [] as $m)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $m->rawMaterial ? $m->rawMaterial->name : 'N/A' }}</td>
                <td class="text-right">{{ number_format($m->material_quantity ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($m->material_cost ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($m->total_material_cost ?? 0, 0, ',', ' ') }} BIF</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(!empty($plan->description))
    <p class="doc-meta" style="margin-top:10px;"><strong>@lang('messages.Description:')</strong> {{ $plan->description }}</p>
    @endif

    <p class="doc-meta" style="margin-top:18px;"><strong>@lang('messages.Coût total estimé:')</strong> {{ number_format($plan->total_estimated_cost ?? 0, 0, ',', ' ') }} BIF</p>
    <p class="doc-meta"><strong>@lang('messages.Créé par:')</strong> {{ $plan->created_by ?? 'N/A' }}</p>

</body>
</html>
