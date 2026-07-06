
@extends('backend.layouts.master')

@section('title')
@lang('messages.inventory') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
    .alert-warning {
        background-color: #fff3cd;
        border-color: #ffc107;
        color: #856404;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
</style>
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.inventory')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.raw-material-extra-big-store-inventory.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.edit')</span></li>
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
                    <h4 class="header-title">@lang('messages.edit') @lang('messages.inventory')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    @if($inventory->status == 2)
                        <div class="alert alert-warning">
                            <strong>@lang('messages.Attention!')</strong> Cet inventaire est validé et ne peut pas être modifié.
                        </div>
                    @else
                    
                    <form action="{{ route('admin.raw-material-extra-big-store-inventory.update', $inventory->inventory_no) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="code_store" value="{{ $code }}">
                        <div class="row">
                            <div class="col-sm-6">
                                <input type="hidden" class="form-control" name="bon_no">
                            <div class="form-group">
                                <label for="date">@lang('messages.date')</label>
                                <input type="date" class="form-control" id="date" name="date" value="{{ \Carbon\Carbon::parse($inventory->date)->format('Y-m-d') }}" required>
                            </div>
                            </div>
                            <div class="col-sm-6">
                            <div class="form-group">
                                <label for="title">@lang('messages.title')</label>
                                <input type="text" class="form-control" id="title" name="title" value="{{ $inventory->title }}" required>
                            </div>
                        </div>

                         <table class="table table-bordered" id="dynamicTable">  
                            <thead>
                                <tr class="bg-secondary">
                                    <th>#</th>
                                    <th>@lang('messages.item')</th>
                                    <th>@lang('messages.Quantité théorique')</th>
                                    <th>@lang('messages.unit')</th>
                                    <th>@lang('messages.CUMP')</th>
                                    <th>@lang('messages.selling_price')</th>
                                    <th>@lang('messages.Quantité physique')</th>
                                    <th>@lang('messages.New unit')</th>
                                    <th>@lang('messages.Nouveau CUMP')</th>
                                    <th>@lang('messages.new_selling_price')</th>
                                    <th>@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($inventoryDetails as $detail)
                            <tr class="">  
                                <td>{{ $loop->index + 1 }}</td>
                                <td> 
                                    <select class="form-control" name="r_material_id[]" required>
                                        <option value="{{ $detail->r_material_id }}" selected="selected">{{ $detail->rawMaterial->name }}/{{ $detail->rawMaterial->code }}</option>
                                        @foreach($raw_materials as $raw_material)
                                            @if($raw_material->id != $detail->r_material_id)
                                                <option value="{{ $raw_material->id }}">{{ $raw_material->name }}/{{ $raw_material->code }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </td>  
                                <td><input type="number" name="quantity[]" value="{{ $detail->quantity }}" class="form-control" step="any" min="0" required readonly="readonly" /></td>  
                                <td>
                                    <select class="form-control" name="unit[]" id="unit" required readonly disabled>
                                        <option value="pieces" {{ $detail->unit == 'pieces' ? 'selected' : '' }}>Pieces</option>
                                        <option value="portions" {{ $detail->unit == 'portions' ? 'selected' : '' }}>Portions</option>
                                        <option value="kg" {{ $detail->unit == 'kg' ? 'selected' : '' }}>Kilogrammes</option>
                                        <option value="mg" {{ $detail->unit == 'mg' ? 'selected' : '' }}>Milligrammes</option>
                                        <option value="litres" {{ $detail->unit == 'litres' ? 'selected' : '' }}>Litres</option>
                                        <option value="paquets" {{ $detail->unit == 'paquets' ? 'selected' : '' }}>Paquets</option>
                                        <option value="botts" {{ $detail->unit == 'botts' ? 'selected' : '' }}>Botts</option>
                                        <option value="grammes" {{ $detail->unit == 'grammes' ? 'selected' : '' }}>Grammes</option>
                                        <option value="bidons" {{ $detail->unit == 'bidons' ? 'selected' : '' }}>Bidons</option>
                                        <option value="rouleau" {{ $detail->unit == 'rouleau' ? 'selected' : '' }}>Rouleau</option>
                                        <option value="bouteilles" {{ $detail->unit == 'bouteilles' ? 'selected' : '' }}>Bouteilles</option>
                                        <option value="sachets" {{ $detail->unit == 'sachets' ? 'selected' : '' }}>Sachets</option>
                                        <option value="boites" {{ $detail->unit == 'boites' ? 'selected' : '' }}>Boites</option>
                                    </select>
                                    <input type="hidden" name="unit[]" value="{{ $detail->unit }}">
                                </td>
                                <td><input type="number" name="purchase_price[]" value="{{ $detail->cump ?? $detail->purchase_price }}" class="form-control" step="any" min="0" required readonly="readonly"/></td>  
                                <td><input type="number" name="selling_price[]" value="{{ $detail->selling_price ?? '' }}" class="form-control" step="any" min="0" readonly="readonly"/></td> 
                                <td><input type="number" name="new_quantity[]" value="{{ $detail->new_quantity }}" class="form-control" step="any" min="0" required /></td>
                                <td>
                                    <select class="form-control" name="new_unit[]" id="new_unit" required>
                                        <option value="pieces" {{ $detail->new_unit == 'pieces' ? 'selected' : '' }}>Pieces</option>
                                        <option value="portions" {{ $detail->new_unit == 'portions' ? 'selected' : '' }}>Portions</option>
                                        <option value="kg" {{ $detail->new_unit == 'kg' ? 'selected' : '' }}>Kilogrammes</option>
                                        <option value="mg" {{ $detail->new_unit == 'mg' ? 'selected' : '' }}>Milligrammes</option>
                                        <option value="litres" {{ $detail->new_unit == 'litres' ? 'selected' : '' }}>Litres</option>
                                        <option value="paquets" {{ $detail->new_unit == 'paquets' ? 'selected' : '' }}>Paquets</option>
                                        <option value="botts" {{ $detail->new_unit == 'botts' ? 'selected' : '' }}>Botts</option>
                                        <option value="grammes" {{ $detail->new_unit == 'grammes' ? 'selected' : '' }}>Grammes</option>
                                        <option value="bidons" {{ $detail->new_unit == 'bidons' ? 'selected' : '' }}>Bidons</option>
                                        <option value="rouleau" {{ $detail->new_unit == 'rouleau' ? 'selected' : '' }}>Rouleau</option>
                                        <option value="bouteilles" {{ $detail->new_unit == 'bouteilles' ? 'selected' : '' }}>Bouteilles</option>
                                        <option value="sachets" {{ $detail->new_unit == 'sachets' ? 'selected' : '' }}>Sachets</option>
                                        <option value="boites" {{ $detail->new_unit == 'boites' ? 'selected' : '' }}>Boites</option>
                                    </select>
                                </td>
                                <td><input type="number" name="new_purchase_price[]" value="{{ $detail->new_cump ?? $detail->new_purchase_price }}" class="form-control" step="any" min="0" required /></td> 
                                <td><input type="number" name="new_selling_price[]" value="{{ $detail->new_selling_price }}" class="form-control" step="any" min="0" required /></td>
                                <td><button type="button" class="btn btn-danger remove-tr"><i class="fa fa-trash-o" aria-hidden='false'></i>@lang('messages.&nbsp;Supprimer')</button></td>   
                            </tr> 
                            @endforeach 
                            </tbody>
                        </table> 
                        <button type="button" name="add" id="add" class="btn btn-primary">@lang('messages.addmore')</button>
                        <div class="col-lg-12 mt-3">
                            <label for="description"> @lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" required>{{ $inventory->description }}</textarea>
                        </div>
                        <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.update')</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        <!-- data table end -->
        
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
    <script type="text/javascript">
    var i = 0;
       
    $("#add").click(function(){
   
        ++i;

         var markup = "<tr class=''>"+
                      "<td>"+
                         "<select class='form-control' name='r_material_id[]' required>"+
                            "<option value=''>@lang('messages.merci de choisir')</option>"+
                            "@foreach($raw_materials as $raw_material)"+
                                 "<option value='{{$raw_material->id}}'>{{$raw_material->name}}/{{$raw_material->code}}</option>"+
                            "@endforeach"+
                          "</select>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='quantity[]' placeholder='Quantity' class='form-control' step='any' min='0' required readonly />"+
                        "</td>"+
                        "<td>"+
                          "<select class='form-control' name='unit[]' required readonly disabled>"+
                            "<option value='pieces'>@lang('messages.Pieces')</option>"+
                            "<option value='portions'>@lang('messages.Portions')</option>"+
                            "<option value='kg'>@lang('messages.Kilogrammes')</option>"+
                            "<option value='mg'>@lang('messages.Milligrammes')</option>"+
                            "<option value='litres'>@lang('messages.Litres')</option>"+
                            "<option value='paquets'>@lang('messages.Paquets')</option>"+
                            "<option value='botts'>@lang('messages.Botts')</option>"+
                            "<option value='grammes'>@lang('messages.Grammes')</option>"+
                            "<option value='bidons'>@lang('messages.Bidons')</option>"+
                            "<option value='rouleau'>@lang('messages.Rouleau')</option>"+
                            "<option value='bouteilles'>@lang('messages.Bouteilles')</option>"+
                            "<option value='sachets'>@lang('messages.Sachets')</option>"+
                            "<option value='boites'>@lang('messages.Boites')</option>"+
                          "</select>"+
                          "<input type='hidden' name='unit[]' value='pieces'>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='purchase_price[]' placeholder='Purchase Price' class='form-control' step='any' min='0' required readonly />"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='selling_price[]' placeholder='Selling Price' class='form-control' step='any' min='0' required readonly />"+
                        "</td>"+
                        "<td>"+
                        "<input type='number' name='new_quantity[]' placeholder='new quantity' class='form-control' step='any' min='0' required />"+
                        "</td>"+
                        "<td>"+
                          "<select class='form-control' name='new_unit[]' required>"+
                            "<option value='pieces'>@lang('messages.Pieces')</option>"+
                            "<option value='portions'>@lang('messages.Portions')</option>"+
                            "<option value='kg'>@lang('messages.Kilogrammes')</option>"+
                            "<option value='mg'>@lang('messages.Milligrammes')</option>"+
                            "<option value='litres'>@lang('messages.Litres')</option>"+
                            "<option value='paquets'>@lang('messages.Paquets')</option>"+
                            "<option value='botts'>@lang('messages.Botts')</option>"+
                            "<option value='grammes'>@lang('messages.Grammes')</option>"+
                            "<option value='bidons'>@lang('messages.Bidons')</option>"+
                            "<option value='rouleau'>@lang('messages.Rouleau')</option>"+
                            "<option value='bouteilles'>@lang('messages.Bouteilles')</option>"+
                            "<option value='sachets'>@lang('messages.Sachets')</option>"+
                            "<option value='boites'>@lang('messages.Boites')</option>"+
                          "</select>"+
                        "</td>"+
                        "<td>"+
                        "<input type='number' name='new_purchase_price[]' placeholder='new purchase price' class='form-control' step='any' min='0' required />"+
                        "</td>"+
                        "<td>"+
                        "<input type='number' name='new_selling_price[]' placeholder='new selling price' class='form-control' step='any' min='0' required />"+
                        "</td>"+
                        "<td>"+
                          "<button type='button' class='btn btn-danger remove-tr'><i class='fa fa-trash-o' aria-hidden='false'></i>@lang('messages.&nbsp;Supprimer')</button>"+
                        "</td>"+
                    "</tr>";
   
        $("#dynamicTable tbody").append(markup);
    });
   
    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
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

