
@extends('backend.layouts.master')

@section('title')
@lang('messages.Les réglages') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <!-- Start datatable css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Les réglages')</h4>
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
<!-- page title area end -->
<div class="main-content-inner">
    <div class="row">
        <!-- data table start -->
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title float-left">@lang('messages.Les réglages')</h4>
                    <p class="float-right mb-2">
                            <a class="btn btn-success text-white" href="{{ route('admin.hr-reglages.create') }}"><i class="fa fa-plus-square" title="{{ __('messages.Ajouter') }}" aria-hidden="false"></i></a>
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr class="bg-warning">
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.Plafond Impôt')</th>
                                    <th width="10%">@lang('messages.Plafond Cotisation')</th>
                                    <th width="10%">@lang('messages.Nbre Jrs Ouvrable')</th>
                                    <th></th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @if($reglage)
                               <tr class="bg-success">
                                    <td>1</td>
                                    <td>{{ $reglage->prafond_impot }}</td>
                                    <td>{{ $reglage->prafond_cotisation }}</td>
                                    <td>{{ $reglage->nbre_jours_ouvrables }}</td>
                                    <td></td>
                                </tr>
                               @endif
                            </tbody>
                                <tr class="bg-warning">
                                    <th width="5%"></th>
                                    <th width="10%">@lang('messages.Nbre Jrs Anticipation Congé')</th>
                                    <th width="10%">@lang('messages.Nbre Jrs Congé Par mois')</th>
                                    <th width="10%">@lang('messages.Min Jrs Congé Payé par Mois')</th>
                                    <th width="10%">@lang('messages.Max Jrs Congé Payé Par Mois')</th>
                                    <th width="15%"></th>
                                </tr>
                                @if($reglage)
                                <tr class="bg-default">
                                    <td>2</td>
                                    <td>{{ $reglage->jour_anticipation_conge }}</td>
                                    <td>{{ $reglage->jour_conge_par_mois }}</td>
                                    <td>{{ $reglage->min_jour_conge_paye }}</td>
                                    <td>{{ $reglage->max_jour_conge_paye }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('hr_reglage.edit'))
                                            <a class="btn btn-success text-white" href="{{ route('admin.hr-reglages.edit', $reglage->id) }}"><i class="fa fa-edit" title="{{ __('messages.Modifier') }}" aria-hidden="false"></i></a>
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('hr_reglage.delete'))
                                            <a class="btn btn-danger text-white" href="{{ route('admin.hr-reglages.destroy', $reglage->id) }}"
                                            onclick="event.preventDefault(); if(confirm(@json(__('messages.delete_confirm_irreversible')))) { document.getElementById('delete-form-{{ $reglage->id }}').submit(); }">
                                                <i class="fa fa-trash" title="{{ __('messages.Supprimer') }}" aria-hidden="false"></i>
                                            </a>

                                            <form id="delete-form-{{ $reglage->id }}" action="{{ route('admin.hr-reglages.destroy', $reglage->id) }}" method="POST" style="display: none;">
                                                @method('DELETE')
                                                @csrf
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                               @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- data table end -->
        
    </div>
</div>
<!-- page title area end -->
<div class="row">
    <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title float-left">@lang('messages.Motif Absence')</h4>
                    <p class="float-right mb-2">
                            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#typeAbsenceModal" data-whatever="@mdo"><i class="fa fa-plus-square" title="{{ __('messages.Ajouter') }}" aria-hidden="false"></i></button>
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.Désignation')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($type_absences as $type_absence)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $type_absence->name }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('hr_absence.edit'))
                                            <a class="btn btn-success text-white" href="{{ route('admin.hr-type-absences.edit', $type_absence->id) }}"><i class="fa fa-edit" title="{{ __('messages.Modifier') }}" aria-hidden="false"></i></a>
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('hr_absence.delete'))
                                            <a class="btn btn-danger text-white" href="{{ route('admin.hr-type-absences.destroy', $type_absence->id) }}"
                                            onclick="event.preventDefault(); if(confirm(@json(__('messages.delete_confirm_irreversible')))) { document.getElementById('delete-form-{{ $type_absence->id }}').submit(); }">
                                                <i class="fa fa-trash" title="{{ __('messages.Supprimer') }}" aria-hidden="false"></i>
                                            </a>

                                            <form id="delete-form-{{ $type_absence->id }}" action="{{ route('admin.hr-type-absences.destroy', $type_absence->id) }}" method="POST" style="display: none;">
                                                @method('DELETE')
                                                @csrf
                                            </form>
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
    <!-- page title area end -->
</div>
@include('backend.pages.hr.type_absence.create')
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
        if ($('#dataTable').length) {
            $('#dataTable').DataTable({
                responsive: true
            });
        }

     </script>
@endsection