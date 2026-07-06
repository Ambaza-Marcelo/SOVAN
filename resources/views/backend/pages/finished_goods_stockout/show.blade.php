@extends('backend.layouts.master')

@section('title')
@lang('messages.Details des sorties') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <!-- Start datatable css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
    <style>
        .nav-tabs .nav-link { color: #495057; border: 1px solid transparent; border-top-left-radius: 0.25rem; border-top-right-radius: 0.25rem; }
        .nav-tabs .nav-link:hover { border-color: #e9ecef #e9ecef #dee2e6; }
        .nav-tabs .nav-link.active { color: #495057; background-color: #fff; border-color: #dee2e6 #dee2e6 #fff; }
        .tab-content { padding: 20px; border: 1px solid #dee2e6; border-top: none; background-color: #fff; }
        .info-row { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e9ecef; }
        .info-label { font-weight: 600; color: #495057; margin-bottom: 5px; }
        .info-value { color: #212529; }
        .status-badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; }
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
                <h4 class="page-title pull-left">@lang('messages.Details des sorties sur bon de sortie No') : {{ $stockout->stockout_no ?? '' }}</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.finished-goods-stockouts.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Details des sorties')</span></li>
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

                    <ul class="nav nav-tabs" id="stockoutTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab">
                                <i class="fa fa-info-circle"></i> Informations Générales
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="stock-tab" data-toggle="tab" href="#stock" role="tab">
                                <i class="fa fa-warehouse"></i> Stock
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="items-tab" data-toggle="tab" href="#items" role="tab">
                                <i class="fa fa-list"></i> Articles ({{ $stockoutDetails->count() }})
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="financial-tab" data-toggle="tab" href="#financial" role="tab">
                                <i class="fa fa-money"></i> Informations Financières
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="status-tab" data-toggle="tab" href="#status" role="tab">
                                <i class="fa fa-check-circle"></i> Statut & Approbations
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="stockoutTabsContent">
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            @php
                                $fgStoreType = $stockout->store_type ?: 'MAIN';
                                $fgStore = null;
                                if ($fgStoreType === 'SM') {
                                    $fgStore = $stockout->finishedGoodsSmStore ?? null;
                                } else {
                                    $fgStore = $stockout->finishedGoodsStore ?? null;
                                }
                            @endphp
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Numéro de Bon de Sortie')</div>
                                        <div class="info-value">{{ $stockout->stockout_no ?? 'N/A' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Signature')</div>
                                        <div class="info-value">{{ $stockout->stockout_signature ?? 'N/A' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Date')</div>
                                        <div class="info-value">{{ $stockout ? \Carbon\Carbon::parse($stockout->date)->format('d/m/Y H:i') : 'N/A' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Type de Sortie')</div>
                                        <div class="info-value">
                                            @if($stockout->item_movement_type == 'SN') Sortie Normale
                                            @elseif($stockout->item_movement_type == 'SP') Sortie Perte
                                            @elseif($stockout->item_movement_type == 'SV') Sortie Vol
                                            @elseif($stockout->item_movement_type == 'SC') Sortie Casse
                                            @elseif($stockout->item_movement_type == 'SAJ') Sortie Ajustement
                                            @elseif($stockout->item_movement_type == 'SAU') Sortie Autre
                                            @else {{ $stockout->item_movement_type }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Demandeur')</div>
                                        <div class="info-value">{{ $stockout->asker ?? 'N/A' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Destination')</div>
                                        <div class="info-value">{{ $stockout->destination ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Magasin d\'Origine')</div>
                                        <div class="info-value">
                                            @if($fgStore)
                                                {{ $fgStore->code }} - {{ $fgStore->name }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Devise')</div>
                                        <div class="info-value">{{ $stockout->item_purchase_or_sale_currency ?? 'N/A' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Créé par')</div>
                                        <div class="info-value">{{ $stockout->created_by ?? 'N/A' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Créé le')</div>
                                        <div class="info-value">{{ $stockout ? \Carbon\Carbon::parse($stockout->created_at)->format('d/m/Y H:i:s') : 'N/A' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Modifié par')</div>
                                        <div class="info-value">{{ $stockout->updated_by ?? 'N/A' }}</div>
                                    </div>
                                    @if($stockout && $stockout->updated_at)
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Modifié le')</div>
                                        <div class="info-value">{{ \Carbon\Carbon::parse($stockout->updated_at)->format('d/m/Y H:i:s') }}</div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @if($stockout && $stockout->description)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="info-label">@lang('messages.Description')</div>
                                    <div class="info-value" style="white-space: pre-wrap;">{{ $stockout->description }}</div>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="tab-pane fade" id="stock" role="tabpanel" aria-labelledby="stock-tab">
                            @php
                                $fgStoreType = $stockout->store_type ?: 'MAIN';
                                $fgStore = null;
                                if ($fgStoreType === 'SM') {
                                    $fgStore = $stockout->finishedGoodsSmStore ?? null;
                                } else {
                                    $fgStore = $stockout->finishedGoodsStore ?? null;
                                }
                            @endphp
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Stock Type:')</label>
                                        <div class="info-value">
                                            @if($fgStoreType === 'SM')
                                                <span class="badge badge-info badge-status">@lang('messages.Finished Goods SM')</span>
                                            @else
                                                <span class="badge badge-primary badge-status">@lang('messages.Finished Goods (Main)')</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Store (Origine):')</label>
                                        <div class="info-value">
                                            @if($fgStore)
                                                <strong>{{ $fgStore->code }}</strong> — {{ $fgStore->name }}
                                            @else
                                                <span class="text-muted">@lang('messages.N/A')</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="items" role="tabpanel" aria-labelledby="items-tab">
                            <div class="data-tables">
                                <table id="itemsTable" class="text-center table table-bordered">
                                    <thead class="bg-light text-capitalize">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="20%">@lang('messages.item')</th>
                                            <th width="15%">@lang('messages.quantity')</th>
                                            <th width="15%">@lang('messages.Prix de Coût')</th>
                                            <th width="15%">@lang('messages.Valeur Totale')</th>
                                            <th width="15%">@lang('messages.Type Sortie')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($stockoutDetails as $detail)
                                        <tr>
                                            <td>{{ $loop->index+1 }}</td>
                                            <td>
                                                @if($detail->item)
                                                    {{ $detail->item->name }}
                                                    @if($detail->item->code)
                                                        <br><small class="text-muted">Code: {{ $detail->item->code }}</small>
                                                    @endif
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ number_format($detail->quantity, 0, ',', ' ') }}</td>
                                            <td>{{ number_format($detail->cost_price, 0, ',', ' ') }}</td>
                                            <td>{{ number_format($detail->total_cost_value, 0, ',', ' ') }}</td>
                                            <td>
                                                @if($detail->item_movement_type == 'SN') Sortie Normale
                                                @elseif($detail->item_movement_type == 'SP') Sortie Perte
                                                @elseif($detail->item_movement_type == 'SV') Sortie Vol
                                                @elseif($detail->item_movement_type == 'SC') Sortie Casse
                                                @elseif($detail->item_movement_type == 'SAJ') Sortie Ajustement
                                                @elseif($detail->item_movement_type == 'SAU') Sortie Autre
                                                @else {{ $detail->item_movement_type }}
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-light font-weight-bold">
                                            <td colspan="2" class="text-right">@lang('messages.TOTAL:')</td>
                                            <td>{{ number_format($totalQuantity, 0, ',', ' ') }}</td>
                                            <td colspan="2"></td>
                                            <td>{{ number_format($totalCostValue, 0, ',', ' ') }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="financial" role="tabpanel" aria-labelledby="financial-tab">
                            <div class="row">
                                <div class="col-md-8 offset-md-2">
                                    <div class="card border-primary mb-3">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0"><i class="fa fa-shopping-cart"></i> @lang('messages.Informations Financières')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Quantité Totale')</div>
                                                <div class="info-value h4">{{ number_format($totalQuantity, 0, ',', ' ') }}</div>
                                            </div>
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Prix de Coût Moyen')</div>
                                                <div class="info-value">
                                                    @if($stockoutDetails->count() > 0 && $totalQuantity > 0)
                                                        {{ number_format($totalCostValue / $totalQuantity, 2, ',', ' ') }} BIF
                                                    @else
                                                        0 BIF
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Valeur Totale')</div>
                                                <div class="info-value h4 text-primary">{{ number_format($totalCostValue, 0, ',', ' ') }} BIF</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

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
                                                    @if($stockout->status == 1)
                                                        <span class="status-badge badge-encours">@lang('messages.En Cours')</span>
                                                    @elseif($stockout->status == 2)
                                                        <span class="status-badge badge-valide">@lang('messages.Validé')</span>
                                                    @elseif($stockout->status == 3)
                                                        <span class="status-badge badge-confirme">@lang('messages.Confirmé')</span>
                                                    @elseif($stockout->status == 4)
                                                        <span class="status-badge badge-approuve">@lang('messages.Approuvé')</span>
                                                    @elseif($stockout->status == -1)
                                                        <span class="status-badge badge-rejete">@lang('messages.Rejeté')</span>
                                                    @else
                                                        <span class="status-badge">{{ $stockout->status }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-secondary mb-3">
                                        <div class="card-header bg-secondary text-white">
                                            <h5 class="mb-0"><i class="fa fa-users"></i> @lang('messages.Workflow')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Créé par')</div>
                                                <div class="info-value">{{ $stockout->created_by ?? 'N/A' }}</div>
                                            </div>
                                            @if($stockout && $stockout->validated_by)
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Validé par')</div>
                                                <div class="info-value">{{ $stockout->validated_by }}</div>
                                            </div>
                                            @endif
                                            @if($stockout && $stockout->confirmed_by)
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Confirmé par')</div>
                                                <div class="info-value">{{ $stockout->confirmed_by }}</div>
                                            </div>
                                            @endif
                                            @if($stockout && $stockout->approuved_by)
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Approuvé par')</div>
                                                <div class="info-value">{{ $stockout->approuved_by }}</div>
                                            </div>
                                            @endif
                                            @if($stockout && $stockout->rejected_by)
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Rejeté par')</div>
                                                <div class="info-value text-danger">{{ $stockout->rejected_by }}</div>
                                            </div>
                                            @endif
                                            @if($stockout && $stockout->reseted_by)
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Annulé par')</div>
                                                <div class="info-value">{{ $stockout->reseted_by }}</div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if($stockout && $stockout->rejected_motif)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card border-danger">
                                        <div class="card-header bg-danger text-white">
                                            <h5 class="mb-0"><i class="fa fa-exclamation-triangle"></i> @lang('messages.Motif de Rejet')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="info-value" style="white-space: pre-wrap;">{{ $stockout->rejected_motif }}</div>
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
    <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
    <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
    <script>
        if ($('#itemsTable').length) {
            $('#itemsTable').DataTable({
                responsive: true,
                order: [[0, 'asc']],
                pageLength: 10
            });
        }

        $(document).ready(function() {
            $('#stockoutTabs a').on('click', function (e) {
                e.preventDefault();
                $(this).tab('show');
            });
            $('#stockoutTabs a:first').tab('show');
        });
    </script>
@endsection

