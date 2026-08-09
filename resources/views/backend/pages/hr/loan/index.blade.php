
@extends('backend.layouts.master')

@section('title')
@lang('Loans List') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('Loans List')</h4>
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
                    <h4 class="header-title float-left">Loans List</h4>
                    <p class="text-muted small mb-0">Un employé ne peut pas prendre une nouvelle avance tant qu'il a une dette en attente ou partiellement payée (toutes les tranches doivent être soldées).</p>
                    <p class="float-right mb-2">
                            <a class="btn btn-success text-white" href="{{ route('admin.hr-loans.create') }}"><i class="fa fa-plus-square" title="Ajouter" aria-hidden="false"></i></a>
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">Date</th>
                                    <th width="18%">Employé</th>
                                    <th width="12%">Montant</th>
                                    <th width="8%">Tranches</th>
                                    <th width="10%">Statut</th>
                                    <th width="15%">État de Paiement</th>
                                    <th width="20%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($loans as $loan)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>
                                        @if($loan->date)
                                            {{ \Carbon\Carbon::parse($loan->date)->format('d/m/Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if($loan->employe)
                                            {{ $loan->employe->matricule_no }} - {{ $loan->employe->firstname }} {{ $loan->employe->lastname }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ number_format($loan->amount_loan, 2, ',', ' ') }} FBU</td>
                                    <td>{{ $loan->nbre_tranche }} mois</td>
                                    <td>
                                        @if($loan->status == 0)
                                            <span class="badge badge-warning">En Attente</span>
                                        @elseif($loan->status == 1)
                                            <span class="badge badge-info">Validé</span>
                                        @elseif($loan->status == 2)
                                            <span class="badge badge-success">Confirmé</span>
                                        @elseif($loan->status == 3)
                                            <span class="badge badge-success">Approuvé</span>
                                        @elseif($loan->status == 4)
                                            <span class="badge badge-success">Approuvé et Sorti</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $flag1 = $loan->flag1 ?? '0';
                                            $flag2 = $loan->flag2 ?? '0';
                                            // Convert to string for comparison (they are stored as strings)
                                            $flag1 = (string)$flag1;
                                            $flag2 = (string)$flag2;
                                        @endphp
                                        @if($flag1 == '0' && $flag2 == '0')
                                            <span class="badge badge-warning">En attente de paiement</span>
                                        @elseif($flag1 == '1' && $flag2 == '0')
                                            <span class="badge badge-info">Le premier mois est déjà payé</span>
                                        @elseif($flag1 == '1' && $flag2 == '1')
                                            <span class="badge badge-primary">Le second mois est déjà payé</span>
                                        @elseif($flag1 == '1' && $flag2 == '2')
                                            <span class="badge badge-success">L'avance sur salaire est totalement payée</span>
                                        @else
                                            <span class="badge badge-secondary">En attente de paiement</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('hr_loan.view'))
                                            <a class="btn btn-info text-white btn-sm" href="{{ route('admin.hr-loans.show', $loan->id) }}">
                                                <i class="fa fa-eye" title="Voir" aria-hidden="false"></i>
                                            </a>
                                        @endif

                                        @if ($loan->status != 3 && Auth::guard('admin')->user()->can('hr_loan.edit'))
                                            <a class="btn btn-success text-white btn-sm" href="{{ route('admin.hr-loans.edit', $loan->id) }}">
                                                <i class="fa fa-edit" title="Modifier" aria-hidden="false"></i>
                                            </a>
                                        @endif

                                        @if ($loan->status == 3 && Auth::guard('admin')->user()->can('account_cash_ouflow_request.create'))
                                            <a class="btn btn-primary text-white btn-sm" href="{{ route('admin.cash-out-flow-request.loan-create', $loan->id) }}" title="Demande de sortie de fonds">
                                                <i class="fa fa-money-bill"></i> Demande de sortie de fonds
                                            </a>
                                        @endif

                                        @if ($loan->status != 3 && Auth::guard('admin')->user()->can('hr_loan.delete'))
                                            <a class="btn btn-danger text-white btn-sm" href="{{ route('admin.hr-loans.destroy', $loan->id) }}"
                                            onclick="event.preventDefault(); if(confirm('Êtes-vous sûr de vouloir supprimer cette demande?')) { document.getElementById('delete-form-{{ $loan->id }}').submit(); }">
                                                <i class="fa fa-trash" title="Supprimer" aria-hidden="false"></i>
                                            </a>

                                            <form id="delete-form-{{ $loan->id }}" action="{{ route('admin.hr-loans.destroy', $loan->id) }}" method="POST" style="display: none;">
                                                @method('DELETE')
                                                @csrf
                                            </form>
                                        @endif
                                        
                                        @if (Auth::guard('admin')->user()->can('hr_loan.edit') && !$loan->isFullyPaid())
                                            @php
                                                $flag1Action = (string) ($loan->flag1 ?? '0');
                                                $flag2Action = (string) ($loan->flag2 ?? '0');
                                            @endphp

                                            {{-- Marquer 1ère tranche payée --}}
                                            @if($loan->amount_to_pay_1m > 0 && $flag1Action === '0' && $loan->status == 4)
                                                <form action="{{ route('admin.hr-loans.mark-first-tranche', $loan->id) }}" method="POST" style="display:inline-block; margin-top: 5px;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-info btn-sm">1ère tranche payée</button>
                                                </form>
                                            @endif

                                            {{-- Marquer 2ème tranche payée --}}
                                            @if($loan->amount_to_pay_2m > 0 && $flag1Action === '1' && $flag2Action === '0' && $loan->status == 4)
                                                <form action="{{ route('admin.hr-loans.mark-second-tranche', $loan->id) }}" method="POST" style="display:inline-block; margin-top: 5px;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-primary btn-sm">2ème tranche payée</button>
                                                </form>
                                            @endif

                                            {{-- Marquer toutes les tranches payées --}}
                                            <form action="{{ route('admin.hr-loans.mark-all-tranches', $loan->id) }}" method="POST" style="display:inline-block; margin-top: 5px;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success btn-sm">Toutes les tranches payées</button>
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