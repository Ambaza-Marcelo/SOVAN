
@extends('backend.layouts.master')

@section('title')
@lang('messages.Delivery Notes') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Delivery Notes')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Bons de Livraison')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('delivery_note.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.proforma-invoice.index') }}">@lang('messages.Créer depuis Facture Proforma')</a>
                        @endif
                    </p>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials._index_filter_export', ['filterRoute' => 'admin.delivery-note.index', 'exportRoute' => 'admin.delivery-note.export-to-excel'])
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.Numéro BL')</th>
                                    <th width="10%">@lang('messages.date')</th>
                                    <th width="20%">@lang('messages.Signature')</th>
                                    <th width="20%">@lang('messages.Customer')</th>
                                    <th width="10%">@lang('messages.Facture Proforma')</th>
                                    <th width="10%">@lang('messages.status')</th>
                                    <th width="10%">@lang('messages.created_by')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($delivery_notes as $delivery_note)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td><a href="{{ route('admin.delivery-note.show', $delivery_note->delivery_number) }}">{{ $delivery_note->delivery_number }}</a></td>
                                    <td>{{ $delivery_note->delivery_date ? Carbon\Carbon::parse($delivery_note->delivery_date)->format('d/m/Y') : 'N/A' }}</td>
                                    <td>{{ $delivery_note->delivery_signature ?? 'N/A' }}</td>
                                    <td>{{ $delivery_note->client ? $delivery_note->client->customer_name : 'N/A' }}</td>
                                    <td>
                                        @if($delivery_note->document_no)
                                            <a href="{{ route('admin.proforma-invoice.show', $delivery_note->document_no) }}">
                                                {{ $delivery_note->document_no }}
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if($delivery_note->etat == '0')
                                            <span class="badge badge-secondary">@lang('messages.Brouillon')</span>
                                        @elseif($delivery_note->etat == '1')
                                            <span class="badge badge-success">@lang('messages.Validée')</span>
                                        @elseif($delivery_note->etat == '2')
                                            <span class="badge badge-info">@lang('messages.Confirmée')</span>
                                        @elseif($delivery_note->etat == '3')
                                            <span class="badge badge-primary">@lang('messages.Approuvée')</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $delivery_note->etat }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $delivery_note->auteur ?? 'N/A' }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a class="btn btn-info btn-sm text-white" href="{{ route('admin.delivery-note.show', $delivery_note->delivery_number) }}" title="@lang('messages.Voir')">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            @if (Auth::guard('admin')->user()->can('delivery_note.view'))
                                                <a class="btn btn-secondary btn-sm text-white" href="{{ route('admin.delivery-note.print', $delivery_note->delivery_number) }}" target="_blank" title="@lang('messages.Imprimer (PDF)')"><i class="fa fa-print"></i></a>
                                            @endif
                                            @php
                                                $hasValidatedInvoice = false;
                                                if ($delivery_note->flag1 == '1') {
                                                    $invoice = \App\Models\Invoice::where('document_no', $delivery_note->delivery_number)
                                                        ->whereIn('etat', ['1', '2'])
                                                        ->first();
                                                    $hasValidatedInvoice = $invoice ? true : false;
                                                }
                                            @endphp
                                            
                                            @if(in_array($delivery_note->etat, ['0', '1', '2', '3']) && !$hasValidatedInvoice)
                                                @if (Auth::guard('admin')->user()->can('delivery_note.edit'))
                                                    <a class="btn btn-success btn-sm text-white" href="{{ route('admin.delivery-note.edit', $delivery_note->delivery_number) }}" title="@lang('messages.Modifier')">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                @endif
                                            @endif
                                            
                                            @if($delivery_note->etat == '0')
                                                @if (Auth::guard('admin')->user()->can('delivery_note.validate'))
                                                    <a class="btn btn-primary btn-sm text-white" href="{{ route('admin.delivery-note.validate', $delivery_note->delivery_number) }}"
                                                        onclick="event.preventDefault(); document.getElementById('validate-form-{{ $delivery_note->delivery_number }}').submit();" title="@lang('messages.Valider')">
                                                        <i class="fa fa-check"></i>
                                                    </a>
                                                    <form id="validate-form-{{ $delivery_note->delivery_number }}" action="{{ route('admin.delivery-note.validate', $delivery_note->delivery_number) }}" method="POST" style="display: none;">
                                                        @method('PUT')
                                                        @csrf
                                                    </form>
                                                @endif
                                            @endif
                                            
                                            @if($delivery_note->etat == '1')
                                                @if (Auth::guard('admin')->user()->can('delivery_note.confirm'))
                                                    <a class="btn btn-info btn-sm text-white" href="{{ route('admin.delivery-note.confirm', $delivery_note->delivery_number) }}"
                                                        onclick="event.preventDefault(); document.getElementById('confirm-form-{{ $delivery_note->delivery_number }}').submit();" title="@lang('messages.Confirmer')">
                                                        <i class="fa fa-check-circle"></i>
                                                    </a>
                                                    <form id="confirm-form-{{ $delivery_note->delivery_number }}" action="{{ route('admin.delivery-note.confirm', $delivery_note->delivery_number) }}" method="POST" style="display: none;">
                                                        @method('PUT')
                                                        @csrf
                                                    </form>
                                                @endif
                                            @endif
                                            
                                            @if($delivery_note->etat == '2')
                                                @if (Auth::guard('admin')->user()->can('delivery_note.approuve'))
                                                    <a class="btn btn-warning btn-sm text-white" href="{{ route('admin.delivery-note.approve', $delivery_note->delivery_number) }}"
                                                        onclick="event.preventDefault(); document.getElementById('approve-form-{{ $delivery_note->delivery_number }}').submit();" title="@lang('messages.Approuver')">
                                                        <i class="fa fa-thumbs-up"></i>
                                                    </a>
                                                    <form id="approve-form-{{ $delivery_note->delivery_number }}" action="{{ route('admin.delivery-note.approve', $delivery_note->delivery_number) }}" method="POST" style="display: none;">
                                                        @method('PUT')
                                                        @csrf
                                                    </form>
                                                @endif
                                            @endif
                                            
                                            @if($delivery_note->etat == '3')
                                                @if($delivery_note->flag1 == '1')
                                                    @php
                                                        $invoice = \App\Models\Invoice::where('document_no', $delivery_note->delivery_number)->first();
                                                    @endphp
                                                    @if($invoice && $invoice->etat == '0' && Auth::guard('admin')->user()->can('invoice.edit'))
                                                        <a class="btn btn-warning btn-sm text-white" href="{{ route('admin.invoice.edit', $invoice->invoice_number) }}" title="@lang('messages.Modifier la Facture')">
                                                            <i class="fa fa-edit"></i> @lang('messages.Modifier Facture')
                                                        </a>
                                                    @endif
                                                @else
                                                    @if (Auth::guard('admin')->user()->can('invoice.create'))
                                                        <a class="btn btn-success btn-sm text-white" href="{{ route('admin.invoice.create', $delivery_note->delivery_number) }}" title="@lang('messages.Créer Facture')">
                                                            <i class="fa fa-file-invoice"></i> @lang('messages.Facture')
                                                        </a>
                                                    @endif
                                                @endif
                                            @endif
                                            
                                            @if (!$hasValidatedInvoice)
                                                @if (Auth::guard('admin')->user()->can('delivery_note.delete'))
                                                    <a class="btn btn-danger btn-sm text-white" href="{{ route('admin.delivery-note.destroy', $delivery_note->delivery_number) }}"
                                                        onclick="event.preventDefault(); if(confirm(@json(__('messages.Êtes-vous sûr de vouloir supprimer ce bon de livraison ?')))) { document.getElementById('delete-form-{{ $delivery_note->delivery_number }}').submit(); }" title="@lang('messages.Supprimer')">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                    <form id="delete-form-{{ $delivery_note->delivery_number }}" action="{{ route('admin.delivery-note.destroy', $delivery_note->delivery_number) }}" method="POST" style="display: none;">
                                                        @method('DELETE')
                                                        @csrf
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
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
