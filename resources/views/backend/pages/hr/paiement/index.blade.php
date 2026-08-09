
@extends('backend.layouts.master')

@section('title')
@lang('messages.Liste de paies pour un mois dernier') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Liste de paies pour un mois dernier')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Liste de paies pour un mois dernier')</h4>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.Mois/Année')</th>
                                    <th width="10%">@lang('messages.No Matricule')</th>
                                    <th width="10%">@lang('messages.Employé')</th>
                                    <th width="10%">@lang('messages.Fonction')</th>
                                    <th width="10%">@lang('messages.Somme S.Base')</th>
                                    <th width="10%">@lang('messages.Salaire Brut Imposable')</th>
                                    <th width="10%">@lang('messages.Salaire Brut Non Imposable')</th>
                                    <th width="10%">@lang('messages.Salaire Net Imposable')</th>
                                    <th width="10%">@lang('messages.Salaire Net Non Imposable')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($paiements as $paiement)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>
                                        {{ $paiement->date_debut ? \Carbon\Carbon::parse($paiement->date_debut)->format('m/Y') : 'N/A' }}
                                        @if($paiement->etat == 0)
                                            <span class="badge badge-primary">@lang('messages.Encours...')</span>
                                        @elseif($paiement->etat == 1)
                                            <span class="badge badge-success">@lang('messages.Déjà clôturé')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($paiement->employe_id && $paiement->employe)
                                            <a href="{{ route('admin.hr-paiements.show', $paiement->id) }}">
                                                {{ $paiement->employe->matricule_no }}
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        @if($paiement->employe)
                                            {{ $paiement->employe->firstname }}&nbsp;{{ $paiement->employe->lastname }}
                                        @endif
                                    </td>
                                    <td>{{ optional(optional($paiement->employe)->fonction)->name }}</td>
                                    <td>{{ number_format($paiement->somme_salaire_base ?? 0, 0, ',', ' ') }}</td>
                                    <td>{{ number_format($paiement->somme_salaire_brut_imposable ?? 0, 0, ',', ' ') }}</td>
                                    <td>{{ number_format($paiement->somme_salaire_brut_non_imposable ?? 0, 0, ',', ' ') }}</td>
                                    <td>{{ number_format($paiement->somme_salaire_net_imposable ?? 0, 0, ',', ' ') }}</td>
                                    <td>{{ number_format($paiement->somme_salaire_net_non_imposable ?? 0, 0, ',', ' ') }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('hr_paiement.create'))
                                            <a href="{{ route('admin.hr-fiche-paie.print', $paiement->id) }}"
                                               class="btn btn-info btn-sm text-white"
                                               target="_blank"
                                               title="@lang('messages.Imprimer')">
                                                <i class="fa fa-print"></i>
                                            </a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_paiement.edit') && $paiement->etat != 1 && $paiement->employe_id)
                                            <a href="{{ route('admin.hr-paiements.edit', [$paiement->id, $paiement->company_id]) }}" class="btn btn-success btn-sm">
                                                @lang('messages.Modifier')
                                            </a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('hr_paiement.delete') && $paiement->etat != 1)
                                            <a class="btn btn-danger btn-sm text-white" href="{{ route('admin.hr-paiements.destroy', $paiement->id) }}"
                                            onclick="event.preventDefault(); if(confirm(@json(__('messages.Êtes-vous sûr de vouloir supprimer cet élément ?')))) { document.getElementById('delete-form-{{ $paiement->id }}').submit(); }">
                                                @lang('messages.Supprimer')
                                            </a>

                                            <form id="delete-form-{{ $paiement->id }}" action="{{ route('admin.hr-paiements.destroy', $paiement->id) }}" method="POST" style="display: none;">
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
