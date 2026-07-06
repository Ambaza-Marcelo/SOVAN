<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    @include('backend.pages.document.partials._document_styles')
</head>
<body style="font-family: 'DejaVu Sans', sans-serif;">
    @include('backend.pages.document.partials._document_header')

    <h3 class="doc-title">ÉTAT DU STOCK PRODUITS FINIS - {{ $finished_goods_store->name ?? $finished_goods_store->code }}</h3>
    <div class="doc-meta">
        <strong>@lang('messages.Code magasin:')</strong> {{ $finished_goods_store->code }} &nbsp;|&nbsp;
        <strong>@lang('messages.Emplacement:')</strong> {{ $finished_goods_store->emplacement ?? 'N/A' }} &nbsp;|&nbsp;
        <strong>@lang('messages.Gestionnaire:')</strong> {{ $finished_goods_store->manager ?? 'N/A' }}<br>
        <strong>@lang('messages.Date d\'édition:')</strong> {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
    </div>

    <table class="doc-table">
        <thead>
            <tr>
                <th>#</th>
                <th>@lang('messages.Désignation')</th>
                <th class="text-right">@lang('messages.Quantité')</th>
                <th>@lang('messages.Unité')</th>
                <th class="text-right">@lang('messages.CUMP')</th>
                <th class="text-right">@lang('messages.Valeur totale')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datas ?? [] as $d)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $d->item->name ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($d->quantity ?? 0, 0, ',', ' ') }}</td>
                <td>{{ $d->unit ?? optional($d->item)->unit ?? '-' }}</td>
                <td class="text-right">{{ number_format($d->cump ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($d->total_cump_value ?? 0, 0, ',', ' ') }} BIF</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:bold;">
                <td colspan="5" class="text-right">@lang('messages.TOTAL VALEUR')</td>
                <td class="text-right">{{ number_format($totalValue ?? 0, 0, ',', ' ') }} BIF</td>
            </tr>
        </tfoot>
    </table>

    <table style="width:100%; margin-top:28px; font-size:10px;">
        <tr>
            <td style="width:50%; padding-right:20px; vertical-align:bottom;">@lang('messages.Nom et signature : _______________________')</td>
            <td style="width:50%; vertical-align:bottom;">@lang('messages.Nom et signature : _______________________')</td>
        </tr>
    </table>

</body>
</html>
