@extends('backend.layouts.master')

@section('title')
@lang('messages.exchange_rate') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.exchange_rate')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.exchange_rate')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('item.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.exchange-rates.create') }}">@lang('messages.new')</a>
                        @endif
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">@lang('messages.Devise d\'Origine')</th>
                                    <th width="15%">@lang('messages.Devise de Destination')</th>
                                    <th width="12%">@lang('messages.Taux')</th>
                                    <th width="12%">@lang('messages.Date')</th>
                                    <th width="15%">@lang('messages.Description')</th>
                                    <th width="8%">@lang('messages.Statut')</th>
                                    <th width="18%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($exchange_rates as $exchange_rate)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>
                                        <strong>{{ $exchange_rate->originCurrency->currency_name ?? '-' }}</strong>
                                    </td>
                                    <td>
                                        <strong>{{ $exchange_rate->destinationCurrency->currency_name ?? '-' }}</strong>
                                    </td>
                                    <td>
                                        <strong>1 {{ $exchange_rate->originCurrency->currency_name ?? '' }} = {{ number_format($exchange_rate->rate, 8) }} {{ $exchange_rate->destinationCurrency->currency_name ?? '' }}</strong>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($exchange_rate->date)->format('d/m/Y') }}</td>
                                    <td>{{ $exchange_rate->description ?? '-' }}</td>
                                    <td>
                                        @if($exchange_rate->active)
                                            <span class="badge badge-success">@lang('messages.Active')</span>
                                        @else
                                            <span class="badge badge-danger">@lang('messages.Inactive')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('item.edit'))
                                            <a class="btn btn-success text-white" href="{{ route('admin.exchange-rates.edit', $exchange_rate->id) }}">@lang('messages.edit')</a>
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('item.delete'))
                                            <a class="btn btn-danger text-white" href="{{ route('admin.exchange-rates.destroy', $exchange_rate->id) }}"
                                            onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this item? This action is irreversible.')) { document.getElementById('delete-form-{{ $exchange_rate->id }}').submit(); }">
                                                @lang('messages.delete')
                                            </a>

                                            <form id="delete-form-{{ $exchange_rate->id }}" action="{{ route('admin.exchange-rates.destroy', $exchange_rate->id) }}" method="POST" style="display: none;">
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
                responsive: true,
                order: [[4, 'desc']] // Trier par date décroissante par défaut
            });
        }

     </script>
@endsection

