
@extends('backend.layouts.master')

@section('title')
@lang('messages.Sortie de fonds') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Sortie de fonds')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Sortie de fonds')</h4>
                    <div class="clearfix"></div>
                    <form action="{{ route('admin.cash-out-flow.index') }}" method="GET" class="mb-3">
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
                                    <a href="{{ route('admin.cash-out-flow.index') }}" class="btn btn-secondary">@lang('messages.Réinitialiser')</a>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.date')</th>
                                    <th width="10%">@lang('messages.cash out flow No')</th>
                                    <th width="10%">@lang('messages.cash out flow R. No')</th>
                                    <th width="10%">@lang('messages.cash out flow Signature')</th>
                                    <th width="10%">@lang('messages.status')</th>
                                    <th width="10%">@lang('messages.Asker')</th>
                                    <th width="10%">@lang('messages.Currency')</th>
                                    <th width="30%">
                                        @lang('messages.description')</th>
                                    <th width="10%">@lang('messages.created_by')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($datas as $data)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($data->date)->format('d/m/Y') }}</td>
                                    <td><a href="{{ route('admin.cash-out-flow.show',$data->cash_outflow_no) }}">{{ $data->cash_outflow_no }}</a></td>
                                    <td><a href="{{ route('admin.cash-out-flow-request.show',$data->cash_outflow_request_no) }}">{{ $data->cash_outflow_request_no }}</a></td>
                                    <td>{{ $data->cash_outflow_signature }}</td>
                                    @if($data->status == 2)
                                    <td><span class="badge badge-success">@lang('messages.Validated')</span></td>
                                    @elseif($data->status == 1 || $data->status == 0)
                                    <td><span class="badge badge-secondary">@lang('messages.Draft')</span></td>
                                    @elseif($data->status == 3)
                                    <td><span class="badge badge-info">@lang('messages.Confirmed')</span></td>
                                    @elseif($data->status == 4)
                                    <td><span class="badge badge-success">@lang('messages.Approved')</span>
                                        @if(isset($data->flag1) && $data->flag1 == 0)
                                        <br><small class="badge badge-info">@lang('messages.Clôturée')</small>
                                        @elseif(isset($data->flag2) && $data->flag2 == 1)
                                        <br><small class="badge badge-info">@lang('messages.Les fonds sont déjà sorti')</small>
                                        @endif
                                    </td>
                                    @elseif($data->status == 40)
                                    <td><span class="badge badge-success">@lang('messages.Clôturée sans retour')</span></td>
                                    @elseif($data->status == 5)
                                    <td><span class="badge badge-warning">@lang('messages.Clôturée avec retour encours...')</span></td>
                                    @elseif($data->status == 6)
                                    <td><span class="badge badge-success">@lang('messages.Clôturée avec retour')</span></td>
                                    @else
                                    <td><span class="badge badge-primary">@lang('messages.In Progress...')</span></td>
                                    @endif
                                    <td>{{ $data->asker }}</td>
                                    <td>@if($data->currency_id){{ $data->currency->currency_name }} @endif</td>
                                    <td>{{ $data->description }}</td>
                                    <td>{{ $data->created_by }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('account_cash_ouflow.print'))
                                        <a href="{{ route('admin.cash-out-flow.document',$data->cash_outflow_no) }}"><img src="{{ asset('img/ISSh.gif') }}" width="60" title="Télécharger d'abord le document et puis imprimer"></a>
                                        @endif
                                            @if (Auth::guard('admin')->user()->can('account_cash_ouflow.validate'))
                                            @if($data->status == 1 || $data->status == -1 || $data->status == 0)
                                                <a class="btn btn-primary text-white" href="{{ route('admin.cash-out-flow.validate', $data->cash_outflow_no) }}"
                                                onclick="event.preventDefault(); document.getElementById('validate-form-{{ $data->cash_outflow_no }}').submit();">
                                                    Valider
                                                </a>

                                                <form id="validate-form-{{ $data->cash_outflow_no }}" action="{{ route('admin.cash-out-flow.validate', $data->cash_outflow_no) }}" method="POST" style="display: none;">
                                                    @method('PUT')
                                                    @csrf
                                                </form>
                                            @endif
                                            @endif
                                            @if (Auth::guard('admin')->user()->can('account_cash_ouflow.confirm'))
                                            @if($data->status == 2)
                                                <a class="btn btn-primary text-white" href="{{ route('admin.cash-out-flow.confirm', $data->cash_outflow_no) }}"
                                                onclick="event.preventDefault(); document.getElementById('confirm-form-{{ $data->cash_outflow_no }}').submit();">
                                                    Confirmer
                                                </a>

                                                <form id="confirm-form-{{ $data->cash_outflow_no }}" action="{{ route('admin.cash-out-flow.confirm', $data->cash_outflow_no) }}" method="POST" style="display: none;">
                                                    @method('PUT')
                                                    @csrf
                                                </form>
                                            @endif
                                            @endif
                                            @if (Auth::guard('admin')->user()->can('account_cash_ouflow.approuve'))
                                            @if($data->status == 3)
                                                <a class="btn btn-primary text-white" href="{{ route('admin.cash-out-flow.approuve', $data->cash_outflow_no) }}"
                                                onclick="event.preventDefault(); document.getElementById('approuve-form-{{ $data->cash_outflow_no }}').submit();">
                                                    Approuver
                                                </a>

                                                <form id="approuve-form-{{ $data->cash_outflow_no }}" action="{{ route('admin.cash-out-flow.approuve', $data->cash_outflow_no) }}" method="POST" style="display: none;">
                                                    @method('PUT')
                                                    @csrf
                                                </form>
                                            @endif
                                            @endif
                                            @if($data->status == 4)
                                            @if (Auth::guard('admin')->user()->can('fund_transfer.create'))
                                                <a class="btn btn-info text-white" href="#" 
                                                   onclick="event.preventDefault(); handleReturnFund('{{ $data->cash_outflow_no }}');">
                                                    @lang('messages.Retour de fonds')
                                                </a>
                                                <form id="no-return-form-{{ $data->cash_outflow_no }}" action="{{ route('admin.cash-out-flow.mark-as-no-return', $data->cash_outflow_no) }}" method="POST" style="display: none;">
                                                    @method('PUT')
                                                    @csrf
                                                </form>
                                            @endif
                                            @endif
                                            @if (Auth::guard('admin')->user()->can('account_cash_ouflow.reject'))
                                                @if($data->status == 1 || $data->status == 2 || $data->status == 3)
                                                <a class="btn btn-primary text-white" href="{{ route('admin.cash-out-flow.reject', $data->cash_outflow_no) }}"
                                                onclick="event.preventDefault(); document.getElementById('reject-form-{{ $data->cash_outflow_no }}').submit();">
                                                    Rejeter
                                                </a>
                                                @endif
                                                <form id="reject-form-{{ $data->cash_outflow_no }}" action="{{ route('admin.cash-out-flow.reject', $data->cash_outflow_no) }}" method="POST" style="display: none;">
                                                    @method('PUT')
                                                    @csrf
                                                </form>
                                            @endif
                                            @if (Auth::guard('admin')->user()->can('account_cash_ouflow.reset'))
                                                @if($data->status == -1 || $data->status == 2 || $data->status == 3 || $data->status == 4)
                                                <a class="btn btn-primary text-white" href="{{ route('admin.cash-out-flow.reset', $data->cash_outflow_no) }}"
                                                onclick="event.preventDefault(); document.getElementById('reset-form-{{ $data->cash_outflow_no }}').submit();">
                                                    Annuler
                                                </a>
                                                @endif
                                                <form id="reset-form-{{ $data->cash_outflow_no }}" action="{{ route('admin.cash-out-flow.reset', $data->cash_outflow_no) }}" method="POST" style="display: none;">
                                                    @method('PUT')
                                                    @csrf
                                                </form>
                                            @endif
                                            @if (Auth::guard('admin')->user()->can('account_cash_ouflow.delete'))
                                                @if(!isset($data->flag2) || $data->flag2 != 1)
                                                <a class="btn btn-danger text-white" href="{{ route('admin.cash-out-flow.destroy', $data->cash_outflow_no) }}"
                                                onclick="event.preventDefault(); if(confirm(@json(__('messages.delete_confirm_irreversible')))) { document.getElementById('delete-form-{{ $data->cash_outflow_no }}').submit(); }">
                                                    @lang('messages.delete')
                                                </a>
                                                @endif

                                                <form id="delete-form-{{ $data->cash_outflow_no }}" action="{{ route('admin.cash-out-flow.destroy', $data->cash_outflow_no) }}" method="POST" style="display: none;">
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

    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () {
        null
    };

    function handleReturnFund(cashOutflowNo) {
        if (confirm(@json(__('messages.cash_outflow_return_fund_prompt')))) {
            // User accepted - redirect to create return fund page
            window.location.href = '{{ route("admin.accounting-return-fund.create", ":cash_outflow_no") }}'.replace(':cash_outflow_no', cashOutflowNo);
        } else {
            // User cancelled - mark as no return (status = 40)
            document.getElementById('no-return-form-' + cashOutflowNo).submit();
        }
    }

     </script>
@endsection