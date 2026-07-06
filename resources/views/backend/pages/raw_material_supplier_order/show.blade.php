
@extends('backend.layouts.master')

@section('title')
@lang('messages.details des commandes') - @lang('messages.admin_panel')
@endsection

@section('styles')
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
                            <h4 class="header-title">Details sur bon de commande No : {{ $order->order_no ?? 'N/A' }}</h4>
                            @if(isset($order->status))
                                <span class="badge badge-secondary badge-lg mt-2">
                                    <i class="fa fa-info-circle"></i> Statut : {{ $order->status }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('admin.raw-material-supplier-orders.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Retour à la liste
                            </a>
                        </div>
                    </div>

                    @include('backend.layouts.partials.messages')
                    <div class="mb-3">
                        @include('backend.layouts.partials.update_supplier_modal', [
                            'updateSupplierRoute' => route('admin.raw-material-supplier-orders.update-supplier', $order->order_no),
                            'currentSupplierId' => $order->supplier_id ?? '',
                            'suppliers' => $suppliers ?? collect(),
                            'entityLabel' => 'ce bon de commande fournisseur',
                        ])
                    </div>
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="orderTabs" role="tablist">
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
                    <div class="tab-content" id="orderTabContent">
                        <!-- Tab 1: Informations Générales -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card mb-3">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0">@lang('messages.Informations de la Commande')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr>
                                                                <th class="info-label" width="40%">@lang('messages.Numéro de commande:')</th>
                                                                <td class="info-value">{{ $order->order_no ?? 'N/A' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Bon d\'achat (Purchase No):')</th>
                                                                <td class="info-value">{{ $orderDetails->first()->purchase_no ?? 'N/A' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.date'):</th>
                                                                <td class="info-value">{{ $order->date ? \Carbon\Carbon::parse($order->date)->format('d/m/Y H:i') : 'N/A' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.supplier'):</th>
                                                                <td class="info-value">
                                                                    @if($order->supplier_id && $order->supplier)
                                                                        {{ $order->supplier->supplier_name ?? $order->supplier->name ?? 'N/A' }}
                                                                    @else
                                                                        N/A
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.created_by'):</th>
                                                                <td class="info-value">{{ $order->created_by ?? 'N/A' }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr>
                                                                <th class="info-label" width="40%">@lang('messages.Statut:')</th>
                                                                <td class="info-value">{{ $order->status ?? 'N/A' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Signature commande:')</th>
                                                                <td class="info-value">{{ $order->order_signature ?? 'N/A' }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            @if($order->description ?? null)
                                            <div class="row mt-3">
                                                <div class="col-md-12">
                                                    <div class="card">
                                                        <div class="card-header bg-light">
                                                            <h6 class="mb-0">@lang('messages.description')</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <p class="mb-0">{{ $order->description }}</p>
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
                            @if($order && ($order->supplier_id || (isset($order->employe_id) && $order->employe_id)))
                            <div class="row">
                                @if($order->supplier_id && $order->supplier)
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0"><i class="fa fa-truck"></i> @lang('messages.Fournisseur')</h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered table-sm">
                                                <tbody>
                                                    <tr><th class="info-label" width="40%">@lang('messages.Nom')</th><td>{{ $order->supplier->supplier_name ?? $order->supplier->name ?? 'N/A' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Téléphone')</th><td>{{ $order->supplier->telephone ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Email')</th><td>{{ $order->supplier->mail ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Adresse')</th><td>{{ $order->supplier->supplier_address ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.NIF/TIN')</th><td>{{ $order->supplier->supplier_TIN ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Assujetti TVA')</th><td>{{ isset($order->supplier->vat_supplier_payer) && $order->supplier->vat_supplier_payer == '1' ? 'Oui' : 'Non' }}</td></tr>
                                                    @if(!empty(optional($order->supplier)->company))<tr><th class="info-label">@lang('messages.Société')</th><td>{{ $order->supplier->company }}</td></tr>@endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if(isset($order->employe_id) && $order->employe_id && $order->employe)
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0"><i class="fa fa-user"></i> @lang('messages.Employé')</h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered table-sm">
                                                <tbody>
                                                    <tr><th class="info-label" width="40%">@lang('messages.Nom')</th><td>{{ $order->employe->firstname }} {{ $order->employe->lastname }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Matricule')</th><td>{{ $order->employe->matricule_no ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Téléphone')</th><td>{{ $order->employe->phone_no ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Email')</th><td>{{ $order->employe->mail ?? '-' }}</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @else
                            <div class="alert alert-info">@lang('messages.Aucun fournisseur ou employé associé à cette commande.')</div>
                            @endif
                        </div>

                        <!-- Tab 2: Détails des Articles -->
                        <div class="tab-pane fade" id="items" role="tabpanel" aria-labelledby="items-tab">
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0">@lang('messages.Détails des Articles Commandés')</h5>
                                        </div>
                                        <div class="card-body">
                    <div class="data-tables">
                                                <table id="dataTable" class="text-center table table-striped table-bordered">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.date')</th>
                                    <th width="10%">@lang('messages.Order No')</th>
                                    <th width="10%">@lang('messages.Purchase R. No')</th>
                                                            <th width="12%">@lang('messages.item')</th>
                                                            <th width="8%">@lang('messages.quantity')</th>
                                                            <th width="8%">@lang('messages.unit')</th>
                                                            <th width="8%">@lang('messages.PU HTVA')</th>
                                                            <th width="8%">@lang('messages.PT HTVA')</th>
                                                            <th width="6%">@lang('messages.Taux TVA')</th>
                                                            <th width="8%">@lang('messages.TVA')</th>
                                                            <th width="8%">@lang('messages.TVAC')</th>
                                </tr>
                            </thead>
                            <tbody>
                                                        @foreach ($orderDetails as $detail)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                                            <td>{{ $detail->date ? \Carbon\Carbon::parse($detail->date)->format('d/m/Y') : '-' }}</td>
                                                            <td>{{ $detail->order_no }}</td>
                                                            <td>{{ $detail->purchase_no }}</td>
                                                            <td>{{ $detail->rawMaterial->name ?? 'N/A' }}</td>
                                                            <td>{{ $detail->quantity }}</td>
                                                            <td>{{ $detail->rawMaterial->rawMaterialMeasurement->purchase_unit ?? 'N/A' }}</td>
                                                            <td>{{ number_format($detail->purchase_price, 3, ',', ' ') }}</td>
                                                            <td>{{ number_format($detail->price_nvat, 3, ',', ' ') }}</td>
                                                            <td>{{ $detail->vat_rate }}%</td>
                                                            <td>{{ number_format($detail->vat, 3, ',', ' ') }}</td>
                                                            <td>{{ number_format($detail->price_wvat, 0, ',', ' ') }}</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="bg-light">
                                                            <th colspan="5" class="text-right">@lang('messages.TOTAL:')</th>
                                                            <th>{{ number_format($totalQuantity ?? 0, 3, ',', ' ') }}</th>
                                                            <th colspan="2"></th>
                                                            <th>{{ number_format($totalPriceNvat ?? 0, 3, ',', ' ') }}</th>
                                                            <th></th>
                                                            <th>{{ number_format($totalVat ?? 0, 3, ',', ' ') }}</th>
                                                            <th>{{ number_format($totalPriceWvat ?? 0, 0, ',', ' ') }}</th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>

                                            {{-- Description affichée en bas --}}
                                            @php
                                                $hasDescription = !empty($order->description);
                                                foreach ($orderDetails as $d) { if (!empty($d->description)) { $hasDescription = true; break; } }
                                            @endphp
                                            @if($hasDescription)
                                            <div class="card mt-4">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0">@lang('messages.description')</h6>
                                                </div>
                                                <div class="card-body">
                                                    @if(!empty($order->description))
                                                        <p class="mb-2"><strong>@lang('messages.Commande :')</strong> {{ $order->description }}</p>
                                                    @endif
                                                    @foreach ($orderDetails as $detail)
                                                        @if(!empty($detail->description))
                                                            <p class="mb-1"><strong>{{ $detail->rawMaterial->name ?? 'Ligne ' . $loop->iteration }} :</strong> {{ $detail->description }}</p>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Approbations -->
                        <div class="tab-pane fade" id="approbations" role="tabpanel" aria-labelledby="approbations-tab">
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card mb-4">
                                        <div class="card-header bg-secondary text-white">
                                            <h5 class="mb-0"><i class="fa fa-check-circle"></i> @lang('messages.Approbations de la commande')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr>
                                                                <th class="info-label" width="45%">@lang('messages.Auteur (Créé par):')</th>
                                                                <td class="info-value">{{ $order->created_by ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Modifié par:')</th>
                                                                <td class="info-value">{{ $order->updated_by ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Validé par:')</th>
                                                                <td class="info-value">{{ $order->validated_by ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Confirmé par:')</th>
                                                                <td class="info-value">{{ $order->confirmed_by ?? '-' }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr>
                                                                <th class="info-label" width="45%">@lang('messages.Approuvé par:')</th>
                                                                <td class="info-value">{{ $order->approuved_by ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Rejeté par:')</th>
                                                                <td class="info-value">{{ $order->rejected_by ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Réinitialisé par:')</th>
                                                                <td class="info-value">{{ $order->reseted_by ?? '-' }}</td>
                                                            </tr>
                                                            @if(!empty($order->rejected_motif))
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Motif de rejet:')</th>
                                                                <td class="info-value">{{ $order->rejected_motif }}</td>
                                                            </tr>
                                                            @endif
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">@lang('messages.Auteur par ligne (détails)')</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm table-bordered">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th width="5%">#</th>
                                                        <th width="35%">@lang('messages.item')</th>
                                                        <th>@lang('messages.Auteur (créé par)')</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($orderDetails as $detail)
                                                    <tr>
                                                        <td>{{ $loop->index+1 }}</td>
                                                        <td>{{ $detail->rawMaterial->name ?? 'N/A' }}</td>
                                                        <td>{{ $detail->created_by ?? '-' }}</td>
                                </tr>
                               @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
                        </div>

                        <!-- Tab 4: Résumé -->
                        <div class="tab-pane fade" id="summary" role="tabpanel" aria-labelledby="summary-tab">
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="card text-center mb-3">
                                                <div class="card-body">
                                                    <h5 class="card-title text-primary">@lang('messages.Quantité totale')</h5>
                                                    <h2 class="text-primary">{{ number_format($totalQuantity ?? 0, 3, ',', ' ') }}</h2>
                                                    <p class="text-muted">@lang('messages.Unités')</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card text-center mb-3">
                                                <div class="card-body">
                                                    <h5 class="card-title text-info">@lang('messages.Total HTVA')</h5>
                                                    <h2 class="text-info">{{ number_format($totalPriceNvat ?? 0, 0, ',', ' ') }}</h2>
                                                    <p class="text-muted">@lang('messages.Prix total hors taxes')</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card text-center mb-3">
                                                <div class="card-body">
                                                    <h5 class="card-title text-warning">@lang('messages.TVA')</h5>
                                                    <h2 class="text-warning">{{ number_format($totalVat ?? 0, 0, ',', ' ') }}</h2>
                                                    <p class="text-muted">@lang('messages.Montant TVA')</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card text-center mb-3">
                                                <div class="card-body">
                                                    <h5 class="card-title text-success">@lang('messages.Total TTC')</h5>
                                                    <h2 class="text-success">{{ number_format($totalPriceWvat ?? 0, 0, ',', ' ') }}</h2>
                                                    <p class="text-muted">@lang('messages.Prix total TTC')</p>
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
                                                                <th width="30%">@lang('messages.Nombre d\'articles (lignes):')</th>
                                                                <td><strong>{{ count($orderDetails) }}</strong></td>
                                                            </tr>
                                                            <tr>
                                                                <th>@lang('messages.Numéro de commande:')</th>
                                                                <td><strong>{{ $order->order_no ?? 'N/A' }}</strong></td>
                                                            </tr>
                                                            <tr>
                                                                <th>@lang('messages.Fournisseur:')</th>
                                                                <td>
                                                                    @if($order->supplier_id && $order->supplier)
                                                                        {{ $order->supplier->supplier_name ?? $order->supplier->name ?? 'N/A' }}
                                                                    @else
                                                                        N/A
                                                                    @endif
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
     <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
     <script>
        if ($('#dataTable').length) {
            $('#dataTable').DataTable({
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
