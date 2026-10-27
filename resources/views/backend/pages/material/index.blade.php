
@extends('backend.layouts.master')

@section('title')
@lang('messages.materials') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.materials')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.materials')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('material.view'))
                            <a class="btn btn-success btn-sm text-white" href="{{ route('admin.immobilisations.export-excel') }}" title="Registre des immobilisations + plans d'amortissement">
                                <i class="fa fa-file-excel-o"></i> Registre immo. Excel
                            </a>
                            <a class="btn btn-info btn-sm text-white" href="{{ route('admin.immobilisations.export-pdf') }}" target="_blank" title="Registre des immobilisations + plans d'amortissement">
                                <i class="fa fa-file-pdf-o"></i> Registre immo. PDF
                            </a>
                        @endif
                        @if (Auth::guard('admin')->user()->can('material.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.materials.create') }}">@lang('messages.new')</a>
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
                                    <th width="10%">@lang('messages.Code Store')</th>
                                    <th width="10%">@lang('messages.quantity')</th>
                                    <th width="10%">@lang('messages.unit')</th>
                                    <th width="10%">@lang('messages.purchase_price')</th>
                                    <th width="10%">@lang('messages.Compte')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($materials as $material)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $material->name }}
                                        <div class="mt-1">
                                            @if($material->is_stockable ?? true)
                                                <span class="badge badge-secondary">Stockable</span>
                                            @else
                                                <span class="badge badge-light">Non stockable</span>
                                            @endif
                                            @if($material->is_amortizable)
                                                <span class="badge badge-info">Amortissable</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $material->code }}</td>
                                    <td>{{ $material->code_store }}</td>
                                    <td>{{ $material->quantity }}</td>
                                    <td>{{ $material->materialMeasurement ? $material->materialMeasurement->purchase_unit : '-' }}</td>
                                    <td>{{ number_format($material->purchase_price,0,',',' ') }}</td>
                                    <td>@if($material->account) {{ $material->account->account_number }}-{{ $material->account->account_name }} @else - @endif</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('material.view') && $material->is_amortizable)
                                            <a class="btn btn-info text-white btn-sm" href="{{ route('admin.materials.depreciation', $material->id) }}" title="Plan d'amortissement">
                                                Amortissement
                                            </a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('material.edit'))
                                            <a class="btn btn-success text-white" href="{{ route('admin.materials.edit', $material->id) }}">@lang('messages.edit')</a>
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('material.delete'))
                                            <a class="btn btn-danger text-white" href="{{ route('admin.materials.destroy', $material->id) }}"
                                            onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this item? This action is irreversible.')) { document.getElementById('delete-form-{{ $material->id }}').submit(); }">
                                                @lang('messages.delete')
                                            </a>

                                            <form id="delete-form-{{ $material->id }}" action="{{ route('admin.materials.destroy', $material->id) }}" method="POST" style="display: none;">
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