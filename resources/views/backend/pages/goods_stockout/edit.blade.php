@extends('backend.layouts.master')

@section('title')
@lang('messages.stockout_edit') - @lang('messages.admin_panel')
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.stockout_edit')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.goods-stockouts.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.stockout_edit')</span></li>
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
        <!-- data table start -->
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">@lang('messages.Modifier Sortie Marchandises')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.goods-stockouts.update', $stockout->stockout_no) }}" method="POST">
                        @csrf
                        @method('PUT')
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                             <label for="date">@lang('messages.date')</label>
                                <input type="date" class="form-control" id="date" name="date" value="{{ \Carbon\Carbon::parse($stockout->date)->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="asker">@lang('messages.Demandeur')</label>
                                <input type="text" class="form-control" id="asker" name="asker" value="{{ $stockout->asker }}" placeholder="@lang('messages.Enter asker')" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="destination">@lang('messages.Destination')</label>
                                <input type="text" class="form-control" id="destination" name="destination" value="{{ $stockout->destination }}" placeholder="@lang('messages.Enter destination')" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="requisition_no">@lang('messages.N° Réquisition')</label>
                                    <input type="text" class="form-control" id="requisition_no" name="requisition_no" value="{{ $stockout->requisition_no }}" placeholder="@lang('messages.Enter requisition number')">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="item_movement_type">@lang('messages.Type Sortie')</label>
                                    <select class="form-control" name="item_movement_type" id="item_movement_type" required>
                                        <option value="SN" {{ $stockout->item_movement_type == 'SN' ? 'selected' : '' }}>SN - Sortie Normale</option>
                                        <option value="SP" {{ $stockout->item_movement_type == 'SP' ? 'selected' : '' }}>SP - Sortie Perte</option>
                                        <option value="SV" {{ $stockout->item_movement_type == 'SV' ? 'selected' : '' }}>SV - Sortie Vol</option>
                                        <option value="SC" {{ $stockout->item_movement_type == 'SC' ? 'selected' : '' }}>SC - Sortie Casse</option>
                                        @if (Auth::guard('admin')->user()->can('goods_stockout.edit'))
                                        <option value="SAJ" {{ $stockout->item_movement_type == 'SAJ' ? 'selected' : '' }}>SAJ - Sortie Ajustement</option>
                                        @endif
                                        <option value="SAU" {{ $stockout->item_movement_type == 'SAU' ? 'selected' : '' }}>SAU - Sortie Autre</option>
                                </select>
                                </div>
                            </div>
                    </div>
                    <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="goods_store_id">@lang('messages.Magasin marchandises d\'Origine')<strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="goods_store_id" id="goods_store_id" required>
                                        @foreach($goods_stores as $store)
                                        <option value="{{ $store->id }}" {{ $stockout->goods_store_id == $store->id ? 'selected' : '' }}>{{ $store->code }} - {{ $store->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="item_purchase_or_sale_currency">@lang('messages.Devise')</label>
                                    <input type="text" class="form-control" id="item_purchase_or_sale_currency" name="item_purchase_or_sale_currency" placeholder="BIF" value="{{ $stockout->item_purchase_or_sale_currency ?? 'BIF' }}">
                                </div>
                            </div>
                    </div>
                         <table class="table table-bordered" id="dynamicTable">  
                            <tr>
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.Prix de Coût')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            @foreach($stockoutDetails as $index => $detail)
                            <tr>  
                                <td>
                                    <div class="ajax-ac-wrap">
                                        @php
                                            $item = $detail->item;
                                            if($item) {
                                                $itemName = $item->name . '/' . number_format($item->cump ?? 0,0,',',' ') . '/' . ($item->code ?? '');
                                            } else {
                                                $itemName = '';
                                            }
                                        @endphp
                                        <input type="text" class="form-control item_search" placeholder="@lang('messages.Tapez pour rechercher un article...')" autocomplete="off" value="{{ $itemName }}" required>
                                        <input type="hidden" name="item_id[]" class="item_id" value="{{ $detail->item_id }}">
                                    </div>
                                </td>  
                                <td><input type="number" name="quantity[]" placeholder="@lang('messages.Enter quantity')" class="form-control" value="{{ $detail->quantity }}" step="any" min="0" required /></td>  
                                <td><input type="number" name="cost_price[]" placeholder="@lang('messages.Enter cost price')" class="form-control" value="{{ $detail->cost_price }}" step="any" min="0" readonly /></td>     
                                <td>@if($index == 0)<button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button>@else<button type="button" class="btn btn-danger remove-tr">@lang('messages.delete')</button>@endif</td>  
                            </tr>
                            @endforeach
                        </table> 
                        <div class="col-lg-12">
                            <label for="description"> @lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Entrer description')" required>{{ $stockout->description }}</textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary">@lang('messages.update')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script type="text/javascript">
    var i18n = {
        searchItem: @json(__('messages.Tapez pour rechercher un article...')),
        quantity: @json(__('messages.Enter quantity')),
        costPrice: @json(__('messages.Enter cost price')),
        purchasePrice: @json(__('messages.Enter purchase price')),
        unitPrice: @json(__('messages.Enter Unit price')),
        delete: @json(__('messages.delete'))
    };
</script>
@include('backend.layouts.partials.ajax_autocomplete')
    <script type="text/javascript">
    function initItemAutocomplete($wrap){
        attachAjaxAutocomplete({
            $input: $wrap.find('.item_search'),
            $hidden: $wrap.find('.item_id'),
            url: '{{ route("admin.autocomplete.items") }}',
            minChars: 1,
            onSelect: function(item){
                $wrap.closest('tr').find('input[name="cost_price[]"]').val(item.cump ?? 0);
            }
        });
    }

    $(document).ready(function() {
        $('.ajax-ac-wrap').each(function() {
            if ($(this).find('.item_search').length) {
                initItemAutocomplete($(this));
            }
        });
    });
    
    var i = 0;
       
    $("#add").click(function(){
   
        ++i;

         var markup = "<tr>"+
                      "<td>"+
                         "<div class='ajax-ac-wrap'>"+
                            "<input type='text' class='form-control item_search' placeholder='" + i18n.searchItem + "' autocomplete='off' required>"+
                            "<input type='hidden' name='item_id[]' class='item_id'>"+
                         "</div>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='quantity[]' step='any' placeholder='" + i18n.quantity + "' min='0' class='form-control' required />"+
                        "</td>"+
                        "<td>"+
                        "<input type='number' step='any' name='cost_price[]' placeholder='" + i18n.costPrice + "' class='form-control' min='0' readonly />"+
                        "</td>"+
                        "<td>"+
                          "<button type='button' class='btn btn-danger remove-tr'>@lang('messages.delete')</button>"+
                        "</td>"+
                    "</tr>";
   
        var newRow = $(markup);
        $("#dynamicTable").append(newRow);
        
        initItemAutocomplete(newRow.find('.ajax-ac-wrap'));
    });
   
    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
    }); 

</script>
@endsection

