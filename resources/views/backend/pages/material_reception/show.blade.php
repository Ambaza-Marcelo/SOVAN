
@extends('backend.layouts.master')

@section('title')
@lang('messages.details des receptions') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
    <style>
        .nav-tabs .nav-link { color: #495057; border: 1px solid transparent; border-top-left-radius: 0.25rem; border-top-right-radius: 0.25rem; }
        .nav-tabs .nav-link:hover { border-color: #e9ecef #e9ecef #dee2e6; }
        .nav-tabs .nav-link.active { color: #495057; background-color: #fff; border-color: #dee2e6 #dee2e6 #fff; }
        .info-label { font-weight: 600; color: #495057; }
        .info-value { color: #6c757d; }
        .badge-status-1 { background-color: #ffc107; color: #000; }
        .badge-status-2 { background-color: #17a2b8; color: white; }
        .badge-status-3 { background-color: #28a745; color: white; }
        .badge-status-4 { background-color: #007bff; color: white; }
        .tab-content { border: 1px solid #dee2e6; border-top: none; padding: 20px; background-color: #fff; }
    </style>
@endsection

@section('admin-content')
<div class="main-content-inner">
    <div class="row">
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    @php
                        $status = $reception->status ?? null;
                        $statusText = $status == 1 ? 'En cours' : ($status == 2 ? 'Validée' : ($status == 3 ? 'Confirmée' : ($status == 4 ? 'Approuvé' : 'Non défini')));
                        $statusClass = $status == 1 ? 'badge-status-1' : ($status == 2 ? 'badge-status-2' : ($status == 3 ? 'badge-status-3' : ($status == 4 ? 'badge-status-4' : 'badge-secondary')));
                    @endphp
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="header-title">{{ __('messages.reception_details_title', ['no' => $reception->reception_no ?? '']) }}</h4>
                            <span class="badge {{ $statusClass }} badge-lg mt-2"><i class="fa fa-info-circle"></i> {{ $statusText }}</span>
                        </div>
                        <div>
                            <a href="{{ route('admin.material-receptions.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Retour à la liste
                            </a>
                        </div>
                    </div>

                    @include('backend.layouts.partials.messages')
                    <div class="mb-3">
                        @include('backend.layouts.partials.update_supplier_modal', [
                            'updateSupplierRoute' => route('admin.material-receptions.update-supplier', $reception->reception_no),
                            'currentSupplierId' => $reception->supplier_id ?? '',
                            'suppliers' => $suppliers ?? collect(),
                            'entityLabel' => 'cette fiche de réception',
                        ])
                    </div>
                    <ul class="nav nav-tabs" id="receptionTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab"><i class="fa fa-info-circle"></i> @lang('messages.Informations Générales')</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="parties-tab" data-toggle="tab" href="#parties" role="tab"><i class="fa fa-user-tie"></i> @lang('messages.Détails Fournisseur / Employé')</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="items-tab" data-toggle="tab" href="#items" role="tab"><i class="fa fa-list"></i> @lang('messages.Détails des Articles')</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="approbations-tab" data-toggle="tab" href="#approbations" role="tab"><i class="fa fa-check-circle"></i> @lang('messages.Approbations')</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="summary-tab" data-toggle="tab" href="#summary" role="tab"><i class="fa fa-chart-bar"></i> @lang('messages.Résumé')</a>
                        </li>
                    </ul>

                    <div class="tab-content" id="receptionTabContent">
                        <!-- General -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card mb-3">
                                        <div class="card-header bg-primary text-white"><h5 class="mb-0">@lang('messages.Informations de la Réception')</h5></div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr><th class="info-label" width="40%">@lang('messages.Numéro de Réception')</th><td class="info-value">{{ $reception->reception_no ?? 'N/A' }}</td></tr>
                                                            <tr><th class="info-label">@lang('messages.Numéro de Commande')</th><td class="info-value">{{ $reception->order_no ?? 'N/A' }}</td></tr>
                                                            <tr><th class="info-label">@lang('messages.Date')</th><td class="info-value">{{ $reception->date ? \Carbon\Carbon::parse($reception->date)->format('d/m/Y H:i:s') : 'N/A' }}</td></tr>
                                                            <tr><th class="info-label">@lang('messages.Fournisseur')</th><td class="info-value">@if($reception->supplier_id && $reception->supplier){{ $reception->supplier->supplier_name ?? 'N/A' }}@else N/A @endif</td></tr>
                                                            <tr><th class="info-label">@lang('messages.Numéro de Facture')</th><td class="info-value">{{ $reception->invoice_no ?? 'N/A' }}</td></tr>
                                                            <tr><th class="info-label">@lang('messages.Devise de la Facture')</th><td class="info-value">{{ $reception->invoice_currency ?? 'N/A' }}</td></tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr><th class="info-label" width="40%">@lang('messages.Remettant')</th><td class="info-value">{{ $reception->handingover ?? 'N/A' }}</td></tr>
                                                            <tr><th class="info-label">@lang('messages.Réceptionniste')</th><td class="info-value">{{ $reception->receptionist ?? 'N/A' }}</td></tr>
                                                            <tr><th class="info-label">@lang('messages.Magasin de Destination')</th><td class="info-value">{{ $reception->destinationLabel() }}</td></tr>
                                                            <tr><th class="info-label">@lang('messages.Fournisseur TVA')</th><td class="info-value">
                                                                @if($reception->vat_supplier_payer == '1')
                                                                    <span class="badge badge-success">@lang('messages.Oui')</span>
                                                                @else
                                                                    <span class="badge badge-secondary">@lang('messages.Non')</span>
                                                                @endif
                                                            </td></tr>
                                                            <tr><th class="info-label">@lang('messages.Statut')</th><td class="info-value"><span class="badge {{ $statusClass }}">{{ $statusText }}</span></td></tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            @if($reception->description)
                                            <div class="row mt-3">
                                                <div class="col-md-12">
                                                    <div class="card"><div class="card-header bg-light"><h6 class="mb-0">@lang('messages.Description')</h6></div><div class="card-body"><p class="mb-0">{{ $reception->description }}</p></div></div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Détails Fournisseur / Employé -->
                        <div class="tab-pane fade" id="parties" role="tabpanel">
                            @if($reception->supplier_id || (isset($reception->employe_id) && $reception->employe_id))
                            <div class="row">
                                @if($reception->supplier_id && $reception->supplier)
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fa fa-truck"></i> @lang('messages.Fournisseur')</h5></div>
                                        <div class="card-body">
                                            <table class="table table-bordered table-sm">
                                                <tbody>
                                                    <tr><th class="info-label" width="40%">@lang('messages.Nom')</th><td>{{ $reception->supplier->supplier_name ?? 'N/A' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Téléphone')</th><td>{{ $reception->supplier->telephone ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Email')</th><td>{{ $reception->supplier->mail ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Adresse')</th><td>{{ $reception->supplier->supplier_address ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.NIF/TIN')</th><td>{{ $reception->supplier->supplier_TIN ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Assujetti TVA')</th><td>{{ isset($reception->supplier->vat_supplier_payer) && $reception->supplier->vat_supplier_payer == '1' ? __('messages.Oui') : __('messages.Non') }}</td></tr>
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
                                        <div class="card-header bg-info text-white"><h5 class="mb-0"><i class="fa fa-user"></i> @lang('messages.Employé')</h5></div>
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

                        <!-- Items -->
                        <div class="tab-pane fade" id="items" role="tabpanel">
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header bg-success text-white"><h5 class="mb-0">@lang('messages.Détails des Articles Reçus')</h5></div>
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
                                                                @php
                                                                    $designation = ($detail->material_id && $detail->material)
                                                                        ? ($detail->material->name ?? 'N/A')
                                                                        : (($detail->item_id && $detail->item) ? ($detail->item->name ?? 'N/A') : 'N/A');
                                                                    $code = ($detail->material_id && $detail->material)
                                                                        ? ($detail->material->code ?? null)
                                                                        : (($detail->item_id && $detail->item) ? ($detail->item->code ?? null) : null);
                                                                @endphp
                                                                <strong>{{ $designation }}</strong>
                                                                @if($code)<br><small class="text-muted">Code: {{ $code }}</small>@endif
                                                            </td>
                                                            <td>{{ $detail->quantity_ordered ?? '0' }}</td>
                                                            <td><strong class="text-success">{{ $detail->quantity_received ?? '0' }}</strong></td>
                                                            <td>{{ number_format($detail->purchase_price ?? 0, 0, ',', ' ') }}</td>
                                                            <td>{{ $detail->unit ?? ($detail->material && $detail->material->materialMeasurement ? $detail->material->materialMeasurement->purchase_unit : 'N/A') }}</td>
                                                            <td><strong>{{ number_format($detail->total_amount_received ?? 0, 0, ',', ' ') }}</strong></td>
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

                        <!-- Approbations -->
                        <div class="tab-pane fade" id="approbations" role="tabpanel">
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card mb-3">
                                        <div class="card-header bg-secondary text-white"><h5 class="mb-0"><i class="fa fa-check-circle"></i> @lang('messages.Approbations de la réception')</h5></div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr><th class="info-label" width="45%">@lang('messages.Auteur (Créé par)')</th><td class="info-value">{{ $reception->created_by ?? '-' }}</td></tr>
                                                            <tr><th class="info-label">@lang('messages.Modifié par')</th><td class="info-value">{{ $reception->updated_by ?? '-' }}</td></tr>
                                                            <tr><th class="info-label">@lang('messages.Validé par')</th><td class="info-value">{{ $reception->validated_by ?? '-' }}</td></tr>
                                                            <tr><th class="info-label">@lang('messages.Confirmé par')</th><td class="info-value">{{ $reception->confirmed_by ?? '-' }}</td></tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr><th class="info-label" width="45%">@lang('messages.Approuvé par')</th><td class="info-value">{{ $reception->approuved_by ?? '-' }}</td></tr>
                                                            <tr><th class="info-label">@lang('messages.Rejeté par')</th><td class="info-value">{{ $reception->rejected_by ?? '-' }}</td></tr>
                                                            <tr><th class="info-label">@lang('messages.Réinitialisé par')</th><td class="info-value">{{ $reception->reseted_by ?? '-' }}</td></tr>
                                                            @if(!empty($reception->rejected_motif))<tr><th class="info-label">@lang('messages.Motif de rejet')</th><td class="info-value">{{ $reception->rejected_motif }}</td></tr>@endif
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="tab-pane fade" id="summary" role="tabpanel">
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
                                                <div class="card-header bg-info text-white"><h5 class="mb-0">@lang('messages.Statistiques')</h5></div>
                                                <div class="card-body">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr><th width="30%">@lang('messages.Nombre d\'articles')</th><td><strong>{{ $receptionDetails->count() }}</strong></td></tr>
                                                            <tr><th>@lang('messages.Différence (Commandé - Reçu)')</th><td>
                                                                @php $diff = ($totalQuantityOrdered ?? 0) - ($totalQuantityReceived ?? 0); @endphp
                                                                @if($diff > 0)
                                                                    <span class="badge badge-warning">{{ number_format($diff, 0, ',', ' ') }} unités manquantes</span>
                                                                @elseif($diff < 0)
                                                                    <span class="badge badge-info">{{ number_format(abs($diff), 0, ',', ' ') }} unités en surplus</span>
                                                                @else
                                                                    <span class="badge badge-success">@lang('messages.Réception complète')</span>
                                                                @endif
                                                            </td></tr>
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
    <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
    <script>
        $(function () {
            if ($('#itemsTable').length) {
                $('#itemsTable').DataTable({
                    responsive: true,
                    order: [[0, 'asc']],
                    pageLength: 25,
                    language: { url: '//cdn.datatables.net/plug-ins/1.10.19/i18n/French.json' }
                });
            }
        });
    </script>
@endsection
