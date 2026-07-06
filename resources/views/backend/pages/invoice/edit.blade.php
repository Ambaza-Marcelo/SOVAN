
@extends('backend.layouts.master')

@section('title')
@lang('messages.Edit invoice') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
</style>
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Edit invoice')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.invoice.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Edit invoice')</span></li>
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
        <!-- data table start -->
         <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">@lang('messages.Edit Invoice -') {{ $invoice->invoice_number }}</h4>
                    <div class="alert alert-info">
                        @lang('messages.Lors de la modification, les informations societe sont verifiees et rechargees depuis les Parametres (Setting).')
                    </div>
                    @if(!empty($syncedFromPrevious))
                        <p class="text-info">@lang('messages.Les données sont synchronisées depuis le document source:') <strong>{{ $delivery_number }}</strong>
                            @if(($sourceDocumentType ?? null) === 'sales_order' && $sales_order && Auth::guard('admin')->user()->can('sales_order.edit'))
                                <a href="{{ route('admin.sales-order.edit', $sales_order->sales_order_number) }}" class="btn btn-sm btn-outline-primary ml-2">@lang('messages.Bons de commande')</a>
                            @elseif(($sourceDocumentType ?? null) === 'delivery_note' && $delivery_note && Auth::guard('admin')->user()->can('delivery_note.edit'))
                                <a href="{{ route('admin.delivery-note.edit', $delivery_note->delivery_number) }}" class="btn btn-sm btn-outline-primary ml-2">@lang('messages.Edit delivery note')</a>
                            @endif
                        </p>
                    @else
                        <p class="text-muted">@lang('messages.Les données sont synchronisées depuis le document source:') <strong>{{ $delivery_number }}</strong></p>
                    @endif
                    @if($invoice->etat != '0')
                        <p class="text-warning"><strong>@lang('messages.Tous les champs sont en lecture seule car l\'état de la facture est :etat. Seules les factures avec état=0 peuvent être modifiées.', ['etat' => $invoice->etat])</strong></p>
                    @else
                        <p class="text-info"><strong>@lang('messages.Vous pouvez modifier cette facture car son état est 0.')</strong></p>
                    @endif
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.invoice.update', $invoice->invoice_number) }}" method="post" id="dynamic_form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="delivery_number" value="{{ $delivery_number }}" />
                        <input type="hidden" name="document_no" value="{{ $delivery_number }}" />
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="client_id">@lang('messages.Client')</label>
                                <div class="ajax-ac-wrap">
                                    <input type="text" id="client_name" name="client_name" class="form-control" placeholder="@lang('messages.Merci de choisir Client')" autocomplete="off"
                                        value="{{ ($formClient ?? $invoice->client) ? ($formClient ?? $invoice->client)->customer_name . '/' . ($formClient ?? $invoice->client)->telephone : ($defaultClient ? $defaultClient->customer_name . '/' . $defaultClient->telephone : '') }}" {{ $invoice->etat != '0' ? 'readonly' : '' }} />
                                    <input type="hidden" name="client_id" id="client_id" value="{{ $formClientId ?? $invoice->client_id ?? ($defaultClient ? $defaultClient->id : '') }}" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="invoice_date">@lang('messages.date')</label>
                                <input type="date" class="form-control" id="invoice_date" name="invoice_date"
                                       value="{{ old('invoice_date', $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : now()->format('Y-m-d')) }}" {{ $invoice->etat != '0' ? 'readonly' : '' }}>
                            </div>
                        </div>
                    </div>
                         <table class="table table-bordered" id="dynamicTable">  
                            <tr>
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.Prix Unitaire')</th>
                                <th>@lang('messages.Total')</th>
                            </tr>
                            @php $i = 0; @endphp
                            @foreach($delivery_details as $detail)
                            <tr>  
                                <td>
                                    <div class="ajax-ac-wrap">
                                        <input type="text" name="item_name[]" id="item_name_{{ $i }}" placeholder="@lang('messages.Rechercher un item...')" class="form-control" autocomplete="off"
                                            value="@if($detail->item){{ $detail->item->name }}/{{ number_format($detail->item_price ?? $detail->item->selling_price,0,',',' ') }}@else{{ $detail->item_designation }}@endif" {{ $invoice->etat != '0' ? 'readonly' : '' }} />
                                        <input type="hidden" name="item_id[]" id="item_id_{{ $i }}" value="{{ $detail->item_id }}" />
                                        <input type="hidden" name="item_price[]" id="item_price_{{ $i }}" value="{{ $detail->item_price }}" />
                                    </div>
                                </td>  
                                <td><input type="number" name="item_quantity[]" id="quantity_{{ $i }}" placeholder="@lang('messages.Entrer quantite')" class="form-control quantity-input" value="{{ $detail->item_quantity }}" {{ $invoice->etat != '0' ? 'readonly' : '' }} /></td>
                                <td><input type="text" id="unit_price_{{ $i }}" class="form-control unit-price" value="{{ number_format($detail->item_price, 0, ',', ' ') }} BIF" readonly /></td>
                                <td><input type="text" id="total_price_{{ $i }}" class="form-control total-price" value="{{ number_format($detail->item_total_amount, 0, ',', ' ') }} BIF" readonly /></td>
                                @if($invoice->etat == '0')
                                <td><button type="button" class="btn btn-danger remove-tr">@lang('messages.delete')</button></td>
                                @endif
                            </tr>
                            @php $i++; @endphp
                            @endforeach
                            @if($delivery_details->count() == 0)
                            <tr>  
                                <td colspan="{{ $invoice->etat == '0' ? '5' : '4' }}" class="text-center">@lang('messages.Aucun article trouvé')</td>
                            </tr>
                            @endif
                        </table>
                        @if($invoice->etat == '0')
                        <div>
                            <button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button>
                        </div>
                        @endif
                        <div class="row mt-3">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">@lang('messages.Total Général')</h5>
                                        <h3 id="grand_total" class="text-primary">{{ number_format($delivery_details->sum('item_total_amount'), 0, ',', ' ') }} BIF</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Enter Description')" {{ $invoice->etat != '0' ? 'readonly' : '' }}>{{ $formDescription ?? $invoice->description }}</textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            @if($invoice->etat == '0')
                                <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary" id="save">@lang('messages.update')</button>
                            @endif
                            <a href="{{ route('admin.invoice.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
                            @if($invoice->etat != '0')
                                <a href="{{ route('admin.proforma-invoice.index') }}" class="btn btn-info">@lang('messages.Modifier depuis Facture Proforma')</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
@include('backend.layouts.partials._document_lines_autocomplete', [
    'canEdit' => $invoice->etat == '0',
    'initialRowIndex' => $delivery_details->count() > 0 ? $delivery_details->count() - 1 : 0,
    'detailIndices' => $delivery_details->isEmpty() ? [] : range(0, $delivery_details->count() - 1),
])
@endsection
