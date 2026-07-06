
@extends('backend.layouts.master')

@section('title')
@lang('messages.Rapport du stock marchandises') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Rapport du stock marchandises')</h4>
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
                    @include('backend.layouts.partials._stock_report_modal', [
                        'filterRoute' => 'admin.goods-store-report.index',
                        'exportExcelRoute' => 'admin.goods-store-report.export-to-excel',
                        'exportPdfRoute' => 'admin.goods-store-report.export-to-pdf',
                        'stores' => $stores ?? collect(),
                        'articles' => $articles ?? collect(),
                        'storeContext' => 'goods',
                        'showDocumentNo' => false,
                    ])
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="3%">#</th>
                                    <th width="7%">@lang('messages.Date')</th>
                                    <th width="8%">@lang('messages.Code magasin')</th>
                                    <th width="12%">@lang('messages.Article')</th>
                                    <th width="7%">@lang('messages.Code')</th>
                                    <th width="7%">@lang('messages.Stock init.')</th>
                                    <th width="7%">@lang('messages.Entrée')</th>
                                    <th width="7%">@lang('messages.Sortie')</th>
                                    <th width="7%">@lang('messages.Stock final')</th>
                                    <th width="7%">@lang('messages.C.U.M.P')</th>
                                    <th width="8%">@lang('messages.Type')</th>
                                    <th width="9%">@lang('messages.No doc.')</th>
                                    <th width="10%">@lang('messages.Description')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($datas as $data)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $data->date ? \Carbon\Carbon::parse($data->date)->format('d/m/Y') : \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</td>
                                    <td>{{ $data->code_store ?? '-' }}</td>
                                    <td>{{ $data->item ? $data->item->name : 'N/A' }}</td>
                                    <td>{{ $data->item ? $data->item->code : 'N/A' }}</td>
                                    <td>{{ number_format($data->quantity_stock_initial ?? 0, 2) }}</td>
                                    <td>{{ number_format($data->quantity_stockin ?? 0, 2) }}</td>
                                    <td>{{ number_format($data->quantity_stockout ?? 0, 2) }}</td>
                                    <td>{{ number_format($data->quantity_stock_final ?? 0, 2) }}</td>
                                    <td>{{ number_format($data->cump ?? 0, 2) }}</td>
                                    <td>{{ $data->type_transaction ?? 'N/A' }}</td>
                                    <td>{{ $data->document_no ?? 'N/A' }}</td>
                                    <td>{{ $data->description ?? '-' }}</td>
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
