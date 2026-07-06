
@extends('backend.layouts.master')

@section('title')
@lang('messages.Demande de sortie de fonds') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Demande de sortie de fonds')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.cash-out-flow-request.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Demande de sortie de fonds')</span></li>
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
                    <h4 class="header-title">@lang('messages.Demande de sortie de fonds')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.material-purchase-payment-request.store') }}" method="post" id="dynamic_form">
                        @csrf
                        @method('POST')
                    <input type="hidden" name="purchase_no" value="{{ $data->reception_no }}">
                    <div class="row">
                        <div class="col-md-6" id="dynamicDiv">
                            <div class="form-group">
                                <label for="date">@lang('messages.date')</label>
                                <input type="datetime-local" class="form-control" id="date" name="date" value="{{ $data->date }}">
                            </div>
                        </div>
                        <div class="col-md-6" id="dynamicDiv">
                            <div class="form-group">
                                <label for="asker">@lang('messages.asker')</label>
                                <input type="text" class="form-control" id="asker" name="asker" value="{{ $data->receptionist }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="supplier_id">@lang('messages.Fournisseur')</label>
                                <input type="text"
                                       class="form-control supplier-name"
                                       id="supplier_name"
                                       placeholder="@lang('messages.type_to_search_supplier')"
                                       list="suppliers_datalist"
                                       autocomplete="off"
                                       value="{{ optional($suppliers->firstWhere('id', $data->supplier_id))->supplier_name }}"
                                       required>
                                <input type="hidden" name="supplier_id" id="supplier_id" class="supplier-id" value="{{ $data->supplier_id }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="cost_center_id">@lang('messages.Cost Center')</label>
                                <select class="form-control" name="cost_center_id" id="cost_center_id" required>
                                 <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                @foreach($cost_centers as $cost_center)
                                    <option value="{{$cost_center->id}}" {{ $data->cost_center_id == $cost_center->id ? 'selected' : '' }}>{{$cost_center->name}}</option>
                                @endforeach
                             </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="currency_id">@lang('messages.Currency')</label>
                                <select class="form-control" name="currency_id" id="currency_id" required>
                                 <option disabled="disabled">@lang('messages.Merci de choisir')</option>
                                @foreach($currencies as $currency)
                                    <option value="{{$currency->id}}" {{ ($data->currency_id == $currency->id) || (!$data->currency_id && strtoupper($currency->currency_name) == 'BIF') ? 'selected' : '' }}>{{$currency->currency_name}}</option>
                                @endforeach
                             </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="vat_supplier_payer">@lang('messages.assujetti à la tva?')</label>
                                <select class="form-control" required name="vat_supplier_payer" id="vat_supplier_payer" required>
                                 <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                    <option value="0" {{ $data->vat_supplier_payer == '0' ? 'selected' : '' }}>Non assujetti</option>
                                    <option value="1" {{ $data->vat_supplier_payer == '1' ? 'selected' : '' }}>Assujetti</option>
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
                                <th>@lang('messages.purchase_price')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            @foreach($datas as $row)
                            <tr class="">    
                                <td>
                                <select class="form-control article-select" name="article_key[]" required>
                                    <option value="">@lang('messages.merci de choisir')</option>
                                    <optgroup label="Matériaux">
                                    @foreach($materials as $material)
                                        <option value="m_{{ $material->id }}" data-cost="{{ $material->cump ?? 0 }}" {{ (isset($row->material_id) && $row->material_id == $material->id) ? 'selected' : '' }}>{{ $material->name }} / {{ number_format($material->cump ?? 0, 0, ',', ' ') }} / {{ $material->materialMeasurement->purchase_unit ?? '-' }}</option>
                                    @endforeach
                                    </optgroup>
                                    @if(isset($items) && $items->isNotEmpty())
                                    <optgroup label="Articles">
                                    @foreach($items as $item)
                                        <option value="i_{{ $item->id }}" data-cost="{{ $item->cump ?? 0 }}" {{ (isset($row->item_id) && $row->item_id == $item->id) ? 'selected' : '' }}>{{ $item->name }} / {{ number_format($item->cump ?? 0, 0, ',', ' ') }} / {{ $item->code ?? '-' }}</option>
                                    @endforeach
                                    </optgroup>
                                    @endif
                                </select>
                                <input type="hidden" name="material_id[]" class="material-id-input" value="{{ $row->material_id ?? '' }}">
                                <input type="hidden" name="item_id[]" class="item-id-input" value="{{ $row->item_id ?? '' }}">
                                </td> 
                                <td><input type="number" name="quantity[]" class="form-control" min="0" value="{{ $row->quantity_received }}" step="any" required /></td> 
                                <td><input type="number" name="cost_price[]" class="form-control cost-price-input" min="0" readonly value="{{ $row->purchase_price ?? $row->material->cump ?? $row->item->cump ?? 0 }}" step="any" required /></td>  
                                <td><button type='button' class='btn btn-danger remove-tr'>@lang('messages.delete')</button></td>     
                            </tr>
                            @endforeach
                        </table>
                        <datalist id="suppliers_datalist">
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->supplier_name }}"></option>
                            @endforeach
                        </datalist>
                        <div class="col-lg-12">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Enter Description')">
                                PAIEMENT FOURNISSEUR {{ $data->supplier->supplier_name }}
                            </textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary" id="save">@lang('messages.save')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript">
    var i18n = {
        pleaseChoose: @json(__('messages.merci de choisir')),
        delete: @json(__('messages.delete')),
        enterQuantity: @json(__('messages.Enter Quantity')),
        searchExpense: @json(__('messages.Tapez pour rechercher un article...')),
    };
    // Suppliers dataset for autocomplete
    var suppliersData = [
@foreach($suppliers as $supplier)
        { id: {{ $supplier->id }}, name: "{{ addslashes($supplier->supplier_name) }}" }@if(!$loop->last),@endif
@endforeach
    ];

    function findSupplierByName(name){
        if(!name){ return null; }
        name = (name+"").toLowerCase().trim();
        for (var idx = 0; idx < suppliersData.length; idx++){
            if((suppliersData[idx].name+"").toLowerCase() === name){
                return suppliersData[idx];
            }
        }
        return null;
    }

    // Supplier autocomplete: set hidden supplier_id
    $(document).on('change blur', '.supplier-name', function () {
        var val = $(this).val();
        var supplier = findSupplierByName(val);
        if (supplier) {
            $('.supplier-id').val(supplier.id);
        } else {
            $('.supplier-id').val('');
        }
    });

    $(document).on('change', '.article-select', function() {
        var row = $(this).closest('tr');
        var val = $(this).val();
        if (val) {
            if (val.indexOf('m_') === 0) {
                row.find('.material-id-input').val(val.replace('m_', ''));
                row.find('.item-id-input').val('');
            } else if (val.indexOf('i_') === 0) {
                row.find('.material-id-input').val('');
                row.find('.item-id-input').val(val.replace('i_', ''));
            }
            var cost = $(this).find('option:selected').data('cost');
            if (cost !== undefined) row.find('.cost-price-input').val(cost);
        } else {
            row.find('.material-id-input').val('');
            row.find('.item-id-input').val('');
        }
    });
    $(document).ready(function() { $('.article-select').each(function() { $(this).trigger('change'); }); });
    $('#vat_supplier_payer').change(function () { 
    if ($(this).val() === '1'){

                var vat_rate = "<label for='vat_rate'>" + i18n.pleaseChoose + "<strong style='color: red;'>*</strong></label>"+
                            "<select name='vat_rate' required class='form-control'>"+
                                "<option selected disabled>" + i18n.pleaseChoose + "</option>"+
                                "<option value='0' {{ $data->vat_rate == '0' ? 'selected' : '' }}>0%</option>"+
                                "<option value='10' {{ $data->vat_rate == '10' ? 'selected' : '' }}>10%</option>"+
                                "<option value='18' {{ $data->vat_rate == '18' ? 'selected' : '' }}>18%</option>";
        
        $("#vat_rate").append([vat_rate]);
    }

    })
    .trigger( "change" ); 

    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () {
        null
    };

    </script>
@endsection
