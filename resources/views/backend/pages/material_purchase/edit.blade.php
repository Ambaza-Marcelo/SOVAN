
@extends('backend.layouts.master')

@section('title')
@lang('Modifier la demande d\'achat') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('Modifier la demande d\'achat')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.material-purchases.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('Modifier la demande d\'achat')</span></li>
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
                    <h4 class="header-title">@lang('messages.Modifier la demande d\'achat')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.material-purchases.update',$data->purchase_no) }}" method="post" id="dynamic_form">
                        @csrf
                        @method('PUT')
                    <div class="row">
                        <div class="col-md-6" id="dynamicDiv">
                            <div class="form-group">
                                <label for="date">@lang('messages.date')</label>
                                <input type="datetime-local" class="form-control" id="date" name="date" value="{{ $data->date }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group supplier-autocomplete-wrap">
                                <label for="supplier_search">@lang('messages.Fournisseur')</label>
                                <input type="text" class="form-control" id="supplier_search" placeholder="{{ __('messages.type_to_search_supplier') }}" autocomplete="off" value="{{ optional($suppliers->firstWhere('id', $data->supplier_id))->supplier_name }}">
                                <input type="hidden" name="supplier_id" id="supplier_id" value="{{ $data->supplier_id }}" required>
                                <div class="supplier-results" id="supplier_results" style="display: none;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="vat_supplier_payer">@lang('messages.assujetti à la tva?')</label>
                                <select class="form-control" required name="vat_supplier_payer" id="vat_supplier_payer" required>
                                 <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                    <option value="0" {{ $data->vat_supplier_payer == 0 ? 'selected' : '' }}>Non assujetti</option>
                                    <option value="1" {{ $data->vat_supplier_payer == 1 ? 'selected' : '' }}>Assujetti</option>
                             </select>
                            </div>
                        </div>
                        <div class="col-md-6" id="vat_rate">
                            
                        </div>
                    </div>

                         <table class="table table-bordered" id="dynamicTable">  
                            <tr class="">
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.PU HTVA')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            @foreach($datas as $detail)
                            <tr class="">  
                                <td>
                                    <div class="article-autocomplete-wrap" style="position:relative;">
                                        <input type="text" class="form-control article-name" placeholder="Rechercher l'article" autocomplete="off" value="{{ $detail->material_id ? ($detail->material->name ?? '') : ($detail->item->name ?? '') }}" />
                                        <input type="hidden" name="material_id[]" class="material-id" value="{{ $detail->material_id ?? '' }}" />
                                        <input type="hidden" name="item_id[]" class="item-id" value="{{ $detail->item_id ?? '' }}" />
                                    </div>
                                </td>  
                                <td><input type="number" name="quantity[]" class="form-control quantity-input" min="0" value="{{ $detail->quantity }}" step="any" required /></td> 
                                <td><input type="number" name="price[]" class="form-control price-input" min="0" value="{{ $detail->price }}" step="any" required /></td> 
                                <td><button type='button' class='btn btn-danger remove-tr'>@lang('messages.delete')</button></td>     
                            </tr>
                            @endforeach
                        </table>
                        <div>
                            <button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button>
                        </div>
                        <div class="row" style="margin-top:10px;">
                            <div class="col-md-12 text-right">
                                <h5 style="margin:0;">@lang('messages.Total HTVA:') <span id="total_htva_value">0</span></h5>
                                <h5 style="margin:0;">@lang('messages.TVA:') <span id="total_vat_value">0</span></h5>
                                <h4 style="margin:0; font-weight:600;">@lang('messages.Total TTC:') <span id="total_ttc_value">0</span></h4>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="Enter Description">
                                {{ $data->description ?? '' }}
                            </textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary" id="update">@lang('messages.Modifier')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
