
@extends('backend.layouts.master')

@section('title')
@lang('messages.Prendre un congé payé') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Prendre un congé payé')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Prendre un congé payé')</h4>
                    <p class="text-muted small mb-0">Un employé qui a déjà un congé en cours ne peut pas se voir attribuer un autre congé : la date de début du nouveau doit être strictement supérieure à la date de fin du dernier congé.</p>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.hr-take-conge-payes.create',$company_id) }}">@lang('messages.new')</a>
                        @endif
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.Date')</th>
                                    <th width="10%">@lang('messages.Employé')</th>
                                    <th width="10%">@lang('messages.Date Début')</th>
                                    <th width="10%">@lang('messages.Date Fin')</th>
                                    <th width="10%">@lang('messages.Nbre Jrs Congé Annuel')</th>
                                    <th width="10%">@lang('messages.Nbre Jrs Congé Sollicité')</th>
                                    <th width="10%">@lang('messages.Nbre Jrs Congé pris')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($take_conge_payes as $take_conge_paye)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $take_conge_paye->created_at }}</td>
                                    <td>{{ $take_conge_paye->employe->firstname }}&nbsp;{{ $take_conge_paye->employe->lastname }}</td>
                                    <td>{{ $take_conge_paye->date_heure_debut }}</td>
                                    <td>{{ $take_conge_paye->date_heure_fin }}</td>
                                    <td>{{ $take_conge_paye->nbre_jours_conge_paye }}</td>
                                    <td>{{ $take_conge_paye->nbre_jours_conge_sollicite }}
                                        @if($take_conge_paye->etat == 1)<span class="badge badge-success">@lang('messages.Encours...')</span>
                                        @elseif($take_conge_paye->etat == 2)<span class="badge badge-success">@lang('messages.validé')</span>
                                        @elseif($take_conge_paye->etat == 3)<span class="badge badge-success">@lang('messages.confirmé')</span>
                                        @elseif($take_conge_paye->etat == 4)<span class="badge badge-success">@lang('messages.approuvé')</span>
                                        @elseif($take_conge_paye->etat == -1)<span class="badge badge-danger">@lang('messages.rejeté')</span>
                                        @endif
                                    </td>
                                    <td>{{ $take_conge_paye->nbre_jours_conge_pris }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.create'))
                                        <a href="{{ route('admin.hr-take-conge-payes.lettreDemandeConge', $take_conge_paye->id) }}"><img src="{{ asset('img/ISSh.gif') }}" width="60" title="{{ __('messages.hr_loan_print_hint') }}"></a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.edit'))
                                            <a class="btn btn-success text-white" href="{{ route('admin.hr-take-conge-payes.edit', $take_conge_paye->id) }}">@lang('messages.Editer')</a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.edit') && $take_conge_paye->etat == 1)
                                        <a class="btn btn-success text-white" href="{{ route('admin.hr-take-conge-paye.valider',$take_conge_paye->id) }}" title="{{ __('messages.hr_validate_paid_leave') }}" 
                                            onclick="event.preventDefault(); document.getElementById('validate-form-{{ $take_conge_paye->id }}').submit();">
                                                @lang('messages.hr_loan_btn_validate')
                                            </a>&nbsp;&nbsp;

                                        <form id="validate-form-{{ $take_conge_paye->id }}" action="{{ route('admin.hr-take-conge-paye.valider',$take_conge_paye->id) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                        </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.edit')&& $take_conge_paye->etat == 2)
                                        <a class="btn btn-success text-white" href="{{ route('admin.hr-take-conge-paye.confirmer',$take_conge_paye->id) }}" title="{{ __('messages.hr_confirm_paid_leave') }}" 
                                            onclick="event.preventDefault(); document.getElementById('validate-form-{{ $take_conge_paye->id }}').submit();">
                                                @lang('messages.hr_loan_btn_confirm')
                                            </a>&nbsp;&nbsp;

                                        <form id="validate-form-{{ $take_conge_paye->id }}" action="{{ route('admin.hr-take-conge-paye.confirmer',$take_conge_paye->id) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                        </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.edit') && $take_conge_paye->etat == 3)
                                        <a class="btn btn-success text-white" href="{{ route('admin.hr-take-conge-paye.approuver',$take_conge_paye->id) }}" title="{{ __('messages.hr_approve_paid_leave') }}" 
                                            onclick="event.preventDefault(); document.getElementById('validate-form-{{ $take_conge_paye->id }}').submit();">
                                                @lang('messages.hr_loan_btn_approve')
                                            </a>&nbsp;&nbsp;

                                        <form id="validate-form-{{ $take_conge_paye->id }}" action="{{ route('admin.hr-take-conge-paye.approuver',$take_conge_paye->id) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                        </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.edit') && $take_conge_paye->etat == 1 || $take_conge_paye->etat == 2 || $take_conge_paye->etat == 3 || $take_conge_paye->etat == 4)
                                        <a class="btn btn-success text-white" href="{{ route('admin.hr-take-conge-paye.rejeter',$take_conge_paye->id) }}" title="{{ __('messages.hr_reject_paid_leave') }}" 
                                            onclick="event.preventDefault(); document.getElementById('validate-form-{{ $take_conge_paye->id }}').submit();">
                                                @lang('messages.hr_btn_reject')
                                            </a>&nbsp;&nbsp;

                                        <form id="validate-form-{{ $take_conge_paye->id }}" action="{{ route('admin.hr-take-conge-paye.rejeter',$take_conge_paye->id) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                        </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.edit'))
                                        <a class="btn btn-success text-white" href="{{ route('admin.hr-take-conge-paye.annuler',$take_conge_paye->id) }}" title="{{ __('messages.hr_cancel_paid_leave') }}" 
                                            onclick="event.preventDefault(); document.getElementById('validate-form-{{ $take_conge_paye->id }}').submit();">
                                                @lang('messages.hr_btn_cancel')
                                            </a>&nbsp;&nbsp;

                                        <form id="validate-form-{{ $take_conge_paye->id }}" action="{{ route('admin.hr-take-conge-paye.annuler',$take_conge_paye->id) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                        </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.delete'))
                                            <a class="btn btn-danger text-white" href="{{ route('admin.hr-take-conge-payes.destroy', $take_conge_paye->id) }}"
                                            onclick="event.preventDefault(); if(confirm(@json(__('messages.delete_confirm_irreversible')))) { document.getElementById('delete-form-{{ $take_conge_paye->id }}').submit(); }">
                                                @lang('messages.delete')
                                            </a>

                                            <form id="delete-form-{{ $take_conge_paye->id }}" action="{{ route('admin.hr-take-conge-payes.destroy', $take_conge_paye->id) }}" method="POST" style="display: none;">
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
        <!-- data table end -->
        
    </div>
</div>

<div class="row">
<!-- page title area end -->
    <div class="col-md-12">
        <div class="main-content-inner">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title float-left">@lang('messages.Gérer Congé Payé')</h4>
                    <p class="float-right mb-2">
                            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#congePayeModal" data-whatever="@mdo"><i class="fa fa-plus-square" title="{{ __('messages.Gérer Congé Payé') }}" aria-hidden="false"></i></button>
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        <table id="dataTable" class="text-center">
                            <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.Date de Saisie')</th>
                                    <th width="10%">@lang('messages.Session')</th>
                                    <th width="20%">@lang('messages.Nbre Jours')</th>
                                    <th width="10%">@lang('messages.Etat')</th>
                                    <th width="30%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($conge_payes as $conge_paye)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($conge_paye->created_at)->format('d/m/Y') }}</td>
                                    <td><a href="">{{ $conge_paye->session }}</a> </td>
                                    <td>{{ $conge_paye->nbre_jours }}</td>
                                    <td>@if($conge_paye->etat == 0) <span class="badge badge-primary">@lang('messages.Encours...')</span> @elseif($conge_paye->etat == 1) <span class="badge badge-success">@lang('messages.Déjà clôturé')</span> @endif</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.edit'))
                                            <a class="btn btn-success text-white" href="{{ route('admin.hr-conge-payes.edit', $conge_paye->id) }}"><i class="fa fa-edit" title="{{ __('messages.Modifier') }}" aria-hidden="false"></i></a>
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.delete'))
                                            <a class="btn btn-danger text-white" href="{{ route('admin.hr-conge-payes.destroy', $conge_paye->id) }}"
                                            onclick="event.preventDefault(); if(confirm(@json(__('messages.delete_confirm_irreversible')))) { document.getElementById('delete-form-{{ $conge_paye->id }}').submit(); }">
                                                <i class="fa fa-trash" title="{{ __('messages.Supprimer') }}" aria-hidden="false"></i>
                                            </a>

                                            <form id="delete-form-{{ $conge_paye->id }}" action="{{ route('admin.hr-conge-payes.destroy', $conge_paye->id) }}" method="POST" style="display: none;">
                                                @method('DELETE')
                                                @csrf
                                            </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.create'))
                                        <a href=""><img src="{{ asset('img/ISSh.gif') }}" width="60" title="{{ __('messages.hr_loan_print_hint') }}"></a>
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

@include('backend.pages.hr.conge_paye.create')
@endsection


@section('scripts')
     <!-- Start datatable js -->
     <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>

     <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js"></script>
     
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