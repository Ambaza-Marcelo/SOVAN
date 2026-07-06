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
                           &nbsp;&nbsp; <img src="data:image/png;base64, {!! base64_encode(QrCode::size(50)->generate('MATRICULE : '.$data->matricule_no.' www.edengardenresort.bi ')) !!} ">
                        </small><br>
                        <small>
                           &nbsp;&nbsp; Employé : {{ $data->employe->firstname }}&nbsp;{{ $data->employe->lastname }}
                        </small><br>
                        <small>
                           &nbsp;&nbsp; Département : {{ $data->employe->departement->name }}
                        </small><br>
                        <small>
                           &nbsp;&nbsp; Service : {{ $data->employe->service->name }}
                        </small><br>
                        <small>
                           &nbsp;&nbsp; Fonction : {{ $data->employe->fonction->name }}
                        </small><br>
                        <small>
                           &nbsp;&nbsp; Date d'absence
                        </small><br>

                        <small>
                           &nbsp;&nbsp; Du : {{ \Carbon\Carbon::parse($take_conge_paye->date_heure_debut)->format('d/m/Y') }} AU {{ \Carbon\Carbon::parse($take_conge_paye->date_heure_fin)->format('d/m/Y') }}
                        </small>
                    </div>
                    <br><br><br><br><br><br><br><br><br><br><br><br>
                    <h3 style="text-align: center;">@lang('messages.LETTRE DE DEMANDE ET APPROBATION DE CONGE')</h3>
                    <div>
                        <h3><span>@lang('messages.Objet :') </span>@lang('messages.Demande de Congé annuel')</h3>
                        <div>
                          Motif: Au titre de l'année {{ \Carbon\Carbon::parse($take_conge_paye->date_heure_debut)->format('Y') }}, j'aimerais solliciter {{ $data->nbre_jours_conge_sollicite}} jours de congé payé. Je tiens par la présente à vous informer de mon souhait de prendre le congé tel que décrit ci-dessous:
                        </div>
                    </div><br>
                    <div>
                        <table style="border: 1px solid black;border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th>@lang('messages.Nombre de jours de congé annuel')</th>
                                    <th>@lang('messages.Nombre de jours de congé sollicité')</th>
                                    <th>@lang('messages.Nombre de jours de congé déjà pris')</th>
                                    <th>@lang('messages.Nombre de jours de congé restant')</th>
                                </tr>
                            </thead>
                            <tbody>
                               <tr>
                                    <td>{{ $nbre_jours_conge_paye }}</td>
                                    <td>{{ $take_conge_paye->nbre_jours_conge_sollicite }}</td>
                                    <td>{{ $data->nbre_jours_conge_pris }}</td>
                                    <td>{{ $data->nbre_jours_conge_restant }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <br>
                <div>
                Date de la demande et signature de l'Employé : {{ $data->employe->firstname }} {{ $data->employe->lastname }}
                </div>
                <br>
                <div>
                        <table style="border: 1px solid black;border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th>@lang('messages.Approbation du Supérieur hiérarchique')</th>
                                    <th>Approbation du Responsable de Gestion des ressources Hummaines</th>
                                    <th>@lang('messages.Approbation de la Direction')</th>
                                </tr>
                            </thead>
                            <tbody>
                               <tr>
                                    <td>@lang('messages.Nom et Signature :') <br> </td>
                                    <td>@lang('messages.Nom et Signature :')<br> </td>
                                    <td>@lang('messages.Nom et Signature :')<br> </td>
                                </tr>
                                <tr>
                                    <td>@lang('messages.Motif en cas du rejet :')<br> .................................</td>
                                    <td>@lang('messages.Motif en cas du rejet :') <br>.................................</td>
                                    <td>@lang('messages.Motif en cas du rejet :')<br> .................................</td>
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

