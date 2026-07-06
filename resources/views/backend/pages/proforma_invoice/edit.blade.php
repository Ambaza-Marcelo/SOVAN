
@extends('backend.layouts.master')

@section('title')
@lang('messages.Edit proforma invoice') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Edit proforma invoice')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.proforma-invoice.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Edit proforma invoice')</span></li>
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
                    <h4 class="header-title">Edit Proforma Invoice - {{ $proforma_invoice->proforma_number }}</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.proforma-invoice.update', $proforma_invoice->proforma_number) }}" method="post" id="dynamic_form" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="client_id">@lang('messages.Client')</label>
                                <div class="ajax-ac-wrap">
                                    <input type="text" id="client_name" name="client_name" class="form-control" placeholder="@lang('messages.Merci de choisir Client')" autocomplete="off" 
                                        value="{{ $proforma_invoice->client ? $proforma_invoice->client->customer_name . '/' . $proforma_invoice->client->telephone : ($defaultClient ? $defaultClient->customer_name . '/' . $defaultClient->telephone : '') }}" required />
                                    <input type="hidden" name="client_id" id="client_id" value="{{ $proforma_invoice->client_id ?? ($defaultClient ? $defaultClient->id : '') }}" required />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="proforma_date">@lang('messages.date')</label>
                                <input type="date" class="form-control" id="proforma_date" name="proforma_date"
                                       value="{{ old('proforma_date', $proforma_invoice->proforma_date ? \Carbon\Carbon::parse($proforma_invoice->proforma_date)->format('Y-m-d') : now()->format('Y-m-d')) }}">
                            </div>
                        </div>
                    </div>
                         <table class="table table-bordered" id="dynamicTable">  
                            <tr>
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.Prix Unitaire')</th>
                                <th>@lang('messages.Total')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            @php $i = 0; @endphp
                            @foreach($proforma_invoice->details as $detail)
                            <tr>  
                                <td>
                                    <div class="ajax-ac-wrap">
                                        <input type="text" name="item_name[]" id="item_name_{{ $i }}" placeholder="@lang('messages.Rechercher un article (PF, marchandise)...')" class="form-control" autocomplete="off" 
                                            value="@if($detail->item){{ $detail->item->name }}/{{ number_format($detail->item_price ?? $detail->item->selling_price,0,',',' ') }}@else{{ $detail->item_designation }}@endif" required />
                                        <input type="hidden" name="item_id[]" id="item_id_{{ $i }}" value="{{ $detail->item_id }}" />
                                        <input type="hidden" name="item_price[]" id="item_price_{{ $i }}" value="{{ $detail->item_price }}" />
                                    </div>
                                </td>  
                                <td><input type="number" name="quantity[]" id="quantity_{{ $i }}" placeholder="@lang('messages.Entrer quantite')" class="form-control quantity-input" value="{{ $detail->item_quantity }}" required /></td>
                                <td><input type="text" id="unit_price_{{ $i }}" class="form-control unit-price" value="{{ number_format($detail->item_price, 0, ',', ' ') }} BIF" readonly /></td>
                                <td><input type="text" id="total_price_{{ $i }}" class="form-control total-price" value="{{ number_format($detail->item_total_amount, 0, ',', ' ') }} BIF" readonly /></td>
                                <td><button type="button" class="btn btn-danger remove-tr">@lang('messages.delete')</button></td>     
                            </tr>
                            @php $i++; @endphp
                            @endforeach
                            @if($proforma_invoice->details->count() == 0)
                            <tr>  
                                <td>
                                    <div class="ajax-ac-wrap">
                                        <input type="text" name="item_name[]" id="item_name_0" placeholder="@lang('messages.Rechercher un article (PF, marchandise)...')" class="form-control" autocomplete="off" required />
                                        <input type="hidden" name="item_id[]" id="item_id_0" />
                                        <input type="hidden" name="item_price[]" id="item_price_0" />
                                    </div>
                                </td>  
                                <td><input type="number" name="quantity[]" id="quantity_0" placeholder="@lang('messages.Entrer quantite')" class="form-control quantity-input" required /></td>
                                <td><input type="text" id="unit_price_0" class="form-control unit-price" readonly /></td>
                                <td><input type="text" id="total_price_0" class="form-control total-price" readonly /></td>
                                <td><button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button></td>     
                            </tr>
                            @endif
                        </table>
                        <div>
                            <button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">@lang('messages.Total Général')</h5>
                                        <h3 id="grand_total" class="text-primary">{{ number_format($proforma_invoice->details->sum('item_total_amount'), 0, ',', ' ') }} BIF</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 form-group">
                            <label for="document">@lang('messages.Document (PDF / Image)')</label>
                            <input type="file" class="form-control document-upload-with-preview" name="document" id="document" accept=".pdf,.jpg,.jpeg,.png">
                            @if($proforma_invoice->document)
                                <small class="form-text text-muted">@lang('messages.Document actuel:') <a href="{{ asset('storage/' . $proforma_invoice->document) }}" target="_blank" class="mr-2"><i class="fa fa-eye"></i> @lang('messages.Voir')</a> <a href="{{ asset('storage/' . $proforma_invoice->document) }}" download="{{ basename($proforma_invoice->document) }}"><i class="fa fa-download"></i> @lang('messages.Télécharger')</a></small>
                            @else
                                <small class="form-text text-muted">@lang('messages.PDF, JPG, JPEG, PNG')</small>
                            @endif
                            @include('backend.layouts.partials.document_upload_preview')
                        </div>
                        <div class="col-lg-12 mt-2">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Enter Description')">{{ $proforma_invoice->description }}</textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary" id="save">@lang('messages.update')</button>
                            <a href="{{ route('admin.proforma-invoice.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