@include('backend.layouts.partials.ajax_autocomplete')
    <script type="text/javascript">
    var i = 0;

    function numberFormat(x){
        var parts = (Math.round((x + Number.EPSILON) * 100) / 100).toString().split(".");
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        return parts.join(",");
    }

    function calculateTotal(){
        var baseTotal = 0;
        $('#dynamicTable tbody tr').each(function(){
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
       
    $("#add").click(function(){
   
        ++i;

         var markup = "<tr>"+
                      "<td>"+
                        "<div class='article-autocomplete-wrap' style='position:relative;'>"+
                        "<input type='text' class='form-control article-name' placeholder=\"Rechercher l'article\" autocomplete='off' />"+
                        "<input type='hidden' name='material_id[]' class='material-id' value='' />"+
                        "<input type='hidden' name='item_id[]' class='item-id' value='' />"+
                        "</div>"+
                      "</td>"+
                      "<td>"+
                        "<input type='number' name='quantity[]' placeholder='Enter Quantity' step='any' class='form-control quantity-input' min='0' required/>"+
                      "</td>"+
                      "<td>"+
                        "<input type='number' name='price[]' placeholder='Enter Price' class='form-control price-input' step='any' min='0' required/>"+
                      "</td>"+
                      "<td>"+
                        "<button type='button' class='btn btn-danger remove-tr'>@lang('messages.delete')</button>"+
                      "</td>"+
                   "</tr>";
   
        $("#dynamicTable").append(markup);
        setTimeout(function(){
            initArticleAutocomplete($('#dynamicTable tbody tr:last .article-name'));
        }, 50);
        calculateTotal();
    });
   
    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
         calculateTotal();
    });

    function setArticleInRow(row, article){
        row.find('.material-id').val(article.type === 'material' ? article.id : '');
        row.find('.item-id').val(article.type === 'item' ? article.id : '');
        row.find('.price-input').val(article.cump || '');
        calculateTotal();
    }

    function initArticleAutocomplete($input){
        if (!$input || !$input.length) return;
        var $row = $input.closest('tr');
        var $materialId = $row.find('.material-id');
        var $itemId = $row.find('.item-id');

        // If user changes text after selection, clear ids so we don't submit stale values.
        $input.off('input.articleClear').on('input.articleClear', function(){
            $materialId.val('');
            $itemId.val('');
        });

        attachAjaxAutocomplete({
            $input: $input,
            // use one hidden field so helper clears it too
            $hidden: $materialId,
            url: '{{ route("admin.autocomplete.articles") }}',
            minChars: 1,
            renderItem: function(item){
                var name = (item.name || item.text || '').toString()
                    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                var label = item.type === 'material' ? 'Mat' : 'PF/March';
                return '<div><strong>' + name + '</strong> <small class="text-muted">[' + label + ']</small></div>';
            },
            onSelect: function(item){
                setArticleInRow($row, { type: item.type, id: item.entity_id, name: item.name, cump: item.cump });
            }
        });
    }

    $(document).ready(function(){
        $('#dynamicTable tbody .article-name').each(function(){
            initArticleAutocomplete($(this));
        });
    });

    // Recalculate on quantity/price change
    $(document).on('input', '.quantity-input, .price-input', function(){
        calculateTotal();
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
            var vat_rate = "<label for='vat_rate'>@lang('messages.merci de choisir')<strong style='color: red;'>*</strong></label>"+
                            "<select name='vat_rate' required class='form-control'>"+
                                "<option selected disabled>@lang('messages.merci de choisir')</option>"+
                                "<option value='0' {{ $data->vat_rate == 0 ? 'selected' : '' }}>0%</option>"+
                                "<option value='10' {{ $data->vat_rate == 10 ? 'selected' : '' }}>10%</option>"+
                                "<option value='18' {{ $data->vat_rate == 18 ? 'selected' : '' }}>18%</option>";
            $("#vat_rate").html(vat_rate);
        } else {
            $("#vat_rate").html("");
        }
        calculateTotal();
    })
    .trigger( "change" );

    $(document).on('change', '#vat_rate select', function(){
        calculateTotal();
    });

    // Calculate totals on page load
    $(document).ready(function(){
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
