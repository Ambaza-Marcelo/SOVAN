@extends('backend.layouts.master')

@section('title')
@lang('messages.Plan de production') - @lang('messages.admin_panel')
@endsection

@section('styles')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Plan de production')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('messages.list')</span></li>
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
                    <h4 class="header-title float-left">@lang('messages.Plan de production')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('production_plan.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.production-plan.create') }}">
                                <i class="fa fa-plus"></i> @lang('messages.new')
                            </a>
                        @endif
                    </p>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials._index_filter_export', ['filterRoute' => 'admin.production-plan.index', 'exportRoute' => 'admin.production-plan.export-to-excel', 'useDateType' => 'date'])
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.Date')</th>
                                    <th width="12%">@lang('messages.N° Plan')</th>
                                    <th width="15%">@lang('messages.Signature')</th>
                                    <th width="10%">@lang('messages.État')</th>
                                    <th width="10%">@lang('messages.Coût total')</th>
                                    <th width="10%">@lang('messages.Auteur')</th>
                                    <th width="18%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($plans as $plan)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($plan->production_plan_date)->format('d/m/Y') }}</td>
                                    <td><a href="{{ route('admin.production-plan.show', $plan->prodution_plan_number) }}">{{ $plan->prodution_plan_number }}</a></td>
                                    <td>{{ $plan->production_signature }}</td>
                                    @if($plan->status == '2' || $plan->status == 2)
                                        <td><span class="badge badge-success">@lang('messages.Validé')</span></td>
                                    @elseif($plan->status == '-1' || $plan->status == -1)
                                        <td><span class="badge badge-danger">@lang('messages.Rejeté')</span></td>
                                    @elseif($plan->status == '3' || $plan->status == 3)
                                        <td><span class="badge badge-info">@lang('messages.Confirmé')</span></td>
                                    @elseif($plan->status == '4' || $plan->status == 4)
                                        <td><span class="badge badge-success">@lang('messages.Approuvé')</span></td>
                                    @else
                                        <td><span class="badge badge-primary">@lang('messages.En cours')</span></td>
                                    @endif
                                    <td>{{ number_format($plan->total_estimated_cost ?? 0, 0, ',', ' ') }}</td>
                                    <td>{{ $plan->created_by }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('production_plan.view'))
                                            <a class="btn btn-info btn-sm" href="{{ route('admin.production-plan.show', $plan->prodution_plan_number) }}" title="@lang('messages.Voir')"><i class="fa fa-eye"></i></a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('production_plan.edit'))
                                            @if($plan->status != '4' && $plan->status != 4 && $plan->flag1 != '1')
                                                <a class="btn btn-primary btn-sm" href="{{ route('admin.production-plan.edit', $plan->prodution_plan_number) }}" title="@lang('messages.Modifier')"><i class="fa fa-edit"></i></a>
                                            @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('production_plan.validate'))
                                            @if($plan->status == '0' || $plan->status == 0 || $plan->status == '-1' || $plan->status == -1)
                                                <form action="{{ route('admin.production-plan.validate', $plan->prodution_plan_number) }}" method="POST" style="display:inline;">@csrf @method('PUT')
                                                    <button type="submit" class="btn btn-success btn-sm" title="@lang('messages.Valider')" onclick="return confirm(@json(__('messages.Valider ce plan ?')));"><i class="fa fa-check"></i></button>
                                                </form>
                                            @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('production_plan.confirm'))
                                            @if($plan->status == '2' || $plan->status == 2)
                                                <form action="{{ route('admin.production-plan.confirm', $plan->prodution_plan_number) }}" method="POST" style="display:inline;">@csrf @method('PUT')
                                                    <button type="submit" class="btn btn-info btn-sm" title="@lang('messages.Confirmer')" onclick="return confirm(@json(__('messages.Confirmer ce plan ?')));"><i class="fa fa-check-circle"></i></button>
                                                </form>
                                            @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('production_plan.approuve'))
                                            @if($plan->status == '3' || $plan->status == 3)
                                                <form action="{{ route('admin.production-plan.approve', $plan->prodution_plan_number) }}" method="POST" style="display:inline;">@csrf @method('PUT')
                                                    <button type="submit" class="btn btn-success btn-sm" title="@lang('messages.Approuver')" onclick="return confirm(@json(__('messages.Approuver ce plan ?')));"><i class="fa fa-check-double"></i></button>
                                                </form>
                                            @endif
                                        @endif
                                        @if($plan->status == '2' || $plan->status == 2 || $plan->status == '3' || $plan->status == 3 || $plan->status == '4' || $plan->status == 4)
                                            <a class="btn btn-secondary btn-sm" href="{{ route('admin.production-plan.print', $plan->prodution_plan_number) }}" title="@lang('messages.Imprimer')" target="_blank"><i class="fa fa-print"></i></a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_requisition.create'))
                                            @if(($plan->status == '4' || $plan->status == 4) && $plan->flag1 != '1')
                                                <a class="btn btn-warning btn-sm" href="{{ route('admin.raw-material-requisitions.create-from-plan', $plan->prodution_plan_number) }}" title="@lang('messages.Créer requête matières premières')">@lang('messages.Requête')</a>
                                            @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('production_plan.delete'))
                                            @if($plan->status != '4' && $plan->status != 4 && $plan->flag1 != '1')
                                                <form action="{{ route('admin.production-plan.destroy', $plan->prodution_plan_number) }}" method="POST" style="display:inline;">@csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="@lang('messages.Supprimer')" onclick="return confirm(@json(__('messages.Supprimer ce plan ?')));"><i class="fa fa-trash"></i></button>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
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
<script>
if ($('#dataTable').length) $('#dataTable').DataTable({ responsive: true });
</script>
@endsection
