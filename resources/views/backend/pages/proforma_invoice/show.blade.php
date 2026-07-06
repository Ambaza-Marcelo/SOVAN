
@extends('backend.layouts.master')

@section('title')
@lang('messages.Proforma Invoice Details') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .nav-tabs .nav-link {
        color: #495057;
        border: 1px solid transparent;
        border-top-left-radius: 0.25rem;
        border-top-right-radius: 0.25rem;
    }
    .nav-tabs .nav-link:hover {
        border-color: #e9ecef #e9ecef #dee2e6;
    }
    .nav-tabs .nav-link.active {
        color: #495057;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
    }
    .card-header {
        font-weight: 600;
    }
    .table th {
        background-color: #f8f9fa;
    }
    .badge-etat-0 { background-color: #6c757d; color: white; }
    .badge-etat-1 { background-color: #28a745; color: white; }
    .badge-etat-2 { background-color: #17a2b8; color: white; }
    .badge-etat-3 { background-color: #ffc107; color: black; }
    .badge-etat-4 { background-color: #007bff; color: white; }
</style>
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Proforma Invoice Details')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.proforma-invoice.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Details')</span></li>
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
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="header-title">@lang('messages.Facture Proforma No:') {{ $proforma_invoice->proforma_number }}</h4>
                        <div>
                            @php
                                $hasValidatedInvoice = \App\Support\BillingChain::proformaHasValidatedInvoice($proforma_invoice->proforma_number);
                                $linkedSalesOrder = \App\Models\SalesOrder::where('document_no', $proforma_invoice->proforma_number)->first();
                            @endphp
                            
                            @if(in_array($proforma_invoice->etat, ['0', '1', '2', '3', '4']) && !$hasValidatedInvoice)
                                @if (Auth::guard('admin')->user()->can('proforma_invoice.edit'))
                                    <a class="btn btn-success text-white" href="{{ route('admin.proforma-invoice.edit', $proforma_invoice->proforma_number) }}">@lang('messages.edit')</a>
                                @endif
                            @endif
                            @if($proforma_invoice->etat == '0')
                                @if (Auth::guard('admin')->user()->can('proforma_invoice.validate'))
                                    <a class="btn btn-primary text-white" href="{{ route('admin.proforma-invoice.validate', $proforma_invoice->proforma_number) }}"
                                        onclick="event.preventDefault(); document.getElementById('validate-form').submit();">
                                        @lang('messages.Valider')
                                    </a>
                                    <form id="validate-form" action="{{ route('admin.proforma-invoice.validate', $proforma_invoice->proforma_number) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                    </form>
                                @endif
                            @endif
                            @if($proforma_invoice->etat == '1')
                                @if (Auth::guard('admin')->user()->can('proforma_invoice.confirm'))
                                    <a class="btn btn-info text-white" href="{{ route('admin.proforma-invoice.confirm', $proforma_invoice->proforma_number) }}"
                                        onclick="event.preventDefault(); document.getElementById('confirm-form').submit();">
                                        @lang('messages.Confirmer')
                                    </a>
                                    <form id="confirm-form" action="{{ route('admin.proforma-invoice.confirm', $proforma_invoice->proforma_number) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                    </form>
                                @endif
                            @endif
                            @if($proforma_invoice->etat == '2')
                                @if (Auth::guard('admin')->user()->can('proforma_invoice.approve'))
                                    <a class="btn btn-warning text-white" href="{{ route('admin.proforma-invoice.approve', $proforma_invoice->proforma_number) }}"
                                        onclick="event.preventDefault(); document.getElementById('approve-form').submit();">
                                        @lang('messages.Approuver')
                                    </a>
                                    <form id="approve-form" action="{{ route('admin.proforma-invoice.approve', $proforma_invoice->proforma_number) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                    </form>
                                @endif
                            @endif
                            @if($linkedSalesOrder)
                                <a class="btn btn-outline-primary" href="{{ route('admin.sales-order.show', $linkedSalesOrder->sales_order_number) }}">BC {{ $linkedSalesOrder->sales_order_number }}</a>
                                @if(!$hasValidatedInvoice && Auth::guard('admin')->user()->can('sales_order.edit'))
                                    <a class="btn btn-warning text-white" href="{{ route('admin.sales-order.edit', $linkedSalesOrder->sales_order_number) }}">@lang('messages.Modifier BC')</a>
                                @endif
                            @elseif($proforma_invoice->etat == '3' && Auth::guard('admin')->user()->can('sales_order.create'))
                                <a class="btn btn-success text-white" href="{{ route('admin.sales-order.create-from-proforma', $proforma_invoice->proforma_number) }}">@lang('messages.Créer Bon de commande')</a>
                            @endif
                            @php
                                $linkedDeliveryNote = \App\Models\DeliveryNote::where('document_no', $proforma_invoice->proforma_number)->first();
                            @endphp
                            @if($linkedDeliveryNote)
                                <a class="btn btn-outline-info" href="{{ route('admin.delivery-note.show', $linkedDeliveryNote->delivery_number) }}">BL {{ $linkedDeliveryNote->delivery_number }}</a>
                                @if(!$hasValidatedInvoice && Auth::guard('admin')->user()->can('delivery_note.edit'))
                                    <a class="btn btn-warning text-white" href="{{ route('admin.delivery-note.edit', $linkedDeliveryNote->delivery_number) }}">@lang('messages.Modifier BL')</a>
                                @endif
                            @endif
                            @if (Auth::guard('admin')->user()->can('proforma_invoice.view'))
                                <a class="btn btn-info text-white" href="{{ route('admin.proforma-invoice.print', $proforma_invoice->proforma_number) }}" target="_blank" title="@lang('messages.Imprimer (PDF)')">
                                    <i class="fa fa-print"></i> @lang('messages.Imprimer (BIF)')
                                </a>
                                <a class="btn btn-outline-light text-dark border" href="{{ route('admin.proforma-invoice.print-usd', $proforma_invoice->proforma_number) }}" target="_blank" title="@lang('messages.Imprimer en USD (PDF)')">
                                    <i class="fa fa-dollar-sign"></i> @lang('messages.PDF USD')
                                </a>
                            @endif
                            @if($proforma_invoice->document ?? null)
                                <a class="btn btn-secondary text-white" href="{{ asset('storage/' . $proforma_invoice->document) }}" target="_blank" title="Voir le document"><i class="fa fa-eye"></i> @lang('messages.Voir document')</a>
                                <a class="btn btn-secondary text-white" href="{{ asset('storage/' . $proforma_invoice->document) }}" download="{{ basename($proforma_invoice->document) }}" title="Télécharger le document"><i class="fa fa-download"></i> @lang('messages.Télécharger')</a>
                            @endif
                            <a class="btn btn-light" href="{{ route('admin.proforma-invoice.index') }}">
                                <i class="fa fa-arrow-left"></i> {{ __('messages.back') }}
                            </a>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials.messages')
                    
                    @if($proforma_invoice->details->count() > 0)
                    @php
                        $total_amount = $proforma_invoice->details->sum('item_total_amount');
                        $_proFxDate = $proforma_invoice->proforma_date ? \Carbon\Carbon::parse($proforma_invoice->proforma_date)->format('Y-m-d') : null;
                        $_proFx = $_proFxDate ? \App\Models\ExchangeRate::bifPerUsdForDate($_proFxDate) : null;
                        $_proBifPerUsd = $_proFx['bif_per_usd'] ?? null;
                        $_proFxRateLabel = isset($_proFx['rate_date']) && $_proFx['rate_date'] ? \Carbon\Carbon::parse($_proFx['rate_date'])->format('d/m/Y') : null;
                        $_proUsdTotal = ($_proBifPerUsd && (float) $_proBifPerUsd > 0) ? $total_amount / (float) $_proBifPerUsd : null;
                    @endphp
                    @if($_proUsdTotal === null)
                        <div class="alert alert-warning mt-3 mb-0">{{ \App\Models\ExchangeRate::missingBifUsdRateMessage() }}</div>
                    @endif
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs mt-4" id="proformaInvoiceTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="info-tab" data-toggle="tab" href="#info" role="tab" aria-controls="info" aria-selected="true">
                                Informations Générales
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="client-tab" data-toggle="tab" href="#client" role="tab" aria-controls="client" aria-selected="false">
                                Client
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="items-tab" data-toggle="tab" href="#items" role="tab" aria-controls="items" aria-selected="false">
                                Articles ({{ $proforma_invoice->details->count() }})
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="status-tab" data-toggle="tab" href="#status" role="tab" aria-controls="status" aria-selected="false">
                                Statut & Historique
                            </a>
                        </li>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content mt-4" id="proformaInvoiceTabsContent">
                        <!-- General Information Tab -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0">@lang('messages.Informations de Base')</h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered">
                                                <tbody>
                                                    <tr>
                                                        <th width="40%">@lang('messages.Numéro Proforma:')</th>
                                                        <td><strong>{{ $proforma_invoice->proforma_number ?? 'N/A' }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Date:')</th>
                                                        <td>{{ $proforma_invoice->proforma_date ? Carbon\Carbon::parse($proforma_invoice->proforma_date)->format('d/m/Y H:i') : 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Signature:')</th>
                                                        <td>{{ $proforma_invoice->proforma_signature ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Date de Signature:')</th>
                                                        <td>{{ $proforma_invoice->proforma_signature_date ? Carbon\Carbon::parse($proforma_invoice->proforma_signature_date)->format('d/m/Y H:i') : 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.État:')</th>
                                                        <td>
                                                            @if($proforma_invoice->etat == '0')
                                                                <span class="badge badge-etat-0">@lang('messages.Brouillon')</span>
                                                            @elseif($proforma_invoice->etat == '1')
                                                                <span class="badge badge-etat-1">@lang('messages.Validée')</span>
                                                            @elseif($proforma_invoice->etat == '2')
                                                                <span class="badge badge-etat-2">@lang('messages.Confirmée')</span>
                                                            @elseif($proforma_invoice->etat == '3')
                                                                <span class="badge badge-etat-3">@lang('messages.En Attente')</span>
                                                            @elseif($proforma_invoice->etat == '4')
                                                                <span class="badge badge-etat-4">@lang('messages.Approuvée')</span>
                                                            @else
                                                                <span class="badge badge-secondary">{{ $proforma_invoice->etat }}</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Créé par:')</th>
                                                        <td>{{ $proforma_invoice->auteur ?? 'N/A' }}</td>
                                                    </tr>
                                                    {{-- Description affichée sous le tableau des articles --}}
                                                    @if($proforma_invoice->document ?? null)
                                                    <tr>
                                                        <th>@lang('messages.Document:')</th>
                                                        <td><a href="{{ asset('storage/' . $proforma_invoice->document) }}" target="_blank">{{ basename($proforma_invoice->document) }}</a></td>
                                                    </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0">@lang('messages.Résumé Financier')</h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered">
                                                <tbody>
                                                    <tr>
                                                        <th width="40%">@lang('messages.Nombre d\'Articles:')</th>
                                                        <td><strong>{{ $proforma_invoice->details->count() }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Quantité Totale:')</th>
                                                        <td><strong>{{ $proforma_invoice->details->sum('item_quantity') }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Montant Total:')</th>
                                                        <td><strong class="text-success">{{ number_format($total_amount, 0, ',', ' ') }} BIF</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Équivalent USD:')</th>
                                                        <td>
                                                            @if($_proUsdTotal !== null)
                                                                <strong class="text-primary">{{ number_format($_proUsdTotal, 2, ',', ' ') }} USD</strong>
                                                                <span class="text-muted small">(1 USD = {{ number_format((float) $_proBifPerUsd, 0, ',', ' ') }} BIF, taux au {{ $_proFxRateLabel }})</span>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Client Information Tab -->
                        <div class="tab-pane fade" id="client" role="tabpanel" aria-labelledby="client-tab">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card mb-3">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0">@lang('messages.Informations Client')</h5>
                                        </div>
                                        <div class="card-body">
                                            @if($proforma_invoice->client)
                                                <table class="table table-bordered">
                                                    <tbody>
                                                        <tr>
                                                            <th width="30%">@lang('messages.Nom du Client:')</th>
                                                            <td>{{ $proforma_invoice->client->customer_name ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>@lang('messages.Téléphone:')</th>
                                                            <td>{{ $proforma_invoice->client->telephone ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>@lang('messages.Email:')</th>
                                                            <td>{{ $proforma_invoice->client->mail ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>@lang('messages.Adresse:')</th>
                                                            <td>{{ $proforma_invoice->client->customer_address ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>@lang('messages.TIN Client:')</th>
                                                            <td>{{ $proforma_invoice->client->customer_TIN ?? 'N/A' }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            @else
                                                <div class="alert alert-warning">
                                                    <strong>@lang('messages.Aucune information client disponible!')</strong>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Items Tab -->
                        <div class="tab-pane fade" id="items" role="tabpanel" aria-labelledby="items-tab">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card mb-3">
                                        <div class="card-header bg-warning text-dark">
                                            <h5 class="mb-0">@lang('messages.Détails des Articles')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>@lang('messages.Article')</th>
                                                            <th>@lang('messages.Quantité')</th>
                                                            <th>@lang('messages.Prix Unitaire')</th>
                                                            <th>@lang('messages.Montant Total')</th>
                                                            <th>@lang('messages.Montant (USD)')</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($proforma_invoice->details as $index => $detail)
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td>
                                                                @if($detail->item)
                                                                    {{ $detail->item->name }}
                                                                @else
                                                                    {{ $detail->item_designation ?? 'N/A' }}
                                                                @endif
                                                            </td>
                                                            <td>{{ number_format($detail->item_quantity ?? 0, 0, ',', ' ') }}</td>
                                                            <td>{{ number_format($detail->item_price ?? 0, 0, ',', ' ') }} BIF</td>
                                                            <td><strong>{{ number_format($detail->item_total_amount ?? 0, 0, ',', ' ') }} BIF</strong></td>
                                                            <td>
                                                                @if($_proUsdTotal !== null)
                                                                    <strong>{{ number_format(($detail->item_total_amount ?? 0) / (float) $_proBifPerUsd, 2, ',', ' ') }} USD</strong>
                                                                @else
                                                                    <span class="text-muted">—</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                        <tr class="table-info">
                                                            <td colspan="4" class="text-right"><strong>@lang('messages.TOTAL GÉNÉRAL:')</strong></td>
                                                            <td><strong>{{ number_format($total_amount, 0, ',', ' ') }} BIF</strong></td>
                                                            <td>
                                                                @if($_proUsdTotal !== null)
                                                                    <strong>{{ number_format($_proUsdTotal, 2, ',', ' ') }} USD</strong>
                                                                @else
                                                                    <span class="text-muted">—</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="mt-3">
                                                <label><strong>@lang('messages.Description:')</strong></label>
                                                <p>{{ $proforma_invoice->description ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status & History Tab -->
                        <div class="tab-pane fade" id="status" role="tabpanel" aria-labelledby="status-tab">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card mb-3">
                                        <div class="card-header bg-secondary text-white">
                                            <h5 class="mb-0">@lang('messages.Statut & Historique')</h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered">
                                                <tbody>
                                                    <tr>
                                                        <th width="30%">@lang('messages.État Actuel:')</th>
                                                        <td>
                                                            @if($proforma_invoice->etat == '0')
                                                                <span class="badge badge-etat-0">@lang('messages.Brouillon')</span>
                                                            @elseif($proforma_invoice->etat == '1')
                                                                <span class="badge badge-etat-1">@lang('messages.Validée')</span>
                                                            @elseif($proforma_invoice->etat == '2')
                                                                <span class="badge badge-etat-2">@lang('messages.Confirmée')</span>
                                                            @elseif($proforma_invoice->etat == '3')
                                                                <span class="badge badge-etat-3">@lang('messages.En Attente')</span>
                                                            @elseif($proforma_invoice->etat == '4')
                                                                <span class="badge badge-etat-4">@lang('messages.Approuvée')</span>
                                                            @else
                                                                <span class="badge badge-secondary">{{ $proforma_invoice->etat }}</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Créé par:')</th>
                                                        <td>{{ $proforma_invoice->auteur ?? 'N/A' }}</td>
                                                    </tr>
                                                    @if($proforma_invoice->validated_by)
                                                    <tr>
                                                        <th>@lang('messages.Validée par:')</th>
                                                        <td>{{ $proforma_invoice->validated_by }}</td>
                                                    </tr>
                                                    @endif
                                                    @if($proforma_invoice->confirmed_by)
                                                    <tr>
                                                        <th>@lang('messages.Confirmée par:')</th>
                                                        <td>{{ $proforma_invoice->confirmed_by }}</td>
                                                    </tr>
                                                    @endif
                                                    @if($proforma_invoice->approuved_by)
                                                    <tr>
                                                        <th>@lang('messages.Approuvée par:')</th>
                                                        <td>{{ $proforma_invoice->approuved_by }}</td>
                                                    </tr>
                                                    @endif
                                                    <tr>
                                                        <th>@lang('messages.Date de Création:')</th>
                                                        <td>{{ $proforma_invoice->created_at ? Carbon\Carbon::parse($proforma_invoice->created_at)->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Dernière Mise à Jour:')</th>
                                                        <td>{{ $proforma_invoice->updated_at ? Carbon\Carbon::parse($proforma_invoice->updated_at)->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                        <div class="alert alert-warning mt-4">
                            <strong>@lang('messages.Aucun détail de facture proforma trouvé!')</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Bootstrap 4 tabs are automatically initialized by Bootstrap JS
</script>
@endsection
