
@extends('backend.layouts.master')

@section('title')
@lang('messages.Proforma Invoices') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Proforma Invoices')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Proforma Invoices')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('proforma_invoice.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.proforma-invoice.create') }}">@lang('messages.new')</a>
                        @endif
                    </p>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials._index_filter_export', ['filterRoute' => 'admin.proforma-invoice.index', 'exportRoute' => 'admin.proforma-invoice.export-to-excel'])
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.Proforma Number')</th>
                                    <th width="10%">@lang('messages.date')</th>
                                    <th width="10%">@lang('messages.Total BIF')</th>
                                    <th width="10%">@lang('messages.Équiv. USD')</th>
                                    <th width="30%">@lang('messages.proforma signature')</th>
                                    <th width="30%">@lang('messages.Customer')</th>
                                    <th width="10%">@lang('messages.status')</th>
                                    <th width="20%">@lang('messages.description')</th>
                                    <th width="10%">@lang('messages.created_by')</th>
                                    <th width="20%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($proforma_invoices as $proforma_invoice)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td><a href="{{ route('admin.proforma-invoice.show',$proforma_invoice->proforma_number) }}">{{ $proforma_invoice->proforma_number }}</a></td>
                                    <td>{{ Carbon\Carbon::parse($proforma_invoice->proforma_date)->format('d/m/Y') }}</td>
                                    @php
                                        $__piTotalBif = $proforma_invoice->details->sum('item_total_amount');
                                        $__piFxDate = $proforma_invoice->proforma_date ? \Carbon\Carbon::parse($proforma_invoice->proforma_date)->format('Y-m-d') : null;
                                        $__piFx = $__piFxDate ? \App\Models\ExchangeRate::bifPerUsdForDate($__piFxDate) : null;
                                        $__piBpu = $__piFx['bif_per_usd'] ?? null;
                                        $__piUsd = ($__piBpu && (float) $__piBpu > 0) ? $__piTotalBif / (float) $__piBpu : null;
                                    @endphp
                                    <td>{{ number_format($__piTotalBif, 0, ',', ' ') }}</td>
                                    <td>
                                        @if($__piUsd !== null)
                                            {{ number_format($__piUsd, 2, ',', ' ') }} USD
                                        @else
                                            <span class="text-muted" title="{{ \App\Models\ExchangeRate::missingBifUsdRateMessage() }}">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $proforma_invoice->proforma_signature }}</td>
                                    <td>{{ $proforma_invoice->client ? $proforma_invoice->client->customer_name : 'N/A' }}</td>
                                    <td>
                                        @if($proforma_invoice->etat == '0')
                                            <span class="badge badge-secondary">@lang('messages.proforma_status_brouillon')</span>
                                        @elseif($proforma_invoice->etat == '1')
                                            <span class="badge badge-success">@lang('messages.proforma_status_validee')</span>
                                        @elseif($proforma_invoice->etat == '2')
                                            <span class="badge badge-info">@lang('messages.proforma_status_confirmee')</span>
                                        @elseif($proforma_invoice->etat == '3')
                                            <span class="badge badge-primary">@lang('messages.proforma_status_approuvee')</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $proforma_invoice->etat }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $proforma_invoice->description ?? 'N/A' }}</td>
                                    <td>{{ $proforma_invoice->auteur ?? 'N/A' }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a class="btn btn-info btn-sm text-white" href="{{ route('admin.proforma-invoice.show', $proforma_invoice->proforma_number) }}" title="Voir">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            
                                            @php
                                                $hasValidatedInvoice = \App\Support\BillingChain::proformaHasValidatedInvoice($proforma_invoice->proforma_number);
                                            @endphp
                                            
                                            @if(in_array($proforma_invoice->etat, ['0', '1', '2', '3', '4']) && !$hasValidatedInvoice)
                                                @if (Auth::guard('admin')->user()->can('proforma_invoice.edit'))
                                                    <a class="btn btn-success btn-sm text-white" href="{{ route('admin.proforma-invoice.edit', $proforma_invoice->proforma_number) }}" title="Modifier">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                @endif
                                            @endif
                                            
                                            @if($proforma_invoice->etat == '0')
                                                @if (Auth::guard('admin')->user()->can('proforma_invoice.validate'))
                                                    <a class="btn btn-primary btn-sm text-white" href="{{ route('admin.proforma-invoice.validate', $proforma_invoice->proforma_number) }}"
                                                        onclick="event.preventDefault(); document.getElementById('validate-form-{{ $proforma_invoice->proforma_number }}').submit();" title="Valider">
                                                        <i class="fa fa-check"></i>
                                                    </a>
                                                    <form id="validate-form-{{ $proforma_invoice->proforma_number }}" action="{{ route('admin.proforma-invoice.validate', $proforma_invoice->proforma_number) }}" method="POST" style="display: none;">
                                                        @method('PUT')
                                                        @csrf
                                                    </form>
                                                @endif
                                            @endif
                                            
                                            @if($proforma_invoice->etat == '1')
                                                @if (Auth::guard('admin')->user()->can('proforma_invoice.confirm'))
                                                    <a class="btn btn-info btn-sm text-white" href="{{ route('admin.proforma-invoice.confirm', $proforma_invoice->proforma_number) }}"
                                                        onclick="event.preventDefault(); document.getElementById('confirm-form-{{ $proforma_invoice->proforma_number }}').submit();" title="Confirmer">
                                                        <i class="fa fa-check-circle"></i>
                                                    </a>
                                                    <form id="confirm-form-{{ $proforma_invoice->proforma_number }}" action="{{ route('admin.proforma-invoice.confirm', $proforma_invoice->proforma_number) }}" method="POST" style="display: none;">
                                                        @method('PUT')
                                                        @csrf
                                                    </form>
                                                @endif
                                            @endif
                                            
                                            @if($proforma_invoice->etat == '2')
                                                @if (Auth::guard('admin')->user()->can('proforma_invoice.approuve'))
                                                    <a class="btn btn-warning btn-sm text-white" href="{{ route('admin.proforma-invoice.approve', $proforma_invoice->proforma_number) }}"
                                                        onclick="event.preventDefault(); document.getElementById('approve-form-{{ $proforma_invoice->proforma_number }}').submit();" title="Approuver">
                                                        <i class="fa fa-thumbs-up"></i>
                                                    </a>
                                                    <form id="approve-form-{{ $proforma_invoice->proforma_number }}" action="{{ route('admin.proforma-invoice.approve', $proforma_invoice->proforma_number) }}" method="POST" style="display: none;">
                                                        @method('PUT')
                                                        @csrf
                                                    </form>
                                                @endif
                                            @endif
                                            
                                            @if($proforma_invoice->etat == '3')
                                                @php
                                                    $linkedSalesOrder = \App\Models\SalesOrder::where('document_no', $proforma_invoice->proforma_number)->first();
                                                @endphp
                                                @if (!$linkedSalesOrder && Auth::guard('admin')->user()->can('sales_order.create'))
                                                    <a class="btn btn-success btn-sm text-white" href="{{ route('admin.sales-order.create-from-proforma', $proforma_invoice->proforma_number) }}" title="@lang('messages.Créer Bon de commande (données de la facture proforma auto-complétées)')">
                                                        <i class="fa fa-shopping-cart"></i> @lang('messages.Bon de commande')
                                                    </a>
                                                @elseif($linkedSalesOrder && !$hasValidatedInvoice && Auth::guard('admin')->user()->can('sales_order.edit'))
                                                    <a class="btn btn-warning btn-sm text-white" href="{{ route('admin.sales-order.edit', $linkedSalesOrder->sales_order_number) }}" title="@lang('messages.Modifier le Bon de commande')">
                                                        <i class="fa fa-edit"></i> @lang('messages.Modifier BC')
                                                    </a>
                                                @endif
                                            @endif
                                            @if($proforma_invoice->flag1 == '1')
                                                <span class="badge badge-info" title="@lang('messages.Déjà livré')">@lang('messages.Livré')</span>
                                                @php
                                                    $delivery_note = \App\Models\DeliveryNote::where('document_no', $proforma_invoice->proforma_number)->first();
                                                @endphp
                                                @if($delivery_note && !$hasValidatedInvoice && Auth::guard('admin')->user()->can('delivery_note.edit'))
                                                    <a class="btn btn-warning btn-sm text-white" href="{{ route('admin.delivery-note.edit', $delivery_note->delivery_number) }}" title="@lang('messages.Modifier le Bon de Livraison')">
                                                        <i class="fa fa-edit"></i> @lang('messages.Modifier BL')
                                                    </a>
                                                @endif
                                            @endif
                                            
                                            @if (Auth::guard('admin')->user()->can('proforma_invoice.view'))
                                                <a class="btn btn-secondary btn-sm text-white" href="{{ route('admin.proforma-invoice.print', $proforma_invoice->proforma_number) }}" target="_blank" title="Imprimer (PDF BIF)"><i class="fa fa-print"></i></a>
                                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.proforma-invoice.print-usd', $proforma_invoice->proforma_number) }}" target="_blank" title="Imprimer (PDF USD)"><i class="fa fa-dollar-sign"></i></a>
                                            @endif
                                            @if (!$hasValidatedInvoice)
                                                @if (Auth::guard('admin')->user()->can('proforma_invoice.delete'))
                                                    <a class="btn btn-danger btn-sm text-white" href="{{ route('admin.proforma-invoice.destroy', $proforma_invoice->proforma_number) }}"
                                                        onclick="event.preventDefault(); if(confirm(@json(__('messages.delete_proforma_confirm')))) { document.getElementById('delete-form-{{ $proforma_invoice->proforma_number }}').submit(); }" title="@lang('messages.delete')">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                    <form id="delete-form-{{ $proforma_invoice->proforma_number }}" action="{{ route('admin.proforma-invoice.destroy', $proforma_invoice->proforma_number) }}" method="POST" style="display: none;">
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
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                language: window.dtLanguage || {}
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