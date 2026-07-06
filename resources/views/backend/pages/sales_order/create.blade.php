@extends('backend.layouts.master')

@section('title')
{{ $proforma_number ? __('messages.Créer Bon de commande (Proforma)') : __('messages.Nouveau Bon de commande') }} - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .autocomplete { position: relative; display: inline-block; width: 100%; }
    .autocomplete-items { position: absolute; border: 1px solid #d4d4d4; z-index: 99; top: 100%; left: 0; right: 0; max-height: 200px; overflow-y: auto; background: white; }
    .autocomplete-items div { padding: 10px; cursor: pointer; border-bottom: 1px solid #d4d4d4; }
    .autocomplete-items div:hover { background-color: #e9e9e9; }
    .autocomplete-active { background-color: DodgerBlue !important; color: #fff; }
    .readonly-field { background-color: #f5f5f5; cursor: not-allowed; }
</style>
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">{{ $proforma_number ? __('messages.Créer Bon de commande') : __('messages.Nouveau Bon de commande') }}</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.sales-order.index') }}">@lang('messages.Bons de commande')</a></li>
                    <li><span>{{ $proforma_number ? __('messages.Depuis Proforma') . ' ' . $proforma_number : __('messages.new') }}</span></li>
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
                    <h4 class="header-title">{{ $proforma_number ? __('messages.Créer Bon de commande - Proforma:') . ' ' . $proforma_number : __('messages.Nouveau Bon de commande') }}</h4>
                    @include('backend.layouts.partials.messages')
                    <form action="{{ route('admin.sales-order.store') }}" method="post" id="dynamic_form" enctype="multipart/form-data">
                        @csrf
                        @if($proforma_number)
                            <input type="hidden" name="proforma_number" value="{{ $proforma_number }}">
                            <input type="hidden" name="document_no" value="{{ $proforma_number }}">
                        @endif
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="client_id">@lang('messages.Client')</label>
                                    @if($proforma_invoice && $proforma_invoice->client)
                                        <input type="text" class="form-control readonly-field" value="{{ $proforma_invoice->client->customer_name }}/{{ $proforma_invoice->client->telephone }}" readonly />
                                        <input type="hidden" name="client_id" value="{{ $proforma_invoice->client_id }}" />
                                    @else
                                    <div class="ajax-ac-wrap">
                                            <input type="text" id="client_name" name="client_name" class="form-control" placeholder="@lang('messages.Choisir un client')" autocomplete="off" required
                                                value="{{ ($defaultClient ?? null) ? $defaultClient->customer_name . '/' . $defaultClient->telephone : '' }}" />
                                            <input type="hidden" name="client_id" id="client_id" value="{{ ($defaultClient ?? null) ? $defaultClient->id : '' }}" required />
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sales_order_date">@lang('messages.date')</label>
                                    <input type="date" class="form-control" id="sales_order_date" name="sales_order_date"
                                           value="{{ old('sales_order_date', now()->format('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>
                        <table class="table table-bordered" id="dynamicTable">
                            <tr>
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.Prix Unitaire')</th>
                                <th>@lang('messages.Total')</th>
                                @if(!$datas->isNotEmpty())<th>@lang('messages.Action')</th>@endif
                            </tr>
                            @if($datas->isNotEmpty())
                                @php $grand_total = 0; @endphp
                                @foreach($datas as $index => $detail)
                                @php
                                    $item_name = $detail->item ? $detail->item->name : ($detail->item_designation ?? 'N/A');
                                    $grand_total += $detail->item_total_amount;
                                @endphp
                                <tr>
                                    <td>
                                        <input type="text" class="form-control readonly-field" value="{{ $item_name }}/{{ number_format($detail->item_price ?? 0, 0, ',', ' ') }}" readonly />
                                        <input type="hidden" name="item_id[]" value="{{ $detail->item_id }}" />
                                    </td>
                                    <td><input type="number" name="quantity[]" class="form-control" value="{{ $detail->item_quantity }}" required /></td>
                                    <td><input type="text" class="form-control readonly-field" value="{{ number_format($detail->item_price ?? 0, 0, ',', ' ') }} BIF" readonly /><input type="hidden" name="item_price[]" value="{{ $detail->item_price ?? 0 }}" /></td>
                                    <td><input type="text" class="form-control readonly-field" value="{{ number_format($detail->item_total_amount ?? 0, 0, ',', ' ') }} BIF" readonly /></td>
                                </tr>
                                @endforeach
                            @else
                            <tr>
                                <td>
                                    <div class="ajax-ac-wrap">
                                        <input type="text" name="item_name[]" id="item_name_0" placeholder="@lang('messages.Rechercher un article...')" class="form-control" autocomplete="off" required />
                                        <input type="hidden" name="item_id[]" id="item_id_0" />
                                        <input type="hidden" name="item_price[]" id="item_price_0" />
                                    </div>
                                </td>
                                <td><input type="number" name="quantity[]" id="quantity_0" class="form-control quantity-input" required /></td>
                                <td><input type="text" id="unit_price_0" class="form-control unit-price" readonly /></td>
                                <td><input type="text" id="total_price_0" class="form-control total-price" readonly /></td>
                                <td><button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button></td>
                            </tr>
                            @endif
                        </table>
                        @if($datas->isNotEmpty())
                        <div class="row mt-3">
                            <div class="col-md-4 offset-md-8">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">@lang('messages.Total Général')</h5>
                                        <h3 id="grand_total" class="text-primary">{{ number_format($datas->sum('item_total_amount'), 0, ',', ' ') }} BIF</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="row mt-3">
                            <div class="col-md-4 offset-md-8">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">@lang('messages.Total Général')</h5>
                                        <h3 id="grand_total" class="text-primary">@lang('messages.0 BIF')</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="col-lg-12 mt-2 form-group">
                            <label for="document">@lang('messages.Document (PDF / Image)')</label>
                            <input type="file" class="form-control document-upload-with-preview" name="document" id="document" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">@lang('messages.PDF, JPG, JPEG, PNG')</small>
                            @include('backend.layouts.partials.document_upload_preview')
                        </div>
                        <div class="col-lg-12 mt-2">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.description')">{{ $proforma_invoice->description ?? '' }}</textarea>
                        </div>
                        <div style="margin-top: 15px; margin-left: 15px;">
                            <button type="submit" class="btn btn-primary" id="save">@lang('messages.save')</button>
                            <a href="{{ $proforma_number ? route('admin.proforma-invoice.index') : route('admin.sales-order.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@if(!$datas->isNotEmpty())
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
@include('backend.layouts.partials.ajax_autocomplete')
<script>
var i = 0;

function parseBifToNumber(s) {
    if (!s) return 0;
    return parseFloat(String(s).replace(/[^\d.]/g, '')) || 0;
}

function calculateRowTotal(rowIndex) {
    var quantity = parseFloat(document.getElementById('quantity_' + rowIndex).value) || 0;
    var unitPrice = parseBifToNumber(document.getElementById('unit_price_' + rowIndex).value);
    var total = quantity * unitPrice;
    document.getElementById('total_price_' + rowIndex).value = total.toLocaleString() + ' BIF';
    calculateGrandTotal();
}

function calculateGrandTotal() {
    var grandTotal = 0;
    document.querySelectorAll('.total-price').forEach(function(input) {
        var value = input.value.replace(/[^\d]/g, '');
        if (value) grandTotal += parseFloat(value);
    });
    document.getElementById('grand_total').textContent = grandTotal.toLocaleString() + ' BIF';
}

$(document).on('input', '.quantity-input', function() {
    var rowIndex = this.id.split('_')[1];
    calculateRowTotal(rowIndex);
});

function escHtml(s) {
    return (s ?? '').toString()
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function initItemRow(rowIndex) {
    attachAjaxAutocomplete({
        $input: $('#item_name_' + rowIndex),
        $hidden: $('#item_id_' + rowIndex),
        url: '{{ route("admin.autocomplete.invoice-items") }}',
        minChars: 1,
        renderItem: function(item) {
            var price = (item.price ?? 0);
            var typeLabel = item.type_label ? ('<span class="badge badge-secondary ml-1">' + escHtml(item.type_label) + '</span>') : '';
            var name = escHtml(item.name || item.text || '');
            return '<div><strong>' + name + '</strong>' + typeLabel +
                ' <span class="text-muted">- ' + Number(price).toLocaleString() + ' BIF</span></div>';
        },
        onSelect: function(item) {
            var price = Number(item.price ?? 0);
            $('#unit_price_' + rowIndex).val(price.toLocaleString() + ' BIF');
            $('#item_price_' + rowIndex).val(price);
            calculateRowTotal(rowIndex);
        }
    });
}

initItemRow(0);

attachAjaxAutocomplete({
    $input: $('#client_name'),
    $hidden: $('#client_id'),
    url: '{{ route("admin.autocomplete.clients") }}',
    minChars: 1,
    renderItem: function(item) {
        var name = (item.name || item.text || '');
        var tel = item.telephone ? (' - ' + item.telephone) : '';
        name = name.toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        tel = tel.toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        return '<div><strong>' + name + '</strong><span class="text-muted">' + tel + '</span></div>';
    }
});

var i18n = {
    searchItem: @json(__('messages.Rechercher un article (PF, marchandise)...')),
    delete: @json(__('messages.delete'))
};

$("#add").click(function(){
    ++i;
    var markup = "<tr>" +
        "<td><div class='ajax-ac-wrap'>" +
        "<input type='text' name='item_name[]' id='item_name_" + i + "' placeholder='" + i18n.searchItem + "' class='form-control' autocomplete='off' required />" +
        "<input type='hidden' name='item_id[]' id='item_id_" + i + "' />" +
        "<input type='hidden' name='item_price[]' id='item_price_" + i + "' />" +
        "</div></td>" +
        "<td><input type='number' name='quantity[]' id='quantity_" + i + "' class='form-control quantity-input' required /></td>" +
        "<td><input type='text' id='unit_price_" + i + "' class='form-control unit-price' readonly /></td>" +
        "<td><input type='text' id='total_price_" + i + "' class='form-control total-price' readonly /></td>" +
        "<td><button type='button' class='btn btn-danger remove-tr'>" + i18n.delete + "</button></td>" +
        "</tr>";
    $("#dynamicTable").append(markup);
    setTimeout(function() { initItemRow(i); }, 100);
});

$(document).on("click", ".remove-tr", function(){ $(this).closest("tr").remove(); calculateGrandTotal(); });

$('#dynamic_form').on('submit', function(e) {
    var missingSelection = false;
    $('[id^="item_name_"]').each(function() {
        var rowIndex = this.id.replace('item_name_', '');
        if ($(this).val() && !$('#item_id_' + rowIndex).val()) {
            missingSelection = true;
        }
    });
    if (missingSelection) {
        e.preventDefault();
        alert(@json(__('messages.sales_order_select_item_from_list')));
        $('#save').css('visibility', 'visible');
        return false;
    }
});
</script>
@endif
@endsection
