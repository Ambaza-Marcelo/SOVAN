
@extends('backend.layouts.master')

@section('title')
@lang('messages.Invoices') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Invoices')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Factures')</h4>
                    {{-- Création de facture depuis le BL désactivée pour éviter la double facturation
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('invoice.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.delivery-note.index') }}">@lang('messages.Créer depuis Bon de Livraison')</a>
                        @endif
                    </p>
                    --}}
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials._index_filter_export', ['filterRoute' => 'admin.invoice.index', 'exportRoute' => 'admin.invoice.export-to-excel'])
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.Numéro Facture')</th>
                                    <th width="10%">@lang('messages.date')</th>
                                    <th width="9%">@lang('messages.Total BIF')</th>
                                    <th width="9%">@lang('messages.Équiv. USD')</th>
                                    <th width="20%">@lang('messages.Signature')</th>
                                    <th width="20%">@lang('messages.Customer')</th>
                                    <th width="10%">@lang('messages.Bon de Livraison')</th>
                                    <th width="10%">@lang('messages.status')</th>
                                    <th width="10%">@lang('messages.created_by')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($invoices as $invoice)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td><a href="{{ route('admin.invoice.show', $invoice->invoice_number) }}">{{ $invoice->invoice_number }}</a></td>
                                    <td>{{ $invoice->invoice_date ? Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') : 'N/A' }}</td>
                                    @php
                                        $__invTotalBif = $invoice->details->sum('item_total_amount');
                                        $__invFxDate = $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : null;
                                        $__invFx = $__invFxDate ? \App\Models\ExchangeRate::bifPerUsdForDate($__invFxDate) : null;
                                        $__invBpu = $__invFx['bif_per_usd'] ?? null;
                                        $__invUsd = ($__invBpu && (float) $__invBpu > 0) ? $__invTotalBif / (float) $__invBpu : null;
                                    @endphp
                                    <td>{{ number_format($__invTotalBif, 0, ',', ' ') }}</td>
                                    <td>
                                        @if($__invUsd !== null)
                                            {{ number_format($__invUsd, 2, ',', ' ') }} USD
                                        @else
                                            <span class="text-muted" title="{{ \App\Models\ExchangeRate::missingBifUsdRateMessage() }}">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $invoice->invoice_signature ?? 'N/A' }}</td>
                                    <td>{{ $invoice->client ? $invoice->client->customer_name : 'N/A' }}</td>
                                    <td>
                                        @if($invoice->document_no)
                                            <a href="{{ route('admin.delivery-note.show', $invoice->document_no) }}">
                                                {{ $invoice->document_no }}
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if($invoice->etat == '0')
                                            <span class="badge badge-secondary">@lang('messages.Brouillon')</span>
                                        @elseif($invoice->etat == '1')
                                            <span class="badge badge-success">@lang('messages.Validée')</span>
                                        @elseif($invoice->etat == '2')
                                            <span class="badge badge-info">@lang('messages.Confirmée')</span>
                                        @elseif($invoice->etat == '3')
                                            <span class="badge badge-primary">@lang('messages.Approuvée')</span>
                                        @elseif($invoice->etat == '-1')
                                            <span class="badge badge-danger">@lang('messages.Annulée')</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $invoice->etat }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $invoice->auteur ?? 'N/A' }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a class="btn btn-info btn-sm text-white" href="{{ route('admin.invoice.show', $invoice->invoice_number) }}" title="@lang('messages.Voir')">
                                                <i class="fa fa-eye"></i>
                                            </a>
            
                                                @if (Auth::guard('admin')->user()->can('invoice.view'))
                                                    <a class="btn btn-secondary btn-sm text-white" href="{{ route('admin.invoice.print', $invoice->invoice_number) }}" target="_blank" title="@lang('messages.Imprimer (PDF BIF) - transmet à l\'OBR si nécessaire')"><i class="fa fa-print"></i></a>
                                                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.invoice.print-usd', $invoice->invoice_number) }}" target="_blank" title="@lang('messages.Imprimer (PDF USD, sans OBR)')"><i class="fa fa-dollar-sign"></i></a>
                                                @endif
                                            @if(in_array((string) $invoice->etat, ['1', '2'], true) && Auth::guard('admin')->user()->can('note_credit.create'))
                                                <a class="btn btn-warning btn-sm text-white" href="{{ route('admin.note-de-credit.create', $invoice->invoice_number) }}" title="@lang('messages.Facture d\'avoir (FA)')">
                                                    <i class="fa fa-file-invoice-dollar"></i> FA
                                                </a>
                                            @endif
                                            @if($invoice->etat == '0')
                                                @if (Auth::guard('admin')->user()->can('invoice.edit'))
                                                    <a class="btn btn-success btn-sm text-white" href="{{ route('admin.invoice.edit', $invoice->invoice_number) }}" title="@lang('messages.Modifier')">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                @endif
                                            @endif

                                            @if($invoice->etat == '0' && Auth::guard('admin')->user()->can('invoice.reset'))
                                                <button
                                                    type="button"
                                                    class="btn btn-warning btn-sm text-white cancel-invoice-btn"
                                                    data-invoice-number="{{ $invoice->invoice_number }}"
                                                    data-toggle="modal"
                                                    data-target="#cancelInvoiceModal"
                                                    title="@lang('messages.Annuler la facture')"
                                                >
                                                    <i class="fa fa-times-circle"></i> @lang('messages.Annuler')
                                                </button>
                                            @endif
                                            
                                            @if($invoice->etat == '0')
                                                @if (Auth::guard('admin')->user()->can('invoice.view'))
                                                    <a class="btn btn-primary btn-sm text-white" href="{{ route('admin.invoice.cash', $invoice->invoice_number) }}" title="@lang('messages.Valider Cash')">
                                                        <i class="fa fa-money-bill"></i> @lang('messages.Valider Cash')
                                                    </a>
                                                    <a class="btn btn-info btn-sm text-white" href="{{ route('admin.invoice.credit', $invoice->invoice_number) }}" title="@lang('messages.Valider Credit')">
                                                        <i class="fa fa-credit-card"></i> @lang('messages.Valider Credit')
                                                    </a>
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

@if (Auth::guard('admin')->user()->can('invoice.reset'))
    <div class="modal fade" id="cancelInvoiceModal" tabindex="-1" role="dialog" aria-labelledby="cancelInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form
                    id="cancelInvoiceForm"
                    action="{{ route('admin.invoice.cancel', '__INVOICE__') }}"
                    method="POST"
                    data-base-action="{{ route('admin.invoice.cancel', '__INVOICE__') }}"
                >
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelInvoiceModalLabel">@lang('messages.Annuler la facture')</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label for="cn_motif">@lang('messages.Motif d\'annulation')</label>
                            <textarea id="cn_motif" name="cn_motif" class="form-control" rows="4" required>{{ old('cn_motif') }}</textarea>
                            @error('cn_motif')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('messages.Fermer')</button>
                        <button type="submit" class="btn btn-danger">@lang('messages.Confirmer')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif


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

        // Set cancel form URL based on clicked invoice
        $(document).on('click', '.cancel-invoice-btn', function () {
            var invoiceNumber = $(this).data('invoice-number');
            var baseAction = $('#cancelInvoiceForm').data('base-action');
            if (invoiceNumber && baseAction) {
                $('#cancelInvoiceForm').attr('action', baseAction.replace('__INVOICE__', invoiceNumber));
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
