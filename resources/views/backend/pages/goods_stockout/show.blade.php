@extends('backend.layouts.master')

@section('title')
@lang('messages.Détail sortie marchandises') - @lang('messages.admin_panel')
@endsection

@section('styles')
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
                <h4 class="page-title pull-left">@lang('messages.Détail sortie marchandises')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.goods-stockouts.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Détail')</span></li>
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

                    @if (!isset($stockout))
                        <p class="text-muted">@lang('messages.Aucune sortie sélectionnée.')</p>
                    @else
                        <div class="d-flex justify-content-between mb-3">
                            <div>
                                <p><strong>@lang('messages.N° sortie :')</strong> {{ $stockout->stockout_no ?? '-' }}</p>
                                <p><strong>@lang('messages.Date :')</strong> {{ $stockout->date ? \Carbon\Carbon::parse($stockout->date)->format('d/m/Y H:i') : 'N/A' }}</p>
                            </div>
                            <div class="text-right">
                                @if (Auth::guard('admin')->user()->can('goods_stockout.view'))
                                    <a class="btn btn-secondary text-white mb-1" href="{{ route('admin.goods-stockouts.print', $stockout->stockout_no) }}" target="_blank" title="@lang('messages.Imprimer le document')">
                                        <i class="fa fa-print"></i> @lang('messages.Imprimer')
                                    </a>
                                @endif
                                @if (Auth::guard('admin')->user()->can('goods_stockout.edit') && $stockout->status != 4)
                                    <a class="btn btn-success text-white mb-1" href="{{ route('admin.goods-stockouts.edit', $stockout->stockout_no) }}">@lang('messages.edit')</a>
                                @endif
                            </div>
                        </div>

                        <!-- Onglets de détail -->
                        <ul class="nav nav-tabs" id="stockoutTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" id="general-tab" data-toggle="tab" href="#info-generales" role="tab" aria-controls="info-generales" aria-selected="true">
                                    <i class="fa fa-info-circle"></i> Informations générales
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="items-tab" data-toggle="tab" href="#details-articles" role="tab" aria-controls="details-articles" aria-selected="false">
                                    <i class="fa fa-list"></i> Articles ({{ $stockoutDetails->count() }})
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="financial-tab" data-toggle="tab" href="#financial" role="tab" aria-controls="financial" aria-selected="false">
                                    <i class="fa fa-money"></i> Informations financières
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="status-tab" data-toggle="tab" href="#status" role="tab" aria-controls="status" aria-selected="false">
                                    <i class="fa fa-check-circle"></i> Statut & Approbations
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content mt-3">
                            <!-- Infos générales -->
                            <div class="tab-pane fade show active" id="info-generales" role="tabpanel" aria-labelledby="general-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <div class="info-label">@lang('messages.N° sortie')</div>
                                            <div class="info-value">{{ $stockout->stockout_no ?? 'N/A' }}</div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">@lang('messages.Signature')</div>
                                            <div class="info-value">{{ $stockout->stockout_signature ?? 'N/A' }}</div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">@lang('messages.Date')</div>
                                            <div class="info-value">{{ $stockout->date ? \Carbon\Carbon::parse($stockout->date)->format('d/m/Y H:i') : 'N/A' }}</div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">@lang('messages.Type sortie')</div>
                                            <div class="info-value">{{ $stockout->item_movement_type ?? 'N/A' }}</div>
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
                                            <div class="info-label">@lang('messages.Magasin d\'origine')</div>
                                            <div class="info-value">
                                                @if($stockout->goodsStore)
                                                    {{ $stockout->goodsStore->code }} - {{ $stockout->goodsStore->name }}
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
                                            <div class="info-value">{{ $stockout->created_at ? \Carbon\Carbon::parse($stockout->created_at)->format('d/m/Y H:i:s') : 'N/A' }}</div>
                                        </div>
                                        @if($stockout->updated_by)
                                        <div class="info-row">
                                            <div class="info-label">@lang('messages.Modifié par')</div>
                                            <div class="info-value">{{ $stockout->updated_by }}</div>
                                        </div>
                                        @endif
                                        @if($stockout->updated_at)
                                        <div class="info-row">
                                            <div class="info-label">@lang('messages.Modifié le')</div>
                                            <div class="info-value">{{ \Carbon\Carbon::parse($stockout->updated_at)->format('d/m/Y H:i:s') }}</div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @if(!empty($stockout->description))
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="info-label">@lang('messages.description')</div>
                                            <div class="info-value" style="white-space: pre-wrap;">{{ $stockout->description }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Détails articles -->
                            <div class="tab-pane fade" id="details-articles" role="tabpanel" aria-labelledby="items-tab">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>@lang('messages.item')</th>
                                                <th class="text-right">@lang('messages.quantity')</th>
                                                <th class="text-right">@lang('messages.Prix de Coût')</th>
                                                <th class="text-right">@lang('messages.Total')</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($stockoutDetails ?? [] as $detail)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $detail->item->name ?? 'N/A' }}</td>
                                                    <td class="text-right">{{ number_format($detail->quantity ?? 0, 0, ',', ' ') }}</td>
                                                    <td class="text-right">{{ number_format($detail->cost_price ?? 0, 0, ',', ' ') }}</td>
                                                    <td class="text-right">{{ number_format($detail->total_cost_value ?? 0, 0, ',', ' ') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">@lang('messages.Aucun article pour cette sortie.')</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        @if(isset($totalQuantity) || isset($totalCostValue))
                                        <tfoot>
                                            <tr class="bg-light font-weight-bold">
                                                <th colspan="2" class="text-right">@lang('messages.Totaux')</th>
                                                <th class="text-right">{{ isset($totalQuantity) ? number_format($totalQuantity, 0, ',', ' ') : '' }}</th>
                                                <th></th>
                                                <th class="text-right">{{ isset($totalCostValue) ? number_format($totalCostValue, 0, ',', ' ') : '' }}</th>
                                            </tr>
                                        </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>

                            <!-- Informations financières -->
                            <div class="tab-pane fade" id="financial" role="tabpanel" aria-labelledby="financial-tab">
                                <div class="row">
                                    <div class="col-md-8 offset-md-2">
                                        <div class="card border-primary mb-3">
                                            <div class="card-header bg-primary text-white">
                                                <h5 class="mb-0"><i class="fa fa-shopping-cart"></i> @lang('messages.Informations financières')</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="info-row">
                                                    <div class="info-label">@lang('messages.Quantité totale')</div>
                                                    <div class="info-value h4">{{ number_format($totalQuantity, 0, ',', ' ') }}</div>
                                                </div>
                                                <div class="info-row">
                                                    <div class="info-label">@lang('messages.Prix de coût moyen')</div>
                                                    <div class="info-value">
                                                        @if($stockoutDetails->count() > 0 && $totalQuantity > 0)
                                                            {{ number_format($totalCostValue / $totalQuantity, 2, ',', ' ') }} BIF
                                                        @else
                                                            0 BIF
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="info-row">
                                                    <div class="info-label">@lang('messages.Valeur totale')</div>
                                                    <div class="info-value h4 text-primary">{{ number_format($totalCostValue, 0, ',', ' ') }} BIF</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Statut & approbations -->
                            <div class="tab-pane fade" id="status" role="tabpanel" aria-labelledby="status-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card border-warning mb-3">
                                            <div class="card-header bg-warning">
                                                <h5 class="mb-0"><i class="fa fa-info-circle"></i> @lang('messages.Statut actuel')</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="info-row">
                                                    <div class="info-label">@lang('messages.Statut')</div>
                                                    <div class="info-value">
                                                        @if($stockout->status == 1)
                                                            <span class="status-badge badge-encours">@lang('messages.En cours')</span>
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
                                                @if($stockout->validated_by)
                                                <div class="info-row">
                                                    <div class="info-label">@lang('messages.Validé par')</div>
                                                    <div class="info-value">{{ $stockout->validated_by }}</div>
                                                </div>
                                                @endif
                                                @if($stockout->confirmed_by)
                                                <div class="info-row">
                                                    <div class="info-label">@lang('messages.Confirmé par')</div>
                                                    <div class="info-value">{{ $stockout->confirmed_by }}</div>
                                                </div>
                                                @endif
                                                @if($stockout->approuved_by)
                                                <div class="info-row">
                                                    <div class="info-label">@lang('messages.Approuvé par')</div>
                                                    <div class="info-value">{{ $stockout->approuved_by }}</div>
                                                </div>
                                                @endif
                                                @if($stockout->rejected_by)
                                                <div class="info-row">
                                                    <div class="info-label">@lang('messages.Rejeté par')</div>
                                                    <div class="info-value text-danger">{{ $stockout->rejected_by }}</div>
                                                </div>
                                                @endif
                                                @if($stockout->reseted_by)
                                                <div class="info-row">
                                                    <div class="info-label">@lang('messages.Annulé par')</div>
                                                    <div class="info-value">{{ $stockout->reseted_by }}</div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if($stockout->rejected_motif)
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card border-danger">
                                            <div class="card-header bg-danger text-white">
                                                <h5 class="mb-0"><i class="fa fa-exclamation-triangle"></i> @lang('messages.Motif de rejet')</h5>
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
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
