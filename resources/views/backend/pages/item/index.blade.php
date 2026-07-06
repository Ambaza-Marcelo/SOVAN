
@extends('backend.layouts.master')

@section('title')
@lang('messages.Items') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Items')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Items')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('item.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.items.create') }}">@lang('messages.new')</a>
                        @endif
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.item')</th>
                                    <th width="10%">@lang('messages.code')</th>
                                    <th width="10%">@lang('messages.cump')</th>
                                    <th width="10%">@lang('messages.store_type')</th>
                                    <th width="10%">@lang('messages.stock')</th>
                                    <th width="10%">@lang('messages.Taux TVA')</th>
                                    <th width="10%">@lang('messages.selling_price')</th>
                                    <th width="10%">@lang('messages.Status')</th>
                                    <th width="15%">@lang('messages.account')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($items as $item)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td><a href="{{ route('admin.items.show',$item->code) }}"> {{ $item->code }}</a> </td>
                                    <td>{{ number_format($item->cump,0,',',' ') }}</td>
                                    <td>
                                        @if($item->store_type == '1')
                                            @lang('messages.store_type_finished_goods')
                                        @elseif($item->store_type == '2')
                                            @lang('messages.store_type_goods')
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $storeCode = $item->code_store ?? null;
                                            $storeName = $storeCode && isset($storeNameByCode[$storeCode]) ? $storeNameByCode[$storeCode] : null;
                                        @endphp
                                        @if($storeName)
                                            {{ $storeName }} ({{ $storeCode }})
                                        @elseif($storeCode)
                                            {{ $storeCode }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->vat }}%</td>
                                    <td>{{ number_format($item->selling_price,0,',',' ') }}</td>
                                    <td>
                                        @if($item->store_type == \App\Models\Item::STORE_TYPE_GOODS)
                                            @if((string)($item->status ?? '1') === '0')
                                                <span class="badge badge-secondary">@lang('messages.Non stockable')</span>
                                            @else
                                                <span class="badge badge-success">@lang('messages.Stockable')</span>
                                            @endif
                                        @else
                                            <span class="badge badge-success">@lang('messages.Stockable')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->defaultAccount)
                                            {{ $item->defaultAccount->account_number ?? '' }} - {{ $item->defaultAccount->account_name ?? '' }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('item.edit'))
                                            <a class="btn btn-success btn-sm text-white" href="{{ route('admin.items.edit', $item->code) }}">
                                                <i class="fa fa-edit"></i> @lang('messages.edit')
                                            </a>
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('item.delete'))
                                            <a class="btn btn-danger btn-sm text-white" href="{{ route('admin.items.destroy', $item->code) }}"
                                            onclick="event.preventDefault(); if(confirm('{{ __('messages.Are you sure you want to delete this item? This action is irreversible.') }}')) { document.getElementById('delete-form-{{ $item->code }}').submit(); }">
                                                <i class="fa fa-trash"></i> @lang('messages.delete')
                                            </a>

                                            <form id="delete-form-{{ $item->code }}" action="{{ route('admin.items.destroy', $item->code) }}" method="POST" style="display: none;">
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
     <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
     
     <script>
        $(function () {
            if ($('#dataTable').length) {
                $('#dataTable').DataTable({ responsive: true });
            }
        });

    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () {
        null
    };

     </script>
@endsection