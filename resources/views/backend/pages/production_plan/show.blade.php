@extends('backend.layouts.master')

@section('title')
@lang('messages.Détails plan') {{ $plan->prodution_plan_number }} - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .info-label { font-weight: 600; color: #495057; margin-bottom: 5px; }
    .info-value { margin-bottom: 15px; }
    .detail-section { padding: 20px; background: #f8f9fa; border-radius: 5px; margin-bottom: 15px; }
</style>
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Plan de production:') {{ $plan->prodution_plan_number }}</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.production-plan.index') }}">@lang('messages.Liste')</a></li>
                    <li><span>@lang('messages.Détails')</span></li>
                </ul>
            </div>
        </div>
        <div class="col-sm-6 clearfix">@include('backend.layouts.partials.logout')</div>
    </div>
</div>

<div class="main-content-inner">
    <div class="row">
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">@lang('messages.Détails du plan') {{ $plan->prodution_plan_number }}</h4>
                    @include('backend.layouts.partials.messages')

                    <div class="mb-3">
                        @if (Auth::guard('admin')->user()->can('production_plan.edit') && $plan->status != '4' && $plan->status != 4 && $plan->flag1 != '1')
                            <a href="{{ route('admin.production-plan.edit', $plan->prodution_plan_number) }}" class="btn btn-primary"><i class="fa fa-edit"></i> @lang('messages.Modifier')</a>
                        @endif
                        <a href="{{ route('admin.production-plan.index') }}" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> @lang('messages.Retour')</a>
                        @if($plan->status == '2' || $plan->status == 2 || $plan->status == '3' || $plan->status == 3 || $plan->status == '4' || $plan->status == 4)
                            <a href="{{ route('admin.production-plan.print', $plan->prodution_plan_number) }}" class="btn btn-info" target="_blank"><i class="fa fa-print"></i> @lang('messages.Imprimer')</a>
                        @endif
                        @if (Auth::guard('admin')->user()->can('production_plan.validate') && ($plan->status == '0' || $plan->status == 0 || $plan->status == '-1' || $plan->status == -1))
                            <form action="{{ route('admin.production-plan.validate', $plan->prodution_plan_number) }}" method="POST" style="display:inline;">@csrf @method('PUT')
                                <button type="submit" class="btn btn-success" onclick="return confirm(@json(__('messages.Valider ce plan ?')));"><i class="fa fa-check"></i> @lang('messages.Valider')</button>
                            </form>
                        @endif
                        @if (Auth::guard('admin')->user()->can('production_plan.confirm') && ($plan->status == '2' || $plan->status == 2))
                            <form action="{{ route('admin.production-plan.confirm', $plan->prodution_plan_number) }}" method="POST" style="display:inline;">@csrf @method('PUT')
                                <button type="submit" class="btn btn-info" onclick="return confirm(@json(__('messages.Confirmer ce plan ?')));"><i class="fa fa-check-circle"></i> @lang('messages.Confirmer')</button>
                            </form>
                        @endif
                        @if (Auth::guard('admin')->user()->can('production_plan.approuve') && ($plan->status == '3' || $plan->status == 3))
                            <form action="{{ route('admin.production-plan.approve', $plan->prodution_plan_number) }}" method="POST" style="display:inline;">@csrf @method('PUT')
                                <button type="submit" class="btn btn-success" onclick="return confirm(@json(__('messages.Approuver ce plan ?')));"><i class="fa fa-check-double"></i> @lang('messages.Approuver')</button>
                            </form>
                        @endif
                        @if (Auth::guard('admin')->user()->can('raw_material_requisition.create'))
                            @if(($plan->status == '4' || $plan->status == 4) && $plan->flag1 != '1')
                                <a href="{{ route('admin.raw-material-requisitions.create-from-plan', $plan->prodution_plan_number) }}" class="btn btn-warning"><i class="fa fa-list"></i> @lang('messages.Créer requête matières premières')</a>
                            @endif
                            @if($requisition)
                                <a href="{{ route('admin.raw-material-requisitions.show', $requisition->requisition_no) }}" class="btn btn-secondary">@lang('messages.Voir requête') {{ $requisition->requisition_no }}</a>
                            @endif
                        @endif
                    </div>

                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#infos">@lang('messages.Infos')</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#products">@lang('messages.Produits finis')</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#materials">@lang('messages.Matières premières')</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#costing">@lang('messages.Coûts')</a></li>
                    </ul>

                    <div class="tab-content mt-4">
                        <div id="infos" class="tab-pane active">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="detail-section">
                                        <div class="form-group"><label class="info-label">@lang('messages.N° Plan')</label><div class="info-value"><strong>{{ $plan->prodution_plan_number }}</strong></div></div>
                                        <div class="form-group"><label class="info-label">@lang('messages.Signature')</label><div class="info-value">{{ $plan->production_signature }}</div></div>
                                        <div class="form-group"><label class="info-label">@lang('messages.Date')</label><div class="info-value">{{ \Carbon\Carbon::parse($plan->production_plan_date)->format('d/m/Y') }}</div></div>
                                        <div class="form-group"><label class="info-label">@lang('messages.Début / Fin')</label><div class="info-value">{{ \Carbon\Carbon::parse($plan->start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($plan->end_date)->format('d/m/Y') }}</div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-section">
                                        <div class="form-group"><label class="info-label">@lang('messages.État')</label><div class="info-value">
                                            @if($plan->status == 2 || $plan->status == '2')<span class="badge badge-success">@lang('messages.Validé')</span>
                                            @elseif($plan->status == -1 || $plan->status == '-1')<span class="badge badge-danger">@lang('messages.Rejeté')</span>
                                            @elseif($plan->status == 3 || $plan->status == '3')<span class="badge badge-info">@lang('messages.Confirmé')</span>
                                            @elseif($plan->status == 4 || $plan->status == '4')<span class="badge badge-success">@lang('messages.Approuvé')</span>
                                            @else<span class="badge badge-primary">@lang('messages.En cours')</span>@endif
                                        </div></div>
                                        <div class="form-group"><label class="info-label">@lang('messages.Créé par')</label><div class="info-value">{{ $plan->created_by }}</div></div>
                                        @if(!empty($plan->validated_by))<div class="form-group"><label class="info-label">@lang('messages.Validé par')</label><div class="info-value">{{ $plan->validated_by }}</div></div>@endif
                                        @if(!empty($plan->confirmed_by))<div class="form-group"><label class="info-label">@lang('messages.Confirmé par')</label><div class="info-value">{{ $plan->confirmed_by }}</div></div>@endif
                                        @if(!empty($plan->approuved_by))<div class="form-group"><label class="info-label">@lang('messages.Approuvé par')</label><div class="info-value">{{ $plan->approuved_by }}</div></div>@endif
                                        <div class="form-group"><label class="info-label">@lang('messages.Description')</label><div class="info-value">{{ $plan->description ?? 'N/A' }}</div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="products" class="tab-pane">
                            <table class="table table-bordered">
                                <thead class="bg-light"><tr><th>#</th><th>@lang('messages.Article')</th><th>@lang('messages.Quantité')</th><th>@lang('messages.Prix unit.')</th><th>@lang('messages.Total')</th></tr></thead>
                                <tbody>
                                    @forelse($finishedGoods as $i => $g)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $g->item ? $g->item->code . ' - ' . $g->item->name : 'N/A' }}</td>
                                        <td>{{ number_format($g->item_quantity, 2) }}</td>
                                        <td>{{ number_format($g->cost_price ?? 0, 0, ',', ' ') }}</td>
                                        <td>{{ number_format($g->total_item_cost ?? 0, 0, ',', ' ') }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center">@lang('messages.Aucun produit fini')</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div id="materials" class="tab-pane">
                            <table class="table table-bordered">
                                <thead class="bg-light"><tr><th>#</th><th>@lang('messages.Matière première')</th><th>@lang('messages.Quantité')</th><th>@lang('messages.Unité')</th><th>@lang('messages.Coût')</th><th>@lang('messages.Total')</th></tr></thead>
                                <tbody>
                                    @forelse($rawMaterials as $i => $m)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $m->rawMaterial ? $m->rawMaterial->name : 'N/A' }}</td>
                                        <td>{{ number_format($m->material_quantity, 2) }}</td>
                                        <td>{{ $m->rawMaterial && $m->rawMaterial->rawMaterialMeasurement ? $m->rawMaterial->rawMaterialMeasurement->purchase_unit : '' }}</td>
                                        <td>{{ number_format($m->material_cost ?? 0, 0, ',', ' ') }}</td>
                                        <td>{{ number_format($m->total_material_cost ?? 0, 0, ',', ' ') }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center">@lang('messages.Aucune matière première')</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div id="costing" class="tab-pane">
                            <div class="detail-section">
                                <p><strong>@lang('messages.Main d\'œuvre estimée:')</strong> {{ number_format($plan->estimated_labor_cost ?? 0, 0, ',', ' ') }}</p>
                                <p><strong>@lang('messages.Matériaux estimés:')</strong> {{ number_format($plan->estimated_material_cost ?? 0, 0, ',', ' ') }}</p>
                                <p><strong>@lang('messages.Frais généraux estimés:')</strong> {{ number_format($plan->estimated_overhead_cost ?? 0, 0, ',', ' ') }}</p>
                                <hr>
                                <p><strong>@lang('messages.Coût total estimé:')</strong> {{ number_format($plan->total_estimated_cost ?? 0, 0, ',', ' ') }}</p>
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
<script>
$('.nav-tabs a').on('click', function (e) { e.preventDefault(); $(this).tab('show'); });
</script>
@endsection
