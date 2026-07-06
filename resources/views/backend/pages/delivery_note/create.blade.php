
@extends('backend.layouts.master')

@section('title')
@lang('messages.Create Delivery Note') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
    .autocomplete {
        position: relative;
        display: inline-block;
        width: 100%;
    }
    .autocomplete-items {
        position: absolute;
        border: 1px solid #d4d4d4;
        border-bottom: none;
        border-top: none;
        z-index: 99;
        top: 100%;
        left: 0;
        right: 0;
        max-height: 200px;
        overflow-y: auto;
        background: white;
    }
    .autocomplete-items div {
        padding: 10px;
        cursor: pointer;
        background-color: #fff;
        border-bottom: 1px solid #d4d4d4;
    }
    .autocomplete-items div:hover {
        background-color: #e9e9e9;
    }
    .autocomplete-active {
        background-color: DodgerBlue !important;
        color: #ffffff;
    }
    .readonly-field {
        background-color: #f5f5f5;
        cursor: not-allowed;
    }
</style>
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Create Delivery Note')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.proforma-invoice.index') }}">@lang('messages.Factures Proforma')</a></li>
                    <li><span>@lang('messages.Créer Bon de Livraison')</span></li>
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
                    <h4 class="header-title">@lang('messages.Créer Bon de Livraison - Facture Proforma:') {{ $proforma_number }}</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.delivery-note.store') }}" method="post" id="dynamic_form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="proforma_number" value="{{ $proforma_number }}">
                        <input type="hidden" name="document_no" value="{{ $proforma_number }}">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="client_id">@lang('messages.Client')</label>
                                    <input type="text" class="form-control readonly-field" 
                                        value="@if($proforma_invoice->client){{ $proforma_invoice->client->customer_name }}/{{ $proforma_invoice->client->telephone }}@endif" 
                                        readonly />
                                    <input type="hidden" name="client_id" value="{{ $proforma_invoice->client_id }}" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="delivery_date">@lang('messages.date')</label>
                                    <input type="date" class="form-control" id="delivery_date" name="delivery_date"
                                           value="{{ old('delivery_date', now()->format('Y-m-d')) }}">
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
                            @php
                                $items_data = [];
                                $grand_total = 0;
                            @endphp
                            @foreach($datas as $index => $detail)
                            @php
                                $item_name = $detail->item ? $detail->item->name : ($detail->item_designation ?? 'N/A');
                                $item_display = $item_name . '/' . number_format($detail->item_price, 0, ',', ' ');
                                $items_data[] = [
                                    'id' => $detail->item_id,
                                    'name' => $item_name,
                                    'price' => $detail->item_price,
                                    'display' => $item_display
                                ];
                                $grand_total += $detail->item_total_amount;
                            @endphp
                            <tr>  
                                <td>
                                    <div class="autocomplete">
                                        <input type="text" 
                                            id="item_name_{{ $index }}" 
                                            class="form-control readonly-field" 
                                            value="{{ $item_display }}" 
                                            readonly />
                                        <input type="hidden" name="item_id[]" value="{{ $detail->item_id }}" />
                                    </div>
                                </td>  
                                <td>
                                    <input type="number" 
                                        name="item_quantity[]" 
                                        id="quantity_{{ $index }}" 
                                        class="form-control readonly-field quantity-input" 
                                        value="{{ $detail->item_quantity }}" 
                                        readonly />
                                </td>
                                <td>
                                    <input type="text" 
                                        id="unit_price_{{ $index }}" 
                                        class="form-control readonly-field unit-price" 
                                        value="{{ number_format($detail->item_price, 0, ',', ' ') }} BIF" 
                                        readonly />
                                    <input type="hidden" name="item_price[]" value="{{ $detail->item_price }}" />
                                </td>
                                <td>
                                    <input type="text" 
                                        id="total_price_{{ $index }}" 
                                        class="form-control readonly-field total-price" 
                                        value="{{ number_format($detail->item_total_amount, 0, ',', ' ') }} BIF" 
                                        readonly />
                                </td>
                            </tr>
                            @endforeach
                        </table>
                        
                        <div class="row mt-3">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">@lang('messages.Total Général')</h5>
                                        <h3 id="grand_total" class="text-primary">{{ number_format($grand_total, 0, ',', ' ') }} BIF</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-12 form-group">
                            <label for="document">@lang('messages.Document (PDF / Image)')</label>
                            <input type="file" class="form-control document-upload-with-preview" name="document" id="document" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">@lang('messages.PDF, JPG, JPEG, PNG')</small>
                            @include('backend.layouts.partials.document_upload_preview')
                        </div>
                        <div class="col-lg-12 mt-2">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Enter Description')">{{ $proforma_invoice->description ?? '' }}</textarea>
                        </div>
                        
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" class="btn btn-primary" id="save">@lang('messages.save')</button>
                            <a href="{{ route('admin.proforma-invoice.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script type="text/javascript">
    // Items data for display (read-only, no autocomplete functionality needed)
    var items = @json($items_data);
    
    // Calculate grand total on page load
    $(document).ready(function() {
        var grandTotal = 0;
        $('.total-price').each(function() {
            var value = $(this).val().replace(/[^\d]/g, '');
            if (value) {
                grandTotal += parseFloat(value);
            }
        });
        $('#grand_total').text(grandTotal.toLocaleString() + ' BIF');
    });
</script>
@endsection
