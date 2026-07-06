<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    @include('backend.pages.document.partials._document_styles')
    <style> @lang('messages.table.doc-table { font-size: 10px; }') </style>
</head>
<body style="font-family: 'DejaVu Sans', sans-serif;">
    @include('backend.pages.document.partials._document_header')

    <h3 class="doc-title">@lang('messages.RAPPORT DU STOCK DE MATERIELS (GROS STOCK)')</h3>
    <div class="doc-meta">
        <strong>@lang('messages.Période:')</strong> {{ $start_date ?? '' }} au {{ $end_date ?? '' }} &nbsp;|&nbsp;
        <strong>@lang('messages.Magasin:')</strong> {{ $code_store ?? '-' }} &nbsp;|&nbsp;
        <strong>@lang('messages.Signature:')</strong> {{ $store_signature ?? 'N/A' }}
    </div>

    <table class="doc-table">
        <thead>
            <tr>
                <th>#</th>
                <th>@lang('messages.Date')</th>
                <th>@lang('messages.Article')</th>
                <th class="text-right">@lang('messages.Qté init.')</th>
                @if(empty($quantitiesOnly))
                <th class="text-right">@lang('messages.Val. init.')</th>
                @endif
                <th class="text-right">@lang('messages.Qté entrée')</th>
                @if(empty($quantitiesOnly))
                <th class="text-right">@lang('messages.Val. entrée')</th>
                @endif
                <th class="text-right">@lang('messages.Qté sortie')</th>
                @if(empty($quantitiesOnly))
                <th class="text-right">@lang('messages.Val. sortie')</th>
                @endif
                <th class="text-right">@lang('messages.Qté finale')</th>
                @if(empty($quantitiesOnly))
                <th class="text-right">@lang('messages.Val. finale')</th>
                @endif
                <th>@lang('messages.Document')</th>
                <th>@lang('messages.Type')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datas ?? [] as $d)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $d->created_at ? \Carbon\Carbon::parse($d->created_at)->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $d->material->name ?? $d->material_id ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($d->quantity_stock_initial ?? 0, 0, ',', ' ') }}</td>
                @if(empty($quantitiesOnly))
                <td class="text-right">{{ number_format($d->value_stock_initial ?? 0, 0, ',', ' ') }}</td>
                @endif
                <td class="text-right">{{ number_format($d->quantity_stockin ?? 0, 0, ',', ' ') }}</td>
                @if(empty($quantitiesOnly))
                <td class="text-right">{{ number_format($d->value_stockin ?? 0, 0, ',', ' ') }}</td>
                @endif
                <td class="text-right">{{ number_format($d->quantity_stockout ?? 0, 0, ',', ' ') }}</td>
                @if(empty($quantitiesOnly))
                <td class="text-right">{{ number_format($d->value_stockout ?? 0, 0, ',', ' ') }}</td>
                @endif
                <td class="text-right">{{ number_format($d->quantity_stock_final ?? 0, 0, ',', ' ') }}</td>
                @if(empty($quantitiesOnly))
                <td class="text-right">{{ number_format($d->value_stock_final ?? 0, 0, ',', ' ') }}</td>
                @endif
                <td>{{ $d->document_no ?? '-' }}</td>
                <td>{{ $d->type_transaction ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
