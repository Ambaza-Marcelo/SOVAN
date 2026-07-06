
@extends('backend.layouts.master')

@section('title')
@lang('messages.reception totale') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.reception totale')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.raw-material-receptions.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.reception totale')</span></li>
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
                    <h4 class="header-title">@lang('messages.new')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.raw-material-reception-without-remaining.store') }}" method="POST">
                        @csrf
                    <input type="hidden" name="type_reception" value="1">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date">@lang('messages.date')</label>
                                <input type="datetime-local" class="form-control" id="date" name="date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="order_no">@lang('messages.Commande No')</label>
                                <input type="text" class="form-control" id="order_no" name="order_no" value="{{$order_no}}" readonly="readonly">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                                <label for="invoice_currency">@lang('messages.Type de Monaie')</label>
                                <div class="form-group">
                                    <label class="text">BIF
                                    <input type="checkbox" name="invoice_currency" value="BIF" checked="checked" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">USD
                                    <input type="checkbox" name="invoice_currency" value="USD" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">EUR
                                    <input type="checkbox" name="invoice_currency" value="EUR" class="form-control">
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                            <div class="form-group">
                                <label for="supplier_id">@lang('messages.Fournisseur')</label>
                                <select class="form-control" name="supplier_id" id="supplier_id" required>
                                 <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{$supplier->id}}" {{ $data->supplier_id == $supplier->id ? 'selected' : '' }}>{{$supplier->supplier_name}}</option>
                                @endforeach
                             </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="vat_supplier_payer">@lang('messages.assujetti à la tva?')</label>
                                <select class="form-control" required name="vat_supplier_payer" id="vat_supplier_payer" required>
                                 <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                    <option value="0" {{ $data->vat_supplier_payer == 0 ? 'selected' : '' }}>Non assujetti</option>
                                    <option value="1" {{ $data->vat_supplier_payer == 1 ? 'selected' : '' }}>Assujetti</option>
                             </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4" id="vat_rate">
                            
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="receptionist">@lang('messages.Receptionniste')</label>
                                <input type="text" class="form-control" id="receptionist" name="receptionist" placeholder="Entrer le nom du Receptionniste" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="handingover">@lang('messages.Remettant')</label>
                                <input type="text" class="form-control" id="handingover" name="handingover" placeholder="Entrer le nom du Remettant" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="invoice_no">@lang('messages.No facture')</label>
                                <input type="text" class="form-control" id="invoice_no" name="invoice_no" placeholder="Entrer No facture" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                                <div class="form-group">
                                    <label for="type_store">@lang('messages.Store Type')<strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="type_store" id="type_store">
                                        <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                        @if (Auth::guard('admin')->user()->can('raw_material_extra_big_inventory.view'))
                                        <option value="0" class="form-control">@lang('messages.Grand Stock')</option>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_big_inventory.view'))
                                        <option value="1" class="form-control">@lang('messages.Stock Intermediaire')</option>
                                        @endif

                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4" id="dynamic_store">
                                
                            </div>
                    </div>
                         <table class="table table-bordered" id="dynamicTable">  
                            <tr>
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.Quantité commandée')</th>
                                <th>@lang('messages.unit_price')</th>
                                <th>@lang('messages.Quantité receptionnée')</th>
                                <th>@lang('messages.Prix Unitaire')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            @foreach($datas as $data)
                            <tr>  
                                <td> <select class="form-control" name="r_material_id[]" id="r_material_id">
                                <option value="{{ $data->r_material_id }}" class="form-control">{{$data->rawMaterial->name}}/{{ number_format($data->rawMaterial->cump,0,',',' ') }}/{{ $data->rawMaterial->rawMaterialMeasurement->purchase_unit }}</option>
                                </select></td>  
                                <td><input type="number" name="quantity_ordered[]" value="{{ $data->quantity }}" step="any" class="form-control"  readonly /></td>  
                                <td><input type="number" name="" value="{{$data->purchase_price}}" class="form-control" step="any" min="0" readonly /></td>
                                <td><input type="number" name="quantity_received[]" value="{{ $data->quantity }}" class="form-control" min="0" step="any" /></td> 
                                <td><input type="number" name="purchase_price[]" value="{{$data->purchase_price}}" class="form-control" step="any" min="0"/></td>
                                <td>
                                <button type='button' class='btn btn-danger remove-tr'>@lang('messages.delete')</button></td>  
                            </tr> 
                            @endforeach 
                        </table>
                        <!--
                        <button type="button" name="add" id="add" class="btn btn-primary">@lang('messages.addmore')</button> 
                    -->
                        <div class="col-lg-12">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="Enter Desccription">
                                MOI {{ Auth::guard('admin')->user()->name }},J'ACCUSE LA RECEPTION DE CES ARTICLES
                            </textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary">@lang('messages.save')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript">
    var i = 0;
       
    $("#add").click(function(){
   
        ++i;

         var markup = "<tr>"+
                      "<td>"+
                         "<select class='form-control' name='r_material_id[]'"+
                            "<option value='0'>@lang('messages.Merci de choisir')</option>"+
                             "@foreach($raw_materials as $raw_material)"+
                                 "<option value='{{ $raw_material->id }}'>{{ $raw_material->name }}/{{ $raw_material->code }}</option>"+
                             "@endforeach>"+
                          "</select>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='quantity[]' placeholder='Enter Quantity' class='form-control' />"+
                        "</td>"+
                        "<td>"+
                        "<input type='number' name='unit_price[]' placeholder='Enter Unit price' class='form-control' />"+
                        "</td>"+
                        "<td>"+
                          
                        "</td>"+
                    "</tr>";
   
        $("#dynamicTable").append(markup);
    });
   
    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
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

    $('#vat_supplier_payer').change(function () { 
    if ($(this).val() === '1'){

                var vat_rate = "<label for='vat_rate'>@lang('messages.merci de choisir')<strong style='color: red;'>*</strong></label>"+
                            "<select name='vat_rate' required class='form-control'>"+
                                "<option selected disabled>@lang('messages.merci de choisir')</option>"+
                                "<option value='0' {{ $data->vat_rate == 0 ? 'selected' : '' }}>0%</option>"+
                                "<option value='10' {{ $data->vat_rate == 10 ? 'selected' : '' }}>10%</option>"+
                                "<option value='18' {{ $data->vat_rate == 18 ? 'selected' : '' }}>18%</option>";
        
        $("#vat_rate").append([vat_rate]);
    }

    })
    .trigger( "change" );

    $('#type_store').change(function () { 
    if ($(this).val() === '0'){

        var big_store = "<div class='form-group'>"+
                            "<label for='dest_extra_store_id'>@lang('messages.Grand Stock')<strong style='color: red;'>*</strong></label>"+
                            "<select name='dest_extra_store_id' class='form-control'>"+
                                "<option selected disabled>@lang('messages.merci de choisir')</option>"+
                                "@foreach($raw_material_big_stores as $material_big_store)"+
                                "<option value='{{$material_big_store->id}}'>{{ $material_big_store->code}}&nbsp;{{ $material_big_store->name}}"+
                                "@endforeach"
                            +
                        "</div>";
        
        $("#dynamic_store").append(big_store);
    }
    if ($(this).val() === '1'){

        var medium_store = "<div class='form-group'>"+
                            "<label for='destination_store_id'>@lang('messages.Stock Intermediaire')<strong style='color: red;'>*</strong></label>"+
                            "<select name='destination_store_id' class='form-control'>"+
                                "<option selected disabled>@lang('messages.merci de choisir')</option>"+
                                "@foreach($raw_material_medium_stores as $raw_material_medium_store)"+
                                "<option value='{{$raw_material_medium_store->id}}'>{{ $raw_material_medium_store->code}}&nbsp;{{ $raw_material_medium_store->name}}"+
                                "@endforeach"
                            +
                        "</div>";
        
        $("#dynamic_store").append(medium_store);
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