@include('backend.layouts.partials.ajax_autocomplete')
    <script type="text/javascript">
    var i = {{ $proforma_invoice->details->count() > 0 ? $proforma_invoice->details->count() - 1 : 0 }};
    var i18n = {
        searchItem: @json(__('messages.Rechercher un article (PF, marchandise)...')),
        enterQuantity: @json(__('messages.Enter Quantity')),
        delete: @json(__('messages.delete'))
    };

    function parseBifToNumber(s) {
        if (!s) return 0;
        return parseFloat(String(s).replace(/[^\d.]/g, '')) || 0;
    }
    
    // Calculate row total
    function calculateRowTotal(rowIndex) {
        var quantity = parseFloat(document.getElementById('quantity_' + rowIndex).value) || 0;
        var unitPrice = parseBifToNumber(document.getElementById('unit_price_' + rowIndex).value);
        
        var total = quantity * unitPrice;
        document.getElementById('total_price_' + rowIndex).value = total.toLocaleString() + ' BIF';
        
        calculateGrandTotal();
    }
    
    // Calculate grand total
    function calculateGrandTotal() {
        var grandTotal = 0;
        var totalInputs = document.querySelectorAll('.total-price');
        
        totalInputs.forEach(function(input) {
            var value = input.value.replace(/[^\d]/g, ''); // Remove non-numeric characters
            if (value) {
                grandTotal += parseFloat(value);
            }
        });
        
        document.getElementById('grand_total').textContent = grandTotal.toLocaleString() + ' BIF';
    }
    
    // Add event listeners for quantity changes
    $(document).on('input', '.quantity-input', function() {
        var rowIndex = this.id.split('_')[1];
        calculateRowTotal(rowIndex);
    });
    
    function escHtml(s) {
        return (s ?? '').toString()
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function initItemRow(rowIndex){
        attachAjaxAutocomplete({
            $input: $('#item_name_' + rowIndex),
            $hidden: $('#item_id_' + rowIndex),
            url: '{{ route("admin.autocomplete.invoice-items") }}',
            renderItem: function(item){
                var price = (item.price ?? 0);
                var typeLabel = item.type_label ? ('<span class="badge badge-secondary ml-1">' + escHtml(item.type_label) + '</span>') : '';
                var name = escHtml(item.name || item.text || '');
                return '<div><strong>' + name + '</strong>' + typeLabel +
                    ' <span class="text-muted">- ' + Number(price).toLocaleString() + ' BIF</span></div>';
            },
            onSelect: function(item){
                var price = Number(item.price ?? 0);
                $('#unit_price_' + rowIndex).val(price.toLocaleString() + ' BIF');
                $('#item_price_' + rowIndex).val(price);
                calculateRowTotal(rowIndex);
            }
        });
    }

    // Initialize autocomplete for existing rows
    @foreach($proforma_invoice->details as $index => $detail)
    initItemRow({{ $index }});
    @endforeach
    @if($proforma_invoice->details->count() == 0)
    initItemRow(0);
    @endif

    attachAjaxAutocomplete({
        $input: $('#client_name'),
        $hidden: $('#client_id'),
        url: '{{ route("admin.autocomplete.clients") }}',
        renderItem: function(item){
            var name = (item.name || item.text || '');
            var tel = item.telephone ? (' - ' + item.telephone) : '';
            name = name.toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            tel = tel.toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            return '<div><strong>' + name + '</strong><span class="text-muted">' + tel + '</span></div>';
        }
    });
       
    $("#add").click(function(){
        ++i;
        
        var markup = "<tr>"+
                    "<td>"+
                        "<div class='ajax-ac-wrap'>"+
                            "<input type='text' name='item_name[]' id='item_name_" + i + "' placeholder='" + i18n.searchItem + "' class='form-control' autocomplete='off' required />"+
                            "<input type='hidden' name='item_id[]' id='item_id_" + i + "' />"+
                            "<input type='hidden' name='item_price[]' id='item_price_" + i + "' />"+
                        "</div>"+
                    "</td>"+
                    "<td>"+
                        "<input type='number' name='quantity[]' id='quantity_" + i + "' placeholder='" + i18n.enterQuantity + "' class='form-control quantity-input' required/>"+
                    "</td>"+
                    "<td>"+
                        "<input type='text' id='unit_price_" + i + "' class='form-control unit-price' readonly />"+
                    "</td>"+
                    "<td>"+
                        "<input type='text' id='total_price_" + i + "' class='form-control total-price' readonly />"+
                    "</td>"+
                    "<td>"+
                        "<button type='button' class='btn btn-danger remove-tr'>" + i18n.delete + "</button>"+
                    "</td>"+
                "</tr>";
        
        $("#dynamicTable").append(markup);
        
        // Initialize autocomplete for the new row
        setTimeout(function() {
            initItemRow(i);
        }, 100);
    });
   
    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
         calculateGrandTotal(); // Recalculate total after removing a row
    });

    $('#dynamic_form').on('submit', function(e) {
        var missingSelection = false;
        $('[id^="item_name_"]').each(function() {
            var rowIndex = this.id.replace('item_name_', '');
            var itemName = $(this).val();
            var itemId = $('#item_id_' + rowIndex).val();
            if (itemName && !itemId) {
                missingSelection = true;
            }
        });
        if (missingSelection) {
            e.preventDefault();
            alert('Veuillez sélectionner chaque article dans la liste (produit fini ou marchandise).');
            $('#save').css('visibility', 'visible');
            return false;
        }
    });

    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () {
        null
    };

</script>
@endsection
