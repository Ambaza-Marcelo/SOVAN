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
                @include('backend.pages.document.partials.standard_document_header_contact', ['setting' => $setting ?? null])
<div>
                                        <div style="float: right; border-top-right-radius: 10px solid black;border-top-left-radius: 10px solid black;border-bottom-right-radius: 10px solid black;border-bottom-left-radius: 10px solid black;background-color: rgb(150,150,150);width: 242px;padding: 20px;">
                        <small>
                           &nbsp;&nbsp; Date: {{ \Carbon\Carbon::parse($loan->date)->format('d/m/Y') }}
                        </small><br>
                        <small>
                           &nbsp;&nbsp; Employé: {{ $loan->employe->firstname }}&nbsp;{{ $loan->employe->lastname }}
                        </small><br>
                        <small>
                           &nbsp;&nbsp; Département: {{ $loan->employe->departement->name }}
                        </small><br>
                        <small>
                           &nbsp;&nbsp; Service: {{ $loan->employe->service->name }}
                        </small><br>
                        <small>
                           &nbsp;&nbsp; Fonction: {{ $loan->employe->fonction->name }}
                        </small>
                    </div>


                    

@include('backend.pages.document.partials.standard_document_header_spacer')
<h3 style="text-align: center;">LETTRE DE DEMANDE D'AVANCE SUR SALAIRE</h3>
                    <div>
                        <div>
                          &nbsp;&nbsp;&nbsp;&nbsp;Madame/Monsieur ,<br><br>Salarié de votre entreprise depuis le {{ \Carbon\Carbon::parse($loan->employe->date_debut)->format('d/m/Y') }}, je rencontre aujourd'hui quelques difficultés financières personnelles passagères. Par la présente, je vous exprime donc mon souhait de bénéficier d'une avance sur salaire.

                        </div><br><br>
                        <div>
                            En effet, {{ $loan->description }}
                        </div>
                    </div><br>
                    <div style="clear: both; width: 100%;">
                        <table style="border: 1px solid black;border-collapse: collapse; width: 100%">
                            <thead>
                                <tr>
                                    <th>Montant à payer 1ere tranche</th>
                                    <th>Montant à payer 2eme tranche</th>
                                    <th>Montant à payer 3eme tranche</th>
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
                <div style="clear: both; width: 100%;">
                        <table style="border: 1px solid black;border-collapse: collapse; width: 100%">
                            <thead>
                                <tr>
                                    <th>Approbation du Supérieur hiérarchique(1er degré)</th>
                                    <th>Approbation du Supérieur hiérarchique(2eme degré)</th>
                                    <th>Approbation de la Direction</th>
                                </tr>
                            </thead>
                            <tbody>
                               <tr>
                                    <td>Nom et Signature : <br>{{ $loan->valide_par }} </td>
                                    <td>Nom et Signature :<br> {{ $loan->confirme_par }}</td>
                                    <td>Nom et Signature :<br> {{ $loan->approuve_par }}</td>
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

