
@extends('backend.layouts.master')

@section('title')
@lang('messages.goods_store_index_title') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Stock des marchandises')</h4>
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

<div class="main-content-inner">
    <div class="row">
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title float-left">@lang('messages.goods_store_index_title')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('goods_stock.create') && $goods_stores->isEmpty())
                            <a class="btn btn-primary text-white" href="{{ route('admin.goods-store.create') }}">
                                <i class="fa fa-plus"></i> @lang('messages.new')
                            </a>
                        @elseif ($goods_stores->isNotEmpty())
                            <span class="text-muted small">@lang('messages.goods_store_one_only')</span>
                        @endif
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.Désignation')</th>
                                    <th width="10%">@lang('messages.code')</th>
                                    <th width="10%">@lang('messages.Signature')</th>
                                    <th width="10%">@lang('messages.Emplacement')</th>
                                    <th width="10%">@lang('messages.Gestionnaire')</th>
                                    <th width="15%">@lang('messages.account')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($goods_stores as $store)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $store->name }}</td>
                                    <td><a href="{{ route('admin.goods-store.show', $store->code) }}">{{ $store->code }}</a></td>
                                    <td>{{ $store->store_signature }}</td>
                                    <td>{{ $store->emplacement }}</td>
                                    <td>{{ $store->manager }}</td>
                                    <td>
                                        @if($store->defaultAccount)
                                            {{ $store->defaultAccount->account_number ?? '' }} - {{ $store->defaultAccount->account_name ?? '' }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('material_big_inventory.create'))
                                            <a class="btn btn-info btn-sm text-white" href="{{ route('admin.goods-store-inventory.create', $store->code) }}" title="Inventaire">
                                                <i class="fa fa-clipboard-list"></i> Inventaire
                                            </a>
                                        @endif
                                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.goods-store.print', $store->code) }}" target="_blank"><i class="fa fa-print"></i> @lang('messages.PDF')</a>
                                        @if (Auth::guard('admin')->user()->can('goods_stock.edit'))
                                            <a class="btn btn-success btn-sm text-white" href="{{ route('admin.goods-store.edit', $store->id) }}">
                                                <i class="fa fa-edit"></i> @lang('messages.edit')
                                            </a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('goods_stock.delete'))
                                            <a class="btn btn-danger btn-sm text-white" href="{{ route('admin.goods-store.destroy', $store->id) }}"
                                            onclick="event.preventDefault(); if(confirm(@json(__('messages.Supprimer ce magasin ?')))) { document.getElementById('delete-form-{{ $store->id }}').submit(); }">
                                                <i class="fa fa-trash"></i> @lang('messages.delete')
                                            </a>
                                            <form id="delete-form-{{ $store->id }}" action="{{ route('admin.goods-store.destroy', $store->id) }}" method="POST" style="display: none;">
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
    </div>
</div>
@endsection

@section('scripts')
     <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
     <script>
        if ($('#dataTable').length) {
            $('#dataTable').DataTable({ responsive: true });
        }
     </script>
@endsection
