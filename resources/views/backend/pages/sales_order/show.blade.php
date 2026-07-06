@extends('backend.layouts.master')

@section('title')
@lang('messages.Bon de commande') {{ $sales_order->sales_order_number }} - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .nav-tabs .nav-link { color: #495057; }
    .nav-tabs .nav-link.active { background-color: #fff; border-color: #dee2e6 #dee2e6 #fff; }
    .badge-etat-0 { background-color: #6c757d; color: white; }
    .badge-etat-1 { background-color: #28a745; color: white; }
    .badge-etat-2 { background-color: #17a2b8; color: white; }
    .badge-etat-3 { background-color: #007bff; color: white; }
</style>
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Bon de commande') {{ $sales_order->sales_order_number }}</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.sales-order.index') }}">@lang('messages.Bons de commande')</a></li>
                    <li><span>@lang('messages.Détail')</span></li>
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
                    @php
                        $linkedInvoice = \App\Models\Invoice::where('document_no', $sales_order->sales_order_number)->first();
                        $hasValidatedInvoice = $linkedInvoice && in_array($linkedInvoice->etat, ['1', '2']);
                        $canEditSo = in_array($sales_order->etat, ['0','1','2','3']) && !$hasValidatedInvoice;
                        $linkedProforma = $sales_order->document_no
                            ? \App\Models\ProformaInvoice::where('proforma_number', $sales_order->document_no)->first()
                            : null;
                    @endphp
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="header-title">@lang('messages.Bon de commande N°') {{ $sales_order->sales_order_number }}</h4>
                        <div>
                            @if($canEditSo && Auth::guard('admin')->user()->can('sales_order.edit'))
                                <a class="btn btn-success text-white" href="{{ route('admin.sales-order.edit', $sales_order->sales_order_number) }}">@lang('messages.Modifier')</a>
                            @endif
                            @if($linkedProforma && Auth::guard('admin')->user()->can('proforma_invoice.view'))
                                <a class="btn btn-outline-primary" href="{{ route('admin.proforma-invoice.show', $linkedProforma->proforma_number) }}">Proforma {{ $linkedProforma->proforma_number }}</a>
                            @endif
                            @if(!$linkedInvoice)
                                @if($sales_order->etat == '0' && Auth::guard('admin')->user()->can('sales_order.validate'))
                                    <a class="btn btn-primary text-white" href="#" onclick="event.preventDefault(); document.getElementById('validate-form').submit();">@lang('messages.Valider')</a>
                                    <form id="validate-form" action="{{ route('admin.sales-order.validate', $sales_order->sales_order_number) }}" method="POST" style="display:none;">@csrf @method('PUT')</form>
                                @endif
                                @if($sales_order->etat == '1' && Auth::guard('admin')->user()->can('sales_order.confirm'))
                                    <a class="btn btn-info text-white" href="#" onclick="event.preventDefault(); document.getElementById('confirm-form').submit();">@lang('messages.Confirmer')</a>
                                    <form id="confirm-form" action="{{ route('admin.sales-order.confirm', $sales_order->sales_order_number) }}" method="POST" style="display:none;">@csrf @method('PUT')</form>
                                @endif
                                @if($sales_order->etat == '2' && Auth::guard('admin')->user()->can('sales_order.approuve'))
                                    <a class="btn btn-warning text-white" href="#" onclick="event.preventDefault(); document.getElementById('approve-form').submit();">@lang('messages.Approuver')</a>
                                    <form id="approve-form" action="{{ route('admin.sales-order.approve', $sales_order->sales_order_number) }}" method="POST" style="display:none;">@csrf @method('PUT')</form>
                                @endif
                            @endif
                            @if(Auth::guard('admin')->user()->can('sales_order.view'))
                                <a class="btn btn-info text-white" href="{{ route('admin.sales-order.print', $sales_order->sales_order_number) }}" target="_blank"><i class="fa fa-print"></i> @lang('messages.Imprimer')</a>
                            @endif
                            @if($sales_order->document ?? null)
                                <a class="btn btn-secondary text-white" href="{{ asset('storage/'.$sales_order->document) }}" target="_blank" title="Voir le document"><i class="fa fa-eye"></i> @lang('messages.Voir document')</a>
                                <a class="btn btn-secondary text-white" href="{{ asset('storage/'.$sales_order->document) }}" download="{{ basename($sales_order->document) }}" title="Télécharger le document"><i class="fa fa-download"></i> @lang('messages.Télécharger')</a>
                            @endif
                            @if($sales_order->etat == '3' && !$linkedInvoice && Auth::guard('admin')->user()->can('invoice.create'))
                                <a class="btn btn-success text-white" href="{{ route('admin.invoice.create-from-sales-order', $sales_order->sales_order_number) }}">@lang('messages.Créer Facture')</a>
                            @endif
                            @if($linkedInvoice && $linkedInvoice->etat == '0' && Auth::guard('admin')->user()->can('invoice.edit'))
                                <a class="btn btn-warning text-white" href="{{ route('admin.invoice.edit', $linkedInvoice->invoice_number) }}">@lang('messages.Modifier Facture')</a>
                            @endif
                            <a class="btn btn-light" href="{{ route('admin.sales-order.index') }}">
                                <i class="fa fa-arrow-left"></i> {{ __('messages.back') }}
                            </a>
                        </div>
                    </div>
                    @include('backend.layouts.partials.messages')

                    @if($sales_order->details->count() > 0)
                    <ul class="nav nav-tabs mt-4" id="salesOrderTabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" id="info-tab" data-toggle="tab" href="#info" role="tab">@lang('messages.Informations')</a></li>
                        <li class="nav-item"><a class="nav-link" id="items-tab" data-toggle="tab" href="#items" role="tab">Articles ({{ $sales_order->details->count() }})</a></li>
                    </ul>
                    <div class="tab-content mt-4" id="salesOrderTabsContent">
                        <div class="tab-pane fade show active" id="info" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr><th width="40%">@lang('messages.N° Bon de commande')</th><td><strong>{{ $sales_order->sales_order_number }}</strong></td></tr>
                                        <tr><th>@lang('messages.Date')</th><td>{{ $sales_order->sales_order_date ? \Carbon\Carbon::parse($sales_order->sales_order_date)->format('d/m/Y H:i') : 'N/A' }}</td></tr>
                                        <tr><th>@lang('messages.Signature')</th><td>{{ $sales_order->sales_order_signature ?? 'N/A' }}</td></tr>
                                        <tr><th>@lang('messages.État')</th><td>
                                            @if($sales_order->etat == '0') <span class="badge badge-etat-0">@lang('messages.Brouillon')</span>
                                            @elseif($sales_order->etat == '1') <span class="badge badge-etat-1">@lang('messages.Validé')</span>
                                            @elseif($sales_order->etat == '2') <span class="badge badge-etat-2">@lang('messages.Confirmé')</span>
                                            @elseif($sales_order->etat == '3') <span class="badge badge-etat-3">@lang('messages.Approuvé')</span>
                                            @else <span class="badge badge-secondary">{{ $sales_order->etat }}</span>
                                            @endif
                                        </td></tr>
                                        <tr><th>@lang('messages.Créé par')</th><td>{{ $sales_order->auteur ?? 'N/A' }}</td></tr>
                                        {{-- Description affichée sous le tableau des articles --}}
                                        @if($sales_order->document ?? null)
                                        <tr><th>@lang('messages.Document')</th><td><a href="{{ asset('storage/'.$sales_order->document) }}" target="_blank">{{ basename($sales_order->document) }}</a></td></tr>
                                        @endif
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr><th width="40%">@lang('messages.Client')</th><td>{{ $sales_order->client ? $sales_order->client->customer_name : 'N/A' }}</td></tr>
                                        @if($sales_order->client)
                                        <tr><th>@lang('messages.Téléphone')</th><td>{{ $sales_order->client->telephone ?? 'N/A' }}</td></tr>
                                        <tr><th>@lang('messages.Adresse')</th><td>{{ $sales_order->client->customer_address ?? 'N/A' }}</td></tr>
                                        @endif
                                        <tr><th>@lang('messages.Nombre d\'articles')</th><td><strong>{{ $sales_order->details->count() }}</strong></td></tr>
                                        <tr><th>@lang('messages.Montant total')</th><td><strong class="text-success">{{ number_format($sales_order->details->sum('item_total_amount'), 0, ',', ' ') }} BIF</strong></td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="items" role="tabpanel">
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered table-striped">
                                    <thead><tr><th>#</th><th>@lang('messages.Article')</th><th>@lang('messages.Quantité')</th><th>@lang('messages.Prix unit.')</th><th>@lang('messages.Total')</th></tr></thead>
                                    <tbody>
                                        @foreach($sales_order->details as $d)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $d->item_designation ?? ($d->item->name ?? 'N/A') }}</td>
                                            <td>{{ number_format($d->item_quantity ?? 0, 0, ',', ' ') }}</td>
                                            <td>{{ number_format($d->item_price ?? 0, 0, ',', ' ') }} BIF</td>
                                            <td><strong>{{ number_format($d->item_total_amount ?? 0, 0, ',', ' ') }} BIF</strong></td>
                                        </tr>
                                        @endforeach
                                        <tr class="table-info">
                                            <td colspan="4" class="text-right"><strong>@lang('messages.TOTAL')</strong></td>
                                            <td><strong>{{ number_format($sales_order->details->sum('item_total_amount'), 0, ',', ' ') }} BIF</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <label><strong>@lang('messages.Description:')</strong></label>
                                <p>{{ $sales_order->description ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning mt-4">@lang('messages.Aucun détail pour ce bon de commande.')</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
