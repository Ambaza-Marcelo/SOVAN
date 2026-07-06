
@extends('backend.layouts.master')

@section('title')
@lang('messages.foods') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.foods')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.foods')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('raw_material.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.raw-materials.create') }}">@lang('messages.new')</a>
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
                                    <th width="10%">@lang('messages.Status')</th>
                                    <th width="15%">@lang('messages.account')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($raw_materials as $raw_material)
                               <tr class="{{ $raw_material->isInactive() ? 'table-secondary' : '' }}">
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $raw_material->name }}</td>
                                    <td>{{ $raw_material->code }}</td>
                                    <td>{{ $raw_material->code_store }}</td>
                                    <td>{{ $raw_material->quantity }}</td>
                                    <td>{{ $raw_material->rawMaterialMeasurement ? $raw_material->rawMaterialMeasurement->purchase_unit : '-' }}</td>
                                    <td>{{ number_format($raw_material->purchase_price,0,',',' ') }}</td>
                                    <td>
                                        @if($raw_material->isActive())
                                            <span class="badge badge-success">@lang('messages.Active')</span>
                                        @else
                                            <span class="badge badge-danger">@lang('messages.Inactive')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($raw_material->defaultAccount)
                                            {{ $raw_material->defaultAccount->account_number }} - {{ $raw_material->defaultAccount->account_name }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('raw_material.edit'))
                                            <a class="btn btn-success btn-sm text-white" href="{{ route('admin.raw-materials.edit', $raw_material->id) }}">
                                                <i class="fa fa-edit"></i> @lang('messages.edit')
                                            </a>
                                            
                                            @if($raw_material->isActive())
                                                <a class="btn btn-warning btn-sm text-white" href="{{ route('admin.raw-materials.toggle-status', $raw_material->id) }}"
                                                onclick="event.preventDefault(); if(confirm('{{ __('messages.Are you sure you want to deactivate this raw material? It will be removed from raw material stores.') }}')) { document.getElementById('toggle-form-{{ $raw_material->id }}').submit(); }">
                                                    <i class="fa fa-ban"></i> @lang('messages.Deactivate')
                                                </a>
                                            @else
                                                <a class="btn btn-info btn-sm text-white" href="{{ route('admin.raw-materials.toggle-status', $raw_material->id) }}"
                                                onclick="event.preventDefault(); if(confirm('{{ __('messages.Are you sure you want to activate this raw material?') }}')) { document.getElementById('toggle-form-{{ $raw_material->id }}').submit(); }">
                                                    <i class="fa fa-check"></i> @lang('messages.Activate')
                                                </a>
                                            @endif

                                            <form id="toggle-form-{{ $raw_material->id }}" action="{{ route('admin.raw-materials.toggle-status', $raw_material->id) }}" method="POST" style="display: none;">
                                                @csrf
                                            </form>
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('raw_material.delete'))
                                            <a class="btn btn-danger btn-sm text-white" href="{{ route('admin.raw-materials.destroy', $raw_material->id) }}"
                                            onclick="event.preventDefault(); if(confirm('{{ __('messages.Are you sure you want to delete this raw material? This action is irreversible.') }}')) { document.getElementById('delete-form-{{ $raw_material->id }}').submit(); }">
                                                <i class="fa fa-trash"></i> @lang('messages.delete')
                                            </a>

                                            <form id="delete-form-{{ $raw_material->id }}" action="{{ route('admin.raw-materials.destroy', $raw_material->id) }}" method="POST" style="display: none;">
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
         /*================================
        datatable active
        ==================================*/
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