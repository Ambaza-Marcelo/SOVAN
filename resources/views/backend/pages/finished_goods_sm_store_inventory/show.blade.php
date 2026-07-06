
@extends('backend.layouts.master')

@section('title')
@lang('messages.inventory') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.inventory') - Petit stock des produits finis</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.finished-goods-sm-store-inventory.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Détail')</span></li>
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
                    <h4 class="header-title float-left">Détails de l'inventaire N° {{ $code }}</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('finished_goods_sm_store_inventory.view'))
                            <a href="{{ route('admin.finished-goods-sm-store-inventory.generatePdf', $code) }}" class="btn btn-info"><i class="fa fa-file-pdf-o"></i> @lang('messages.PDF')</a>
                        @endif
                        <a href="{{ route('admin.finished-goods-sm-store-inventory.index') }}" class="btn btn-secondary">@lang('messages.Retour')</a>
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.date')</th>
                                    <th width="10%">@lang('messages.inventory_no')</th>
                                    <th width="10%">@lang('messages.Inventory Signature')</th>
                                    <th width="15%">@lang('messages.title')</th>
                                    <th width="12%">@lang('messages.item')</th>
                                    <th width="8%">@lang('messages.Item Code')</th>
                                    <th width="8%">@lang('messages.quantity')</th>
                                    <th width="8%">@lang('messages.CUMP')</th>
                                    <th width="8%">@lang('messages.new_quantity')</th>
                                    <th width="8%">@lang('messages.Nouveau CUMP')</th>
                                    <th width="10%">@lang('messages.Valeur totale')</th>
                                    <th width="6%">@lang('messages.Relicat')</th>
                                    <th width="10%">@lang('messages.created_by')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($inventories as $inv)
                                <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($inv->date)->format('d/m/Y') }}</td>
                                    <td>{{ $inv->inventory_no }}</td>
                                    <td>{{ $inv->inventory_signature }}</td>
                                    <td>{{ $inv->title }}</td>
                                    <td>{{ $inv->item->name ?? '-' }}</td>
                                    <td>{{ $inv->item->code ?? '-' }}</td>
                                    <td>{{ $inv->quantity }}</td>
                                    <td>{{ number_format($inv->cump ?? $inv->purchase_price ?? 0, 0, ',', '.') }}</td>
                                    <td>{{ $inv->new_quantity }}</td>
                                    <td>{{ number_format($inv->new_cump ?? $inv->new_purchase_price ?? 0, 0, ',', '.') }}</td>
                                    <td>{{ number_format($inv->new_total_cump_value ?? $inv->new_total_purchase_value ?? 0, 0, ',', '.') }}</td>
                                    <td>{{ $inv->relicat }}</td>
                                    <td>{{ $inv->created_by }}</td>
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
