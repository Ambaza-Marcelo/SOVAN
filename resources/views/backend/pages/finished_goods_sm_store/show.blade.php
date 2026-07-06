
@extends('backend.layouts.master')

@section('title')
@lang('messages.finished goods store details') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.finished goods store details : '){{ $finished_goods_store->code ?? '' }}</h4>
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
                    <h4 class="header-title float-left">@lang('messages.finished goods store details : '){{ $finished_goods_store->code ?? '' }}</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('finished_product_sm_stock.create') && !$hasRecord)
                            <a class="btn btn-primary text-white" href="{{ route('admin.finished-goods-sm-store.create') }}">
                                <i class="fa fa-plus"></i> @lang('messages.new')
                            </a>
                        @endif
                        @if (Auth::guard('admin')->user()->can('finished_product_sm_stock.edit'))
                            <a class="btn btn-success text-white" href="{{ route('admin.finished-goods-sm-store.edit', $finished_goods_store->id) }}">
                                <i class="fa fa-edit"></i> @lang('messages.edit')
                            </a>
                        @endif
                        <a class="btn btn-secondary text-white" href="{{ route('admin.finished-goods-sm-store.index') }}">
                            <i class="fa fa-arrow-left"></i> @lang('messages.back')
                        </a>
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">@lang('messages.item')</th>
                                    <th width="10%">@lang('messages.item') @lang('messages.code')</th>
                                    <th width="10%">@lang('messages.Code Store')</th>
                                    <th width="10%">@lang('messages.quantity')</th>
                                    <th width="10%">@lang('messages.Min Store')</th>
                                    <th width="10%">@lang('messages.CUMP')</th>
                                    <th width="10%">@lang('messages.Selling Price')</th>
                                    <th width="10%">@lang('messages.Status')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @forelse ($finished_goods_store_details as $detail)
                               <tr class="{{ $detail->item_id && $detail->item && $detail->item->isInactive() ? 'table-secondary' : '' }}">
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>
                                        @if($detail->item_id && $detail->item)
                                            {{ $detail->item->name }}
                                            @if($detail->item->isInactive())
                                                <span class="badge badge-danger ml-1">@lang('messages.Inactive')</span>
                                            @endif
                                        @else 
                                            {{ $detail->name }}
                                        @endif
                                    </td>
                                    <td>@if($detail->item_id && $detail->item){{ $detail->item->code }} @else {{ $detail->code }} @endif</td>
                                    <td>{{ $detail->code_store }}</td>
                                    @if(($detail->quantity ?? 0) <= ($detail->threshold_quantity ?? 0))
                                    <td>{{ $detail->quantity ?? 0 }}<img src="{{ asset('img/warning.gif')}}" width="30"></td>
                                    @else
                                    <td>{{ $detail->quantity ?? 0 }}</td>
                                    @endif
                                    <td>{{ $detail->threshold_quantity ?? 0 }}</td>
                                    <td>{{ number_format($detail->cump ?? 0, 0, ',', ' ') }}</td>
                                    <td>{{ number_format($detail->selling_price ?? 0, 0, ',', ' ') }}</td>
                                    <td>
                                        @if($detail->item_id && $detail->item)
                                            @if($detail->item->isActive())
                                                <span class="badge badge-success">@lang('messages.Active')</span>
                                            @else
                                                <span class="badge badge-danger">@lang('messages.Inactive')</span>
                                            @endif
                                        @else
                                            {{ $detail->status ?? '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('finished_product_sm_stock.delete'))
                                            <a class="btn btn-danger btn-sm text-white" href="#"
                                            onclick="event.preventDefault(); 
                                            if(confirm(@json(__('messages.delete_confirm_irreversible')))) { 
                                                document.getElementById('delete-form-{{ $detail->id }}').submit(); 
                                            }">
                                                <i class="fa fa-trash"></i> @lang('messages.delete')
                                            </a>

                                            <form id="delete-form-{{ $detail->id }}" action="{{ route('admin.finished-goods-sm-store-detail.destroy', $detail->id) }}" method="POST" style="display: none;">
                                                @method('DELETE')
                                                @csrf
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                               @empty
                               <tr>
                                    <td colspan="10" class="text-center">@lang('messages.No items found')</td>
                                </tr>
                               @endforelse
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