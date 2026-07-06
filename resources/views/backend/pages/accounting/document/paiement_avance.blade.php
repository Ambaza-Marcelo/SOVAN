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
                    'documentTitle' => 'FICHE DE PAIEMENT AVANCE',
                    'documentNumber' => $advance->code ?? null,
                    'date' => $formattedDate ?? ($advance->date ?? null)
                ])
                <br>
                <div style="margin-bottom: 20px;">
                    <table class="header-table">
                        <tr>
                            <td><strong>@lang('messages.Date:')</strong> {{ $formattedDate ?? ($advance->date ? \Carbon\Carbon::parse($advance->date)->format('d F Y') : 'N/A') }}</td>
                            <td style="text-align: right;"><strong>@lang('messages.Réf:')</strong> {{ $advance->code ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="info-section">
                    <table class="content-table" style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold; width: 30%;">@lang('messages.Client')</td>
                            <td style="border: 1px solid black; padding: 10px;">
                                @if($advance->client && $advance->client->customer_name)
                                    <strong>{{ $advance->client->customer_name }}</strong>
                                    @if($advance->client->telephone)
                                        <br><small>Téléphone: {{ $advance->client->telephone }}</small>
                                    @endif
                                    @if($advance->client->customer_address)
                                        <br><small>Adresse: {{ $advance->client->customer_address }}</small>
                                    @endif
                                    @if($advance->client->customer_TIN)
                                        <br><small>NIF: {{ $advance->client->customer_TIN }}</small>
                                    @endif
                                @else
                                    <span class="text-danger">@lang('messages.N/A')</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold;">@lang('messages.Montant')</td>
                            <td style="border: 1px solid black; padding: 10px;">
                                <strong>{{ number_format($advance->amount ?? 0, 0, ',', '.') }}</strong>
                                @if(isset($amountInWords) && $amountInWords)
                                    <br><em>({{ $amountInWords }} francs Burundais)</em>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold;">@lang('messages.Compte Banque/Caisse')</td>
                            <td style="border: 1px solid black; padding: 10px;">
                                @if($advance->account)
                                    {{ $advance->account->account_number ?? 'N/A' }} - {{ $advance->account->account_name ?? 'N/A' }}
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold;">@lang('messages.Compte Avance')</td>
                            <td style="border: 1px solid black; padding: 10px;">
                                @if($advance->advanceAccount)
                                    {{ $advance->advanceAccount->account_number ?? 'N/A' }} - {{ $advance->advanceAccount->account_name ?? 'N/A' }}
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold;">@lang('messages.Personne de Contact')</td>
                            <td style="border: 1px solid black; padding: 10px;">
                                {{ $advance->contact_name ?? 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold;">@lang('messages.Téléphone de contact')</td>
                            <td style="border: 1px solid black; padding: 10px;">
                                {{ $advance->contact_tel ?? 'N/A' }}
                            </td>
                        </tr>
                        @if($advance->description)
                        <tr>
                            <td style="border: 1px solid black; padding: 10px; font-weight: bold;">@lang('messages.Description')</td>
                            <td style="border: 1px solid black; padding: 10px;">{{ $advance->description }}</td>
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
                                {{ $advance->created_by ?? '________________' }}
                            </td>
                            <td style="width: 25%; text-align: center;">
                                <strong>@lang('messages.Client')</strong><br><br><br>
                                {{ $advance->contact_name ?? '________________' }}
                            </td>
                        </tr>
                    </table>
                </div>
                <br><br>
                <div style="margin: 20px 0;">
                    <p><h3>Frais d'annulation (calculés sur la valeur totale estimée de l'événement):</h3>
                        <ul>
                            <li>@lang('messages.90 jours avant l\'événement :') <strong>@lang('messages.aucun frais')</strong></li>
                            <li>@lang('messages.Entre 89 et 60 jours avant l\'événement :') <strong>@lang('messages.10 à 25% de la valeur totale')</strong></li>
                            <li>@lang('messages.Entre 59 et 30 jours avant l\'événement :') <strong>@lang('messages.50 à 75% de la valeur totale')</strong></li>
                            <li>@lang('messages.Quelques jours avant l\'événement :') <strong>@lang('messages.100% de la valeur totale')</strong></li>
                        </ul>
                        <p>
                        Acomptes et paiements;les acomptes ne sont pas remboursables et sont déduits des éventuels frais d'annulation.
                        </p>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

