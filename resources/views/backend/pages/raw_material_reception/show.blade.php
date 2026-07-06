
@extends('backend.layouts.master')

@section('title')
@lang('messages.details des receptions') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <!-- Start datatable css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
    <style>
        .nav-tabs .nav-link {
            color: #495057;
            border: 1px solid transparent;
            border-top-left-radius: 0.25rem;
            border-top-right-radius: 0.25rem;
        }
        .nav-tabs .nav-link:hover {
            border-color: #e9ecef #e9ecef #dee2e6;
        }
        .nav-tabs .nav-link.active {
            color: #495057;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .info-value {
            color: #6c757d;
        }
        .badge-status-1 { background-color: #ffc107; color: #000; }
        .badge-status-2 { background-color: #17a2b8; color: white; }
        .badge-status-3 { background-color: #28a745; color: white; }
        .badge-status-4 { background-color: #007bff; color: white; }
        .badge-status-4-paid { background-color: #6f42c1; color: white; }
    </style>
@endsection

@section('admin-content')
<div class="main-content-inner">
    <div class="row">
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="header-title">Details sur bon de reception No : {{ $reception->reception_no ?? '' }}</h4>
                            @php
                                $status = $reception->status ?? null;
                                $flag2 = $reception->flag2 ?? null;
                                $statusText = '';
                                $statusClass = '';
                                
                                if ($status == 1) {
                                    $statusText = 'En cours';
                                    $statusClass = 'badge-status-1';
                                } elseif ($status == '2') {
                                    $statusText = 'Validée';
                                    $statusClass = 'badge-status-2';
                                } elseif ($status == '3') {
                                    $statusText = 'Confirmée';
                                    $statusClass = 'badge-status-3';
                                } elseif ($status == 4 && $flag2 == 0) {
                                    $statusText = 'Approuvé';
                                    $statusClass = 'badge-status-4';
                                } elseif ($status == 4 && $flag2 == 1) {
                                    $statusText = 'Approuvé et déjà payé';
                                    $statusClass = 'badge-status-4-paid';
                                } else {
                                    $statusText = 'Non défini';
                                    $statusClass = 'badge-secondary';
                                }
                            @endphp
                            <span class="badge {{ $statusClass }} badge-lg mt-2">
                                <i class="fa fa-info-circle"></i> {{ $statusText }}
                            </span>
                        </div>
                        <div>
                            <a href="{{ route('admin.raw-material-receptions.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Retour à la liste
                            </a>
                        </div>
                    </div>
                    
                    @include('backend.layouts.partials.messages')
                    <div class="mb-3">
                        @include('backend.layouts.partials.update_supplier_modal', [
                            'updateSupplierRoute' => route('admin.raw-material-receptions.update-supplier', $reception->reception_no),
                            'currentSupplierId' => $reception->supplier_id ?? '',
                            'suppliers' => $suppliers ?? collect(),
                            'entityLabel' => 'cette fiche de réception',
                        ])
                    </div>
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="receptionTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">
                                <i class="fa fa-info-circle"></i> Informations Générales
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="parties-tab" data-toggle="tab" href="#parties" role="tab" aria-controls="parties" aria-selected="false">
                                <i class="fa fa-user-tie"></i> Détails Fournisseur / Employé
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="items-tab" data-toggle="tab" href="#items" role="tab" aria-controls="items" aria-selected="false">
                                <i class="fa fa-list"></i> Détails des Articles
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="approbations-tab" data-toggle="tab" href="#approbations" role="tab" aria-controls="approbations" aria-selected="false">
                                <i class="fa fa-check-circle"></i> Approbations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="summary-tab" data-toggle="tab" href="#summary" role="tab" aria-controls="summary" aria-selected="false">
                                <i class="fa fa-chart-bar"></i> Résumé
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content" id="receptionTabContent">
                        <!-- General Information Tab -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card mb-3">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0">@lang('messages.Informations de la Réception')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr>
                                                                <th class="info-label" width="40%">@lang('messages.Numéro de Réception:')</th>
                                                                <td class="info-value">{{ $reception->reception_no ?? 'N/A' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Numéro de Commande:')</th>
                                                                <td class="info-value">{{ $reception->order_no ?? 'N/A' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Date:')</th>
                                                                <td class="info-value">{{ $reception->date ? \Carbon\Carbon::parse($reception->date)->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Fournisseur:')</th>
                                                                <td class="info-value">
                                                                    @if($reception->supplier_id && $reception->supplier)
                                                                        {{ $reception->supplier->supplier_name ?? 'N/A' }}
                                                                    @else
                                                                        N/A
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Numéro de Facture:')</th>
                                                                <td class="info-value">{{ $reception->invoice_no ?? 'N/A' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Devise de la Facture:')</th>
                                                                <td class="info-value">{{ $reception->invoice_currency ?? 'N/A' }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr>
                                                                <th class="info-label" width="40%">@lang('messages.Remettant:')</th>
                                                                <td class="info-value">{{ $reception->handingover ?? 'N/A' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Réceptionniste:')</th>
                                                                <td class="info-value">{{ $reception->receptionist ?? 'N/A' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Magasin de Destination:')</th>
                                                                <td class="info-value">
                                                                    @if($reception->destinationStore)
                                                                        {{ $reception->destinationStore->code ?? 'N/A' }} - {{ $reception->destinationStore->name ?? 'N/A' }}
                                                                    @elseif($reception->destinationExtraStore)
                                                                        {{ $reception->destinationExtraStore->code ?? 'N/A' }} - {{ $reception->destinationExtraStore->name ?? 'N/A' }}
                                                                    @else
                                                                        N/A
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Fournisseur TVA:')</th>
                                                                <td class="info-value">
                                                                    @if($reception->vat_supplier_payer == '1')
                                                                        <span class="badge badge-success">@lang('messages.Oui')</span>
                                                                    @else
                                                                        <span class="badge badge-secondary">@lang('messages.Non')</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Type de Réception:')</th>
                                                                <td class="info-value">{{ $reception->type_reception ?? 'N/A' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Statut:')</th>
                                                                <td class="info-value">
                                                                    <span class="badge {{ $statusClass }}">
                                                                        {{ $statusText }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            @if($reception->description)
                                            <div class="row mt-3">
                                                <div class="col-md-12">
                                                    <div class="card">
                                                        <div class="card-header bg-light">
                                                            <h6 class="mb-0">@lang('messages.Description')</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <p class="mb-0">{{ $reception->description }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Détails Fournisseur / Employé Tab -->
                        <div class="tab-pane fade" id="parties" role="tabpanel" aria-labelledby="parties-tab">
                            @if($reception && ($reception->supplier_id || (isset($reception->employe_id) && $reception->employe_id)))
                            <div class="row">
                                @if($reception->supplier_id && $reception->supplier)
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0"><i class="fa fa-truck"></i> @lang('messages.Fournisseur')</h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered table-sm">
                                                <tbody>
                                                    <tr><th class="info-label" width="40%">@lang('messages.Nom')</th><td>{{ $reception->supplier->supplier_name ?? 'N/A' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Téléphone')</th><td>{{ $reception->supplier->telephone ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Email')</th><td>{{ $reception->supplier->mail ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Adresse')</th><td>{{ $reception->supplier->supplier_address ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.NIF/TIN')</th><td>{{ $reception->supplier->supplier_TIN ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Assujetti TVA')</th><td>{{ isset($reception->supplier->vat_supplier_payer) && $reception->supplier->vat_supplier_payer == '1' ? 'Oui' : 'Non' }}</td></tr>
                                                    @if(!empty(optional($reception->supplier)->company))<tr><th class="info-label">@lang('messages.Société')</th><td>{{ $reception->supplier->company }}</td></tr>@endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if(isset($reception->employe_id) && $reception->employe_id && $reception->employe)
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0"><i class="fa fa-user"></i> @lang('messages.Employé')</h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered table-sm">
                                                <tbody>
                                                    <tr><th class="info-label" width="40%">@lang('messages.Nom')</th><td>{{ $reception->employe->firstname }} {{ $reception->employe->lastname }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Matricule')</th><td>{{ $reception->employe->matricule_no ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Téléphone')</th><td>{{ $reception->employe->phone_no ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Email')</th><td>{{ $reception->employe->mail ?? '-' }}</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @else
                            <div class="alert alert-info">@lang('messages.Aucun fournisseur ou employé associé à cette réception.')</div>
                            @endif
                        </div>

                        <!-- Items Tab -->
                        <div class="tab-pane fade" id="items" role="tabpanel" aria-labelledby="items-tab">
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0">@lang('messages.Détails des Articles Reçus')</h5>
                                        </div>
                                        <div class="card-body">
                    <div class="data-tables">
                                                <table id="itemsTable" class="text-center table table-striped table-bordered">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                                            <th width="15%">@lang('messages.item')</th>
                                    <th width="10%">@lang('messages.quantity_ordered')</th>
                                                            <th width="10%">@lang('messages.quantity_received')</th>
                                    <th width="10%">@lang('messages.unit_price')</th>
                                    <th width="10%">@lang('messages.unit')</th>
                                    <th width="10%">@lang('messages.total_value')</th>
                                    <th width="20%">@lang('messages.description')</th>
                                </tr>
                            </thead>
                            <tbody>
                                                       @foreach ($receptionDetails as $detail)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                                            <td>
                                                                <strong>{{ $detail->rawMaterial->name ?? 'N/A' }}</strong>
                                                                @if($detail->rawMaterial && $detail->rawMaterial->code)
                                                                    <br><small class="text-muted">Code: {{ $detail->rawMaterial->code }}</small>
                                                                @endif
                                                            </td>
                                                            <td>{{ $detail->quantity_ordered ?? '0' }}</td>
                                                            <td>
                                                                <strong class="text-success">{{ $detail->quantity_received ?? '0' }}</strong>
                                    </td>
                                                            <td>{{ number_format($detail->purchase_price ?? 0,0,',',' ' ) }}</td>
                                                            <td>{{ $detail->rawMaterial->rawMaterialMeasurement->purchase_unit ?? 'N/A' }}</td>
                                                            <td><strong>{{ number_format($detail->total_amount_received ?? 0,0,',',' ' ) }}</strong></td>
                                                            <td>{{ $detail->description ?? '-' }}</td>
                                </tr>
                               @endforeach
                            </tbody>
                                                    <tfoot>
                                                        <tr class="bg-light">
                                                            <th colspan="2" class="text-right">@lang('messages.TOTAL:')</th>
                                                            <th>{{ number_format($totalQuantityOrdered ?? 0, 0, ',', ' ') }}</th>
                                                            <th><strong class="text-success">{{ number_format($totalQuantityReceived ?? 0, 0, ',', ' ') }}</strong></th>
                                                            <th colspan="2"></th>
                                                            <th><strong>{{ number_format($totalAmountReceived ?? 0, 0, ',', ' ') }}</strong></th>
                                                            <th></th>
                                                        </tr>
                                                    </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
                            </div>
                        </div>

                        <!-- Approbations Tab -->
                        <div class="tab-pane fade" id="approbations" role="tabpanel" aria-labelledby="approbations-tab">
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card mb-3">
                                        <div class="card-header bg-secondary text-white">
                                            <h5 class="mb-0"><i class="fa fa-check-circle"></i> @lang('messages.Approbations de la réception')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr>
                                                                <th class="info-label" width="45%">@lang('messages.Auteur (Créé par):')</th>
                                                                <td class="info-value">{{ $reception->created_by ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Modifié par:')</th>
                                                                <td class="info-value">{{ $reception->updated_by ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Validé par:')</th>
                                                                <td class="info-value">{{ $reception->validated_by ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Confirmé par:')</th>
                                                                <td class="info-value">{{ $reception->confirmed_by ?? '-' }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr>
                                                                <th class="info-label" width="45%">@lang('messages.Approuvé par:')</th>
                                                                <td class="info-value">{{ $reception->approuved_by ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Rejeté par:')</th>
                                                                <td class="info-value">{{ $reception->rejected_by ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Réinitialisé par:')</th>
                                                                <td class="info-value">{{ $reception->reseted_by ?? '-' }}</td>
                                                            </tr>
                                                            @if(!empty($reception->rejected_motif))
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Motif de rejet:')</th>
                                                                <td class="info-value">{{ $reception->rejected_motif }}</td>
                                                            </tr>
                                                            @endif
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Tab -->
                        <div class="tab-pane fade" id="summary" role="tabpanel" aria-labelledby="summary-tab">
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card text-center mb-3">
                                                <div class="card-body">
                                                    <h5 class="card-title text-primary">@lang('messages.Quantité Commandée')</h5>
                                                    <h2 class="text-primary">{{ number_format($totalQuantityOrdered ?? 0, 0, ',', ' ') }}</h2>
                                                    <p class="text-muted">@lang('messages.Unités')</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card text-center mb-3">
                                                <div class="card-body">
                                                    <h5 class="card-title text-success">@lang('messages.Quantité Reçue')</h5>
                                                    <h2 class="text-success">{{ number_format($totalQuantityReceived ?? 0, 0, ',', ' ') }}</h2>
                                                    <p class="text-muted">@lang('messages.Unités')</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card text-center mb-3">
                                                <div class="card-body">
                                                    <h5 class="card-title text-info">@lang('messages.Montant Total')</h5>
                                                    <h2 class="text-info">{{ number_format($totalAmountReceived ?? 0, 0, ',', ' ') }}</h2>
                                                    <p class="text-muted">{{ $reception->invoice_currency ?? 'BIF' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="card">
                                                <div class="card-header bg-info text-white">
                                                    <h5 class="mb-0">@lang('messages.Statistiques')</h5>
                                                </div>
                                                <div class="card-body">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr>
                                                                <th width="30%">@lang('messages.Nombre d\'articles:')</th>
                                                                <td><strong>{{ count($receptionDetails) }}</strong></td>
                                                            </tr>
                                                            <tr>
                                                                <th>@lang('messages.Différence (Commandé - Reçu):')</th>
                                                                <td>
                                                                    @php
                                                                        $difference = ($totalQuantityOrdered ?? 0) - ($totalQuantityReceived ?? 0);
                                                                    @endphp
                                                                    @if($difference > 0)
                                                                        <span class="badge badge-warning">{{ number_format($difference, 0, ',', ' ') }} unités manquantes</span>
                                                                    @elseif($difference < 0)
                                                                        <span class="badge badge-info">{{ number_format(abs($difference), 0, ',', ' ') }} unités en surplus</span>
                                                                    @else
                                                                        <span class="badge badge-success">@lang('messages.Réception complète')</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>@lang('messages.Taux de réception:')</th>
                                                                <td>
                                                                    @php
                                                                        $status = $reception->status ?? null;
                                                                        $flag2 = $reception->flag2 ?? null;
                                                                        
                                                                        // Si le statut est 4 (approuvé), le taux est toujours à 100%
                                                                        if ($status == 4) {
                                                                            $rate = 100;
                                                                        } else {
                                                                            // Sinon, calculer le taux normalement
                                                                            $rate = ($totalQuantityOrdered ?? 0) > 0 
                                                                                ? (($totalQuantityReceived ?? 0) / ($totalQuantityOrdered ?? 1)) * 100 
                                                                                : 0;
                                                                        }
                                                                    @endphp
                                                                    <div class="progress">
                                                                        <div class="progress-bar 
                                                                            @if($rate == 100) bg-success
                                                                            @elseif($rate >= 80) bg-warning
                                                                            @else bg-danger
                                                                            @endif" 
                                                                            role="progressbar" 
                                                                            style="width: {{ $rate }}%" 
                                                                            aria-valuenow="{{ $rate }}" 
                                                                            aria-valuemin="0" 
                                                                            aria-valuemax="100">
                                                                            {{ number_format($rate, 2) }}%
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
     <!-- Start datatable js -->
     <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
     
     <script>
         /*================================
        datatable active
        ==================================*/
        if ($('#itemsTable').length) {
            $('#itemsTable').DataTable({
                responsive: true,
                order: [[0, 'asc']],
                pageLength: 25,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.19/i18n/French.json'
                }
            });
        }

     </script>
@endsection
