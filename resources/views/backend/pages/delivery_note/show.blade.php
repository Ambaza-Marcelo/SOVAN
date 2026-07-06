
@extends('backend.layouts.master')

@section('title')
@lang('messages.Delivery Note Details') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Delivery Note Details')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.delivery-note.index') }}">@lang('messages.list')</a></li>
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
                        <h4 class="header-title">@lang('messages.Bon de Livraison No:') {{ $delivery_note->delivery_number }}</h4>
                        <div>
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
                                    <a class="btn btn-success text-white" href="{{ route('admin.delivery-note.edit', $delivery_note->delivery_number) }}">@lang('messages.edit')</a>
                                @endif
                            @endif
                            @if($delivery_note->etat == '0')
                                @if (Auth::guard('admin')->user()->can('delivery_note.validate'))
                                    <a class="btn btn-primary text-white" href="{{ route('admin.delivery-note.validate', $delivery_note->delivery_number) }}"
                                        onclick="event.preventDefault(); document.getElementById('validate-form').submit();">
                                        @lang('messages.Valider')
                                    </a>
                                    <form id="validate-form" action="{{ route('admin.delivery-note.validate', $delivery_note->delivery_number) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                    </form>
                                @endif
                            @endif
                            @if($delivery_note->etat == '1')
                                @if (Auth::guard('admin')->user()->can('delivery_note.confirm'))
                                    <a class="btn btn-info text-white" href="{{ route('admin.delivery-note.confirm', $delivery_note->delivery_number) }}"
                                        onclick="event.preventDefault(); document.getElementById('confirm-form').submit();">
                                        @lang('messages.Confirmer')
                                    </a>
                                    <form id="confirm-form" action="{{ route('admin.delivery-note.confirm', $delivery_note->delivery_number) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                    </form>
                                @endif
                            @endif
                            @if($delivery_note->etat == '2')
                                @if (Auth::guard('admin')->user()->can('delivery_note.approuve'))
                                    <a class="btn btn-warning text-white" href="{{ route('admin.delivery-note.approve', $delivery_note->delivery_number) }}"
                                        onclick="event.preventDefault(); document.getElementById('approve-form').submit();">
                                        @lang('messages.Approuver')
                                    </a>
                                    <form id="approve-form" action="{{ route('admin.delivery-note.approve', $delivery_note->delivery_number) }}" method="POST" style="display: none;">
                                        @method('PUT')
                                        @csrf
                                    </form>
                                @endif
                            @endif
                            @if (Auth::guard('admin')->user()->can('delivery_note.view'))
                                <a class="btn btn-info text-white" href="{{ route('admin.delivery-note.print', $delivery_note->delivery_number) }}" target="_blank" title="@lang('messages.Imprimer (PDF)')">
                                    <i class="fa fa-print"></i> @lang('messages.Imprimer')
                                </a>
                            @endif
                            @if($delivery_note->document ?? null)
                                <a class="btn btn-secondary text-white" href="{{ asset('storage/'.$delivery_note->document) }}" target="_blank" title="@lang('messages.Voir document')"><i class="fa fa-eye"></i> @lang('messages.Voir document')</a>
                                <a class="btn btn-secondary text-white" href="{{ asset('storage/'.$delivery_note->document) }}" download="{{ basename($delivery_note->document) }}" title="@lang('messages.Télécharger')"><i class="fa fa-download"></i> @lang('messages.Télécharger')</a>
                            @endif
                            <a class="btn btn-light" href="{{ route('admin.delivery-note.index') }}">
                                <i class="fa fa-arrow-left"></i> {{ __('messages.back') }}
                            </a>
                            @if($delivery_note->etat == '3')
                                @if (Auth::guard('admin')->user()->can('invoice.create'))
                                    <a class="btn btn-success text-white" href="{{ route('admin.invoice.create', $delivery_note->delivery_number) }}" title="@lang('messages.Créer Facture')">
                                        <i class="fa fa-file-invoice"></i> @lang('messages.Facture')
                                    </a>
                                @endif
                            @endif
                            @if (!$hasValidatedInvoice)
                                @if (Auth::guard('admin')->user()->can('delivery_note.delete'))
                                    <a class="btn btn-danger text-white" href="{{ route('admin.delivery-note.destroy', $delivery_note->delivery_number) }}"
                                        onclick="event.preventDefault(); if(confirm(@json(__('messages.Êtes-vous sûr de vouloir supprimer ce bon de livraison ?')))) { document.getElementById('delete-form').submit(); }">
                                        @lang('messages.delete')
                                    </a>
                                    <form id="delete-form" action="{{ route('admin.delivery-note.destroy', $delivery_note->delivery_number) }}" method="POST" style="display: none;">
                                        @method('DELETE')
                                        @csrf
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials.messages')
                    
                    @if($delivery_note->details->count() > 0)
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs mt-4" id="deliveryNoteTabs" role="tablist">
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
                                @lang('messages.articles') ({{ $delivery_note->details->count() }})
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="stock-tab" data-toggle="tab" href="#stock" role="tab" aria-controls="stock" aria-selected="false">
                                @lang('messages.Stock')
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="details-tab" data-toggle="tab" href="#details" role="tab" aria-controls="details" aria-selected="false">
                                @lang('messages.Détails Complets')
                            </a>
                        </li>
                        @if($delivery_note->document_no)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="proforma-tab" data-toggle="tab" href="#proforma" role="tab" aria-controls="proforma" aria-selected="false">
                                @lang('messages.Facture Proforma')
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
                    <div class="tab-content mt-4" id="deliveryNoteTabsContent">
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
                                                        <th width="40%">@lang('messages.Numéro BL:')</th>
                                                        <td><strong>{{ $delivery_note->delivery_number ?? 'N/A' }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Date:')</th>
                                                        <td>{{ $delivery_note->delivery_date ? Carbon\Carbon::parse($delivery_note->delivery_date)->format('d/m/Y H:i') : 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Signature:')</th>
                                                        <td>{{ $delivery_note->delivery_signature ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Date de Signature:')</th>
                                                        <td>{{ $delivery_note->delivery_signature_date ? Carbon\Carbon::parse($delivery_note->delivery_signature_date)->format('d/m/Y H:i') : 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.État:')</th>
                                                        <td>
                                                            @if($delivery_note->etat == '0')
                                                                <span class="badge badge-etat-0">@lang('messages.Brouillon')</span>
                                                            @elseif($delivery_note->etat == '1')
                                                                <span class="badge badge-etat-1">@lang('messages.Validée')</span>
                                                            @elseif($delivery_note->etat == '2')
                                                                <span class="badge badge-etat-2">@lang('messages.Confirmée')</span>
                                                            @elseif($delivery_note->etat == '3')
                                                                <span class="badge badge-etat-3">@lang('messages.En Attente')</span>
                                                            @elseif($delivery_note->etat == '4')
                                                                <span class="badge badge-etat-4">@lang('messages.Approuvée')</span>
                                                            @else
                                                                <span class="badge badge-secondary">{{ $delivery_note->etat }}</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Créé par:')</th>
                                                        <td>{{ $delivery_note->auteur ?? 'N/A' }}</td>
                                                    </tr>
                                                    {{-- Description affichée sous le tableau des articles --}}
                                                    @if($delivery_note->document_no)
                                                    <tr>
                                                        <th>@lang('messages.Facture Proforma:')</th>
                                                        <td>
                                                            <a href="{{ route('admin.proforma-invoice.show', $delivery_note->document_no) }}">
                                                                {{ $delivery_note->document_no }}
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
                                                    @php
                                                        $total_amount = $delivery_note->details->sum('item_total_amount');
                                                    @endphp
                                                    <tr>
                                                        <th width="40%">@lang('messages.Nombre d\'Articles:')</th>
                                                        <td><strong>{{ $delivery_note->details->count() }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Quantité Totale:')</th>
                                                        <td><strong>{{ $delivery_note->details->sum('item_quantity') }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th>@lang('messages.Montant Total:')</th>
                                                        <td><strong class="text-success">{{ number_format($total_amount, 0, ',', ' ') }} BIF</strong></td>
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
                                            @if($delivery_note->client)
                                                <table class="table table-bordered">
                                                    <tbody>
                                                        <tr>
                                                            <th width="30%">@lang('messages.Nom du Client:')</th>
                                                            <td>{{ $delivery_note->client->customer_name ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>@lang('messages.Téléphone:')</th>
                                                            <td>{{ $delivery_note->client->telephone ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>@lang('messages.Email:')</th>
                                                            <td>{{ $delivery_note->client->mail ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>@lang('messages.Adresse:')</th>
                                                            <td>{{ $delivery_note->client->customer_address ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>@lang('messages.TIN Client:')</th>
                                                            <td>{{ $delivery_note->client->customer_TIN ?? 'N/A' }}</td>
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
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($delivery_note->details as $index => $detail)
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
                                                        </tr>
                                                        @endforeach
                                                        <tr class="table-info">
                                                            <td colspan="4" class="text-right"><strong>@lang('messages.TOTAL GÉNÉRAL:')</strong></td>
                                                            <td><strong>{{ number_format($total_amount, 0, ',', ' ') }} BIF</strong></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="mt-3">
                                                <label><strong>@lang('messages.Description:')</strong></label>
                                                <p>{{ $delivery_note->description ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Tab -->
                        <div class="tab-pane fade" id="stock" role="tabpanel" aria-labelledby="stock-tab">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card mb-3">
                                        <div class="card-header bg-dark text-white">
                                            <h5 class="mb-0">@lang('messages.Détails Stock (par article)')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>@lang('messages.Article')</th>
                                                            <th>@lang('messages.Type Stock')</th>
                                                            <th>@lang('messages.Magasin')</th>
                                                            <th>@lang('messages.Qté actuelle')</th>
                                                            <th>@lang('messages.CUMP')</th>
                                                            <th>@lang('messages.Compte Stock')</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($delivery_note->details as $detail)
                                                            @php
                                                                $itemId = $detail->item_id;
                                                                $itemName = $detail->item ? $detail->item->name : ($detail->item_designation ?? 'N/A');

                                                                $stockType = 'SERVICE';
                                                                $storeCode = null;
                                                                $storeName = null;
                                                                $qtyCurrent = null;
                                                                $cump = null;
                                                                $stockAccountText = null;

                                                                $fgSmDetail = $itemId ? \App\Models\FinishedGoodsSmStoreDetail::where('item_id', $itemId)->first() : null;
                                                                if ($fgSmDetail) {
                                                                    $stockType = 'PF SM';
                                                                    $storeCode = $fgSmDetail->code_store;
                                                                    $qtyCurrent = $fgSmDetail->quantity;
                                                                    $cump = $fgSmDetail->cump;
                                                                    $store = $storeCode ? \App\Models\FinishedGoodsSmStore::where('code', $storeCode)->first() : null;
                                                                    $storeName = $store ? $store->name : null;
                                                                    if ($store && $store->default_account_id) {
                                                                        $acc = \App\Models\Accounting\AccountAccount::find($store->default_account_id);
                                                                        $stockAccountText = $acc ? ($acc->account_number . ' - ' . $acc->account_name) : $store->default_account_id;
                                                                    }
                                                                } else {
                                                                    $fgDetail = $itemId ? \App\Models\FinishedGoodsStoreDetail::where('item_id', $itemId)->first() : null;
                                                                    if ($fgDetail) {
                                                                        $stockType = 'PF MAIN';
                                                                        $storeCode = $fgDetail->code_store;
                                                                        $qtyCurrent = $fgDetail->quantity;
                                                                        $cump = $fgDetail->cump;
                                                                        $store = $storeCode ? \App\Models\FinishedGoodsStore::where('code', $storeCode)->first() : null;
                                                                        $storeName = $store ? $store->name : null;
                                                                        if ($store && $store->default_account_id) {
                                                                            $acc = \App\Models\Accounting\AccountAccount::find($store->default_account_id);
                                                                            $stockAccountText = $acc ? ($acc->account_number . ' - ' . $acc->account_name) : $store->default_account_id;
                                                                        }
                                                                    } else {
                                                                        $gDetail = $itemId ? \App\Models\GoodsStoreDetail::where('item_id', $itemId)->first() : null;
                                                                        if ($gDetail) {
                                                                            $stockType = 'GOODS';
                                                                            $storeCode = $gDetail->code_store;
                                                                            $qtyCurrent = $gDetail->quantity;
                                                                            $cump = $gDetail->cump;
                                                                            $store = $storeCode ? \App\Models\GoodsStore::where('code', $storeCode)->first() : null;
                                                                            $storeName = $store ? $store->name : null;
                                                                            if ($store && $store->default_account_id) {
                                                                                $acc = \App\Models\Accounting\AccountAccount::find($store->default_account_id);
                                                                                $stockAccountText = $acc ? ($acc->account_number . ' - ' . $acc->account_name) : $store->default_account_id;
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            @endphp
                                                            <tr>
                                                                <td>{{ $loop->iteration }}</td>
                                                                <td>{{ $itemName }}</td>
                                                                <td>
                                                                    @if($stockType === 'PF SM')
                                                                        <span class="badge badge-info">@lang('messages.PF SM')</span>
                                                                    @elseif($stockType === 'PF MAIN')
                                                                        <span class="badge badge-primary">@lang('messages.PF MAIN')</span>
                                                                    @elseif($stockType === 'GOODS')
                                                                        <span class="badge badge-secondary">@lang('messages.GOODS')</span>
                                                                    @else
                                                                        <span class="badge badge-light">@lang('messages.SERVICE')</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if($storeCode)
                                                                        <strong>{{ $storeCode }}</strong>{{ $storeName ? ' — ' . $storeName : '' }}
                                                                    @else
                                                                        <span class="text-muted">@lang('messages.N/A')</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if(!is_null($qtyCurrent))
                                                                        {{ number_format($qtyCurrent, 0, ',', ' ') }}
                                                                    @else
                                                                        <span class="text-muted">@lang('messages.N/A')</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if(!is_null($cump))
                                                                        {{ number_format($cump, 2, ',', ' ') }}
                                                                    @else
                                                                        <span class="text-muted">@lang('messages.N/A')</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if($stockAccountText)
                                                                        {{ $stockAccountText }}
                                                                    @else
                                                                        <span class="text-muted">@lang('messages.N/A')</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="alert alert-info mt-3 mb-0">
                                                @lang('messages.Les informations ci-dessus sont déduites de l\'emplacement réel de l\'article dans les détails de stock (PF MAIN / PF SM / Goods). Les services (non stockables) n\'ont pas de stock.')
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
                                            @foreach($delivery_note->details as $index => $detail)
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
                            <strong>@lang('messages.Aucun détail de bon de livraison trouvé!')</strong>
                        </div>
                    @endif
                    
                    <!-- Proforma Invoice Tab -->
                    @if($delivery_note->document_no)
                    <div class="tab-pane fade" id="proforma" role="tabpanel" aria-labelledby="proforma-tab">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">@lang('messages.Facture Proforma Associée')</h5>
                                    </div>
                                    <div class="card-body">
                                        @if($delivery_note->proformaInvoice)
                                            <p><strong>@lang('messages.Numéro:')</strong> 
                                                <a href="{{ route('admin.proforma-invoice.show', $delivery_note->document_no) }}">
                                                    {{ $delivery_note->document_no }}
                                                </a>
                                            </p>
                                            <p><strong>@lang('messages.Date:')</strong> {{ $delivery_note->proformaInvoice->proforma_date ? Carbon\Carbon::parse($delivery_note->proformaInvoice->proforma_date)->format('d/m/Y H:i') : 'N/A' }}</p>
                                            <p><strong>@lang('messages.Signature:')</strong> {{ $delivery_note->proformaInvoice->proforma_signature ?? 'N/A' }}</p>
                                        @else
                                            <p>@lang('messages.Facture Proforma:') {{ $delivery_note->document_no }}</p>
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
                                                        @if($delivery_note->etat == '0')
                                                            <span class="badge badge-etat-0">@lang('messages.Brouillon')</span>
                                                        @elseif($delivery_note->etat == '1')
                                                            <span class="badge badge-etat-1">@lang('messages.Validée')</span>
                                                        @elseif($delivery_note->etat == '2')
                                                            <span class="badge badge-etat-2">@lang('messages.Confirmée')</span>
                                                        @elseif($delivery_note->etat == '3')
                                                            <span class="badge badge-etat-3">@lang('messages.Approuvée')</span>
                                                        @else
                                                            <span class="badge badge-secondary">{{ $delivery_note->etat }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>@lang('messages.Créé par:')</th>
                                                    <td>{{ $delivery_note->auteur ?? 'N/A' }}</td>
                                                </tr>
                                                @if($delivery_note->validated_by)
                                                <tr>
                                                    <th>@lang('messages.Validée par:')</th>
                                                    <td>{{ $delivery_note->validated_by }}</td>
                                                </tr>
                                                @endif
                                                @if($delivery_note->confirmed_by)
                                                <tr>
                                                    <th>@lang('messages.Confirmée par:')</th>
                                                    <td>{{ $delivery_note->confirmed_by }}</td>
                                                </tr>
                                                @endif
                                                @if($delivery_note->approuved_by)
                                                <tr>
                                                    <th>@lang('messages.Approuvée par:')</th>
                                                    <td>{{ $delivery_note->approuved_by }}</td>
                                                </tr>
                                                @endif
                                                <tr>
                                                    <th>@lang('messages.Date de Création:')</th>
                                                    <td>{{ $delivery_note->created_at ? Carbon\Carbon::parse($delivery_note->created_at)->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>@lang('messages.Dernière Mise à Jour:')</th>
                                                    <td>{{ $delivery_note->updated_at ? Carbon\Carbon::parse($delivery_note->updated_at)->format('d/m/Y H:i:s') : 'N/A' }}</td>
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
