
@extends('backend.layouts.master')

@section('title')
@lang('messages.fund transfer list') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.fund transfer list')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.fund transfer list')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('fund_transfer.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.accounting-fund-transfer.create') }}">@lang('messages.new')</a>
                        @endif
                    </p>
                    <div class="clearfix"></div>
                    <form action="{{ route('admin.accounting-fund-transfer.index') }}" method="GET" class="mb-3">
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
                                    <a href="{{ route('admin.accounting-fund-transfer.index') }}" class="btn btn-secondary">@lang('messages.Réinitialiser')</a>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="data-tables">
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.Code')</th>
                                    <th width="10%">@lang('messages.Date')</th>
                                    <th width="10%">@lang('messages.Transfer Type')</th>
                                    <th width="12%">@lang('messages.Origin Account')</th>
                                    <th width="12%">@lang('messages.Destination Account')</th>
                                    <th width="10%">@lang('messages.Amount')</th>
                                    <th width="10%">@lang('messages.Status')</th>
                                    <th width="21%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($fund_transfers as $fund_transfer)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $fund_transfer->code ?? 'N/A' }}</td>
                                    <td>{{ $fund_transfer->date ? \Carbon\Carbon::parse($fund_transfer->date)->format('d/m/Y') : 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $fund_transfer->transfer_type == 'INTERNAL' ? 'info' : 'warning' }}">
                                            {{ $fund_transfer->transfer_type ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($fund_transfer->originAccount)
                                            {{ $fund_transfer->originAccount->account_number }} - {{ Str::limit($fund_transfer->originAccount->account_name, 20) }}
                                        @else
                                            <span class="text-danger">@lang('messages.N/A')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($fund_transfer->DestinationAccount)
                                            {{ $fund_transfer->DestinationAccount->account_number }} - {{ Str::limit($fund_transfer->DestinationAccount->account_name, 20) }}
                                        @else
                                            <span class="text-danger">@lang('messages.N/A')</span>
                                        @endif
                                    </td>
                                    <td><strong>{{ number_format($fund_transfer->amount ?? 0, 2) }}</strong></td>
                                    <td>
                                        @if($fund_transfer->status == 0)
                                            <span class="badge badge-secondary">@lang('messages.Draft')</span>
                                        @elseif($fund_transfer->status == 1)
                                            <span class="badge badge-info">@lang('messages.Validated')</span>
                                        @elseif($fund_transfer->status == 2)
                                            <span class="badge badge-warning">@lang('messages.Confirmed')</span>
                                        @elseif($fund_transfer->status == 3)
                                            <span class="badge badge-success">@lang('messages.Approved')</span>
                                        @else
                                            <span class="badge badge-secondary">@lang('messages.Unknown')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('fund_transfer.view'))
                                            <a class="btn btn-info text-white btn-sm" href="{{ route('admin.accounting-fund-transfer.show', $fund_transfer->id) }}" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a class="btn btn-warning text-white btn-sm" href="{{ route('admin.accounting-fund-transfer.print', $fund_transfer->id) }}" target="_blank" title="Print">
                                                <i class="fa fa-print"></i>
                                            </a>
                                        @endif
                                        
                                        @if ($fund_transfer->status == 0 && Auth::guard('admin')->user()->can('fund_transfer.edit'))
                                            <a class="btn btn-success text-white btn-sm" href="{{ route('admin.accounting-fund-transfer.edit', $fund_transfer->id) }}" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
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