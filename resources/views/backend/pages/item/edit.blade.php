
@extends('backend.layouts.master')

@section('title')
@lang('messages.edit') - @lang('messages.admin_panel')
@endsection

@section('styles')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .form-check-label {
        text-transform: capitalize;
    }
    .select2-container {
        width: 100% !important;
    }
    .select2-container .select2-selection--single {
        height: 38px;
        padding: 5px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .account-autocomplete-wrap { position: relative; }
    .account-results-dropdown {
        position: absolute; top: 100%; left: 0; right: 0; z-index: 1000;
        background: #fff; border: 1px solid #ced4da; border-top: none;
        max-height: 220px; overflow-y: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .account-results-dropdown .account-item {
        padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee;
    }
    .account-results-dropdown .account-item:hover { background: #f8f9fa; }
    .account-results-dropdown .account-item:last-child { border-bottom: none; }
</style>
@endsection



@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.edit')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.items.index') }}">@lang('messages.list')</a></li>
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
                    <h4 class="header-title">@lang('messages.edit')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.items.update',$item->code) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">@lang('messages.item')<strong style="color: red;">*</strong></label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ $item->name }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="store_type">@lang('messages.store_type')<strong style="color: red;">*</strong></label>
                                    @if(!empty($store_type_locked))
                                        <input type="hidden" name="store_type" id="store_type" value="{{ $item->store_type }}">
                                        <input type="text" class="form-control" value="{{ $item->store_type == '1' ? __('messages.store_type_finished_goods') : __('messages.store_type_goods') }}" readonly disabled>
                                        <small class="form-text text-muted">@lang('messages.store_type_locked_help')</small>
                                    @else
                                        <select class="form-control" name="store_type" id="store_type" required>
                                            <option value="">@lang('messages.Thank you to choose')</option>
                                            <option value="1" {{ old('store_type', $item->store_type) == '1' ? 'selected' : '' }}>{{ __('messages.store_type_finished_goods') }}</option>
                                            <option value="2" {{ old('store_type', $item->store_type) == '2' ? 'selected' : '' }}>{{ __('messages.store_type_goods') }}</option>
                                        </select>
                                        <small class="form-text text-muted">@lang('messages.store_type_help')</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @php
                            $selectedFinishedGoodsStoreSelection = null;
                            if (($item->store_type ?? null) === \App\Models\Item::STORE_TYPE_FINISHED_GOODS && !empty($item->code_store)) {
                                $selectedFinishedGoodsStoreSelection = (\App\Models\FinishedGoodsSmStore::where('code', $item->code_store)->exists() ? 'fgsm:' : 'fg:') . $item->code_store;
                            }
                        @endphp
                        <div class="row" id="finished_goods_store_row" style="display: {{ old('store_type', $item->store_type) == '1' ? 'block' : 'none' }};">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="finished_goods_store_selection">@lang('messages.finished_goods_store_selection')<strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="finished_goods_store_selection" id="finished_goods_store_selection">
                                        <option value="">@lang('messages.Thank you to choose')</option>
                                        @foreach($finishedGoodsStores as $store)
                                            <option value="fg:{{ $store->code }}" {{ old('finished_goods_store_selection', $selectedFinishedGoodsStoreSelection) == ('fg:' . $store->code) ? 'selected' : '' }}>
                                                {{ $store->name }} ({{ __('messages.finished_goods_store_menu') }})
                                            </option>
                                        @endforeach
                                        @foreach($finishedGoodsSmStores as $store)
                                            <option value="fgsm:{{ $store->code }}" {{ old('finished_goods_store_selection', $selectedFinishedGoodsStoreSelection) == ('fgsm:' . $store->code) ? 'selected' : '' }}>
                                                {{ $store->name }} ({{ __('messages.finished_goods_sm_store_menu') }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">@lang('messages.finished_goods_store_selection_help')</small>
                                </div>
                            </div>
                        </div>
                        @php
                            $statusValue = old('status', (string)($item->status ?? '1'));
                        @endphp
                        <div class="row" id="status_row" style="display: {{ old('store_type', $item->store_type) == '2' ? 'block' : 'none' }};">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">@lang('messages.Status') (stock marchandises)</label>
                                    <select class="form-control" name="status" id="status">
                                        <option value="1" {{ $statusValue === '1' ? 'selected' : '' }}>@lang('messages.Stockable')</option>
                                        <option value="0" {{ $statusValue === '0' ? 'selected' : '' }}>@lang('messages.Non stockable')</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Visible uniquement lorsque le type de stock est « stock des marchandises ».
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="cump">@lang('messages.cump')<strong style="color: red;">*</strong></label>
                                    <input type="number" class="form-control" id="cump" name="cump" value="{{ $item->cump }}"  min="0" step="any">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="selling_price">@lang('messages.selling_price')<strong style="color: red;">*</strong></label>
                                    <input type="number" class="form-control" id="selling_price" name="selling_price" value="{{ $item->selling_price }}" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                @php
                                    $selectedAccount = $accounts->firstWhere('id', old('default_account_id', $item->default_account_id));
                                    $selectedAccountLabel = $selectedAccount ? $selectedAccount->account_number . ' - ' . $selectedAccount->account_name : '';
                                @endphp
                                <div class="form-group account-autocomplete-wrap">
                                    <label for="account_search">@lang('messages.account')<strong style="color: red;">*</strong></label>
                                    <input type="text" class="form-control" id="account_search" placeholder="Rechercher un compte (numéro ou nom)..." autocomplete="off" value="{{ old('account_search', $selectedAccountLabel) }}">
                                    <input type="hidden" name="default_account_id" id="default_account_id" required value="{{ old('default_account_id', $item->default_account_id) }}">
                                    <div id="account_results" class="account-results-dropdown"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fcategory_id">@lang('messages.category')</label>
                                    <select class="form-control" name="fcategory_id" id="fcategory_id">
                                        <option value="" {{ !old('fcategory_id', $item->fcategory_id) ? 'selected' : '' }} disabled="disabled">Merci de choisir</option>
                                @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('fcategory_id', $item->fcategory_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="specification">@lang('messages.specification')</label>
                                    <input type="text" class="form-control" id="specification" name="specification" value="{{ $item->specification }}" @if(Auth::guard('admin')->user()->can('invoice_drink.edit')) @else readonly @endif>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="vat">@lang('messages.Taux TVA')</label>
                                    <select class="form-control" name="vat" id="vat" required>
                                        <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                        <option value="0" {{ $item->vat == '0' ? 'selected' : '' }} class="form-control">0%</option>
                                        <option value="10" {{ $item->vat == '10' ? 'selected' : '' }} class="form-control">10%</option>
                                        <option value="18" {{ $item->vat == '18' ? 'selected' : '' }} class="form-control">18%</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div id="raw_materials_section" class="raw-materials-section mt-4" style="display: {{ old('store_type', $item->store_type) == '2' ? 'none' : 'block' }};">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fa fa-list-alt"></i> @lang('messages.Matières premières attachées')</h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">@lang('messages.raw_materials_used_help') Vous pouvez <strong>@lang('messages.modifier')</strong> @lang('messages.une ligne (matière ou quantité),') <strong>@lang('messages.enlever')</strong> @lang('messages.une ligne avec le bouton supprimer, ou') <strong>@lang('messages.ajouter')</strong> @lang('messages.d\'autres matières avec le bouton ci-dessous.')</p>
                                    <table class="table table-bordered" id="dynamicTable">  
                                        <tr>
                                            <th>@lang('messages.item') (Matière première)</th>
                                            <th>@lang('messages.quantity')</th>
                                            <th>@lang('messages.Action')</th>
                                        </tr>
                                @forelse($datas as $index => $data)
                                <tr>  
                                    <td> <select class="form-control food-select2 food-ingredient" name="r_material_id[]" data-row="{{ $index }}">
                                    <option value="">@lang('messages.merci de choisir')</option>
                                    @foreach ($raw_materials as $raw_material)
                                    <option value="{{ $raw_material->id }}" 
                                        {{ $data->r_material_id == $raw_material->id ? 'selected' : '' }}
                                        data-cump="{{ $raw_material->cump ?? 0 }}"
                                        data-equivalent="{{ $raw_material->rawMaterialMeasurement ? $raw_material->rawMaterialMeasurement->equivalent : 1 }}">
                                        {{ $raw_material->name }}
                                    </option>
                                    @endforeach
                                    </select>
                                    </td>  
                                    <td>
                                        <input type='number' name='quantity[]' step='any' min='0' value='{{ $data->quantity }}' class='form-control quantity-input' data-row="{{ $index }}" />
                                    </td> 
                                    <td><button type='button' class='btn btn-danger remove-tr'>@lang('messages.delete')</button></td>   
                                </tr>
                                @empty
                                <tr>  
                                    <td> <select class="form-control food-select2 food-ingredient" name="r_material_id[]" data-row="0">
                                    <option value="">@lang('messages.merci de choisir')</option>
                                    @foreach ($raw_materials as $raw_material)
                                    <option value="{{ $raw_material->id }}" data-cump="{{ $raw_material->cump ?? 0 }}" data-equivalent="{{ $raw_material->rawMaterialMeasurement ? $raw_material->rawMaterialMeasurement->equivalent : 1 }}">{{ $raw_material->name }}</option>
                                    @endforeach
                                    </select></td>  
                                    <td><input type='number' name='quantity[]' step='any' min='0' class='form-control quantity-input' data-row="0" /></td> 
                                    <td><button type='button' class='btn btn-danger remove-tr'>@lang('messages.delete')</button></td>  
                                </tr>
                                @endforelse 
                                    </table>
                                    <button type="button" name="add" id="add" class="btn btn-success mt-2"><i class="fa fa-plus-square"></i> @lang('messages.Ajouter une matière première')</button>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- data table end -->
        
    </div>
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script type="text/javascript">
    $(document).ready(function() {
        // Show/hide raw materials section based on store_type (1 = produits finis, 2 = marchandises)
        function toggleRawMaterialsSection() {
            var st = $('#store_type').val();
            var $section = $('#raw_materials_section');
            var $rMaterials = $section.find('select[name="r_material_id[]"]');
            var $quantities = $section.find('input[name="quantity[]"]');
            if (st === '1') {
                $section.show();
                $rMaterials.prop('required', true);
                $quantities.prop('required', true);
            } else {
                $section.hide();
                $rMaterials.prop('required', false);
                $quantities.prop('required', false);
            }
        }
        function toggleStatusField() {
            var st = $('#store_type').val();
            if (st === '2') {
                $('#status_row').show();
            } else {
                $('#status_row').hide();
                $('#status').val('1');
            }
        }
        function toggleFinishedGoodsStoreSelection() {
            var st = $('#store_type').val();
            if (st === '1') {
                $('#finished_goods_store_row').show();
                $('#finished_goods_store_selection').prop('required', true);
            } else {
                $('#finished_goods_store_row').hide();
                $('#finished_goods_store_selection').prop('required', false);
            }
        }
        $('#store_type').on('change', function () {
            toggleRawMaterialsSection();
            toggleStatusField();
            toggleFinishedGoodsStoreSelection();
        });
        toggleRawMaterialsSection();
        toggleStatusField();
        toggleFinishedGoodsStoreSelection();

        // Initialize Select2 on existing food selects
        $('.food-select2').select2({
            placeholder: 'Rechercher un aliment...',
            allowClear: true,
            language: {
                noResults: function() {
                    return "Aucun résultat trouvé";
                },
                searching: function() {
                    return "Recherche en cours...";
                }
            }
        });
        
        // Calculate CUMP on page load
        calculateTotalCump();
    });

    var i = {{ count($datas) ? count($datas) : 1 }};
    var foodOptions = '';
    
    // Build food options once with data attributes
    @foreach($raw_materials as $raw_material)
        foodOptions += "<option value='{{ $raw_material->id }}' data-cump='{{ $raw_material->cump ?? 0 }}' data-equivalent='{{ $raw_material->rawMaterialMeasurement ? $raw_material->rawMaterialMeasurement->equivalent : 1 }}'>{{ $raw_material->name }}</option>";
    @endforeach
    
    // Function to calculate total CUMP
    function calculateTotalCump() {
        var totalCump = 0;
        
        // Loop through each food ingredient row
        $('.food-ingredient').each(function() {
            var $select = $(this);
            var $selectedOption = $select.find('option:selected');
            var rowIndex = $select.data('row');
            var $quantityInput = $('.quantity-input[data-row="' + rowIndex + '"]');
            
            if ($selectedOption.val()) {
                var cump = parseFloat($selectedOption.data('cump')) || 0;
                var equivalent = parseFloat($selectedOption.data('equivalent')) || 1;
                var quantity = parseFloat($quantityInput.val()) || 0;
                
                // Calculate: (cump / equivalent) * quantity
                var itemCost = (cump / equivalent) * quantity;
                totalCump += itemCost;
            }
        });
        
        // Add 15% to the total cost
        var finalCump = totalCump * 1.15;
        
        // Update the CUMP input field
        $('#cump').val(finalCump.toFixed(2));
        
        // Calculate selling price: CUMP + 60% of CUMP (= CUMP × 1.60)
        var sellingPriceBase = finalCump * 1.60;
        
        // Round UP to nearest 5000 (e.g., 46000 → 50000, 41000 → 45000)
        var sellingPriceRounded = Math.ceil(sellingPriceBase / 5000) * 5000;
        
        // Update the selling price input field
        $('#selling_price').val(sellingPriceRounded);
    }
    
    // Event listener for food selection change
    $(document).on('change', '.food-ingredient', function() {
        calculateTotalCump();
    });
    
    // Event listener for quantity change
    $(document).on('input change', '.quantity-input', function() {
        calculateTotalCump();
    });
       
    $("#add").click(function(){
   
        ++i;

         var markup = "<tr>"+
                      "<td>"+
                         "<select class='form-control food-select2 food-ingredient' name='r_material_id[]' required data-row='" + i + "'>"+
                            "<option value=''>@lang('messages.merci de choisir')</option>"+
                            foodOptions +
                          "</select>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='quantity[]' step='any' min='0' step='any' placeholder='Enter Quantity' class='form-control quantity-input' required data-row='" + i + "' />"+
                        "</td>"+
                        "<td>"+
                          "<button type='button' class='btn btn-danger remove-tr'>@lang('messages.delete')</button>"+
                        "</td>"+
                    "</tr>";
   
        $("#dynamicTable").append(markup);
        toggleRawMaterialsSection();
        
        // Initialize Select2 on the newly added select
        $('#dynamicTable tr:last-child .food-select2').select2({
            placeholder: 'Rechercher un aliment...',
            allowClear: true,
            language: {
                noResults: function() {
                    return "Aucun résultat trouvé";
                },
                searching: function() {
                    return "Recherche en cours...";
                }
            }
        });
    });
   
    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
         calculateTotalCump(); // Recalculate after removing a row
    }); 

    var accountSearchUrl = '{{ route("admin.accounting-accounts.search") }}';
    var noAccountLabel = @json(__('messages.Aucun compte trouvé'));
    var accountSearchTimer;
    $('#account_search').on('input', function() {
        var q = $(this).val().trim();
        $('#default_account_id').val('');
        clearTimeout(accountSearchTimer);
        if (q.length < 1) { $('#account_results').hide().empty(); return; }
        accountSearchTimer = setTimeout(function() {
            $.get(accountSearchUrl, { q: q }, function(data) {
                var html = '', esc = function(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); };
                (data.results || []).forEach(function(r) {
                    var raw = (r.text || '').replace(/"/g, '&quot;');
                    html += '<div class="account-item" data-id="' + r.id + '" data-text="' + raw + '">' + esc(r.text) + '</div>';
                });
                $('#account_results').html(html || '<div class="account-item text-muted">' + noAccountLabel + '</div>').show();
            });
        }, 200);
    }).on('blur', function() { setTimeout(function() { $('#account_results').hide(); }, 200); });
    $(document).on('click', '#account_results .account-item[data-id]', function() {
        var id = $(this).data('id'), text = $(this).data('text');
        $('#default_account_id').val(id);
        $('#account_search').val(text);
        $('#account_results').hide().empty();
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