
@extends('backend.layouts.master')

@section('title')
@lang('messages.Les demandes d\'achat') - @lang('messages.admin_panel')
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
        .tab-content {
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
            background-color: #fff;
        }
        .info-row {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }
        .info-value {
            color: #212529;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-encours { background-color: #ffc107; color: #000; }
        .badge-valide { background-color: #17a2b8; color: #fff; }
        .badge-confirme { background-color: #007bff; color: #fff; }
        .badge-approuve { background-color: #28a745; color: #fff; }
        .badge-rejete { background-color: #dc3545; color: #fff; }
    </style>
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">{{ __('messages.purchase_request_details_title', ['no' => $purchase->purchase_no ?? '']) }}</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.raw-material-purchases.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Les demandes d\'achat')</span></li>
                </ul>
            </div>
        </div>
        <div class="col-sm-6 clearfix">
            @include('backend.layouts.partials.logout')
        </div>
    </div>
</div>

<div class="main-content-inner">
    <div class="row">
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    @include('backend.layouts.partials.messages')
                    <div class="mb-3">
                        @include('backend.layouts.partials.update_supplier_modal', [
                            'updateSupplierRoute' => route('admin.raw-material-purchases.update-supplier', $purchase->purchase_no),
                            'currentSupplierId' => $purchase->supplier_id ?? '',
                            'suppliers' => $suppliers ?? collect(),
                            'entityLabel' => __('messages.purchase_request_entity_label'),
                        ])
                    </div>
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs" id="purchaseTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">
                                <i class="fa fa-info-circle"></i> Informations Générales
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="parties-tab" data-toggle="tab" href="#parties" role="tab" aria-controls="parties" aria-selected="false">
                                <i class="fa fa-user-tie"></i> Détails Fournisseur / Employé
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="items-tab" data-toggle="tab" href="#items" role="tab" aria-controls="items" aria-selected="false">
                                <i class="fa fa-list"></i> Articles ({{ $purchaseDetails->count() }})
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="financial-tab" data-toggle="tab" href="#financial" role="tab" aria-controls="financial" aria-selected="false">
                                <i class="fa fa-money"></i> Informations Financières
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="status-tab" data-toggle="tab" href="#status" role="tab" aria-controls="status" aria-selected="false">
                                <i class="fa fa-check-circle"></i> Statut & Approbations
                            </a>
                        </li>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content" id="purchaseTabsContent">
                        <!-- General Information Tab -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Numéro de Demande d\'Achat')</div>
                                        <div class="info-value">{{ $purchase->purchase_no ?? 'N/A' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Signature')</div>
                                        <div class="info-value">{{ $purchase->purchase_signature ?? 'N/A' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Date')</div>
                                        <div class="info-value">{{ $purchase ? \Carbon\Carbon::parse($purchase->date)->format('d/m/Y H:i') : 'N/A' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Fournisseur')</div>
                                        <div class="info-value">{{ $purchase && $purchase->supplier ? $purchase->supplier->supplier_name : 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                            @if($purchase && $purchase->description)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="info-label">@lang('messages.Description')</div>
                                    <div class="info-value" style="white-space: pre-wrap;">{{ $purchase->description }}</div>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Détails Fournisseur / Employé Tab -->
                        <div class="tab-pane fade" id="parties" role="tabpanel" aria-labelledby="parties-tab">
                            @if($purchase && ($purchase->supplier_id || isset($purchase->employe_id)))
                            <div class="row">
                                @if($purchase->supplier_id && $purchase->supplier)
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0"><i class="fa fa-truck"></i> @lang('messages.Fournisseur')</h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered table-sm">
                                                <tbody>
                                                    <tr><th class="info-label" width="40%">@lang('messages.Nom')</th><td>{{ $purchase->supplier->supplier_name ?? 'N/A' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Téléphone')</th><td>{{ $purchase->supplier->telephone ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Email')</th><td>{{ $purchase->supplier->mail ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Adresse')</th><td>{{ $purchase->supplier->supplier_address ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.NIF/TIN')</th><td>{{ $purchase->supplier->supplier_TIN ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Assujetti TVA')</th><td>{{ isset($purchase->supplier->vat_supplier_payer) && $purchase->supplier->vat_supplier_payer == '1' ? __('messages.Oui') : __('messages.Non') }}</td></tr>
                                                    @if(!empty(optional($purchase->supplier)->company))<tr><th class="info-label">@lang('messages.Société')</th><td>{{ $purchase->supplier->company }}</td></tr>@endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if(isset($purchase->employe_id) && $purchase->employe_id && $purchase->employe)
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0"><i class="fa fa-user"></i> @lang('messages.Employé')</h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered table-sm">
                                                <tbody>
                                                    <tr><th class="info-label" width="40%">@lang('messages.Nom')</th><td>{{ $purchase->employe->firstname }} {{ $purchase->employe->lastname }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Matricule')</th><td>{{ $purchase->employe->matricule_no ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Téléphone')</th><td>{{ $purchase->employe->phone_no ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Email')</th><td>{{ $purchase->employe->mail ?? '-' }}</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @else
                            <div class="alert alert-info">@lang('messages.no_supplier_employee_purchase')</div>
                            @endif
                        </div>

                        <!-- Items Details Tab -->
                        <div class="tab-pane fade" id="items" role="tabpanel" aria-labelledby="items-tab">
                            <div class="data-tables">
                                <table id="itemsTable" class="text-center table table-bordered">
                                    <thead class="bg-light text-capitalize">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="15%">@lang('messages.item')</th>
                                            <th width="10%">@lang('messages.code')</th>
                                            <th width="10%">@lang('messages.quantity')</th>
                                            <th width="10%">@lang('messages.unit')</th>
                                            <th width="12%">@lang('messages.PU HTVA')</th>
                                            <th width="12%">@lang('messages.PT HTVA')</th>
                                            <th width="10%">@lang('messages.Taux TVA')</th>
                                            <th width="12%">@lang('messages.TVA')</th>
                                            <th width="12%">@lang('messages.TVAC')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($purchaseDetails as $detail)
                                        <tr>
                                            <td>{{ $loop->index+1 }}</td>
                                            <td>
                                                @if($detail->rawMaterial)
                                                    {{ $detail->rawMaterial->name }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $detail->rawMaterial ? $detail->rawMaterial->code : 'N/A' }}</td>
                                            <td>{{ number_format($detail->quantity, 0, ',', ' ') }}</td>
                                            <td>
                                                @if($detail->rawMaterial && $detail->rawMaterial->rawMaterialMeasurement)
                                                    {{ $detail->rawMaterial->rawMaterialMeasurement->purchase_unit }}
                                                @else
                                                    {{ $detail->unit ?? 'N/A' }}
                                                @endif
                                            </td>
                                            <td>{{ number_format($detail->price ?? 0, 3, ',', ' ') }}</td>
                                            <td>{{ number_format($detail->price_nvat ?? 0, 3, ',', ' ') }}</td>
                                            <td>{{ $detail->vat_rate ?? 0 }}%</td>
                                            <td>{{ number_format($detail->vat ?? 0, 3, ',', ' ') }}</td>
                                            <td>{{ number_format($detail->price_wvat ?? 0, 0, ',', ' ') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-light font-weight-bold">
                                            <td colspan="3" class="text-right">@lang('messages.TOTAL:')</td>
                                            <td>{{ number_format($totalQuantity, 0, ',', ' ') }}</td>
                                            <td colspan="2"></td>
                                            <td>{{ number_format($totalPriceNvat, 3, ',', ' ') }}</td>
                                            <td></td>
                                            <td>{{ number_format($totalVat, 3, ',', ' ') }}</td>
                                            <td>{{ number_format($totalPriceWvat, 0, ',', ' ') }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Financial Information Tab -->
                        <div class="tab-pane fade" id="financial" role="tabpanel" aria-labelledby="financial-tab">
                            <div class="row">
                                <div class="col-md-8 offset-md-2">
                                    <div class="card border-primary mb-3">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0"><i class="fa fa-shopping-cart"></i> @lang('messages.Informations d\'Achat')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Quantité Totale')</div>
                                                <div class="info-value h4">{{ number_format($totalQuantity, 0, ',', ' ') }}</div>
                                            </div>
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Prix Unitaire HTVA Moyen')</div>
                                                <div class="info-value">
                                                    @if($purchaseDetails->count() > 0 && $totalQuantity > 0)
                                                        {{ number_format($totalPriceNvat / $totalQuantity, 3, ',', ' ') }} BIF
                                                    @else
                                                        0 BIF
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Montant Total HTVA')</div>
                                                <div class="info-value h4 text-primary">{{ number_format($totalPriceNvat, 3, ',', ' ') }} BIF</div>
                                            </div>
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.TVA Totale')</div>
                                                <div class="info-value h4">{{ number_format($totalVat, 3, ',', ' ') }} BIF</div>
                                            </div>
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Montant Total TVAC')</div>
                                                <div class="info-value h4 text-success">{{ number_format($totalPriceWvat, 0, ',', ' ') }} BIF</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0"><i class="fa fa-chart-line"></i> @lang('messages.Détails par Article')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>@lang('messages.Article')</th>
                                                            <th>@lang('messages.Quantité')</th>
                                                            <th>@lang('messages.PU HTVA')</th>
                                                            <th>@lang('messages.PT HTVA')</th>
                                                            <th>@lang('messages.Taux TVA')</th>
                                                            <th>@lang('messages.TVA')</th>
                                                            <th>@lang('messages.TVAC')</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($purchaseDetails as $detail)
                                                        <tr>
                                                            <td>{{ $detail->rawMaterial ? $detail->rawMaterial->name : 'N/A' }}</td>
                                                            <td>{{ number_format($detail->quantity, 0, ',', ' ') }}</td>
                                                            <td>{{ number_format($detail->price ?? 0, 3, ',', ' ') }} BIF</td>
                                                            <td>{{ number_format($detail->price_nvat ?? 0, 3, ',', ' ') }} BIF</td>
                                                            <td>{{ $detail->vat_rate ?? 0 }}%</td>
                                                            <td>{{ number_format($detail->vat ?? 0, 3, ',', ' ') }} BIF</td>
                                                            <td>{{ number_format($detail->price_wvat ?? 0, 0, ',', ' ') }} BIF</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="bg-light font-weight-bold">
                                                            <td class="text-right">@lang('messages.TOTAL:')</td>
                                                            <td>{{ number_format($totalQuantity, 0, ',', ' ') }}</td>
                                                            <td></td>
                                                            <td>{{ number_format($totalPriceNvat, 3, ',', ' ') }} BIF</td>
                                                            <td></td>
                                                            <td>{{ number_format($totalVat, 3, ',', ' ') }} BIF</td>
                                                            <td>{{ number_format($totalPriceWvat, 0, ',', ' ') }} BIF</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status & Approvals Tab -->
                        <div class="tab-pane fade" id="status" role="tabpanel" aria-labelledby="status-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-warning mb-3">
                                        <div class="card-header bg-warning">
                                            <h5 class="mb-0"><i class="fa fa-info-circle"></i> @lang('messages.Statut Actuel')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Statut')</div>
                                                <div class="info-value">
                                                    @if($purchase)
                                                        @if($purchase->status == 1)
                                                            <span class="status-badge badge-encours">@lang('messages.En Cours')</span>
                                                        @elseif($purchase->status == 2)
                                                            <span class="status-badge badge-valide">@lang('messages.Validé')</span>
                                                        @elseif($purchase->status == 3)
                                                            <span class="status-badge badge-confirme">@lang('messages.Confirmé')</span>
                                                        @elseif($purchase->status == 4)
                                                            <span class="status-badge badge-approuve">@lang('messages.Approuvé')</span>
                                                        @elseif($purchase->status == -1)
                                                            <span class="status-badge badge-rejete">@lang('messages.Rejeté')</span>
                                                        @else
                                                            <span class="status-badge">{{ $purchase->status }}</span>
                                                        @endif
                                                    @else
                                                        N/A
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-secondary mb-3">
                                        <div class="card-header bg-secondary text-white">
                                            <h5 class="mb-0"><i class="fa fa-users"></i> @lang('messages.Approbations (Workflow)')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Auteur (Créé par)')</div>
                                                <div class="info-value">{{ $purchase->created_by ?? 'N/A' }}</div>
                                            </div>
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Créé le')</div>
                                                <div class="info-value">{{ $purchase ? \Carbon\Carbon::parse($purchase->created_at)->format('d/m/Y H:i:s') : 'N/A' }}</div>
                                            </div>
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Modifié par')</div>
                                                <div class="info-value">{{ $purchase->updated_by ?? 'N/A' }}</div>
                                            </div>
                                            @if($purchase && $purchase->updated_at)
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Modifié le')</div>
                                                <div class="info-value">{{ \Carbon\Carbon::parse($purchase->updated_at)->format('d/m/Y H:i:s') }}</div>
                                            </div>
                                            @endif
                                            @if($purchase && $purchase->validated_by)
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Validé par')</div>
                                                <div class="info-value">{{ $purchase->validated_by }}</div>
                                            </div>
                                            @endif
                                            @if($purchase && $purchase->confirmed_by)
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Confirmé par')</div>
                                                <div class="info-value">{{ $purchase->confirmed_by }}</div>
                                            </div>
                                            @endif
                                            @if($purchase && $purchase->approuved_by)
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Approuvé par')</div>
                                                <div class="info-value">{{ $purchase->approuved_by }}</div>
                                            </div>
                                            @endif
                                            @if($purchase && $purchase->rejected_by)
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Rejeté par')</div>
                                                <div class="info-value text-danger">{{ $purchase->rejected_by }}</div>
                                            </div>
                                            @endif
                                            @if($purchase && $purchase->reseted_by)
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Annulé par')</div>
                                                <div class="info-value">{{ $purchase->reseted_by }}</div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if($purchase && $purchase->rejected_motif)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card border-danger">
                                        <div class="card-header bg-danger text-white">
                                            <h5 class="mb-0"><i class="fa fa-exclamation-triangle"></i> @lang('messages.Motif de Rejet')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="info-value" style="white-space: pre-wrap;">{{ $purchase->rejected_motif }}</div>
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
                pageLength: 10
            });
        }

        // Bootstrap tabs initialization
        $(document).ready(function() {
            // Initialize tabs
            $('#purchaseTabs a').on('click', function (e) {
                e.preventDefault();
                $(this).tab('show');
            });
            
            // Show first tab by default
            $('#purchaseTabs a:first').tab('show');
        });

     </script>
@endsection