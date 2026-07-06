
@extends('backend.layouts.master')

@section('title')
@lang('messages.Retour de fonds') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Retour de fonds')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Liste des retours de fonds')</h4>
                    <div class="clearfix"></div>
                    <form action="{{ route('admin.accounting-return-fund.index') }}" method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="date_debut">@lang('messages.Date-heure début')</label>
                                <input type="datetime-local" class="form-control" id="date_debut" name="date_debut" value="{{ request('date_debut') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="date_fin">@lang('messages.Date-heure fin')</label>
                                <input type="datetime-local" class="form-control" id="date_fin" name="date_fin" value="{{ request('date_fin') }}">
                            </div>
                            <div class="col-md-4">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-info">@lang('messages.Filtrer')</button>
                                    <a href="{{ route('admin.accounting-return-fund.index') }}" class="btn btn-secondary">@lang('messages.Réinitialiser')</a>
                                </div>
                            </div>
                        </div>
                    </form>
                    @include('backend.layouts.partials.messages')
                    <div class="data-tables">
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="12%">@lang('messages.N° Retour')</th>
                                    <th width="10%">@lang('messages.Date')</th>
                                    <th width="12%">@lang('messages.N° Sortie')</th>
                                    <th width="12%">@lang('messages.Employé')</th>
                                    <th width="12%">@lang('messages.Compte')</th>
                                    <th width="10%">@lang('messages.Montant Sorti')</th>
                                    <th width="10%">@lang('messages.Montant Retourné')</th>
                                    <th width="10%">@lang('messages.Status')</th>
                                    <th width="17%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($return_funds as $return_fund)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $return_fund->return_no ?? 'N/A' }}</td>
                                    <td>{{ $return_fund->date ? \Carbon\Carbon::parse($return_fund->date)->format('d/m/Y') : 'N/A' }}</td>
                                    <td>{{ $return_fund->cash_outflow_no ?? 'N/A' }}</td>
                                    <td>
                                        @if($return_fund->employe)
                                            {{ $return_fund->employe->firstname }} {{ $return_fund->employe->lastname }}
                                        @else
                                            <span class="text-danger">@lang('messages.N/A')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($return_fund->account)
                                            {{ $return_fund->account->account_number }} - {{ Str::limit($return_fund->account->account_name, 20) }}
                                        @else
                                            <span class="text-danger">@lang('messages.N/A')</span>
                                        @endif
                                    </td>
                                    <td><strong>{{ number_format($return_fund->total_amount_picked ?? 0, 2) }}</strong></td>
                                    <td><strong>{{ number_format($return_fund->total_amount_returned ?? 0, 2) }}</strong></td>
                                    <td>
                                        @if($return_fund->status == '0')
                                            <span class="badge badge-secondary">@lang('messages.Brouillon')</span>
                                        @elseif($return_fund->status == '1')
                                            <span class="badge badge-info">@lang('messages.Validé')</span>
                                        @elseif($return_fund->status == '2')
                                            <span class="badge badge-warning">@lang('messages.Confirmé')</span>
                                        @elseif($return_fund->status == '3')
                                            <span class="badge badge-success">@lang('messages.Approuvé')</span>
                                        @else
                                            <span class="badge badge-secondary">@lang('messages.Inconnu')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('fund_transfer.view'))
                                            <a class="btn btn-info text-white btn-sm" href="{{ route('admin.accounting-return-fund.show', $return_fund->id) }}" title="Voir">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a class="btn btn-warning text-white btn-sm" href="{{ route('admin.accounting-return-fund.print', $return_fund->id) }}" target="_blank" title="Imprimer">
                                                <i class="fa fa-print"></i>
                                            </a>
                                        @endif
                                        
                                        @if ($return_fund->status != '3' && Auth::guard('admin')->user()->can('fund_transfer.edit'))
                                            <a class="btn btn-success text-white btn-sm" href="{{ route('admin.accounting-return-fund.edit', $return_fund->id) }}" title="Modifier">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        @endif

                                        @if ($return_fund->status != '3' && Auth::guard('admin')->user()->can('fund_transfer.delete'))
                                            <form action="{{ route('admin.accounting-return-fund.destroy', $return_fund->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm(@json(__('messages.accounting_delete_return_fund_confirm')));">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger text-white btn-sm" title="Supprimer">
                                                    <i class="fa fa-trash"></i>
                                                </button>
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