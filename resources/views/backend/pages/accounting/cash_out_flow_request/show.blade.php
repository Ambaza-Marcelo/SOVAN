
@extends('backend.layouts.master')

@section('title')
@lang('messages.Demande de sortie de fonds') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <!-- Start datatable css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
    <style>
        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }
        .info-value {
            color: #212529;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 3px solid #007bff;
        }
        .nav-tabs .nav-link.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .tab-content {
            border: 1px solid #dee2e6;
            border-top: none;
            padding: 20px;
            background-color: white;
        }
    </style>
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Demande de sortie de fonds')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.cash-out-flow-request.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.View Details')</span></li>
                </ul>
            </div>
        </div>
        <div class="col-sm-6 clearfix">
            @include('backend.layouts.partials.logout')
        </div>
    </div>
</div>
<!-- page title area end -->

<div class="main-content-inner">
    <div class="row">
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    @php
                        $firstDetail = $cash_out_flow_requests->first();
                        $request = $firstDetail ? \App\Models\Accounting\CashOutFlowRequest::where('cash_outflow_request_no', $code)->first() : null;
                    @endphp
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="header-title">Details sur Demande de sortie de fonds No : {{$code}}</h4>
                            @if($request)
                                @if($request->status == 2)
                                    <span class="badge badge-success">@lang('messages.Validated')</span>
                                @elseif($request->status == 1 || $request->status == 0)
                                    <span class="badge badge-secondary">@lang('messages.Draft')</span>
                                @elseif($request->status == 3)
                                    <span class="badge badge-info">@lang('messages.Confirmed')</span>
                                @elseif($request->status == 4)
                                    <span class="badge badge-success">@lang('messages.Approved')</span>
                                @elseif($request->status == -1)
                                    <span class="badge badge-danger">@lang('messages.Rejected')</span>
                                @else
                                    <span class="badge badge-primary">@lang('messages.In Progress...')</span>
                                @endif
                            @endif
                        </div>
                        <div>
                            @include('backend.layouts.partials.update_supplier_modal', [
                                'updateSupplierRoute' => route('admin.cash-out-flow-request.update-supplier', $code),
                                'currentSupplierId' => $request->supplier_id ?? '',
                                'suppliers' => $suppliers ?? collect(),
                                'entityLabel' => 'cette demande de sortie de fonds',
                            ])
                            <a href="{{ route('admin.cash-out-flow-request.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Retour à la liste
                            </a>
                        </div>
                    </div>

                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="cashOutFlowRequestTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab">
                                <i class="fa fa-info-circle"></i> General Information
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="parties-tab" data-toggle="tab" href="#parties" role="tab">
                                <i class="fa fa-user-tie"></i> Détails Fournisseur / Employé
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="articles-tab" data-toggle="tab" href="#articles" role="tab">
                                <i class="fa fa-shopping-cart"></i> Articles
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="approbations-tab" data-toggle="tab" href="#approbations" role="tab">
                                <i class="fa fa-check-circle"></i> Approbations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="summary-tab" data-toggle="tab" href="#summary" role="tab">
                                <i class="fa fa-calculator"></i> Summary
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content">
                        <!-- General Information Tab -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            @if($request)
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Cash Out Flow Request No:')</label>
                                        <div class="info-value">{{ $request->cash_outflow_request_no }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Date:')</label>
                                        <div class="info-value">{{ \Carbon\Carbon::parse($request->date)->format('d/m/Y') }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Cash Out Flow Request Signature:')</label>
                                        <div class="info-value">{{ $request->cash_outflow_request_signature }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Asker:')</label>
                                        <div class="info-value">{{ $request->asker }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Currency:')</label>
                                        <div class="info-value">
                                            @if($request->currency_id && $request->currency)
                                                {{ $request->currency->currency_name }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Purchase No:')</label>
                                        <div class="info-value">{{ $request->purchase_no ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Employee:')</label>
                                        <div class="info-value">
                                            @if($request->employe_id && $request->employe)
                                                {{ $request->employe->firstname }} {{ $request->employe->lastname }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Supplier:')</label>
                                        <div class="info-value">
                                            @if($request->supplier_id && $request->supplier)
                                                {{ $request->supplier->supplier_name ?? $request->supplier->name ?? 'N/A' }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Description:')</label>
                                        <div class="info-value">{{ $request->description }}</div>
                                    </div>
                                </div>
                            </div>
                            @else
                            <div class="alert alert-warning">@lang('messages.No general information available.')</div>
                            @endif
                        </div>

                        <!-- Détails Fournisseur / Employé Tab -->
                        <div class="tab-pane fade" id="parties" role="tabpanel">
                            @if($request && ($request->supplier_id || $request->employe_id))
                            <div class="row">
                                @if($request->supplier_id && $request->supplier)
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0"><i class="fa fa-truck"></i> @lang('messages.Fournisseur')</h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered table-sm">
                                                <tbody>
                                                    <tr><th class="info-label" width="40%">@lang('messages.Nom')</th><td>{{ $request->supplier->name ?? $request->supplier->supplier_name ?? 'N/A' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Téléphone')</th><td>{{ $request->supplier->telephone ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Email')</th><td>{{ $request->supplier->mail ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Adresse')</th><td>{{ $request->supplier->supplier_address ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.NIF/TIN')</th><td>{{ $request->supplier->supplier_TIN ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Assujetti TVA')</th><td>{{ isset($request->supplier->vat_supplier_payer) && $request->supplier->vat_supplier_payer == '1' ? 'Oui' : 'Non' }}</td></tr>
                                                    @if(!empty(optional($request->supplier)->company))<tr><th class="info-label">@lang('messages.Société')</th><td>{{ $request->supplier->company }}</td></tr>@endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if($request->employe_id && $request->employe)
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0"><i class="fa fa-user"></i> @lang('messages.Employé')</h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered table-sm">
                                                <tbody>
                                                    <tr><th class="info-label" width="40%">@lang('messages.Nom')</th><td>{{ $request->employe->firstname }} {{ $request->employe->lastname }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Matricule')</th><td>{{ $request->employe->matricule_no ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Téléphone')</th><td>{{ $request->employe->phone_no ?? '-' }}</td></tr>
                                                    <tr><th class="info-label">@lang('messages.Email')</th><td>{{ $request->employe->mail ?? '-' }}</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @else
                            <div class="alert alert-info">@lang('messages.Aucun fournisseur ou employé associé à cette demande.')</div>
                            @endif
                        </div>

                        <!-- Articles Tab -->
                        <div class="tab-pane fade" id="articles" role="tabpanel">
                            <div class="data-tables">
                                @include('backend.layouts.partials.messages')
                                @php
                                    $totalQuantity = $cash_out_flow_requests->sum('quantity');
                                    $totalCostNvat = $cash_out_flow_requests->sum('cost_nvat');
                                    $totalVat = $cash_out_flow_requests->sum('vat');
                                    $totalCostWvat = $cash_out_flow_requests->sum('cost_wvat');
                                @endphp
                                <table id="articlesTable" class="text-center">
                                    <thead class="bg-light text-capitalize">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="15%">@lang('messages.item')</th>
                                            <th width="10%">@lang('messages.code')</th>
                                            <th width="10%">@lang('messages.quantity')</th>
                                            <th width="10%">@lang('messages.PU HTVA')</th>
                                            <th width="10%">@lang('messages.PT HTVA')</th>
                                            <th width="10%">@lang('messages.Taux TVA')</th>
                                            <th width="10%">@lang('messages.TVA')</th>
                                            <th width="10%">@lang('messages.TVAC')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                       @foreach ($cash_out_flow_requests as $data)
                                       <tr>
                                            <td>{{ $loop->index+1 }}</td>
                                            <td>
                                                @if(!empty($data->loan_id) && $data->loan)
                                                    Avance sur salaire - {{ $data->loan->description }}
                                                @elseif(!empty($data->r_material_id) && $data->rawMaterial)
                                                    {{ $data->rawMaterial->name }}
                                                @elseif(!empty($data->material_id) && $data->material)
                                                    {{ $data->material->name }}
                                                @elseif(!empty($data->item_id) && $data->item)
                                                    {{ $data->item->name }}
                                                @elseif(!empty($data->expense_id) && $data->expense)
                                                    {{ $data->expense->name }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                @if(!empty($data->loan_id) && $data->loan)
                                                    LOAN-{{ $data->loan->id }}
                                                @elseif(!empty($data->r_material_id) && $data->rawMaterial)
                                                    {{ $data->rawMaterial->code }}
                                                @elseif(!empty($data->material_id) && $data->material)
                                                    {{ $data->material->code }}
                                                @elseif(!empty($data->item_id) && $data->item)
                                                    {{ $data->item->code ?? '-' }}
                                                @elseif(!empty($data->expense_id) && $data->expense)
                                                    {{ $data->expense->code }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $data->quantity }}</td>
                                            <td>{{ number_format($data->cost_price,2,',',' ') }}</td>
                                            <td>{{ number_format($data->cost_nvat,2,',',' ') }}</td>
                                            <td>{{ $data->vat_rate }}%</td>
                                            <td>{{ number_format($data->vat,2,',',' ') }}</td>
                                            <td>{{ number_format($data->cost_wvat,0,',',' ') }}</td>
                                        </tr>
                                       @endforeach
                                    </tbody>
                                    <tfoot class="bg-light font-weight-bold">
                                        <tr>
                                            <td colspan="3" class="text-right"><strong>@lang('messages.Total:')</strong></td>
                                            <td><strong>{{ number_format($totalQuantity,2,',',' ') }}</strong></td>
                                            <td></td>
                                            <td><strong>{{ number_format($totalCostNvat,2,',',' ') }}</strong></td>
                                            <td></td>
                                            <td><strong>{{ number_format($totalVat,2,',',' ') }}</strong></td>
                                            <td><strong>{{ number_format($totalCostWvat,0,',',' ') }}</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>

                                @if($request)
                                <div class="mt-3">
                                    <label class="info-label">@lang('messages.Description:')</label>
                                    <div class="info-value">{{ $request->description }}</div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Approbations Tab -->
                        <div class="tab-pane fade" id="approbations" role="tabpanel">
                            @if($request)
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card mb-3">
                                        <div class="card-header bg-secondary text-white">
                                            <h5 class="mb-0"><i class="fa fa-check-circle"></i> @lang('messages.Approbations de la demande')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr>
                                                                <th class="info-label" width="45%">@lang('messages.Statut:')</th>
                                                                <td class="info-value">
                                                                    @if($request->status == 2)
                                                                        <span class="badge badge-success">@lang('messages.Validated')</span>
                                                                    @elseif($request->status == 1 || $request->status == 0)
                                                                        <span class="badge badge-secondary">@lang('messages.Draft')</span>
                                                                    @elseif($request->status == 3)
                                                                        <span class="badge badge-info">@lang('messages.Confirmed')</span>
                                                                    @elseif($request->status == 4)
                                                                        <span class="badge badge-success">@lang('messages.Approved')</span>
                                                                    @elseif($request->status == -1)
                                                                        <span class="badge badge-danger">@lang('messages.Rejected')</span>
                                                                    @else
                                                                        <span class="badge badge-primary">@lang('messages.In Progress...')</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Auteur (Créé par):')</th>
                                                                <td class="info-value">{{ $request->created_by ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Modifié par:')</th>
                                                                <td class="info-value">{{ $request->updated_by ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Validé par:')</th>
                                                                <td class="info-value">{{ $request->validated_by ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Confirmé par:')</th>
                                                                <td class="info-value">{{ $request->confirmed_by ?? '-' }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr>
                                                                <th class="info-label" width="45%">@lang('messages.Approuvé par:')</th>
                                                                <td class="info-value">{{ $request->approuved_by ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Rejeté par:')</th>
                                                                <td class="info-value">{{ $request->rejected_by ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Réinitialisé par:')</th>
                                                                <td class="info-value">{{ $request->reseted_by ?? '-' }}</td>
                                                            </tr>
                                                            @if(!empty($request->rejected_motif))
                                                            <tr>
                                                                <th class="info-label">@lang('messages.Motif de rejet:')</th>
                                                                <td class="info-value">{{ $request->rejected_motif }}</td>
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
                            @else
                            <div class="alert alert-warning">@lang('messages.Aucune information d\'approbation disponible.')</div>
                            @endif
                        </div>

                        <!-- Summary Tab -->
                        <div class="tab-pane fade" id="summary" role="tabpanel">
                            @php
                                $totalQuantity = $cash_out_flow_requests->sum('quantity');
                                $totalCostNvat = $cash_out_flow_requests->sum('cost_nvat');
                                $totalVat = $cash_out_flow_requests->sum('vat');
                                $totalCostWvat = $cash_out_flow_requests->sum('cost_wvat');
                            @endphp
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Total Quantity:')</label>
                                        <div class="info-value">{{ number_format($totalQuantity,2,',',' ') }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Total Cost (HTVA):')</label>
                                        <div class="info-value">{{ number_format($totalCostNvat,2,',',' ') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Total VAT:')</label>
                                        <div class="info-value">{{ number_format($totalVat,2,',',' ') }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Total Cost (TVAC):')</label>
                                        <div class="info-value" style="border-left: 3px solid #28a745; font-size: 1.2em; font-weight: bold;">{{ number_format($totalCostWvat,0,',',' ') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Number of Items:')</label>
                                        <div class="info-value">{{ $cash_out_flow_requests->count() }}</div>
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
        if ($('#articlesTable').length) {
            $('#articlesTable').DataTable({
                responsive: true,
                paging: false,
                searching: false,
                info: false
            });
        }

     </script>
@endsection
