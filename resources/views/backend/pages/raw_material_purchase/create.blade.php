
@extends('backend.layouts.master')

@section('title')
@lang('messages.Demande Achat des Articles') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
    .supplier-autocomplete-wrap { position: relative; }
    .supplier-results {
        position: absolute; top: 100%; left: 0; right: 0; z-index: 1000;
        background: #fff; border: 1px solid #ced4da; border-top: none;
        max-height: 220px; overflow-y: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .supplier-results .supplier-item {
        padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee;
    }
    .supplier-results .supplier-item:hover { background: #f8f9fa; }
</style>
@endsection


@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Demande Achat des Articles')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.raw-material-purchases.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Demande Achat des Articles')</span></li>
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
                    <h4 class="header-title">@lang('messages.Demande Achat des Articles')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.raw-material-purchases.store') }}" method="post" id="dynamic_form">
                        @csrf
                    <div class="row">
                        <div class="col-sm-6" id="dynamicDiv">
                            <div class="form-group">
                                <label for="date">@lang('messages.date')</label>
                                <input type="datetime-local" class="form-control" id="date" name="date" value="{{ old('date', now()->format('Y-m-d\TH:i')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group supplier-autocomplete-wrap">
                                <label for="supplier_search">@lang('messages.Fournisseur')</label>
                                <input type="text" class="form-control" id="supplier_search" placeholder="{{ __('messages.type_to_search_supplier') }}" autocomplete="off">
                                <input type="hidden" name="supplier_id" id="supplier_id" value="" required>
                                <div class="supplier-results" id="supplier_results" style="display: none;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="vat_supplier_payer">@lang('messages.assujetti à la tva?')</label>
                                <select class="form-control" name="vat_supplier_payer" id="vat_supplier_payer" required>
                                    <option value="0" {{ old('vat_supplier_payer', '0') == '0' ? 'selected' : '' }}>@lang('messages.Non assujetti')</option>
                                    <option value="1" {{ old('vat_supplier_payer') == '1' ? 'selected' : '' }}>@lang('messages.Assujetti')</option>
                             </select>
                            </div>
                        </div>
                        <div class="col-md-6" id="vat_rate">
                            
                        </div>
                    </div>

                         <table class="table table-bordered" id="dynamicTable">
                            <thead>
                            <tr>
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.PU HTVA')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr class="item-row">
                                <td>
                                    <input type="text" class="form-control food-name" placeholder="{{ __('messages.type_to_search_material') }}" list="foods_datalist" autocomplete="off" />
                                    <input type="hidden" name="r_material_id[]" class="r_material_id" value="{{ old('r_material_id.0') }}" />
                                </td>
                                <td><input type="number" name="quantity[]" placeholder="{{ __('messages.Enter Quantity') }}" class="form-control quantity-input" step="any" min="0.0001" value="{{ old('quantity.0', 1) }}" required /></td>
                                <td><input type="number" name="price[]" placeholder="{{ __('messages.Enter Price') }}" class="form-control price-input" step="any" required min="0" value="{{ old('price.0') }}" /></td>
                                <td><button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button></td>
                            </tr>
                            </tbody>
                        </table>
                        <datalist id="foods_datalist">
                            @foreach($raw_materials as $raw_material)
                                <option value="{{ $raw_material->name }}"></option>
                            @endforeach
                        </datalist>
                        <div class="row" style="margin-top:10px;">
                            <div class="col-md-12 text-right">
                                <h5 style="margin:0;">@lang('messages.Total HTVA:') <span id="total_htva_value">0</span></h5>
                                <h5 style="margin:0;">@lang('messages.TVA:') <span id="total_vat_value">0</span></h5>
                                <h4 style="margin:0; font-weight:600;">@lang('messages.Total TTC:') <span id="total_ttc_value">0</span></h4>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="{{ __('messages.Enter Description') }}">{{ old('description', __('messages.raw_material_purchase_default_description')) }}</textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" class="btn btn-primary" id="save">@lang('messages.save')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
@include('backend.layouts.partials.ajax_autocomplete')
    <script type="text/javascript">
    var i18n = {
        searchMaterial: @json(__('messages.type_to_search_material')),
        enterQuantity: @json(__('messages.Enter Quantity')),
        enterPrice: @json(__('messages.Enter Price')),
        delete: @json(__('messages.delete')),
        chooseVat: @json(__('messages.merci de choisir')),
        tva: @json(__('messages.TVA'))
    };
    var rawMaterialsByName = @json($raw_materials->mapWithKeys(function ($m) {
        $price = (float) ($m->purchase_price ?? 0);
        if ($price <= 0) {
            $price = (float) ($m->cost_price ?? $m->cump ?? 0);
        }
        return [$m->name => ['id' => $m->id, 'purchase_price' => $price]];
    }));

    function numberFormat(x){
        var parts = (Math.round((x + Number.EPSILON) * 100) / 100).toString().split(".");
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        return parts.join(",");
    }

    function unitPriceFromItem(item) {
        var price = parseFloat(item.purchase_price);
        if (!isNaN(price) && price > 0) return price;
        price = parseFloat(item.cump);
        if (!isNaN(price) && price > 0) return price;
        return 0;
    }

    function applyMaterialToRow($row, id, name, price) {
        $row.find('.r_material_id').val(id);
        $row.find('.food-name').val(name);
        $row.find('.price-input').val(price);
        calculateTotal();
    }

    function resolveMaterialRow($row) {
        var name = ($row.find('.food-name').val() || '').trim();
        if (!name || !rawMaterialsByName[name]) return false;
        var mat = rawMaterialsByName[name];
        applyMaterialToRow($row, mat.id, name, mat.purchase_price);
        return true;
    }

    function calculateTotal(){
        var baseTotal = 0;
        $('#dynamicTable tbody tr.item-row').each(function(){
            var qty = parseFloat($(this).find('.quantity-input').val());
            var price = parseFloat($(this).find('.price-input').val());
            if(isNaN(qty)){ qty = 0; }
            if(isNaN(price)){ price = 0; }
            baseTotal += (qty * price);
        });

        var vatRate = 0;
        var vatSelect = $('#vat_rate').find('select');
        if(vatSelect.length){
            var v = parseFloat(vatSelect.val());
            vatRate = isNaN(v) ? 0 : v;
        }
        var vatAmount = baseTotal * vatRate / 100;
        var totalWithVat = baseTotal + vatAmount;
        $('#total_htva_value').text(numberFormat(baseTotal));
        $('#total_vat_value').text(numberFormat(vatAmount));
        $('#total_ttc_value').text(numberFormat(totalWithVat));
    }

    function initRawMaterialAutocomplete($input){
        if (!$input || !$input.length) return;
        var $row = $input.closest('tr');
        $input.off('input.rmClear').on('input.rmClear', function(){
            $row.find('.r_material_id').val('');
        });
        $input.off('change.rmDatalist blur.rmDatalist').on('change.rmDatalist blur.rmDatalist', function(){
            resolveMaterialRow($row);
        });
        attachAjaxAutocomplete({
            $input: $input,
            url: '{{ route("admin.autocomplete.raw-materials") }}',
            minChars: 1,
            renderItem: function(item){
                var name = (item.name || item.text || '').toString()
                    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                var price = unitPriceFromItem(item);
                return '<div><strong>' + name + '</strong> <span class="text-muted">- ' + numberFormat(price) + '</span></div>';
            },
            onSelect: function(item){
                var name = item.name || item.text || '';
                applyMaterialToRow($row, item.id, name, unitPriceFromItem(item));
            }
        });
    }

    function bindItemRows() {
        $('#dynamicTable tbody tr.item-row .food-name').each(function(){
            initRawMaterialAutocomplete($(this));
        });
    }

    $("#add").click(function(){
         var markup = "<tr class='item-row'>"+
                      "<td>"+
                        "<input type='text' class='form-control food-name' placeholder=\"" + i18n.searchMaterial + "\" list='foods_datalist' autocomplete='off' />"+
                        "<input type='hidden' name='r_material_id[]' class='r_material_id' />"+
                      "</td>"+
                      "<td>"+
                        "<input type='number' name='quantity[]' placeholder=\"" + i18n.enterQuantity + "\" step='any' class='form-control quantity-input' min='0.0001' value='1' required/>"+
                      "</td>"+
                      "<td>"+
                        "<input type='number' name='price[]' placeholder=\"" + i18n.enterPrice + "\" class='form-control price-input' step='any' min='0' required/>"+
                      "</td>"+
                      "<td>"+
                        "<button type='button' class='btn btn-danger remove-tr'>" + i18n.delete + "</button>"+
                      "</td>"+
                   "</tr>";

        $("#dynamicTable tbody").append(markup);
        initRawMaterialAutocomplete($('#dynamicTable tbody tr.item-row:last .food-name'));
        calculateTotal();
    });

    $(document).on('click', '.remove-tr', function(){
         var $tbody = $('#dynamicTable tbody');
         if ($tbody.find('tr.item-row').length <= 1) {
             alert(@json(__('messages.at_least_one_raw_material_required')));
             return;
         }
         $(this).closest('tr').remove();
         calculateTotal();
    });

    $(document).on('input', '.quantity-input, .price-input', function(){
        calculateTotal();
    });

    $('#dynamic_form').on('submit', function(e){
        var valid = true;
        $('#dynamicTable tbody tr.item-row').each(function(){
            var $row = $(this);
            if (!$row.find('.r_material_id').val()) {
                resolveMaterialRow($row);
            }
            if (!$row.find('.r_material_id').val()) {
                valid = false;
            }
            var price = parseFloat($row.find('.price-input').val());
            if (isNaN(price) || price < 0) {
                valid = false;
            }
        });
        if (!valid) {
            e.preventDefault();
            alert(@json(__('messages.raw_material_line_incomplete')));
            return false;
        }
        if ($('#vat_supplier_payer').val() === '1' && !$('#vat_rate select').val()) {
            e.preventDefault();
            alert(i18n.chooseVat + ' (' + i18n.tva + ')');
            return false;
        }
        $('#save').prop('disabled', true);
    });


    //one checked box in checkbox group of invoice_currency

    var group_=(el,callback)=>{
        el.forEach((checkbox)=>{
        callback(checkbox)
        })
    }

    group_(document.getElementsByName('invoice_currency'),(item)=>{
    item.onclick=(e)=>{
    group_(document.getElementsByName('invoice_currency'),(item)=>{
    item.checked=false;
    })
    e.target.checked=true;

    }
    })

    attachAjaxAutocomplete({
        $input: $('#supplier_search'),
        $hidden: $('#supplier_id'),
        url: '{{ route("admin.autocomplete.suppliers") }}',
        minChars: 1,
        renderItem: function(item){
            var name = (item.name || item.text || '').toString()
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            return '<div><strong>' + name + '</strong></div>';
        }
    });

    $('#vat_supplier_payer').change(function () {
        if ($(this).val() === '1'){
            var vat_rate = "<label for='vat_rate_select'>@lang('messages.merci de choisir')<strong style='color: red;'>*</strong></label>"+
                            "<select name='vat_rate' id='vat_rate_select' required class='form-control'>"+
                                "<option value='' disabled selected>@lang('messages.merci de choisir')</option>"+
                                "<option value='0'>0%</option>"+
                                "<option value='10'>10%</option>"+
                                "<option value='18'>18%</option>"+
                            "</select>";
            $("#vat_rate").html(vat_rate);
        } else {
            $("#vat_rate").html("");
        }
        calculateTotal();
    }).trigger('change');

    $(document).ready(function(){
        bindItemRows();
        calculateTotal();
    });

    $(document).on('change', '#vat_rate select', function(){
        calculateTotal();
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

