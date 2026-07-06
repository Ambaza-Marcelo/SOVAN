@extends('backend.layouts.master')

@section('title')
@lang('messages.Facture d\'avoir') {{ $credit->invoice_number }} - @lang('messages.admin_panel')
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
    .card-header { font-weight: 600; }
    .table th { background-color: #f8f9fa; }
</style>
@endsection

@section('admin-content')
@php
    $total_amount = $credit->details->sum('item_total_amount');
@endphp
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Facture d\'avoir') {{ $credit->invoice_number }}</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.note-de-credit.index') }}">@lang('messages.Notes de crédit')</a></li>
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
                    @include('backend.layouts.partials.messages')

                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <h4 class="header-title mb-2 mb-md-0">{{ __('messages.note_credit_number_abbr') }} {{ $credit->invoice_number }} <span class="text-muted small">@lang('messages.note_credit_show_fa_subtitle')</span></h4>
                        <div>
                            <a href="{{ route('admin.note-de-credit.facture', $credit->invoice_number) }}" class="btn btn-secondary text-white" target="_blank">
                                <i class="fa fa-print"></i> @lang('messages.note_credit_print_send_obr')
                            </a>
                            <a href="{{ route('admin.invoice.show', $credit->invoice_ref) }}" class="btn btn-light">{{ __('messages.Voir facture') }} {{ $credit->invoice_ref }}</a>
                            <a href="{{ route('admin.note-de-credit.index') }}" class="btn btn-light"><i class="fa fa-arrow-left"></i> @lang('messages.Retour liste')</a>
                        </div>
                    </div>

                    @if($credit->details->count() > 0)
                    <ul class="nav nav-tabs mt-3" id="noteCreditTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="nc-info-tab" data-toggle="tab" href="#nc-info" role="tab">@lang('messages.Informations générales')</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="nc-client-tab" data-toggle="tab" href="#nc-client" role="tab">@lang('messages.Client')</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="nc-items-tab" data-toggle="tab" href="#nc-items" role="tab">{{ __('messages.note_credit_tab_articles', ['count' => $credit->details->count()]) }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="nc-details-tab" data-toggle="tab" href="#nc-details" role="tab">@lang('messages.Détails complets')</a>
                        </li>
                        @if($credit->document_no)
                        <li class="nav-item">
                            <a class="nav-link" id="nc-delivery-tab" data-toggle="tab" href="#nc-delivery" role="tab">@lang('messages.Bon de livraison')</a>
                        </li>
                        @endif
                        <li class="nav-item">
                            <a class="nav-link" id="nc-status-tab" data-toggle="tab" href="#nc-status" role="tab">@lang('messages.Statut')</a>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="noteCreditTabsContent">
                        <div class="tab-pane fade show active" id="nc-info" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-primary text-white">@lang('messages.Document')</div>
                                        <div class="card-body">
                                            <table class="table table-bordered">
                                                <tbody>
                                                    <tr><th width="42%">@lang('messages.N° avoir')</th><td><strong>{{ $credit->invoice_number }}</strong></td></tr>
                                                    <tr><th>@lang('messages.Type (OBR)')</th><td><strong>@lang('messages.FA')</strong> {{ __('messages.note_credit_obr_type_detail', ['ref' => $credit->invoice_ref, 'fn' => __('messages.FN')]) }}</td></tr>
                                                    <tr><th>@lang('messages.Date')</th><td>{{ $credit->invoice_date ? \Carbon\Carbon::parse($credit->invoice_date)->format('d/m/Y H:i') : 'N/A' }}</td></tr>
                                                    <tr><th>@lang('messages.Réf. facture')</th><td><a href="{{ route('admin.invoice.show', $credit->invoice_ref) }}">{{ $credit->invoice_ref }}</a></td></tr>
                                                    <tr><th>@lang('messages.Motif (cn_motif)')</th><td>{{ $credit->cn_motif }} — {{ $credit->description }}</td></tr>
                                                    <tr><th>@lang('messages.Signature')</th><td><small>{{ \Illuminate\Support\Str::limit($credit->invoice_signature ?? '—', 80) }}</small></td></tr>
                                                    <tr><th>@lang('messages.Signature électronique OBR')</th><td><small>{{ \Illuminate\Support\Str::limit($credit->electronic_signature ?? '—', 80) }}</small></td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-info text-white">@lang('messages.Résumé financier')</div>
                                        <div class="card-body">
                                            <table class="table table-bordered">
                                                <tbody>
                                                    <tr><th width="42%">@lang('messages.Lignes')</th><td>{{ $credit->details->count() }}</td></tr>
                                                    <tr><th>@lang('messages.Qté totale')</th><td>{{ number_format($credit->details->sum('item_quantity'), 2, ',', ' ') }}</td></tr>
                                                    <tr><th>@lang('messages.Montant TTC')</th><td><strong class="text-success">{{ number_format($total_amount, 2, ',', ' ') }} {{ $credit->invoice_currency ?? 'BIF' }}</strong></td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($credit->statut != '2' && Auth::guard('admin')->user()->can('note_credit.validate'))
                            <div class="card border-primary">
                                <div class="card-header">@lang('messages.note_credit_validate_section')</div>
                                <div class="card-body">
                                    <p class="small text-muted mb-3">@lang('messages.Les écritures inverses reprennent le') <strong>@lang('messages.même compte que celui débité sur la facture')</strong> @lang('messages.note_credit_validate_help_suffix')</p>
                                    <form action="{{ route('admin-note-de-credit.valider', $credit->invoice_number) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-primary" onclick="return confirm(@json(__('messages.note_credit_validate_confirm')));">@lang('messages.Valider')</button>
                                    </form>
                                </div>
                            </div>
                            @elseif($credit->statut == '2')
                            <div class="alert alert-success mb-0"><i class="fa fa-check"></i> @lang('messages.note_credit_validated_alert')</div>
                            @endif
                        </div>

                        <div class="tab-pane fade" id="nc-client" role="tabpanel">
                            <div class="card">
                                <div class="card-header bg-success text-white">@lang('messages.Client')</div>
                                <div class="card-body">
                                    @if($credit->client)
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr><th width="30%">@lang('messages.Nom')</th><td>{{ $credit->client->customer_name ?? 'N/A' }}</td></tr>
                                            <tr><th>@lang('messages.Téléphone')</th><td>{{ $credit->client->telephone ?? 'N/A' }}</td></tr>
                                            <tr><th>@lang('messages.Email')</th><td>{{ $credit->client->mail ?? 'N/A' }}</td></tr>
                                            <tr><th>@lang('messages.Adresse')</th><td>{{ $credit->client->customer_address ?? 'N/A' }}</td></tr>
                                            <tr><th>@lang('messages.NIF')</th><td>{{ $credit->client->customer_TIN ?? 'N/A' }}</td></tr>
                                        </tbody>
                                    </table>
                                    @else
                                    <div class="alert alert-warning mb-0">@lang('messages.Aucun client lié.')</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="nc-items" role="tabpanel">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">@lang('messages.Lignes')</div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>@lang('messages.Article')</th>
                                                    <th class="text-right">@lang('messages.Qté')</th>
                                                    <th class="text-right">@lang('messages.PU HTVA')</th>
                                                    <th class="text-right">@lang('messages.TVA')</th>
                                                    <th class="text-right">@lang('messages.Total TTC')</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($credit->details as $d)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $d->item_designation ?? optional($d->item)->name }}</td>
                                                    <td class="text-right">{{ number_format($d->item_quantity, 2, ',', ' ') }}</td>
                                                    <td class="text-right">{{ number_format($d->item_price_nvat ?? $d->item_price ?? 0, 2, ',', ' ') }}</td>
                                                    <td class="text-right">{{ number_format($d->vat ?? 0, 2, ',', ' ') }}</td>
                                                    <td class="text-right"><strong>{{ number_format($d->item_total_amount, 2, ',', ' ') }}</strong></td>
                                                </tr>
                                                @endforeach
                                                <tr class="table-info">
                                                    <td colspan="5" class="text-right"><strong>@lang('messages.Total')</strong></td>
                                                    <td class="text-right"><strong>{{ number_format($total_amount, 2, ',', ' ') }}</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="nc-details" role="tabpanel">
                            @foreach($credit->details as $d)
                            <div class="card mb-3">
                                <div class="card-header bg-light"><strong>{{ __('messages.note_credit_article_row', ['number' => $loop->iteration]) }}</strong> — {{ $d->item_designation ?? optional($d->item)->name }}</div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-bordered table-sm">
                                                <tbody>
                                                    <tr><th width="40%">@lang('messages.ID article')</th><td>{{ $d->item_id ?? 'N/A' }}</td></tr>
                                                    <tr><th>@lang('messages.Qté')</th><td>{{ number_format($d->item_quantity ?? 0, 2, ',', ' ') }}</td></tr>
                                                    <tr><th>@lang('messages.Prix unitaire')</th><td>{{ number_format($d->item_price ?? 0, 2, ',', ' ') }}</td></tr>
                                                    <tr><th>@lang('messages.PU HTVA')</th><td>{{ number_format($d->item_price_nvat ?? 0, 2, ',', ' ') }}</td></tr>
                                                    <tr><th>@lang('messages.TVA')</th><td>{{ number_format($d->vat ?? 0, 2, ',', ' ') }}</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-bordered table-sm">
                                                <tbody>
                                                    <tr><th width="40%">@lang('messages.Prix TTC ligne')</th><td>{{ number_format($d->item_price_wvat ?? 0, 2, ',', ' ') }}</td></tr>
                                                    <tr><th>@lang('messages.CT / TL')</th><td>{{ number_format($d->item_ct ?? 0, 2, ',', ' ') }} / {{ number_format($d->item_tl ?? 0, 2, ',', ' ') }}</td></tr>
                                                    <tr><th>@lang('messages.Total TTC')</th><td><strong class="text-success">{{ number_format($d->item_total_amount ?? 0, 2, ',', ' ') }}</strong></td></tr>
                                                    <tr><th>@lang('messages.Statut ligne')</th><td>{{ $d->statut == '2' ? __('messages.Validée') : __('messages.note_credit_line_pending') }}</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        @if($credit->document_no)
                        <div class="tab-pane fade" id="nc-delivery" role="tabpanel">
                            <div class="card">
                                <div class="card-header bg-info text-white">@lang('messages.Bon de livraison associé')</div>
                                <div class="card-body">
                                    <p><strong>{{ __('messages.note_credit_number_abbr') }}</strong> <a href="{{ route('admin.delivery-note.show', $credit->document_no) }}">{{ $credit->document_no }}</a></p>
                                    @if($deliveryNote)
                                    <p><strong>@lang('messages.Date :')</strong> {{ $deliveryNote->delivery_date ? \Carbon\Carbon::parse($deliveryNote->delivery_date)->format('d/m/Y H:i') : 'N/A' }}</p>
                                    <p><strong>@lang('messages.État BL :')</strong> {{ $deliveryNote->etat ?? 'N/A' }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="tab-pane fade" id="nc-status" role="tabpanel">
                            <div class="card">
                                <div class="card-header bg-secondary text-white">@lang('messages.Statut &amp; historique')</div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr><th width="32%">@lang('messages.Statut avoir')</th><td>@if($credit->statut == '2')<span class="badge badge-success">@lang('messages.Validée')</span>@else<span class="badge badge-warning">@lang('messages.En attente de validation')</span>@endif</td></tr>
                                            <tr><th>@lang('messages.Facture source (état)')</th><td>
                                                @if($sourceInvoice)
                                                    {{ $sourceInvoice->etat == '1' ? __('messages.note_credit_invoice_status_cash') : ($sourceInvoice->etat == '2' ? __('messages.Crédit') : $sourceInvoice->etat) }}
                                                    <span class="text-muted small">@lang('messages.note_credit_contrapassation_hint')</span>
                                                @else N/A @endif
                                            </td></tr>
                                            <tr><th>@lang('messages.Auteur')</th><td>{{ $credit->auteur ?? 'N/A' }}</td></tr>
                                            @if($credit->validated_by)
                                            <tr><th>@lang('messages.Validé par')</th><td>{{ $credit->validated_by }}</td></tr>
                                            @endif
                                            <tr><th>@lang('messages.Créé le')</th><td>{{ $credit->created_at ? $credit->created_at->format('d/m/Y H:i:s') : 'N/A' }}</td></tr>
                                            <tr><th>@lang('messages.Mis à jour')</th><td>{{ $credit->updated_at ? $credit->updated_at->format('d/m/Y H:i:s') : 'N/A' }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning">@lang('messages.Aucune ligne sur ce document.')</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Onglets Bootstrap 4
</script>
@endsection
