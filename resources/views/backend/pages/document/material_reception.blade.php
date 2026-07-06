<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    @include('backend.pages.document.partials._document_styles')
</head>
<body style="font-family: 'DejaVu Sans', sans-serif;">
    @include('backend.pages.document.partials._document_header')

    <h3 class="doc-title">Réception matériels N° {{ $code ?? '' }}</h3>
    <div class="doc-meta">
        <strong>@lang('messages.Date:')</strong> {{ isset($date) && $date ? \Carbon\Carbon::parse($date)->format('d/m/Y H:i') : 'N/D' }} &nbsp;|&nbsp;
        <strong>@lang('messages.Signature:')</strong> {{ $reception_signature ?? 'N/D' }}
        @if(isset($data) && $data && $data->supplier)
        &nbsp;|&nbsp; <strong>@lang('messages.Fournisseur:')</strong> {{ $data->supplier->supplier_name ?? 'N/D' }} &nbsp;|&nbsp;
        <strong>@lang('messages.Réceptionniste:')</strong> {{ $receptionniste ?? 'N/D' }} &nbsp;|&nbsp;
        <strong>@lang('messages.Remettant:')</strong> {{ $data->handingover ?? 'N/D' }}
        @endif
    </div>
    <table class="doc-table">
        <thead>
            <tr>
                <th>#</th>
                <th>@lang('messages.Article')</th>
                <th class="text-right">@lang('messages.Quantité commandée')</th>
                <th class="text-right">@lang('messages.Quantité reçue')</th>
                <th class="text-right">@lang('messages.Prix unitaire')</th>
                <th>@lang('messages.Unité de mesure')</th>
                <th class="text-right">@lang('messages.Valeur totale')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datas ?? [] as $d)
            @php
                $articleName = $d->material_id
                    ? (optional($d->material)->name ?? 'Matériel #'.$d->material_id)
                    : ($d->item_id ? (optional($d->item)->name ?? 'Article #'.$d->item_id) : 'N/D');
                $articleCode = $d->material_id ? (optional($d->material)->code ?? '') : ($d->item_id ? (optional($d->item)->code ?? '') : '');
                $unit = $d->unit ?? optional($d->material)->unit ?? optional(optional($d->material)->materialMeasurement)->purchase_unit ?? 'N/D';
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $articleName }}{{ $articleCode ? ' - Code: ' . $articleCode : '' }}</td>
                <td class="text-right">{{ number_format($d->quantity_ordered ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($d->quantity_received ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($d->purchase_price ?? 0, 0, ',', ' ') }}</td>
                <td>{{ $unit }}</td>
                <td class="text-right">{{ number_format($d->total_amount_purchase ?? $d->total_value ?? 0, 0, ',', ' ') }} BIF</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:bold;">
                <td colspan="2" class="text-right">@lang('messages.TOTAL')</td>
                <td class="text-right">{{ number_format(collect($datas ?? [])->sum('quantity_ordered'), 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format(collect($datas ?? [])->sum('quantity_received'), 0, ',', ' ') }}</td>
                <td colspan="2"></td>
                <td class="text-right">{{ number_format($totalValue ?? 0, 0, ',', ' ') }} BIF</td>
            </tr>
        </tfoot>
    </table>

    @if(!empty($amountInWords))
    <p class="doc-meta" style="margin-top:12px;"><strong>@lang('messages.Montant en lettres :')</strong> {{ $amountInWords }}</p>
    @endif

    @if(!empty($description))
    <p class="doc-meta"><strong>@lang('messages.Description:')</strong> {{ $description }}</p>
    @endif

    @if($totalValue >= 1000000)
                <h4 style="text-decoration: underline;text-align: center;">@lang('messages.Pour la commission de Reception :')</h4>
                    <div style="display: flex;">
                        <div style="float: left;">
                            Nom et signature
                            <div>
                                
                            </div>
                        </div>

                        <div style="float: center;margin-left: 65%;">
                            Nom et signature
                            <div>
                            
                            </div>
                        </div>
                    </div>
                    <br><br><br><br>
                        <h4 style="text-decoration: underline;text-align: center;">@lang('messages.Pour le Gestionnaire du stock :')</h4>
                        <div style="float: center;margin-left: 35%;">
                            Nom et signature
                            <div>
                            
                            </div>
                        </div>
            @else
            <div style="display: flex;">
                        <div style="float: left;">
                            Nom et signature
                            <div>
                                
                            </div>
                        </div>

                        <div style="float: center;margin-left: 65%;">
                            Nom et signature
                            <div>
                            
                            </div>
                        </div>
                    </div>
            @endif
            </div>
</body>
</html>
