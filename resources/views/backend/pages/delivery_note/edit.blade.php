
@extends('backend.layouts.master')

@section('title')
@lang('messages.Edit delivery note') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Edit delivery note')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.delivery-note.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Edit delivery note')</span></li>
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
                    <h4 class="header-title">@lang('messages.Modifier le Bon de Livraison') - {{ $delivery_note->delivery_number }}</h4>
                    @if($proforma_number && !empty($syncedFromPrevious))
                        <p class="text-info">@lang('messages.Les données sont synchronisées depuis la facture proforma:') <strong>{{ $proforma_number }}</strong>
                            @if(Auth::guard('admin')->user()->can('proforma_invoice.edit'))
                                <a href="{{ route('admin.proforma-invoice.edit', $proforma_number) }}" class="btn btn-sm btn-outline-primary ml-2">@lang('messages.Edit proforma invoice')</a>
                            @endif
                        </p>
                    @elseif($proforma_number)
                        <p class="text-muted">@lang('messages.Facture Proforma:') <strong>{{ $proforma_number }}</strong></p>
                    @elseif(isset($invoice_source) && $invoice_source && !empty($syncedFromPrevious))
                        <p class="text-info">@lang('messages.Les données sont synchronisées depuis le document source:') <strong>{{ $invoice_source->invoice_number }}</strong></p>
                    @elseif(isset($invoice_source) && $invoice_source)
                        <p class="text-muted">@lang('messages.Facture') : <strong>{{ $invoice_source->invoice_number }}</strong></p>
                    @endif
                    @if(!empty($readonlyForm))
                        <p class="text-warning"><strong>@lang('messages.Ce bon de livraison ne peut plus être modifié car une facture validée existe.')</strong></p>
                    @endif
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.delivery-note.update', $delivery_note->delivery_number) }}" method="post" id="dynamic_form" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @if($proforma_number)
                            <input type="hidden" name="proforma_number" value="{{ $proforma_number }}" />
                        @endif
                        <input type="hidden" name="document_no" value="{{ $document_no ?? $delivery_note->document_no }}" />
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="client_id">@lang('messages.Client')</label>
                                <div class="ajax-ac-wrap">
                                    <input type="text" id="client_name" name="client_name" class="form-control" placeholder="@lang('messages.Merci de choisir Client')" autocomplete="off" 
                                        value="{{ ($formClient ?? $delivery_note->client) ? ($formClient ?? $delivery_note->client)->customer_name . '/' . ($formClient ?? $delivery_note->client)->telephone : ($defaultClient ? $defaultClient->customer_name . '/' . $defaultClient->telephone : '') }}" {{ !empty($readonlyForm) ? 'readonly' : '' }} />
                                    <input type="hidden" name="client_id" id="client_id" value="{{ $formClientId ?? $delivery_note->client_id ?? ($defaultClient ? $defaultClient->id : '') }}" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="delivery_date">@lang('messages.date')</label>
                                <input type="date" class="form-control" id="delivery_date" name="delivery_date"
                                       value="{{ old('delivery_date', $delivery_note->delivery_date ? \Carbon\Carbon::parse($delivery_note->delivery_date)->format('Y-m-d') : now()->format('Y-m-d')) }}" {{ !empty($readonlyForm) ? 'readonly' : '' }}>
                            </div>
                        </div>
                    </div>
                         <table class="table table-bordered" id="dynamicTable">  
                            <tr>
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.Prix Unitaire')</th>
                                <th>@lang('messages.Total')</th>
                                @if(empty($readonlyForm))
                                <th>@lang('messages.Action')</th>
                                @endif
                            </tr>
                            @php $i = 0; @endphp
                            @foreach($proforma_details as $detail)
                            <tr>  
                                <td>
                                    <div class="ajax-ac-wrap">
                                        <input type="text" name="item_name[]" id="item_name_{{ $i }}" placeholder="@lang('messages.Rechercher un item...')" class="form-control" autocomplete="off" 
                                            value="@if($detail->item){{ $detail->item->name }}/{{ number_format($detail->item_price ?? $detail->item->selling_price,0,',',' ') }}@else{{ $detail->item_designation }}@endif" {{ !empty($readonlyForm) ? 'readonly' : '' }} />
                                        <input type="hidden" name="item_id[]" id="item_id_{{ $i }}" value="{{ $detail->item_id }}" />
                                        <input type="hidden" name="item_price[]" id="item_price_{{ $i }}" value="{{ $detail->item_price }}" />
                                    </div>
                                </td>  
                                <td><input type="number" name="item_quantity[]" id="quantity_{{ $i }}" placeholder="@lang('messages.Entrer quantite')" class="form-control quantity-input" value="{{ $detail->item_quantity }}" {{ !empty($readonlyForm) ? 'readonly' : '' }} /></td>
                                <td><input type="text" id="unit_price_{{ $i }}" class="form-control unit-price" value="{{ number_format($detail->item_price, 0, ',', ' ') }} BIF" readonly /></td>
                                <td><input type="text" id="total_price_{{ $i }}" class="form-control total-price" value="{{ number_format($detail->item_total_amount, 0, ',', ' ') }} BIF" readonly /></td>
                                @if(empty($readonlyForm))
                                <td><button type="button" class="btn btn-danger remove-tr">@lang('messages.delete')</button></td>
                                @endif     
                            </tr>
                            @php $i++; @endphp
                            @endforeach
                            @if($proforma_details->count() == 0)
                            <tr>  
                                <td>
                                    <div class="ajax-ac-wrap">
                                        <input type="text" name="item_name[]" id="item_name_0" placeholder="@lang('messages.Rechercher un item...')" class="form-control" autocomplete="off" required />
                                        <input type="hidden" name="item_id[]" id="item_id_0" />
                                    </div>
                                </td>  
                                <td><input type="number" name="quantity[]" id="quantity_0" placeholder="@lang('messages.Entrer quantite')" class="form-control quantity-input" required /></td>
                                <td><input type="text" id="unit_price_0" class="form-control unit-price" readonly /></td>
                                <td><input type="text" id="total_price_0" class="form-control total-price" readonly /></td>
                                <td><button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button></td>     
                            </tr>
                            @endif
                        </table>
                        @if(empty($readonlyForm))
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
                                        <h3 id="grand_total" class="text-primary">{{ number_format($proforma_details->sum('item_total_amount'), 0, ',', ' ') }} BIF</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 form-group">
                            <label for="document">@lang('messages.Document (PDF / Image)')</label>
                            <input type="file" class="form-control document-upload-with-preview" name="document" id="document" accept=".pdf,.jpg,.jpeg,.png">
                            @if($delivery_note->document ?? null)
                                <small class="form-text text-muted">@lang('messages.Document actuel:') <a href="{{ asset('storage/'.$delivery_note->document) }}" target="_blank" class="mr-2"><i class="fa fa-eye"></i> @lang('messages.Voir')</a> <a href="{{ asset('storage/'.$delivery_note->document) }}" download="{{ basename($delivery_note->document) }}"><i class="fa fa-download"></i> @lang('messages.Télécharger')</a></small>
                            @endif
                            @include('backend.layouts.partials.document_upload_preview')
                        </div>
                        <div class="col-lg-12 mt-2">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Enter Description')" {{ !empty($readonlyForm) ? 'readonly' : '' }}>{{ $formDescription ?? $delivery_note->description }}</textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            @if(empty($readonlyForm))
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary" id="save">@lang('messages.update')</button>
                            @endif
                            <a href="{{ route('admin.delivery-note.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
                            @if(!empty($readonlyForm))
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
    'canEdit' => empty($readonlyForm),
    'initialRowIndex' => $proforma_details->count() > 0 ? $proforma_details->count() - 1 : 0,
    'detailIndices' => $proforma_details->isEmpty() ? [] : range(0, $proforma_details->count() - 1),
])
@endsection
