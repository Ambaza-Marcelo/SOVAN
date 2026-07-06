<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style type="text/css">
        table,tr,th,td{
             border: 1px solid black;
             text-align: left;
             width: 100%;
        }

        body{
            font-size: 12px;
        }

    </style>

<body>
<div>
    <div>
        <div>
            <div>
                @include('backend.pages.document.partials._document_header', [
                    'documentTitle' => 'BON DE SORTIE DE FONDS',
                    'documentNumber' => $cash_outflow_no ?? null,
                    'date' => $date ?? null
                ])
                <br>
                <div>
                    <div style="float: right; border-top-right-radius: 10px solid black;border-top-left-radius: 10px solid black;border-bottom-right-radius: 10px solid black;border-bottom-left-radius: 10px solid black; background-color: rgb(150,150,150);width: 242px;padding: 20px;">
                        <small>
                           &nbsp;&nbsp; <img src="data:image/png;base64, {!! base64_encode(QrCode::size(50)->generate('eSIGNATURE : '.$cash_outflow_signature.' www.edengardenresorts.bi, Cash outflow Number : '.$cash_outflow_no)) !!} ">
                        </small><br>
                        <small>
                           &nbsp;&nbsp;CON: {{ $cash_outflow_no }}
                        </small><br>
                        <small>
                           &nbsp;&nbsp;CORN: {{ $data->cash_outflow_request_no }}
                        </small><br>
                        <small>
                           &nbsp;&nbsp;Compte: @if($data->supplier_id) {{ $data->supplier->account->account_number }}-{{ $data->supplier->account->account_name }} @endif
                        </small><br>
                        <small>
                           &nbsp;&nbsp;Monnaie: @if($data->currency_id){{ $data->currency->currency_name }} @endif
                        </small><br>
                        <small>
                           &nbsp;&nbsp;Demandeur: {{ $data->asker }}
                        </small><br>
                        <small>
                           &nbsp;&nbsp; Date : Le {{ \Carbon\Carbon::parse($date)->format('d/m/Y H:i:s') }}
                        </small>
                    </div>
                    <br><br><br><br><br>
                    <br><br><br><br><br>
                    <br><br><br><br>
                    <div>
                        <table style="border: 1px solid black;border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th width="10%">@lang('messages.No')</th>
                                    <th width="10%">@lang('messages.Désignation')</th>
                                    <th width="10%">@lang('messages.Code')</th>
                                    <th width="10%">@lang('messages.quantity')</th>
                                    <th width="10%">@lang('messages.PU HTVA')</th>
                                    <th width="10%">@lang('messages.PT HTVA')</th>
                                    <th width="10%">@lang('messages.Taux TVA')</th>
                                    <th width="10%">@lang('messages.TVA')</th>
                                    <th width="10%">@lang('messages.TVAC')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($datas as $data)
                               @if($data->employe_id && $data->expense_id)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $data->expense->name }}</td>
                                    <td>{{ $data->expense->code }}</td>
                                    <td>{{ $data->quantity }}</td>
                                    <td>{{ number_format($data->cost_price,2,',',' ') }}</td>
                                    <td>{{ number_format($data->cost_nvat,2,',',' ') }}</td>
                                    <td>{{ $data->vat_rate }}%</td>
                                    <td>{{ number_format($data->vat,2,',',' ') }}</td>
                                    <td>{{ number_format($data->cost_wvat,0,',',' ') }}</td>
                                </tr>
                                @elseif(!empty($data->drink_id))
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $data->drink->name }}</td>
                                    <td>{{ $data->drink->code }}</td>
                                    <td>{{ $data->quantity }}</td>
                                    <td>{{ number_format($data->cost_price,2,',',' ') }}</td>
                                    <td>{{ number_format($data->cost_nvat,2,',',' ') }}</td>
                                    <td>{{ $data->vat_rate }}%</td>
                                    <td>{{ number_format($data->vat,2,',',' ') }}</td>
                                    <td>{{ number_format($data->cost_wvat,0,',',' ') }}</td>
                                </tr>
                                @elseif(!empty($data->r_material_id))
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $data->rawMaterial->name }}</td>
                                    <td>{{ $data->rawMaterial->code }}</td>
                                    <td>{{ $data->quantity }}</td>
                                    <td>{{ number_format($data->cost_price,2,',',' ') }}</td>
                                    <td>{{ number_format($data->cost_nvat,2,',',' ') }}</td>
                                    <td>{{ $data->vat_rate }}%</td>
                                    <td>{{ number_format($data->vat,2,',',' ') }}</td>
                                    <td>{{ number_format($data->cost_wvat,0,',',' ') }}</td>
                                </tr>
                                @elseif(!empty($data->material_id))
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $data->material->name ?? 'Matériel #'.$data->material_id }}</td>
                                    <td>{{ $data->material->code ?? '-' }}</td>
                                    <td>{{ $data->quantity }}</td>
                                    <td>{{ number_format($data->cost_price,2,',',' ') }}</td>
                                    <td>{{ number_format($data->cost_nvat,2,',',' ') }}</td>
                                    <td>{{ $data->vat_rate }}%</td>
                                    <td>{{ number_format($data->vat,2,',',' ') }}</td>
                                    <td>{{ number_format($data->cost_wvat,0,',',' ') }}</td>
                                </tr>
                                @elseif(!empty($data->item_id))
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $data->item->name ?? 'Article #'.$data->item_id }}</td>
                                    <td>{{ $data->item->code ?? '-' }}</td>
                                    <td>{{ $data->quantity }}</td>
                                    <td>{{ number_format($data->cost_price,2,',',' ') }}</td>
                                    <td>{{ number_format($data->cost_nvat,2,',',' ') }}</td>
                                    <td>{{ $data->vat_rate }}%</td>
                                    <td>{{ number_format($data->vat,2,',',' ') }}</td>
                                    <td>{{ number_format($data->cost_wvat,0,',',' ') }}</td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>@lang('messages.Total')</th>
                                    <th style="background-color: rgb(150,150,150);" colspan="4"></th>
                                    <th>{{ number_format($cost_nvat,3,',',' ') }}</th>
                                    <th></th>
                                    <th>{{ number_format($vat,3,',',' ') }}</th>
                                    <th>{{ number_format($cost_wvat,0,',',' ') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <br><br>
                <div>
                    &nbsp;&nbsp;{{ $description }}
                </div>
                    <br>
                    <div style="display: flex;">
                        <div style="float: left;">
                            &nbsp;&nbsp;Nom et signature
                            <div>
                                &nbsp;&nbsp;{{ $data->asker }}
                            </div>
                        </div>

                        <div style="float: center;margin-left: 65%;">
                            &nbsp;&nbsp;Nom et signature
                            <div>
                            &nbsp;&nbsp;{{ $data->created_by }}
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
