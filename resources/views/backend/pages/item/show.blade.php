
@extends('backend.layouts.master')

@section('title')
@lang('messages.detail des ingredients') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.detail des ingredients')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.detail des ingredients')</h4>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        @php
                            $item = \App\Models\Item::where('code', $datas->first()->code ?? null)->first();
                        @endphp
                        @if($item)
                            <div class="alert alert-info text-left">
                                <strong>@lang('messages.item'):</strong> {{ $item->name }} ({{ $item->code }})<br>
                                <strong>@lang('messages.store_type'):</strong>
                                @if($item->store_type == \App\Models\Item::STORE_TYPE_FINISHED_GOODS)
                                    @lang('messages.store_type_finished_goods')
                                @elseif($item->store_type == \App\Models\Item::STORE_TYPE_GOODS)
                                    @lang('messages.store_type_goods')
                                @else
                                    -
                                @endif
                                <br>
                                <strong>@lang('messages.stock'):</strong> {{ $item->code_store ?? '-' }}
                            </div>
                        @endif
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.item')</th>
                                    <th width="10%">@lang('messages.code')</th>
                                    <th width="10%">@lang('messages.unit')</th>
                                    <th width="10%">@lang('messages.TVA')</th>
                                    <th width="10%">@lang('messages.selling_price')</th>
                                    <th width="10%">@lang('messages.Ingredient')</th>
                                    <th width="10%">@lang('messages.quantity')</th>
                                    <th width="10%">@lang('messages.unit')</th>
                                    <th width="10%">@lang('messages.CUMP')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($datas as $data)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $data->name }}</td>
                                    <td>{{ $data->code }}</td>
                                    <td>{{ $data->unit }}</td>
                                    <td>{{ $data->vat }}%</td>
                                    <td>{{ number_format($data->selling_price,0,',',' ') }}</td>
                                    <td>{{ $data->rawMaterial->name }}</td>
                                    <td>{{ $data->quantity }}</td>
                                    <td>{{ $data->rawMaterial->rawMaterialMeasurement->production_unit }}</td>
                                    <td>{{ ($data->rawMaterial->cump / $data->rawMaterial->rawMaterialMeasurement->equivalent)*$data->rawMaterial->rawMaterialMeasurement->sub_equivalent }}</td>
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
     </script>
@endsection