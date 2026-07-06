
@extends('backend.layouts.master')

@section('title')
@lang('messages.purchase order') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.purchase order')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.purchase orders List')</h4>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials._index_filter_export', ['filterRoute' => 'admin.raw-material-supplier-orders.index', 'exportRoute' => 'admin.raw-material-supplier-orders.export-to-excel', 'useDateType' => 'datetime-local'])
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.date')</th>
                                    <th width="10%">@lang('messages.Order No')</th>
                                    <th width="10%">@lang('messages.Order Signature')</th>
                                    <th width="10%">@lang('messages.Purchase No')</th>
                                    <th width="10%">@lang('messages.Fournisseur')</th>
                                    <th width="5%">@lang('messages.status')</th>
                                    <th width="20%">@lang('messages.description')</th>
                                    <th width="10%">@lang('messages.created_by')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($orders as $order)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($order->date)->format('d/m/Y') }}</td>
                                    <td><a href="{{ route('admin.raw-material-supplier-orders.show',$order->order_no)}}">{{ $order->order_no }}</a></td>
                                    <td>{{ $order->order_signature }}</td>
                                    <td>{{ $order->purchase_no }}</td>
                                    <td>@if($order->supplier_id)Supplier: {{ $order->supplier->supplier_name }}@endif</td>
                                    <td>
                                        @if($order->status == 1)
                                            <span class="badge badge-warning">@lang('messages.Encours')</span>
                                        @elseif($order->status == 2)
                                            <span class="badge badge-primary">@lang('messages.Validé')</span>
                                        @elseif($order->status == 3)
                                            <span class="badge badge-info">@lang('messages.Confirmé')</span>
                                        @elseif($order->status == -3)
                                            <span class="badge badge-warning">@lang('messages.Modifié')</span>
                                        @elseif($order->status == 4 && $order->flag2 == 0)
                                            <span class="badge badge-primary">@lang('messages.Approuvé')</span>
                                        @elseif($order->status == 4 && $order->flag2 == 1)
                                            <span class="badge badge-success">@lang('messages.Approuvé et déjà payé')</span>
                                        @elseif($order->status == 5 && $order->flag2 == 0)
                                            <span class="badge badge-success">@lang('messages.Reception Totale')</span>
                                        @elseif($order->status == 5 && $order->flag2 == 1)
                                            <span class="badge badge-success">@lang('messages.Reception Totale et déjà payée')</span>
                                        @elseif($order->status == -5)
                                            <span class="badge badge-info">@lang('messages.Reception Partielle')</span>
                                        @elseif($order->status == -1)
                                            <span class="badge badge-danger">@lang('messages.Rejeté')</span>
                                        @else
                                            <span class="badge badge-success">@lang('messages.Receptionné')</span>
                                        @endif
                                    </td>
                                    <td>{{ $order->description }}</td>
                                    <td>{{ $order->created_by }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('raw_material_supplier_order.validate'))
                                        @if($order->status == 1)
                                            <a class="btn btn-primary text-white" href="{{ route('admin.raw-material-supplier-orders.validate', $order->order_no) }}"
                                            onclick="event.preventDefault(); document.getElementById('validate-form-{{ $order->order_no }}').submit();">
                                                Valider
                                            </a>

                                            <form id="validate-form-{{ $order->order_no }}" action="{{ route('admin.raw-material-supplier-orders.validate', $order->order_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_supplier_order.confirm'))
                                        @if($order->status == 2)
                                            <a class="btn btn-primary text-white" href="{{ route('admin.raw-material-supplier-orders.confirm', $order->order_no) }}"
                                            onclick="event.preventDefault(); document.getElementById('confirm-form-{{ $order->order_no }}').submit();">
                                                Confirmer
                                            </a>

                                            <form id="confirm-form-{{ $order->order_no }}" action="{{ route('admin.raw-material-supplier-orders.confirm', $order->order_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_supplier_order.approuve'))
                                        @if($order->status == 3)
                                            <a class="btn btn-primary text-white" href="{{ route('admin.raw-material-supplier-orders.approuve', $order->order_no) }}"
                                            onclick="event.preventDefault(); document.getElementById('approuve-form-{{ $order->order_no }}').submit();">
                                                Approuver
                                            </a>

                                            <form id="approuve-form-{{ $order->order_no }}" action="{{ route('admin.raw-material-supplier-orders.approuve', $order->order_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_supplier_order.reject'))
                                            @if($order->status == 1 || $order->status == 2 || $order->status == 3)
                                            <a class="btn btn-primary text-white" href="{{ route('admin.raw-material-supplier-orders.reject', $order->order_no) }}"
                                            onclick="event.preventDefault(); document.getElementById('reject-form-{{ $order->order_no }}').submit();">
                                                Rejeter
                                            </a>
                                            @endif
                                            <form id="reject-form-{{ $order->order_no }}" action="{{ route('admin.raw-material-supplier-orders.reject', $order->order_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_supplier_order.reset'))
                                            @if($order->status == -1 || $order->status == 2 || $order->status == 3 || $order->status == 4)
                                            <a class="btn btn-primary text-white" href="{{ route('admin.raw-material-supplier-orders.reset', $order->order_no) }}"
                                            onclick="event.preventDefault(); document.getElementById('reset-form-{{ $order->order_no }}').submit();">
                                                Annuler
                                            </a>
                                            @endif
                                            <form id="reset-form-{{ $order->order_no }}" action="{{ route('admin.raw-material-supplier-orders.reset', $order->order_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_reception.create'))
                                        @if($order->status == 4 && $order->flag1 == 0)
                                        <a href="{{ route('admin.raw-material-reception-without-remaining.create',$order->order_no)}}" class="btn btn-success">Receptionner Totalement</a>
                                        @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_reception.edit'))
                                        @if($order->status == 4 && $order->flag1 == 1)
                                        <a href="{{ route('admin.raw-material-reception.edit',$order->order_no)}}" class="btn btn-primary">Modifier la reception</a>
                                        @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('account_cash_ouflow_request.create'))
                                        @if($order->status == 4 && $order->flag2 == 0)
                                        <a href="{{ route('admin.raw-material-purchase-payment-request-before.create',$order->order_no)}}" class="btn btn-success">Demande de Sortie de fonds</a>
                                        @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_supplier_order.delete'))
                                            @if($order->status == -1 || $order->status == 1)
                                            <a class="btn btn-danger text-white" href="{{ route('admin.raw-material-supplier-orders.destroy', $order->order_no) }}"
                                            onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this item? This action is irreversible.')) { document.getElementById('delete-form-{{ $order->order_no }}').submit(); }">
                                                @lang('messages.delete')
                                            </a>

                                            <form id="delete-form-{{ $order->order_no }}" action="{{ route('admin.raw-material-supplier-orders.destroy', $order->order_no) }}" method="POST" style="display: none;">
                                                @method('DELETE')
                                                @csrf
                                            </form>
                                            @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_supplier_order.create'))
                                        <a href="{{ route('admin.raw-material-supplier-orders.rawMaterialSupplierOrder',$order->order_no) }}"><img src="{{ asset('img/ISSh.gif') }}" width="60" title="Télécharger d'abord le document et puis imprimer"></a> 
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