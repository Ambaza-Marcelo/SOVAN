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
                <table class="header-table">
                    <tr>
                        <td>
                            <img src="img/logo.png" width="180" height="85" alt="Logo">
                        </td>
                        <td>
                            @if($setting)
                                <small> &nbsp;&nbsp;{{ $setting->commune }}-{{ $setting->zone }},{{ $setting->rue }}</small><br>
                                <small>&nbsp;&nbsp;{{ $setting->telephone1 }}-{{ $setting->telephone2 }}</small><br>
                                <small>&nbsp;&nbsp;{{ $setting->email }}</small>
                            @endif
                        </td>
                    </tr>
                </table>
                <br><br>
                
                <div class="title">
                    FICHE DE RETOUR DE FONDS
                </div>
                
                <div style="margin-bottom: 20px;">
                    <table class="header-table">
                        <tr>
                            <td><strong>@lang('messages.Date:')</strong> {{ \Carbon\Carbon::parse($return_fund->date)->format('d F Y') }}</td>
                            <td style="text-align: right;"><strong>@lang('messages.N° Retour:')</strong> {{ $return_fund->return_no ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>@lang('messages.N° Sortie:')</strong> {{ $return_fund->cash_outflow_no ?? 'N/A' }}</td>
                            <td style="text-align: right;"><strong>@lang('messages.Signature:')</strong> {{ $return_fund->return_signature ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
                
                <div style="margin: 20px 0;">
                    <p>Ce document atteste du retour de fonds selon les informations suivantes :</p>
                </div>
                
                <div class="info-section">
                    <table class="content-table" style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold; width: 30%;">@lang('messages.Employé')</td>
                            <td style="border: 1px solid black; padding: 10px;">
                                @if($return_fund->employe)
                                    {{ $return_fund->employe->firstname }} {{ $return_fund->employe->lastname }}
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold;">@lang('messages.Compte')</td>
                            <td style="border: 1px solid black; padding: 10px;">
                                @if($return_fund->account)
                                    {{ $return_fund->account->account_number }} - {{ $return_fund->account->account_name }}
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold;">@lang('messages.Montant Sorti')</td>
                            <td style="border: 1px solid black; padding: 10px;">
                                <strong>{{ number_format($return_fund->total_amount_picked, 0, ',', '.') }}</strong>
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold;">@lang('messages.Montant Retourné')</td>
                            <td style="border: 1px solid black; padding: 10px;">
                                <strong>{{ number_format($return_fund->total_amount_returned, 0, ',', '.') }}</strong>
                                <br><em>({{ $amountInWords }} francs Burundais)</em>
                            </td>
                        </tr>
                        @if($return_fund->description)
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold;">@lang('messages.Description')</td>
                            <td style="border: 1px solid black; padding: 10px;">{{ $return_fund->description }}</td>
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
                                @if($return_fund->employe)
                                    {{ $return_fund->employe->firstname }} {{ $return_fund->employe->lastname }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td style="width: 25%; text-align: center;">
                                <strong>@lang('messages.Validé par')</strong><br><br><br>
                                {{ $return_fund->created_by ?? '________________' }}
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

