
@extends('backend.layouts.master')

@section('title')
@lang('messages.La sortie de fonds') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.La sortie de fonds')</h4>
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
                    <h4 class="header-title float-left">Details sur La sortie de fonds No : {{$code}}</h4>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.date')</th>
                                    <th width="10%">@lang('messages.cash out flow No')</th>
                                    <th width="10%">@lang('messages.Asker')</th>
                                    <th width="10%">@lang('messages.Currency')</th>
                                    <th width="10%">@lang('messages.item')</th>
                                    <th width="10%">@lang('messages.code')</th>
                                    <th width="10%">@lang('messages.quantity')</th>
                                    <th width="10%">@lang('messages.PU HTVA')</th>
                                    <th width="10%">@lang('messages.PT HTVA')</th>
                                    <th width="10%">@lang('messages.Taux TVA')</th>
                                    <th width="10%">@lang('messages.TVA')</th>
                                    <th width="10%">@lang('messages.TVAC')</th>
                                    <th width="30%">@lang('messages.description')</th>
                                    <th width="10%">@lang('messages.created_by')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($cash_out_flow_requests as $data)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $data->date }}</td>
                                    <td>{{ $data->cash_outflow_request_no }}</td>
                                    <td>{{ $data->asker }}</td>
                                    <td>@if($data->currency_id){{ $data->currency->currency_name }} @endif</td>
                                    <td>{{ $data->expense->name }}</td>
                                    <td>{{ $data->expense->code }}</td>
                                    <td>{{ $data->quantity }}</td>
                                    <td>{{ number_format($data->cost_price,2,',',' ') }}</td>
                                    <td>{{ number_format($data->cost_nvat,2,',',' ') }}</td>
                                    <td>{{ $data->vat_rate }}%</td>
                                    <td>{{ number_format($data->vat,2,',',' ') }}</td>
                                    <td>{{ number_format($data->cost_wvat,0,',',' ') }}</td>
                                    <td>{{ $data->description }}</td>
                                    <td>{{ $data->created_by }}</td>
                                    <td>
                                        
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