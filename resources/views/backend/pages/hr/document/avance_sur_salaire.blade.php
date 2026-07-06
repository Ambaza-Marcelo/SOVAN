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
            font-size: 16px;
        }

    </style>
</head>
<body>
<div>
    <div>
        <div>
            <div>
                <table>
                    <tr>
                        <td><img src="img/eden_logo.png" width="180" height="85"></td>
                        <td><small> &nbsp;&nbsp;{{$setting->commune}}-{{$setting->zone}},{{$setting->rue}}</small><br>
                          <small>&nbsp;&nbsp;{{$setting->telephone1}}-{{$setting->telephone2}}</small><br>
                          <small>&nbsp;&nbsp;{{$setting->email}}</small></td>
                    </tr>
                </table><br>
                <div>
                    <div style="float: right; border-top-right-radius: 10px solid black;border-top-left-radius: 10px solid black;border-bottom-right-radius: 10px solid black;border-bottom-left-radius: 10px solid black; background-color: rgb(150,150,150);width: 242px;padding: 20px;">
                        <small>
                           &nbsp;&nbsp; Date : {{ \Carbon\Carbon::parse($loan->date)->format('d/m/Y') }}
                        </small><br>
                        <small>
                           &nbsp;&nbsp; Employé : {{ $loan->employe->firstname }}&nbsp;{{ $loan->employe->lastname }}
                        </small><br>
                        <small>
                           &nbsp;&nbsp; Département : {{ $loan->employe->departement->name }}
                        </small><br>
                        <small>
                           &nbsp;&nbsp; Service : {{ $loan->employe->service->name }}
                        </small><br>
                        <small>
                           &nbsp;&nbsp; Fonction : {{ $loan->employe->fonction->name }}
                        </small><br>
                    </div>
                    <br><br><br><br><br><br><br><br><br><br>
                    <h3 style="text-align: center;">@lang('messages.LETTRE DE DEMANDE D\'AVANCE SUR SALAIRE')</h3>
                    <div>
                        <div>
                          &nbsp;&nbsp;&nbsp;&nbsp;Madame/Monsieur ,<br><br>Salarié de votre entreprise depuis le {{ \Carbon\Carbon::parse($loan->employe->date_debut)->format('d/m/Y') }}, je rencontre aujourd'hui quelques difficultés financières personnelles passagères. Par la présente, je vous exprime donc mon souhait de bénéficier d'une avance sur salaire.

                        </div><br><br>
                        <div>
                            En effet, {{ $loan->description }}
                        </div>
                    </div><br>
                    <div>
                        <table style="border: 1px solid black;border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th>@lang('messages.Montant à payer 1ere tranche')</th>
                                    <th>@lang('messages.Montant à payer 2eme tranche')</th>
                                    <th>@lang('messages.Montant à payer 3eme tranche')</th>
                                </tr>
                            </thead>
                            <tbody>
                               <tr>
                                    <td>{{ number_format($loan->amount_to_pay_1m,0,',',' ') }}</td>
                                    <td>{{ number_format($loan->amount_to_pay_2m,0,',',' ') }}</td>
                                    <td>{{ number_format($loan->amount_to_pay_3m,0,',',' ') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <br>
                <div>
                Je vous prie d'agréer, Madame/Monsieur, l'expression de mes sentiments les meilleurs.<br><br>
                Date de la demande et signature de l'Employé : {{ $loan->employe->firstname }} {{ $loan->employe->lastname }}
                </div>
                <br>
                <div>
                        <table style="border: 1px solid black;border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th>@lang('messages.Approbation du Supérieur hiérarchique(1er degré)')</th>
                                    <th>@lang('messages.Approbation du Supérieur hiérarchique(2eme degré)')</th>
                                    <th>@lang('messages.Approbation de la Direction')</th>
                                </tr>
                            </thead>
                            <tbody>
                               <tr>
                                    <td>@lang('messages.Nom et Signature :') <br>{{ $loan->valide_par }} </td>
                                    <td>@lang('messages.Nom et Signature :')<br> {{ $loan->confirme_par }}</td>
                                    <td>@lang('messages.Nom et Signature :')<br> {{ $loan->approuve_par }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

