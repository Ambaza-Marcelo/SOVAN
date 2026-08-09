
@extends('backend.layouts.master')

@section('title')
@lang('Prendre un congé payé') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('Prendre un congé payé')</h4>
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
                    <h4 class="header-title float-left">@lang('Prendre un congé payé')</h4>
                    <p class="text-muted small mb-0">Un employé qui a déjà un congé en cours ne peut pas se voir attribuer un autre congé : la date de début du nouveau doit être strictement supérieure à la date de fin du dernier congé.</p>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.hr-take-conge-payes.create',$company_id) }}">@lang('messages.new')</a>
                        @endif
                    </p>
                    <div class="clearfix"></div>

                    <form action="{{ route('admin.hr-take-conge-payes.index', $company_id) }}" method="GET" class="mb-3 p-3" style="background:#f8f9fa;border-radius:5px;">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="filter_employe_search">Employé</label>
                                @include('backend.pages.hr.partials.employe_autocomplete_field', [
                                    'employes' => $employes,
                                    'inputId' => 'filter_employe_search',
                                    'hiddenId' => 'filter_employe_id',
                                    'hiddenName' => 'employe_id',
                                    'listId' => 'filter_employe_search-list',
                                    'selectedEmployeId' => $selectedFilterEmployeId,
                                    'placeholder' => 'Tous les employés — tapez nom ou matricule',
                                ])
                                <small class="text-muted">Laissez vide pour tous les employés.</small>
                            </div>
                            <div class="col-md-3">
                                <label for="date_debut">Date début (du)</label>
                                <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ request('date_debut') }}">
                            </div>
                            <div class="col-md-3">
                                <label for="date_fin">Date fin (au)</label>
                                <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ request('date_fin') }}">
                            </div>
                            <div class="col-md-3">
                                <label>&nbsp;</label>
                                <div class="d-flex flex-wrap" style="gap:6px;">
                                    <button type="submit" class="btn btn-info">Filtrer</button>
                                    <a href="{{ route('admin.hr-take-conge-payes.index', $company_id) }}" class="btn btn-secondary">Réinitialiser</a>
                                </div>
                            </div>
                        </div>
                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.view'))
                        <div class="row mt-2">
                            <div class="col-12">
                                <a href="{{ route('admin.hr-take-conge-payes.export-excel', array_merge(['company_id' => $company_id], request()->query())) }}" class="btn btn-success btn-sm">
                                    <i class="fa fa-file-excel-o"></i> Exporter Excel
                                </a>
                                <a href="{{ route('admin.hr-take-conge-payes.export-pdf', array_merge(['company_id' => $company_id], request()->query())) }}" class="btn btn-primary btn-sm">
                                    <i class="fa fa-file-pdf-o"></i> Exporter PDF
                                </a>
                                <small class="text-muted ml-2">Excel : tous les mouvements (filtres employé / période). PDF : congés approuvés uniquement.</small>
                            </div>
                        </div>
                        @endif
                    </form>

                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">Date</th>
                                    <th width="10%">Employé</th>
                                    <th width="10%">Date Début</th>
                                    <th width="10%">Date Fin</th>
                                    <th width="10%">Nbre Jrs Congé Annuel</th>
                                    <th width="10%">Nbre Jrs Congé Sollicité</th>
                                    <th width="10%">Nbre Jrs Congé pris</th>
                                    <th width="15%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($take_conge_payes as $take_conge_paye)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $take_conge_paye->created_at }}</td>
                                    <td>{{ optional($take_conge_paye->employe)->display_label }}</td>
                                    <td>{{ $take_conge_paye->date_heure_debut }}</td>
                                    <td>{{ $take_conge_paye->date_heure_fin }}</td>
                                    <td>{{ $take_conge_paye->nbre_jours_conge_paye }}</td>
                                    <td>{{ $take_conge_paye->nbre_jours_conge_sollicite }}
                                        @if($take_conge_paye->etat == 1)<span class="badge badge-success">Encours...</span>@elseif($take_conge_paye->etat == 2)<span class="badge badge-success">validé</span>@elseif($take_conge_paye->etat == 3)<span class="badge badge-success">confirmé</span>@elseif($take_conge_paye->etat == 4)<span class="badge badge-success">approuvé</span>@elseif($take_conge_paye->etat == -1)<span class="badge badge-danger">rejeté</span>@endif
                                    </td>
                                    <td>{{ $take_conge_paye->nbre_jours_conge_pris }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.create'))
                                        <a href="{{ route('admin.hr-take-conge-payes.lettreDemandeConge', $take_conge_paye->id) }}"><img src="{{ asset('img/ISSh.gif') }}" width="60" title="Télécharger d'abord le document et puis imprimer"></a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.edit') && (int) $take_conge_paye->etat !== 4)
                                            <a class="btn btn-success text-white" href="{{ route('admin.hr-take-conge-payes.edit', $take_conge_paye->id) }}">@lang('Editer')</a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.edit') && $take_conge_paye->etat == 1)
                                        <a class="btn btn-success text-white" href="{{ route('admin.hr-take-conge-paye.valider',$take_conge_paye->id) }}" title="valider le congé payé" 
                                            onclick="event.preventDefault(); document.getElementById('validate-form-{{ $take_conge_paye->id }}').submit();">
                                                Valider
                                            </a>&nbsp;&nbsp;

                                        <form id="validate-form-{{ $take_conge_paye->id }}" action="{{ route('admin.hr-take-conge-paye.valider',$take_conge_paye->id) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                        </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.edit') && $take_conge_paye->etat == 2)
                                        <a class="btn btn-success text-white" href="{{ route('admin.hr-take-conge-paye.confirmer',$take_conge_paye->id) }}" title="confirmer le congé payé" 
                                            onclick="event.preventDefault(); document.getElementById('confirm-form-{{ $take_conge_paye->id }}').submit();">
                                                Confirmer
                                            </a>&nbsp;&nbsp;

                                        <form id="confirm-form-{{ $take_conge_paye->id }}" action="{{ route('admin.hr-take-conge-paye.confirmer',$take_conge_paye->id) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                        </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.edit') && $take_conge_paye->etat == 3)
                                        <a class="btn btn-success text-white" href="{{ route('admin.hr-take-conge-paye.approuver',$take_conge_paye->id) }}" title="approuver le congé payé" 
                                            onclick="event.preventDefault(); document.getElementById('approve-form-{{ $take_conge_paye->id }}').submit();">
                                                Approuver
                                            </a>&nbsp;&nbsp;

                                        <form id="approve-form-{{ $take_conge_paye->id }}" action="{{ route('admin.hr-take-conge-paye.approuver',$take_conge_paye->id) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                        </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.edit') && in_array((int) $take_conge_paye->etat, [1, 2, 3, 4], true))
                                        <a class="btn btn-warning text-white" href="{{ route('admin.hr-take-conge-paye.rejeter',$take_conge_paye->id) }}" title="rejeter le congé payé" 
                                            onclick="event.preventDefault(); document.getElementById('reject-form-{{ $take_conge_paye->id }}').submit();">
                                                Rejeter
                                            </a>&nbsp;&nbsp;

                                        <form id="reject-form-{{ $take_conge_paye->id }}" action="{{ route('admin.hr-take-conge-paye.rejeter',$take_conge_paye->id) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                        </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.edit'))
                                        <a class="btn btn-secondary text-white" href="{{ route('admin.hr-take-conge-paye.annuler',$take_conge_paye->id) }}" title="annuler le congé payé" 
                                            onclick="event.preventDefault(); document.getElementById('cancel-form-{{ $take_conge_paye->id }}').submit();">
                                                Annuler
                                            </a>&nbsp;&nbsp;

                                        <form id="cancel-form-{{ $take_conge_paye->id }}" action="{{ route('admin.hr-take-conge-paye.annuler',$take_conge_paye->id) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                        </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.delete'))
                                            <a class="btn btn-danger text-white" href="{{ route('admin.hr-take-conge-payes.destroy', $take_conge_paye->id) }}"
                                            onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this item? This action is irreversible.')) { document.getElementById('delete-form-{{ $take_conge_paye->id }}').submit(); }">
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
                    <h4 class="header-title float-left">Gérer Congé Payé</h4>
                    <p class="float-right mb-2">
                            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#congePayeModal" data-whatever="@mdo"><i class="fa fa-plus-square" title="Gérer Congé Payé" aria-hidden="false"></i></button>
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        <table id="dataTableCongePaye" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">Date de Saisie</th>
                                    <th width="10%">Session</th>
                                    <th width="20%">Nbre Jours</th>
                                    <th width="10%">Etat</th>
                                    <th width="30%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($conge_payes as $conge_paye)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($conge_paye->created_at)->format('d/m/Y') }}</td>
                                    <td><a href="">{{ $conge_paye->session }}</a> </td>
                                    <td>{{ $conge_paye->nbre_jours }}</td>
                                    <td>@if($conge_paye->etat == 0) <span class="badge badge-primary">Encours...</span> @elseif($conge_paye->etat == 1) <span class="badge badge-success">Déjà clôturé</span> @endif</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.edit'))
                                            <a class="btn btn-success text-white" href="{{ route('admin.hr-conge-payes.edit', $conge_paye->id) }}"><i class="fa fa-edit" title="Ajouter" aria-hidden="false"></i></a>
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.delete'))
                                            <a class="btn btn-danger text-white" href="{{ route('admin.hr-conge-payes.destroy', $conge_paye->id) }}"
                                            onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this item? This action is irreversible.')) { document.getElementById('delete-form-{{ $conge_paye->id }}').submit(); }">
                                                <i class="fa fa-trash" title="Ajouter" aria-hidden="false"></i>
                                            </a>

                                            <form id="delete-form-{{ $conge_paye->id }}" action="{{ route('admin.hr-conge-payes.destroy', $conge_paye->id) }}" method="POST" style="display: none;">
                                                @method('DELETE')
                                                @csrf
                                            </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.create'))
                                        <a href=""><img src="{{ asset('img/ISSh.gif') }}" width="60" title="Télécharger d'abord le document et puis imprimer"></a>
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

     @include('backend.pages.hr.partials.employe_autocomplete_scripts')
     
     <script>
         if ($('#dataTable').length) {
            $('#dataTable').DataTable({
                responsive: true
            });
        }
        if ($('#dataTableCongePaye').length) {
            $('#dataTableCongePaye').DataTable({
                responsive: true
            });
        }

        var filterEmployees = [
            @foreach($employes as $employe)
            {
                id: {{ $employe->id }},
                name: {!! json_encode(trim($employe->firstname . ' ' . $employe->lastname)) !!},
                matricule: {!! json_encode($employe->matricule_no ?? '') !!}
            }@if(!$loop->last),@endif
            @endforeach
        ];

        $(document).ready(function () {
            initHrEmployeAutocomplete({
                inputId: 'filter_employe_search',
                hiddenId: 'filter_employe_id',
                listId: 'filter_employe_search-list',
                employees: filterEmployees,
                allowEmpty: true
            });
        });
     </script>
@endsection