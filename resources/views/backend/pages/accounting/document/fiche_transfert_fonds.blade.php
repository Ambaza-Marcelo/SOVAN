<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style type="text/css">
        body{
            font-size: 12px;
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            border: none;
        }
        .header-table td {
            border: none;
            vertical-align: top;
        }
        .content-table {
            border: 1px solid black;
        }
        .content-table td, .content-table th {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        .signature-table {
            border: none;
            margin-top: 30px;
        }
        .signature-table td {
            border: none;
            padding: 10px;
            vertical-align: top;
        }
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-decoration: underline;
            margin: 20px 0;
        }
        .info-section {
            margin: 15px 0;
        }
        .info-label {
            font-weight: bold;
        }
        @media print {
            body {
                margin: 0;
                padding: 20px;
            }
        }

        table,tr,th,td{
             border: 1px solid black;
             text-align: left;
             width: 100%;
        }

    </style>
</head>
<body>
<div>
    <div>
        <div>
            <div>
                @include('backend.pages.document.partials._document_header', [
                    'documentTitle' => 'FICHE DE TRANSFERT DE FONDS',
                    'documentNumber' => $fund_transfer->code ?? null,
                    'date' => $fund_transfer->date ?? null
                ])
                <br>
                <div style="margin-bottom: 20px;">
                    <table class="header-table">
                        <tr>
                            <td><strong>@lang('messages.Date:')</strong> {{ \Carbon\Carbon::parse($fund_transfer->date)->format('d F Y') }}</td>
                            <td style="text-align: right;"><strong>@lang('messages.Réf:')</strong> {{ $fund_transfer->code ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
                
                <div style="margin: 20px 0;">
                    <p>Ce document est établi pour autoriser un transfert de fonds selon les informations suivantes :</p>
                </div>
                
                <div class="info-section">
                    <table class="content-table" style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold; width: 30%;">@lang('messages.Source de fonds')</td>
                            <td style="border: 1px solid black; padding: 10px;">
                                @if($fund_transfer->originAccount)
                                    {{ $fund_transfer->originAccount->account_number }}-{{ $fund_transfer->originAccount->account_name }}
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold;">@lang('messages.Montant')</td>
                            <td style="border: 1px solid black; padding: 10px;">
                                <strong>{{ number_format($fund_transfer->amount, 0, ',', '.') }}</strong>
                                <br><em>({{ $amountInWords }} francs Burundais)</em>
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold;">@lang('messages.Destination')</td>
                            <td style="border: 1px solid black; padding: 10px;">
                                @if($fund_transfer->DestinationAccount)
                                    {{ $fund_transfer->DestinationAccount->account_number }} - {{ $fund_transfer->DestinationAccount->account_name }}
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold;">@lang('messages.Numéro de compte')</td>
                            <td style="border: 1px solid black; padding: 10px;">
                                
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold;">@lang('messages.Titulaire de compte')</td>
                            <td style="border: 1px solid black; padding: 10px;">
                                
                            </td>
                        </tr>
                        @if($fund_transfer->description)
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold;">@lang('messages.Description')</td>
                            <td style="border: 1px solid black; padding: 10px;">{{ $fund_transfer->description }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
                
                <div style="margin-top: 40px;">
                    <h4 style="text-decoration: underline; margin-bottom: 20px;">@lang('messages.Signatures pour approbation')</h4>
                    <table class="signature-table" style="width: 100%;">
                        <tr>
                            <td style="width: 25%; text-align: center;">
                                <strong>@lang('messages.Caissière')</strong><br><br><br>
                                {{ $fund_transfer->created_by ?? '________________' }}
                            </td>
                            <td style="width: 25%; text-align: center;">
                                <strong>@lang('messages.Chef Comptable')</strong><br><br><br>
                                ________________
                            </td>
                            <td style="width: 25%; text-align: center;">
                                <strong>@lang('messages.Chef de service Administration et Finance')</strong><br><br>
                                ________________
                            </td>
                            <td style="width: 25%; text-align: center;">
                                <strong>@lang('messages.Direction Générale')</strong><br><br><br>
                                 ________________
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

