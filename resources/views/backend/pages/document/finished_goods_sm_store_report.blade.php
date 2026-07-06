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

    <h3 class="doc-title">@lang('messages.RAPPORT DU STOCK DES PRODUITS FINIS') (SM)</h3>
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
                <th>@lang('messages.Magasin')</th>
                <th>@lang('messages.Article')</th>
                <th class="text-right">@lang('messages.Qté init.')</th>
                <th class="text-right">@lang('messages.Qté entrée')</th>
                <th class="text-right">@lang('messages.Qté sortie')</th>
                <th class="text-right">@lang('messages.Qté finale')</th>
                <th>@lang('messages.Type')</th>
                <th>@lang('messages.Document')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datas ?? [] as $d)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $d->date ? \Carbon\Carbon::parse($d->date)->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $d->code_store ?? '-' }}</td>
                <td>{{ $d->item->name ?? $d->item_id ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($d->quantity_stock_initial ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($d->quantity_stockin ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($d->quantity_stockout ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($d->quantity_stock_final ?? 0, 0, ',', ' ') }}</td>
                <td>{{ $d->type_transaction ?? '-' }}</td>
                <td>{{ $d->document_no ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
