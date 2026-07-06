
@extends('backend.layouts.master')

@section('title')
@lang('messages.Finished goods store') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Finished goods store')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Finished goods store')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('finished_product_sm_stock.create') && !$hasRecord)
                            <a class="btn btn-primary text-white" href="{{ route('admin.finished-goods-sm-store.create') }}">
                                <i class="fa fa-plus"></i> @lang('messages.new')
                            </a>
                        @endif
                        @if ($hasRecord)
                            <span class="badge badge-warning" style="padding: 10px; font-size: 14px;">
                                <i class="fa fa-info-circle"></i> @lang('messages.Only one record is allowed. Edit the existing record instead.')
                            </span>
                        @endif
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.Designation')</th>
                                    <th width="10%">@lang('messages.code')</th>
                                    <th width="10%">@lang('messages.Store Signature')</th>
                                    <th width="10%">@lang('messages.Emplacement')</th>
                                    <th width="10%">@lang('messages.Store Manager')</th>
                                    <th width="15%">@lang('messages.account')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($finished_goods as $finished_good)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $finished_good->name }}</td>
                                    <td><a href="{{ route('admin.finished-goods-sm-store.show',$finished_good->code) }}">{{ $finished_good->code }}</a> </td>
                                    <td>{{ $finished_good->store_signature }}</td>
                                    <td>{{ $finished_good->emplacement }}</td>
                                    <td>{{ $finished_good->manager }}</td>
                                    <td>
                                        @if($finished_good->defaultAccount)
                                            {{ $finished_good->defaultAccount->account_number ?? '' }} - {{ $finished_good->defaultAccount->account_name ?? '' }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('finished_goods_sm_store_inventory.create'))
                                            <a class="btn btn-info btn-sm text-white" href="{{ route('admin.finished-goods-sm-store-inventory.create', $finished_good->code) }}" title="@lang('messages.inventory')">
                                                <i class="fa fa-clipboard-list"></i> Inventaire
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.finished-goods-sm-store.storeStatus',$finished_good->code) }}"><img src="{{ asset('img/ISSh.gif') }}" width="60" title="Télécharger d'abord le document et puis imprimer"></a>
                                        @if (Auth::guard('admin')->user()->can('finished_product_sm_stock.edit'))
                                            <a class="btn btn-success btn-sm text-white" href="{{ route('admin.finished-goods-sm-store.edit', $finished_good->id) }}">
                                                <i class="fa fa-edit"></i> @lang('messages.edit')
                                            </a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('finished_product_sm_stock.delete'))
                                            <a class="btn btn-danger btn-sm text-white" href="{{ route('admin.finished-goods-sm-store.destroy', $finished_good->id) }}"
                                            onclick="event.preventDefault(); if(confirm(@json(__('messages.delete_confirm_irreversible')))) { document.getElementById('delete-form-{{ $finished_good->id }}').submit(); }">
                                                <i class="fa fa-trash"></i> @lang('messages.delete')
                                            </a>

                                            <form id="delete-form-{{ $finished_good->id }}" action="{{ route('admin.finished-goods-sm-store.destroy', $finished_good->id) }}" method="POST" style="display: none;">
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

     </script>
@endsection