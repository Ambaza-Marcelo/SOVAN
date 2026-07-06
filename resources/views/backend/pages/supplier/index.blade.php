
@extends('backend.layouts.master')

@section('title')
@lang('messages.Liste des fournisseurs') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Liste des fournisseurs')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Liste des fournisseurs')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('supplier.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.suppliers.create') }}">@lang('messages.new')</a>
                        @endif
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">@lang('messages.Type Contribuable')</th>
                                    <th width="20%">@lang('messages.Assujetti à la TVA')</th>
                                    <th width="20%">@lang('messages.Nom Fournisseur')</th>
                                    <th width="20%">@lang('messages.NIF Fournisseur')</th>
                                    <th width="20%">@lang('messages.Adresse Fournisseur')</th>
                                    <th width="20%">@lang('messages.Mail')</th>
                                    <th width="20%">@lang('messages.Phone No')</th>
                                    <th width="20%">@lang('messages.account')</th>
                                    <th width="30%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($suppliers as $supplier)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>@if($supplier->tp_type == 1) @lang('messages.Personne Physique') @else @lang('messages.company_label') @endif</td>
                                    <td>
                                        @if($supplier->vat_supplier_payer == 1)
                                            @lang('messages.Oui')
                                        @else
                                            @lang('messages.Non')
                                        @endif
                                    </td>
                                    <td>{{ $supplier->supplier_name }}</td>
                                    <td>{{ $supplier->supplier_TIN }}</td>
                                    <td>{{ $supplier->supplier_address }}</td>
                                    <td>{{ $supplier->mail }}</td>
                                    <td>{{ $supplier->telephone }}</td>
                                    <td>
                                        @if($supplier->account)
                                            {{ $supplier->account->account_number ?? '' }} - {{ $supplier->account->account_name ?? '' }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('supplier.edit'))
                                            <a class="btn btn-success text-white" href="{{ route('admin.suppliers.edit', $supplier->id) }}">@lang('messages.edit')</a>
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('supplier.delete'))
                                            <a class="btn btn-danger text-white" href="{{ route('admin.suppliers.destroy', $supplier->id) }}"
                                            onclick="event.preventDefault(); if(confirm(@json(__('messages.delete_confirm_irreversible')))) { document.getElementById('delete-form-{{ $supplier->id }}').submit(); }">
                                                @lang('messages.delete')
                                            </a>

                                            <form id="delete-form-{{ $supplier->id }}" action="{{ route('admin.suppliers.destroy', $supplier->id) }}" method="POST" style="display: none;">
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