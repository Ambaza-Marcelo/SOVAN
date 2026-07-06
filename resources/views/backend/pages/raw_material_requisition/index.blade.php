
@extends('backend.layouts.master')

@section('title')
@lang('messages.Demande de Requisition des Articles') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Demande de Requisition des Articles')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Demande de Requisition des Articles')</h4>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials._index_filter_export', ['filterRoute' => 'admin.raw-material-requisitions.index', 'exportRoute' => 'admin.raw-material-requisitions.export-to-excel', 'useDateType' => 'date'])
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.date')</th>
                                    <th width="10%">@lang('messages.requisition_no_column')</th>
                                    <th width="10%">@lang('messages.Requisition Signature')</th>
                                    <th width="10%">@lang('messages.status')</th>
                                    <th width="30%">@lang('messages.description')</th>
                                    <th width="10%">@lang('messages.created_by')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($requisitions as $requisition)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($requisition->date)->format('d/m/Y') }}</td>
                                    <td><a href="{{ route('admin.raw-material-requisitions.show',$requisition->requisition_no) }}">{{ $requisition->requisition_no }}</a></td>
                                    <td>{{ $requisition->requisition_signature }}</td>
                                    @if($requisition->status == 2)
                                    <td><span  class="badge badge-success">@lang('messages.requisition_status_validated')</span></td>
                                    @elseif($requisition->status == -1)
                                    <td><span class="badge badge-danger">@lang('messages.requisition_status_rejected')</span></td>
                                    @elseif($requisition->status == 3)
                                    <td><span class="badge badge-success">@lang('messages.requisition_status_confirmed')</span></td>
                                    @elseif($requisition->status == 4)
                                    <td><span class="badge badge-success">@lang('messages.requisition_status_approved')</span></td>
                                    @elseif($requisition->status == 5)
                                    <td><span class="badge badge-warning">@lang('messages.requisition_status_transfer_pending')</span></td>
                                    @elseif($requisition->status == 6)
                                    <td><span class="badge badge-success">@lang('messages.requisition_status_transferred')</span></td>
                                    @else
                                    <td><span class="badge badge-primary">@lang('messages.requisition_status_in_progress')</span></td>
                                    @endif
                                    <td>{{ $requisition->description }}</td>
                                    <td>{{ $requisition->created_by }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('raw_material_requisition.view'))
                                        @if($requisition->status == 2|| $requisition->status == 3 || $requisition->status == 4 || $requisition->status == 5 || $requisition->status == 6)
                                        <a href="{{ route('admin.raw-material-requisitions.generatepdf',$requisition->requisition_no) }}"><img src="{{ asset('img/ISSh.gif') }}" width="60" title="{{ __('messages.requisition_pdf_title') }}"></a>
                                        @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_requisition.validate'))
                                        @if($requisition->status == 1 || $requisition->status == -1)
                                            <a class="btn btn-primary text-white" href="{{ route('admin.raw-material-requisitions.validate', $requisition->requisition_no) }}"
                                            onclick="event.preventDefault(); document.getElementById('validate-form-{{ $requisition->requisition_no }}').submit();">
                                                @lang('messages.requisition_btn_validate')
                                            </a>

                                            <form id="validate-form-{{ $requisition->requisition_no }}" action="{{ route('admin.raw-material-requisitions.validate', $requisition->requisition_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_requisition.confirm'))
                                        @if($requisition->status == 2)
                                            <a class="btn btn-primary text-white" href="{{ route('admin.raw-material-requisitions.confirm', $requisition->requisition_no) }}"
                                            onclick="event.preventDefault(); document.getElementById('confirm-form-{{ $requisition->requisition_no }}').submit();">
                                                @lang('messages.requisition_btn_confirm')
                                            </a>

                                            <form id="confirm-form-{{ $requisition->requisition_no }}" action="{{ route('admin.raw-material-requisitions.confirm', $requisition->requisition_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_requisition.approuve'))
                                        @if($requisition->status == 3)
                                            <a class="btn btn-primary text-white" href="{{ route('admin.raw-material-requisitions.approuve', $requisition->requisition_no) }}"
                                            onclick="event.preventDefault(); document.getElementById('approuve-form-{{ $requisition->requisition_no }}').submit();">
                                                @lang('messages.requisition_btn_approve')
                                            </a>

                                            <form id="approuve-form-{{ $requisition->requisition_no }}" action="{{ route('admin.raw-material-requisitions.approuve', $requisition->requisition_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_requisition.reject'))
                                            @if($requisition->status == 1 || $requisition->status == 2 || $requisition->status == 3)
                                            <a class="btn btn-primary text-white" href="{{ route('admin.raw-material-requisitions.reject', $requisition->requisition_no) }}"
                                            onclick="event.preventDefault(); document.getElementById('reject-form-{{ $requisition->requisition_no }}').submit();">
                                                @lang('messages.requisition_btn_reject')
                                            </a>
                                            @endif
                                            <form id="reject-form-{{ $requisition->requisition_no }}" action="{{ route('admin.raw-material-requisitions.reject', $requisition->requisition_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_requisition.reset'))
                                            @if($requisition->status == -1 || $requisition->status == 2 || $requisition->status == 3 || $requisition->status == 4)
                                            <a class="btn btn-primary text-white" href="{{ route('admin.raw-material-requisitions.reset', $requisition->requisition_no) }}"
                                            onclick="event.preventDefault(); document.getElementById('reset-form-{{ $requisition->requisition_no }}').submit();">
                                                @lang('messages.requisition_btn_cancel')
                                            </a>
                                            @endif
                                            <form id="reset-form-{{ $requisition->requisition_no }}" action="{{ route('admin.raw-material-requisitions.reset', $requisition->requisition_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_transfer.create') && Auth::guard('admin')->user()->can('raw_material_big_inventory.view'))
                                        @if($requisition->status == 4 && $requisition->type_store == 'md')
                                        <a href="{{ route('admin.raw-material-transfers.create',$requisition->requisition_no)}}" class="btn btn-primary">@lang('messages.requisition_btn_transfer_small')</a>
                                        @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_transfer.create') && Auth::guard('admin')->user()->can('raw_material_extra_big_inventory.view'))
                                        @if($requisition->status == 4 && $requisition->type_store == 'bg')
                                        <a href="{{ route('admin.raw-material-transfers.createFromBig',$requisition->requisition_no)}}" class="btn btn-primary">@lang('messages.requisition_btn_transfer_intermediate')</a>
                                        @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_requisition.edit'))
                                            @if($requisition->status != 6)
                                            <a class="btn btn-success text-white" href="{{ route('admin.raw-material-requisitions.edit', $requisition->requisition_no) }}">@lang('messages.edit')</a>
                                            @endif
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('raw_material_transfer.edit'))
                                            @if($requisition->status == 5 && isset($transfers[$requisition->requisition_no]))
                                            <a class="btn btn-info text-white" href="{{ route('admin.raw-material-transfers.edit', $transfers[$requisition->requisition_no]->transfer_no) }}">
                                                <i class="fa fa-edit"></i> @lang('messages.requisition_edit_transfer')
                                            </a>
                                            @endif
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('raw_material_requisition.delete'))
                                            @if($requisition->status != 6)
                                            <a class="btn btn-danger text-white" href="{{ route('admin.raw-material-requisitions.destroy', $requisition->requisition_no) }}"
                                            onclick="event.preventDefault(); if(confirm(@json(__('messages.delete_confirm_irreversible')))) { document.getElementById('delete-form-{{ $requisition->requisition_no }}').submit(); }">
                                                @lang('messages.delete')
                                            </a>

                                            <form id="delete-form-{{ $requisition->requisition_no }}" action="{{ route('admin.raw-material-requisitions.destroy', $requisition->requisition_no) }}" method="POST" style="display: none;">
                                                @method('DELETE')
                                                @csrf
                                            </form>
                                            @endif
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
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]
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