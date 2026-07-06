@extends('backend.layouts.master')

@section('title')
@lang('messages.Bons de commande') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Bons de commande')</h4>
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

<div class="main-content-inner">
    <div class="row">
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title float-left">@lang('messages.Bons de commande')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('sales_order.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.sales-order.create') }}">@lang('messages.new')</a>
                        @endif
                    </p>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials._index_filter_export', ['filterRoute' => 'admin.sales-order.index', 'exportRoute' => 'admin.sales-order.export-to-excel'])
                    @include('backend.layouts.partials.messages')
                    <div class="data-tables">
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="12%">@lang('messages.N° Bon de commande')</th>
                                    <th width="10%">@lang('messages.Date')</th>
                                    <th width="25%">@lang('messages.Signature')</th>
                                    <th width="20%">@lang('messages.Client')</th>
                                    <th width="10%">@lang('messages.Etat')</th>
                                    <th width="10%">@lang('messages.Auteur')</th>
                                    <th width="25%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sales_orders as $so)
                                @php
                                    $linkedInvoice = \App\Models\Invoice::where('document_no', $so->sales_order_number)->first();
                                    $hasValidatedInvoice = $linkedInvoice && in_array($linkedInvoice->etat, ['1', '2']);
                                    $canEditSo = in_array($so->etat, ['0','1','2','3']) && !$hasValidatedInvoice;
                                @endphp
                                <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td><a href="{{ route('admin.sales-order.show', $so->sales_order_number) }}">{{ $so->sales_order_number }}</a></td>
                                    <td>{{ $so->sales_order_date ? \Carbon\Carbon::parse($so->sales_order_date)->format('d/m/Y') : 'N/A' }}</td>
                                    <td>{{ $so->sales_order_signature ?? 'N/A' }}</td>
                                    <td>{{ $so->client ? $so->client->customer_name : 'N/A' }}</td>
                                    <td>
                                        @if($so->etat == '0') <span class="badge badge-secondary">@lang('messages.Brouillon')</span>
                                        @elseif($so->etat == '1') <span class="badge badge-success">@lang('messages.Validé')</span>
                                        @elseif($so->etat == '2') <span class="badge badge-info">@lang('messages.Confirmé')</span>
                                        @elseif($so->etat == '3') <span class="badge badge-primary">@lang('messages.Approuvé')</span>
                                        @else <span class="badge badge-secondary">{{ $so->etat }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $so->auteur ?? 'N/A' }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a class="btn btn-info btn-sm text-white" href="{{ route('admin.sales-order.show', $so->sales_order_number) }}" title="Voir"><i class="fa fa-eye"></i></a>
                                            @if($canEditSo && Auth::guard('admin')->user()->can('sales_order.edit'))
                                                <a class="btn btn-success btn-sm text-white" href="{{ route('admin.sales-order.edit', $so->sales_order_number) }}" title="Modifier"><i class="fa fa-edit"></i></a>
                                            @endif
                                            @if(!$linkedInvoice)
                                                @if($so->etat == '0' && Auth::guard('admin')->user()->can('sales_order.validate'))
                                                    <a class="btn btn-primary btn-sm text-white" href="#" onclick="event.preventDefault(); document.getElementById('validate-{{ $so->sales_order_number }}').submit();" title="Valider"><i class="fa fa-check"></i></a>
                                                    <form id="validate-{{ $so->sales_order_number }}" action="{{ route('admin.sales-order.validate', $so->sales_order_number) }}" method="POST" style="display:none;">@csrf @method('PUT')</form>
                                                @endif
                                                @if($so->etat == '1' && Auth::guard('admin')->user()->can('sales_order.confirm'))
                                                    <a class="btn btn-info btn-sm text-white" href="#" onclick="event.preventDefault(); document.getElementById('confirm-{{ $so->sales_order_number }}').submit();" title="Confirmer"><i class="fa fa-check-circle"></i></a>
                                                    <form id="confirm-{{ $so->sales_order_number }}" action="{{ route('admin.sales-order.confirm', $so->sales_order_number) }}" method="POST" style="display:none;">@csrf @method('PUT')</form>
                                                @endif
                                                @if($so->etat == '2' && Auth::guard('admin')->user()->can('sales_order.approuve'))
                                                    <a class="btn btn-warning btn-sm text-white" href="#" onclick="event.preventDefault(); document.getElementById('approve-{{ $so->sales_order_number }}').submit();" title="Approuver"><i class="fa fa-thumbs-up"></i></a>
                                                    <form id="approve-{{ $so->sales_order_number }}" action="{{ route('admin.sales-order.approve', $so->sales_order_number) }}" method="POST" style="display:none;">@csrf @method('PUT')</form>
                                                @endif
                                                @if(Auth::guard('admin')->user()->can('sales_order.delete'))
                                                    <a class="btn btn-danger btn-sm text-white" href="#" onclick="if(confirm(@json(__('messages.Supprimer ce bon de commande ?')))) { event.preventDefault(); document.getElementById('delete-{{ $so->sales_order_number }}').submit(); }" title="@lang('messages.Supprimer')"><i class="fa fa-trash"></i></a>
                                                    <form id="delete-{{ $so->sales_order_number }}" action="{{ route('admin.sales-order.destroy', $so->sales_order_number) }}" method="POST" style="display:none;">@csrf @method('DELETE')</form>
                                                @endif
                                            @endif
                                            @if(Auth::guard('admin')->user()->can('sales_order.view'))
                                                <a class="btn btn-secondary btn-sm text-white" href="{{ route('admin.sales-order.print', $so->sales_order_number) }}" target="_blank" title="Imprimer (PDF)"><i class="fa fa-print"></i></a>
                                            @endif
                                            @if($so->document ?? null)
                                                <a class="btn btn-light btn-sm" href="{{ asset('storage/'.$so->document) }}" target="_blank" title="Document"><i class="fa fa-paperclip"></i></a>
                                            @endif
                                            @if($so->etat == '3' && !$linkedInvoice && Auth::guard('admin')->user()->can('invoice.create'))
                                                <a class="btn btn-success btn-sm text-white" href="{{ route('admin.invoice.create-from-sales-order', $so->sales_order_number) }}" title="Créer Facture"><i class="fa fa-file-text-o"></i> @lang('messages.Facture')</a>
                                            @endif
                                            @if($linkedInvoice && $linkedInvoice->etat == '0' && Auth::guard('admin')->user()->can('invoice.edit'))
                                                <a class="btn btn-warning btn-sm text-white" href="{{ route('admin.invoice.edit', $linkedInvoice->invoice_number) }}" title="Modifier Facture"><i class="fa fa-pencil"></i> @lang('messages.Edit Facture')</a>
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
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
    <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
    <script>
        if ($('#dataTable').length) { $('#dataTable').DataTable({ responsive: true, pageLength: 100, lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]] }); }
    </script>
@endsection
