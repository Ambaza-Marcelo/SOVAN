
@extends('backend.layouts.master')

@section('title')
@lang('messages.production order') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.production order')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.production order')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('production_order.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.production-order.create') }}">
                                <i class="fa fa-plus"></i> @lang('messages.new')
                            </a>
                        @endif
                    </p>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials._index_filter_export', ['filterRoute' => 'admin.production-order.index', 'exportRoute' => 'admin.production-order.export-to-excel', 'useDateType' => 'date'])
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.date')</th>
                                    <th width="12%">@lang('messages.Production No')</th>
                                    <th width="15%">@lang('messages.Production Signature')</th>
                                    <th width="10%">@lang('messages.status')</th>
                                    <th width="15%">@lang('messages.Work Center')</th>
                                    <th width="10%">@lang('messages.Total Cost')</th>
                                    <th width="10%">@lang('messages.created_by')</th>
                                    <th width="13%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($production_orders as $order)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($order->production_date)->format('d/m/Y') }}</td>
                                    <td><a href="{{ route('admin.production-order.show', $order->prodution_number) }}">{{ $order->prodution_number }}</a></td>
                                    <td>{{ $order->production_signature }}</td>
                                    @if($order->status == '1' || $order->status == 1)
                                    <td><span class="badge badge-success">@lang('messages.Validé')</span></td>
                                    @elseif($order->status == '-1' || $order->status == -1)
                                    <td><span class="badge badge-danger">@lang('messages.Rejeté')</span></td>
                                    @elseif($order->status == '2' || $order->status == 2)
                                    <td><span class="badge badge-info">@lang('messages.Confirmé')</span></td>
                                    @elseif($order->status == '4' || $order->status == 4)
                                    <td><span class="badge badge-success">@lang('messages.Approuvé')</span></td>
                                    @else
                                    <td><span class="badge badge-primary">@lang('messages.En cours...')</span></td>
                                    @endif
                                    <td>{{ $order->work_center ?? 'N/A' }}</td>
                                    <td>{{ number_format($order->total_estimated_cost ?? 0, 0, ',', ' ') }} BIF</td>
                                    <td>{{ $order->created_by }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('production_order.view'))
                                            <a class="btn btn-info btn-sm" href="{{ route('admin.production-order.show', $order->prodution_number) }}" title="@lang('messages.Voir')">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('production_order.edit'))
                                            @if($order->status != '4' && $order->status != 4)
                                                <a class="btn btn-primary btn-sm" href="{{ route('admin.production-order.edit', $order->prodution_number) }}" title="@lang('messages.Modifier')">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('production_order.validate'))
                                            @if($order->status == '0' || $order->status == 0 || $order->status == '-1' || $order->status == -1)
                                                <form id="validate-form-{{ $order->prodution_number }}" action="{{ route('admin.production-order.validate', $order->prodution_number) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-success btn-sm" title="@lang('messages.Valider')" onclick="return confirm(@json(__('messages.Valider cet ordre de production ?')))">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('production_order.confirm'))
                                            @if($order->status == '1' || $order->status == 1)
                                                <form id="confirm-form-{{ $order->prodution_number }}" action="{{ route('admin.production-order.confirm', $order->prodution_number) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-info btn-sm" title="@lang('messages.Confirmer')" onclick="return confirm(@json(__('messages.Confirmer cet ordre de production ?')))">
                                                        <i class="fa fa-check-circle"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('production_order.approve'))
                                            @if($order->status == '2' || $order->status == 2)
                                                <form id="approve-form-{{ $order->prodution_number }}" action="{{ route('admin.production-order.approve', $order->prodution_number) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-success btn-sm" title="@lang('messages.Approuver')" onclick="return confirm(@json(__('messages.Approuver cet ordre de production ?')))">
                                                        <i class="fa fa-check-double"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('production_order.delete'))
                                            @if($order->status != '4' && $order->status != 4)
                                                <form id="delete-form-{{ $order->prodution_number }}" action="{{ route('admin.production-order.destroy', $order->prodution_number) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="@lang('messages.Delete')" onclick="return confirm(@json(__('messages.Supprimer cet ordre de production ?')))">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
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

    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () {
        null
    };

     </script>
@endsection
