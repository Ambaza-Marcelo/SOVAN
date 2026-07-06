
@extends('backend.layouts.master')

@section('title')
@lang('messages.Fiche des paies') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Fiche des paies')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Fiche des paies')</h4>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.No Matricule')</th>
                                    <th width="10%">@lang('messages.Nom')</th>
                                    <th width="10%">@lang('messages.Prenom')</th>
                                    <th width="10%">@lang('messages.Departement')</th>
                                    <th width="10%">@lang('messages.Fonction')</th>
                                    <th width="10%">@lang('messages.Banque')</th>
                                    <th width="10%">@lang('messages.Numero Compte')</th>
                                    <th width="10%">@lang('messages.Salaire Net')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($paiements as $paiement)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td> <a href="{{ route('admin.hr-employes.show',$paiement->id) }}">{{ $paiement->matricule_no }}</a> </td>
                                    <td>{{ $paiement->firstname }}</td>
                                    <td>{{ $paiement->lastname }}</td>
                                    <td>{{ $paiement->grade->name }}</td>
                                    <td>{{ $paiement->departement->name }}</td>
                                    <td>{{ $paiement->fonction->name }}</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('hr_paiement.edit'))
                                            <a class="btn btn-success text-white" href="{{ route('admin.hr-paiements.edit', $paiement->id) }}">Editer</a>
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
<!-- page title area end -->
<div class="row">
    <div class="col-md-12">
        <div class="main-content-inner">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title float-left">@lang('messages.Journal de paies')</h4>
                    <p class="float-right mb-2">
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#journalPaieModal" data-whatever="@mdo"><i class="fa fa-plus-square" title="Ajouter" aria-hidden="false"></i></button>
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">@lang('messages.Code')</th>
                                    <th width="20%">@lang('messages.Titre')</th>
                                    <th width="20%">@lang('messages.Date Debut')</th>
                                    <th width="20%">@lang('messages.Date Cloture')</th>
                                    <th width="20%">@lang('messages.Etat')</th>
                                    <th width="20%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($journal_paies as $journal_paie)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $journal_paie->code }}</td>
                                    <td>{{ $journal_paie->title }}</td>
                                    <td>{{ $journal_paie->date_debut }}</td>
                                    <td>{{ $journal_paie->date_fin }}</td>
                                    <td>{{ $journal_paie->etat }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('hr_journal_paie.edit'))
                                            <a class="btn btn-success text-white" href="{{ route('admin.hr-journal-paies.edit', $journal_paie->id) }}"><i class="fa fa-edit" title="Modifier" aria-hidden="false"></i></a>
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('hr_journal_paie.delete'))
                                            <a class="btn btn-danger text-white" href="{{ route('admin.hr-journal-paies.destroy', $journal_paie->id) }}"
                                            onclick="event.preventDefault(); if(confirm(@json(__("messages.delete_confirm_irreversible")))) { document.getElementById('delete-form-{{ $journal_paie->id }}').submit(); }">
                                                <i class="fa fa-trash" title="Supprimer" aria-hidden="false"></i>
                                            </a>

                                            <form id="delete-form-{{ $journal_paie->id }}" action="{{ route('admin.hr-journal-paies.destroy', $journal_paie->id) }}" method="POST" style="display: none;">
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
    </div>
</div>
@include('backend.pages.hr.journal_paie.create')

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