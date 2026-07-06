
@extends('backend.layouts.master')

@section('title')
@lang('messages.Invoice Details') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Invoice Details')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.invoice.index') }}">@lang('messages.list')</a></li>
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
                        <h4 class="header-title">@lang('messages.Facture No:') {{ $invoice->invoice_number }}</h4>
                        <div>
                            @if($invoice->etat == '0')
                                @if (Auth::guard('admin')->user()->can('invoice.edit'))
                                    <a class="btn btn-success text-white" href="{{ route('admin.invoice.edit', $invoice->invoice_number) }}">@lang('messages.edit')</a>
                                @endif
                            @endif
                            @if($invoice->etat == '0')
                                @if (Auth::guard('admin')->user()->can('invoice.view'))
                                    <a class="btn btn-primary text-white" href="{{ route('admin.invoice.cash', $invoice->invoice_number) }}" title="@lang('messages.Valider Cash')">
                                        <i class="fa fa-money-bill"></i> @lang('messages.Valider Cash')
                                    </a>
                                    <a class="btn btn-info text-white" href="{{ route('admin.invoice.credit', $invoice->invoice_number) }}" title="@lang('messages.Valider Credit')">
                                        <i class="fa fa-credit-card"></i> @lang('messages.Valider Credit')
                                    </a>
                                @endif
                            @endif
                            
                                @if (Auth::guard('admin')->user()->can('invoice.view'))
                                    <a class="btn btn-secondary text-white" href="{{ route('admin.invoice.print', $invoice->invoice_number) }}" target="_blank" title="@lang('messages.Imprimer (PDF) - transmet à l\'OBR si nécessaire')">
                                        <i class="fa fa-print"></i> @lang('messages.Imprimer (BIF)')
                                    </a>
                                    <a class="btn btn-outline-light text-dark border" href="{{ route('admin.invoice.print-usd', $invoice->invoice_number) }}" target="_blank" title="@lang('messages.Imprimer en USD (PDF, sans OBR)')">
                                        <i class="fa fa-dollar-sign"></i> @lang('messages.PDF USD')
                                    </a>
                                @endif
                                @if (Auth::guard('admin')->user()->can('facture.reimprimer'))
                                    <a class="btn btn-dark text-white" href="{{ route('admin.invoice.reprint', $invoice->invoice_number) }}" target="_blank" title="@lang('messages.Réimprimer (PDF) - impression seule')">
                                        <i class="fa fa-print"></i> @lang('messages.Réimprimer')
                                    </a>
                                @endif
                            @if($invoice->etat == '0')
                                @if (Auth::guard('admin')->user()->can('invoice.reset'))
                                    <button type="button" class="btn btn-warning text-white" data-toggle="modal" data-target="#cancelInvoiceModal" title="@lang('messages.Annuler la facture')">
                                        <i class="fa fa-times-circle"></i> @lang('messages.Annuler')
                                    </button>
                                @endif
                            @endif
                            @if($invoice->document ?? null)
                                <a class="btn btn-light" href="{{ asset('storage/'.$invoice->document) }}" target="_blank" title="@lang('messages.Voir document')"><i class="fa fa-eye"></i> @lang('messages.Voir document')</a>
                                <a class="btn btn-light" href="{{ asset('storage/'.$invoice->document) }}" download="{{ basename($invoice->document) }}" title="@lang('messages.Télécharger')"><i class="fa fa-download"></i> @lang('messages.Télécharger')</a>
                            @endif
                            @if(!\App\Models\DeliveryNote::where('document_no', $invoice->invoice_number)->exists() && Auth::guard('admin')->user()->can('delivery_note.create'))
                                <a class="btn btn-success text-white" href="{{ route('admin.delivery-note.create-from-invoice', $invoice->invoice_number) }}" title="@lang('messages.Créer Bon de livraison')">
                                    <i class="fa fa-truck"></i> @lang('messages.Créer Bon de livraison')
                                </a>
                            @endif
                            @if(in_array((string) $invoice->etat, ['1', '2'], true) && Auth::guard('admin')->user()->can('note_credit.create'))
                                <a class="btn btn-warning text-white" href="{{ route('admin.note-de-credit.create', $invoice->invoice_number) }}" title="@lang('messages.Émettre une note de crédit (facture d\'avoir)')">
                                    <i class="fa fa-file-invoice-dollar"></i> @lang('messages.Note de crédit')
                                </a>
                            @endif
                            <a class="btn btn-light" href="{{ route('admin.invoice.index') }}">
                                <i class="fa fa-arrow-left"></i> {{ __('messages.back') }}
                            </a>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials.messages')

                    @if($invoice->etat == '0' && Auth::guard('admin')->user()->can('invoice.reset'))
                        <div class="modal fade" id="cancelInvoiceModal" tabindex="-1" role="dialog" aria-labelledby="cancelInvoiceModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('admin.invoice.cancel', $invoice->invoice_number) }}" method="POST">
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
                    
                    @if($invoice->details->count() > 0)
                    @php
                        $total_amount = $invoice->details->sum('item_total_amount');
                        $_invFxDate = $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : null;
                        $_invFx = $_invFxDate ? \App\Models\ExchangeRate::bifPerUsdForDate($_invFxDate) : null;
                        $_invBifPerUsd = $_invFx['bif_per_usd'] ?? null;
                        $_invFxRateLabel = isset($_invFx['rate_date']) && $_invFx['rate_date'] ? \Carbon\Carbon::parse($_invFx['rate_date'])->format('d/m/Y') : null;
                        $_invUsdTotal = ($_invBifPerUsd && (float) $_invBifPerUsd > 0) ? $total_amount / (float) $_invBifPerUsd : null;
                    @endphp
                    @if($_invUsdTotal === null)
                        <div class="alert alert-warning mt-3 mb-0">{{ \App\Models\ExchangeRate::missingBifUsdRateMessage() }}</div>
                    @endif
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs mt-4" id="invoiceTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="info-tab" data-toggle="tab" href="#info" role="tab" aria-controls="info" aria-selected="true">
                                @lang('messages.Informations Générales')
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="client-tab" data-toggle="tab" href="#client" role="tab" aria-controls="client" aria-selected="false">
                                @lang('messages.Client')
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="items-tab" data-toggle="tab" href="#items" role="tab" aria-controls="items" aria-selected="false">
                                @lang('messages.articles') ({{ $invoice->details->count() }})
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="details-tab" data-toggle="tab" href="#details" role="tab" aria-controls="details" aria-selected="false">
                                @lang('messages.Détails Complets')
                            </a>
                        </li>
                        @if($invoice->document_no)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="delivery-tab" data-toggle="tab" href="#delivery" role="tab" aria-controls="delivery" aria-selected="false">
                                @lang('messages.Bon de Livraison')
                            </a>
                        </li>
                        @endif
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="status-tab" data-toggle="tab" href="#status" role="tab" aria-controls="status" aria-selected="false">
                                @lang('messages.Statut & Historique')
                            </a>
                        </li>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content mt-4" id="invoiceTabsContent">
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
                                                        <th width="40%">@lang('messages.Numéro Facture:')</th>
                                                        <td><strong>{{ $invoice->invoice_number ?? 'N/A' }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Date:')</th>
                                                        <td>{{ $invoice->invoice_date ? Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y H:i') : 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Signature:')</th>
                                                        <td>{{ $invoice->invoice_signature ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Date de Signature:')</th>
                                                        <td>{{ $invoice->invoice_signature_date ? Carbon\Carbon::parse($invoice->invoice_signature_date)->format('d/m/Y H:i') : 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.État:')</th>
                                                        <td>
                                                            @if($invoice->etat == '0')
                                                                <span class="badge badge-etat-0">@lang('messages.Brouillon')</span>
                                                            @elseif($invoice->etat == '1')
                                                                <span class="badge badge-etat-1">@lang('messages.Validée')</span>
                                                            @elseif($invoice->etat == '2')
                                                                <span class="badge badge-etat-2">@lang('messages.Confirmée')</span>
                                                            @elseif($invoice->etat == '3')
                                                                <span class="badge badge-etat-3">@lang('messages.Approuvée')</span>
                                                            @elseif($invoice->etat == '-1')
                                                                <span class="badge badge-danger">@lang('messages.Annulée')</span>
                                                            @else
                                                                <span class="badge badge-secondary">{{ $invoice->etat }}</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Créé par:')</th>
                                                        <td>{{ $invoice->auteur ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Société (Setting):')</th>
                                                        <td>{{ $invoice->tp_name ?? 'N/A' }} / {{ $invoice->tp_TIN ?? 'N/A' }}</td>
                                                    </tr>
                                                    {{-- Description affichée sous le tableau des articles --}}
                                                    @if($invoice->document_no)
                                                    <tr>
                                                        <th>@lang('messages.Bon de Livraison:')</th>
                                                        <td>
                                                            <a href="{{ route('admin.delivery-note.show', $invoice->document_no) }}">
                                                                {{ $invoice->document_no }}
                                                            </a>
                                                        </td>
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
                                                        <td><strong>{{ $invoice->details->count() }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Quantité Totale:')</th>
                                                        <td><strong>{{ $invoice->details->sum('item_quantity') }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Montant Total:')</th>
                                                        <td><strong class="text-success">{{ number_format($total_amount, 0, ',', ' ') }} BIF</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Équivalent USD:')</th>
                                                        <td>
                                                            @if($_invUsdTotal !== null)
                                                                <strong class="text-primary">{{ number_format($_invUsdTotal, 2, ',', ' ') }} USD</strong>
                                                                <span class="text-muted small">(1 USD = {{ number_format((float) $_invBifPerUsd, 0, ',', ' ') }} BIF, taux au {{ $_invFxRateLabel }})</span>
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
                                            @if($invoice->client)
                                                <table class="table table-bordered">
                                                    <tbody>
                                                        <tr>
                                                            <th width="30%">@lang('messages.Nom du Client:')</th>
                                                            <td>{{ $invoice->client->customer_name ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>@lang('messages.Téléphone:')</th>
                                                            <td>{{ $invoice->client->telephone ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>@lang('messages.Email:')</th>
                                                            <td>{{ $invoice->client->mail ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>@lang('messages.Adresse:')</th>
                                                            <td>{{ $invoice->client->customer_address ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>@lang('messages.TIN Client:')</th>
                                                            <td>{{ $invoice->client->customer_TIN ?? 'N/A' }}</td>
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
                                                        @foreach($invoice->details as $index => $detail)
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
                                                                @if($_invUsdTotal !== null)
                                                                    <strong>{{ number_format(($detail->item_total_amount ?? 0) / (float) $_invBifPerUsd, 2, ',', ' ') }} USD</strong>
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
                                                                @if($_invUsdTotal !== null)
                                                                    <strong>{{ number_format($_invUsdTotal, 2, ',', ' ') }} USD</strong>
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
                                                <p>{{ $invoice->description ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Complete Details Tab -->
                        <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card mb-3">
                                        <div class="card-header bg-secondary text-white">
                                            <h5 class="mb-0">@lang('messages.Détails Complets de tous les Articles')</h5>
                                        </div>
                                        <div class="card-body">
                                            @foreach($invoice->details as $index => $detail)
                                            <div class="card mb-3">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0">@lang('messages.Article') {{ $loop->iteration }}: {{ $detail->item_designation ?? ($detail->item->name ?? 'N/A') }}</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <table class="table table-bordered">
                                                                <tbody>
                                                                    <tr>
                                                                        <th width="40%">@lang('messages.ID Article:')</th>
                                                                        <td>{{ $detail->item_id ?? 'N/A' }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>@lang('messages.Désignation:')</th>
                                                                        <td>{{ $detail->item_designation ?? 'N/A' }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>@lang('messages.Quantité:')</th>
                                                                        <td>{{ number_format($detail->item_quantity ?? 0, 0, ',', ' ') }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>@lang('messages.Prix Unitaire:')</th>
                                                                        <td>{{ number_format($detail->item_price ?? 0, 0, ',', ' ') }} BIF</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>@lang('messages.Prix HTVA:')</th>
                                                                        <td>{{ number_format($detail->item_price_nvat ?? 0, 0, ',', ' ') }} BIF</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>@lang('messages.TVA:')</th>
                                                                        <td>{{ number_format($detail->vat ?? 0, 0, ',', ' ') }} BIF</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <table class="table table-bordered">
                                                                <tbody>
                                                                    <tr>
                                                                        <th width="40%">@lang('messages.Prix TTC:')</th>
                                                                        <td>{{ number_format($detail->item_price_wvat ?? 0, 0, ',', ' ') }} BIF</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>@lang('messages.CT:')</th>
                                                                        <td>{{ number_format($detail->item_ct ?? 0, 0, ',', ' ') }} BIF</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>@lang('messages.TL:')</th>
                                                                        <td>{{ number_format($detail->item_tl ?? 0, 0, ',', ' ') }} BIF</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>@lang('messages.TSCE Tax:')</th>
                                                                        <td>{{ number_format($detail->item_tsce_tax ?? 0, 0, ',', ' ') }} BIF</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>@lang('messages.OTT Tax:')</th>
                                                                        <td>{{ number_format($detail->item_ott_tax ?? 0, 0, ',', ' ') }} BIF</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th><strong>@lang('messages.Montant Total:')</strong></th>
                                                                        <td><strong class="text-success">{{ number_format($detail->item_total_amount ?? 0, 0, ',', ' ') }} BIF</strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>@lang('messages.Description:')</th>
                                                                        <td>{{ $detail->description ?? 'N/A' }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>@lang('messages.État:')</th>
                                                                        <td>
                                                                            @if($detail->etat == '0')
                                                                                <span class="badge badge-secondary">@lang('messages.Brouillon')</span>
                                                                            @elseif($detail->etat == '1')
                                                                                <span class="badge badge-success">@lang('messages.Validée')</span>
                                                                            @else
                                                                                <span class="badge badge-info">{{ $detail->etat }}</span>
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                        <div class="alert alert-warning mt-4">
                            <strong>@lang('messages.Aucun détail de facture trouvé!')</strong>
                        </div>
                    @endif
                    
                    <!-- Delivery Note Tab -->
                    @if($invoice->document_no)
                    <div class="tab-pane fade" id="delivery" role="tabpanel" aria-labelledby="delivery-tab">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">@lang('messages.Bon de Livraison Associé')</h5>
                                    </div>
                                    <div class="card-body">
                                        @if($invoice->deliveryNote)
                                            <p><strong>@lang('messages.Numéro:')</strong> 
                                                <a href="{{ route('admin.delivery-note.show', $invoice->document_no) }}">
                                                    {{ $invoice->document_no }}
                                                </a>
                                            </p>
                                            <p><strong>@lang('messages.Date:')</strong> {{ $invoice->deliveryNote->delivery_date ? Carbon\Carbon::parse($invoice->deliveryNote->delivery_date)->format('d/m/Y H:i') : 'N/A' }}</p>
                                            <p><strong>@lang('messages.Signature:')</strong> {{ $invoice->deliveryNote->delivery_signature ?? 'N/A' }}</p>
                                        @else
                                            <p><strong>@lang('messages.Bon de Livraison:')</strong> {{ $invoice->document_no }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
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
                                                        @if($invoice->etat == '0')
                                                            <span class="badge badge-etat-0">@lang('messages.Brouillon')</span>
                                                        @elseif($invoice->etat == '1')
                                                            <span class="badge badge-etat-1">@lang('messages.Validée')</span>
                                                        @elseif($invoice->etat == '2')
                                                            <span class="badge badge-etat-2">@lang('messages.Confirmée')</span>
                                                        @elseif($invoice->etat == '3')
                                                            <span class="badge badge-etat-3">@lang('messages.Approuvée')</span>
                                                        @else
                                                            <span class="badge badge-secondary">{{ $invoice->etat }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>@lang('messages.Créé par:')</th>
                                                    <td>{{ $invoice->auteur ?? 'N/A' }}</td>
                                                </tr>
                                                @if($invoice->validated_by)
                                                <tr>
                                                    <th>@lang('messages.Validée par:')</th>
                                                    <td>{{ $invoice->validated_by }}</td>
                                                </tr>
                                                @endif
                                                @if($invoice->confirmed_by)
                                                <tr>
                                                    <th>@lang('messages.Confirmée par:')</th>
                                                    <td>{{ $invoice->confirmed_by }}</td>
                                                </tr>
                                                @endif
                                                @if($invoice->approuved_by)
                                                <tr>
                                                    <th>@lang('messages.Approuvée par:')</th>
                                                    <td>{{ $invoice->approuved_by }}</td>
                                                </tr>
                                                @endif
                                                <tr>
                                                    <th>@lang('messages.Date de Création:')</th>
                                                    <td>{{ $invoice->created_at ? Carbon\Carbon::parse($invoice->created_at)->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>@lang('messages.Dernière Mise à Jour:')</th>
                                                    <td>{{ $invoice->updated_at ? Carbon\Carbon::parse($invoice->updated_at)->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
