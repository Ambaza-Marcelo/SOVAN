
@extends('backend.layouts.master')

@section('title')
@lang('messages.edit production order') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
    .ajax-ac-wrap {
        position: relative;
    }
    .autocomplete-list {
        position: absolute;
        border: 1px solid #d4d4d4;
        border-top: none;
        z-index: 99;
        max-height: 200px;
        overflow-y: auto;
        background-color: #fff;
        width: 100%;
        display: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-top: -1px;
    }
    .autocomplete-list div {
        padding: 10px;
        cursor: pointer;
        border-bottom: 1px solid #d4d4d4;
    }
    .autocomplete-list div:hover {
        background-color: #e9e9e9;
    }
    .autocomplete-list div:last-child {
        border-bottom: none;
    }
    .autocomplete-active {
        background-color: #4CAF50 !important;
        color: #ffffff;
    }
    .section-title {
        background: #f8f9fa;
        padding: 10px 15px;
        margin: 20px 0 15px 0;
        border-left: 4px solid #007bff;
        font-weight: 600;
        color: #333;
    }
</style>
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.edit production order')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.production-order.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.edit production order')</span></li>
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
                    <h4 class="header-title">@lang('messages.Edit Production Order:') {{ $productionOrder->prodution_number }}</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.production-order.update', $productionOrder->prodution_number) }}" method="post" id="production_order_form">
                        @csrf
                        @method('PUT')
                        
                        <!-- Order Details Section -->
                        <div class="section-title">
                            <i class="fa fa-info-circle"></i> @lang('messages.Order Details')
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="production_date">@lang('messages.Production Date') <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="production_date" name="production_date" value="{{ \Carbon\Carbon::parse($productionOrder->production_date)->format('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    @php
                                        $fgStoreType = $productionOrder->finished_goods_store_type ?: 'MAIN';
                                    @endphp
                                    <label for="finished_goods_store_type">@lang('messages.Stock Produits Finis (Destination)') <span class="text-danger">*</span></label>
                                    <select class="form-control" name="finished_goods_store_type" id="finished_goods_store_type" required>
                                        <option value="MAIN" {{ $fgStoreType === 'MAIN' ? 'selected' : '' }}>@lang('messages.Stock Principal')</option>
                                        <option value="SM" {{ $fgStoreType === 'SM' ? 'selected' : '' }}>@lang('messages.Stock SM')</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6" id="fg_main_store_col">
                                <div class="form-group">
                                    <label for="finished_g_store_id">@lang('messages.Magasin Produits Finis (Principal)') <span class="text-danger">*</span></label>
                                    <select class="form-control" name="finished_g_store_id" id="finished_g_store_id">
                                        <option disabled>@lang('messages.Merci de choisir')</option>
                                        @foreach($finished_goods_stores as $store)
                                            <option value="{{ $store->id }}" {{ $productionOrder->finished_g_store_id == $store->id ? 'selected' : '' }}>{{ $store->code }} - {{ $store->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6" id="fg_sm_store_col" style="display:none;">
                                <div class="form-group">
                                    <label for="finished_g_sm_store_id">@lang('messages.Magasin Produits Finis (SM)') <span class="text-danger">*</span></label>
                                    <select class="form-control" name="finished_g_sm_store_id" id="finished_g_sm_store_id">
                                        <option disabled>@lang('messages.Merci de choisir')</option>
                                        @foreach($finished_goods_sm_stores as $store)
                                            <option value="{{ $store->id }}" {{ $productionOrder->finished_g_sm_store_id == $store->id ? 'selected' : '' }}>{{ $store->code }} - {{ $store->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Scheduling Section -->
                        <div class="section-title">
                            <i class="fa fa-calendar"></i> @lang('messages.Scheduling')
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="planned_start_time">@lang('messages.Planned Start Time')</label>
                                    <input type="datetime-local" class="form-control" id="planned_start_time" name="planned_start_time" value="{{ $productionOrder->planned_start_time ? \Carbon\Carbon::parse($productionOrder->planned_start_time)->format('Y-m-d\TH:i') : \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="planned_finish_time">@lang('messages.Planned Finish Time')</label>
                                    <input type="datetime-local" class="form-control" id="planned_finish_time" name="planned_finish_time" value="{{ $productionOrder->planned_finish_time ? \Carbon\Carbon::parse($productionOrder->planned_finish_time)->format('Y-m-d\TH:i') : \Carbon\Carbon::now()->addDay()->format('Y-m-d\TH:i') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Finished Goods Section -->
                        <div class="section-title">
                            <i class="fa fa-box"></i> @lang('messages.Product & Quantity (Finished Goods)')
                        </div>
                        <table class="table table-bordered" id="finishedGoodsTable">  
                            <tr>
                                <th width="40%">@lang('messages.Item Code / Name') <span class="text-danger">*</span></th>
                                <th width="20%">@lang('messages.Quantity') <span class="text-danger">*</span></th>
                                <th width="20%">@lang('messages.Cost Price')</th>
                                <th width="20%">@lang('messages.Action')</th>
                            </tr>
                            @foreach($finishedGoods as $good)
                            <tr>  
                                <td>
                                    <div class="ajax-ac-wrap">
                                        <input type="text" class="form-control item_search" value="{{ $good->item->code }} - {{ $good->item->name }}" placeholder="@lang('messages.Rechercher un article (code ou nom)...')" autocomplete="off" required>
                                        <input type="hidden" name="item_id[]" class="item_id" value="{{ $good->item_id }}">
                                        <input type="hidden" class="item_code" value="{{ $good->item->code }}">
                                    </div>
                                </td>  
                                <td><input type="number" name="item_quantity[]" value="{{ $good->item_quantity }}" placeholder="@lang('messages.Quantity')" class="form-control" min="0" step="any" required /></td>
                                <td><input type="number" name="cost_price[]" value="{{ $good->cost_price }}" placeholder="@lang('messages.Cost Price')" class="form-control" min="0" step="any" /></td>
                                <td><button type="button" name="add_finished" id="add_finished" class="btn btn-success">@lang('messages.Add More')</button></td>     
                            </tr>
                            @endforeach
                            @if($finishedGoods->isEmpty())
                            <tr>  
                                <td>
                                    <div class="ajax-ac-wrap">
                                        <input type="text" class="form-control item_search" placeholder="@lang('messages.Rechercher un article (code ou nom)...')" autocomplete="off" required>
                                        <input type="hidden" name="item_id[]" class="item_id">
                                        <input type="hidden" class="item_code" value="">
                                    </div>
                                </td>  
                                <td><input type="number" name="item_quantity[]" placeholder="@lang('messages.Quantity')" class="form-control" min="0" step="any" required /></td>
                                <td><input type="number" name="cost_price[]" placeholder="@lang('messages.Cost Price')" class="form-control" min="0" step="any" /></td>
                                <td><button type="button" name="add_finished" id="add_finished" class="btn btn-success">@lang('messages.Add More')</button></td>     
                            </tr>
                            @endif
                        </table>

                        <!-- Raw Materials Section -->
                        <div class="section-title">
                            <i class="fa fa-flask"></i> @lang('messages.Materials (Raw Materials)')
                            <small class="text-muted ml-2">@lang('messages.(Auto-loaded from ItemDetail when finished goods are selected)')</small>
                        </div>
                        <table class="table table-bordered" id="rawMaterialsTable">  
                            <thead>
                                <tr>
                                    <th width="25%">@lang('messages.Raw Material') <span class="text-danger">*</span></th>
                                    <th width="15%">@lang('messages.Quantity') <span class="text-danger">*</span></th>
                                    <th width="10%">@lang('messages.Unit')</th>
                                    <th width="15%">@lang('messages.Cost') <span class="text-danger">*</span></th>
                                    <th width="5%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rawMaterials as $material)
                                <tr>  
                                    <td>
                                        <div class="ajax-ac-wrap">
                                            <input type="text" class="form-control raw_material_search" value="{{ $material->rawMaterial->name }} ({{ $material->rawMaterial->rawMaterialMeasurement->purchase_unit ?? 'N/A' }})" placeholder="@lang('messages.Type to search raw material...')" autocomplete="off" required>
                                            <input type="hidden" name="r_material_id[]" class="r_material_id" value="{{ $material->r_material_id }}">
                                        </div>
                                    </td>  
                                    <td><input type="number" name="material_quantity[]" value="{{ $material->material_quantity }}" placeholder="@lang('messages.Quantity')" class="form-control" min="0" step="any" required /></td>
                                    <td><input type="text" name="unit[]" value="{{ $material->unit }}" placeholder="@lang('messages.Unit')" class="form-control" /></td>
                                    <td><input type="number" name="material_cost[]" value="{{ $material->material_cost }}" placeholder="@lang('messages.Cost')" class="form-control" min="0" step="any" required /></td>
                                    <td><button type="button" class="btn btn-danger btn-sm remove-tr">@lang('messages.Delete')</button></td>     
                                </tr>
                                @endforeach
                                <!-- Rows will be added automatically when finished goods are selected -->
                            </tbody>
                        </table>
                        <div style="margin-top: 10px;">
                            <button type="button" name="add_raw_material" id="add_raw_material" class="btn btn-success">
                                <i class="fa fa-plus"></i> @lang('messages.Add More Raw Material')
                            </button>
                        </div>

                        <!-- Costing Section -->
                        <div class="section-title">
                            <i class="fa fa-calculator"></i> @lang('messages.Costing')
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="estimated_labor_cost">@lang('messages.Estimated Labor Cost')</label>
                                    <input type="number" class="form-control" id="estimated_labor_cost" name="estimated_labor_cost" value="{{ $productionOrder->estimated_labor_cost }}" min="0" step="any">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="estimated_overhead_cost">@lang('messages.Estimated Overhead Cost')</label>
                                    <input type="number" class="form-control" id="estimated_overhead_cost" name="estimated_overhead_cost" value="{{ $productionOrder->estimated_overhead_cost }}" min="0" step="any">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('messages.Total Estimated Cost')</label>
                                    <input type="text" class="form-control" id="total_estimated_cost" readonly value="{{ $productionOrder->total_estimated_cost }}">
                                    <small class="form-text text-muted">@lang('messages.(Calculated automatically)')</small>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="form-group">
                            <label for="description">@lang('messages.Description') <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="description" id="description" rows="3" required>{{ $productionOrder->description }}</textarea>
                        </div>

                        <div style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary" id="save">@lang('messages.update')</button>
                            <a href="{{ route('admin.production-order.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
@include('backend.layouts.partials.ajax_autocomplete')
<script type="text/javascript">
    var i18n = {
        searchItem: @json(__('messages.Rechercher un article (code ou nom)...')),
        quantity: @json(__('messages.Quantity')),
        costPrice: @json(__('messages.Cost Price')),
        searchRawMaterial: @json(__('messages.Type to search raw material...')),
        unit: @json(__('messages.Unit')),
        cost: @json(__('messages.Cost')),
        delete: @json(__('messages.Delete')),
        confirmSave: @json(__('messages.Enregistrer les modifications de l\'ordre de production ?'))
    };
    var itemsSearchUrl = {!! json_encode(route('admin.production-order.items-search')) !!};

    function getSelectedStoreParams() {
        var storeType = $('#finished_goods_store_type').val();
        var storeId = storeType === 'SM' ? $('#finished_g_sm_store_id').val() : $('#finished_g_store_id').val();
        return { storeType: storeType, storeId: storeId };
    }

    @include('backend.pages.partials.production_bom_data_and_helpers')

    var rawMaterials = [
        @foreach($raw_materials as $raw_material)
        {
            id: {{ $raw_material->id }},
            name: {!! json_encode($raw_material->name . ' (' . ($raw_material->rawMaterialMeasurement ? $raw_material->rawMaterialMeasurement->purchase_unit : 'N/A') . ')') !!},
            unit: {!! json_encode($raw_material->rawMaterialMeasurement ? $raw_material->rawMaterialMeasurement->purchase_unit : '') !!},
            cump: {{ $raw_material->cump ?? 0 }}
        }@if(!$loop->last),@endif
        @endforeach
    ];

    function initFinishedGoodsAutocomplete($wrap) {
        attachAjaxAutocomplete({
            $input: $wrap.find('.item_search'),
            $hidden: $wrap.find('.item_id'),
            url: itemsSearchUrl,
            minChars: 1,
            validateBeforeSearch: function() {
                var params = getSelectedStoreParams();
                return params.storeType && params.storeId;
            },
            extraParams: function() {
                var params = getSelectedStoreParams();
                return { store_type: params.storeType, store_id: params.storeId, limit: 20 };
            },
            renderItem: function(item) {
                return '<div><strong>' + (item.text || item.label || '') + '</strong></div>';
            },
            onSelect: function(item) {
                onFinishedProductSelected($wrap, item);
            }
        });
        $wrap.find('.item_search').on('input.clearItem', function() {
            if (!$(this).val().trim()) {
                $wrap.find('.item_id').val('');
                $wrap.find('.item_code').val('');
                recalculateAllRawMaterials();
            }
        });
    }

    function initRawMaterialAutocomplete($wrap) {
        attachLocalAutocomplete({
            $input: $wrap.find('.raw_material_search'),
            $hidden: $wrap.find('.r_material_id'),
            getItems: function() {
                return rawMaterials.map(function(m) {
                    return { id: m.id, text: m.name, unit: m.unit, cump: m.cump };
                });
            },
            renderItem: function(item) {
                return '<div><strong>' + (item.text || item.name || '') + '</strong></div>';
            },
            onSelect: function(item) {
                var row = $wrap.closest('tr');
                row.find('input[name="unit[]"]').val(item.unit || '');
                var costInput = row.find('input[name="material_cost[]"]');
                if (!costInput.val() || costInput.val() == '0') {
                    costInput.val(item.cump || 0);
                }
                calculateRowTotal(row, 'material');
            }
        });
    }

    $(document).ready(function() {
        function syncFinishedGoodsStoreUI() {
            var type = $('#finished_goods_store_type').val();
            if (type === 'SM') {
                $('#fg_sm_store_col').show();
                $('#fg_main_store_col').hide();
                $('#finished_g_sm_store_id').prop('required', true);
                $('#finished_g_store_id').prop('required', false);
            } else {
                $('#fg_sm_store_col').hide();
                $('#fg_main_store_col').show();
                $('#finished_g_sm_store_id').prop('required', false);
                $('#finished_g_store_id').prop('required', true);
            }
        }

        $('#finished_goods_store_type').on('change', syncFinishedGoodsStoreUI);
        syncFinishedGoodsStoreUI();

        $('.ajax-ac-wrap').each(function() {
            if ($(this).find('.item_search').length) {
                initFinishedGoodsAutocomplete($(this));
            }
            if ($(this).find('.raw_material_search').length) {
                initRawMaterialAutocomplete($(this));
            }
        });
        
        // Load raw materials for existing finished goods on page load
        recalculateAllRawMaterials();
    });

    // When store selection changes: clear selected items and rebuild raw materials
    $('#finished_goods_store_type, #finished_g_store_id, #finished_g_sm_store_id').on('change', function () {
        $('#finishedGoodsTable .item_id').val('');
        $('#finishedGoodsTable .item_code').val('');
        $('#finishedGoodsTable .item_search').val('');
        recalculateAllRawMaterials();
    });

    var finishedGoodsCount = 0;
    $("#add_finished").click(function(){
        ++finishedGoodsCount;
        var markup = "<tr>"+
                     "<td>"+
                        "<div class='ajax-ac-wrap'>"+
                           "<input type='text' class='form-control item_search' placeholder='" + i18n.searchItem + "' autocomplete='off' required>"+
                           "<input type='hidden' name='item_id[]' class='item_id'>"+
                           "<input type='hidden' class='item_code' value=''>"+
                        "</div>"+
                       "</td>"+
                       "<td>"+
                         "<input type='number' name='item_quantity[]' placeholder='" + i18n.quantity + "' class='form-control' min='0' step='any' required/>"+
                       "</td>"+
                       "<td>"+
                         "<input type='number' name='cost_price[]' placeholder='" + i18n.costPrice + "' class='form-control' min='0' step='any'/>"+
                       "</td>"+
                       "<td>"+
                         "<button type='button' class='btn btn-danger remove-tr'>" + i18n.delete + "</button>"+
                       "</td>"+
                   "</tr>";
        var newRow = $(markup);
        $("#finishedGoodsTable").append(newRow);
        initFinishedGoodsAutocomplete(newRow.find('.ajax-ac-wrap'));
    });

    // Add raw material row manually
    var rawMaterialCount = 0;
    $("#add_raw_material").click(function(){
        ++rawMaterialCount;
        var markup = "<tr>"+
                     "<td>"+
                        "<div class='ajax-ac-wrap'>"+
                           "<input type='text' class='form-control raw_material_search' placeholder='" + i18n.searchRawMaterial + "' autocomplete='off' required>"+
                           "<input type='hidden' name='r_material_id[]' class='r_material_id'>"+
                        "</div>"+
                       "</td>"+
                       "<td>"+
                         "<input type='number' name='material_quantity[]' placeholder='" + i18n.quantity + "' class='form-control' min='0' step='any' required/>"+
                       "</td>"+
                       "<td>"+
                         "<input type='text' name='unit[]' placeholder='" + i18n.unit + "' class='form-control'/>"+
                       "</td>"+
                       "<td>"+
                         "<input type='number' name='material_cost[]' placeholder='" + i18n.cost + "' class='form-control' min='0' step='any' required/>"+
                       "</td>"+
                       "<td>"+
                         "<button type='button' class='btn btn-danger btn-sm remove-tr'>" + i18n.delete + "</button>"+
                       "</td>"+
                   "</tr>";
        var newRow = $(markup);
        $("#rawMaterialsTable tbody").append(newRow);
        initRawMaterialAutocomplete(newRow.find('.ajax-ac-wrap'));
    });

    $(document).on('click', '.remove-tr', function(){  
        $(this).parents('tr').remove();
        calculateTotalCost();
    });

    // Backward-compatible hook (kept because some earlier code calls it).
    // We now rebuild the raw materials deterministically for all selected items.
    function loadRawMaterialsFromItem(itemCode, itemRow) {
        recalculateAllRawMaterials();
    }

    // Rebuild raw materials from all selected finished goods
    function recalculateAllRawMaterials() {
        // r_material_id -> { quantity, raw_material_name, unit, raw_material_cump }
        var materialsMap = {};

        // Loop through finished goods rows
        $('#finishedGoodsTable tr').each(function() {
            // Skip header row
            if ($(this).find('th').length > 0) {
                return;
            }

            var $row = $(this);
            var itemId = parseInt($row.find('.item_id').val() || 0, 10);
            var itemCode = String($row.find('.item_code').val() || '').trim();

            if (!itemId && !itemCode) {
                return;
            }

            var multiplier = getFinishedGoodsQtyMultiplier($row);
            var details = findItemDetailsForRow($row);

            details.forEach(function(detail) {
                if (detail.r_material_id && detail.r_material_id > 0) {
                    var rMaterialId = parseInt(detail.r_material_id, 10);
                    var requiredQuantity = multiplier * (parseFloat(detail.quantity) || 0);

                    if (materialsMap[rMaterialId]) {
                        materialsMap[rMaterialId].quantity += requiredQuantity;
                    } else {
                        materialsMap[rMaterialId] = {
                            quantity: requiredQuantity,
                            raw_material_name: detail.raw_material_name || '',
                            unit: detail.unit || '',
                            raw_material_cump: detail.raw_material_cump || 0
                        };
                    }
                }
            });
        });

        // Clear existing raw materials rows
        $('#rawMaterialsTable tbody tr').each(function() {
            // tbody shouldn't contain header, but keep it safe
            if ($(this).find('th').length === 0) {
                $(this).remove();
            }
        });

        // Add all materials
        for (var rMaterialId in materialsMap) {
            if (materialsMap.hasOwnProperty(rMaterialId)) {
                var material = materialsMap[rMaterialId];
                var markup = "<tr>"+
                             "<td>"+
                                "<div class='ajax-ac-wrap'>"+
                                   "<input type='text' class='form-control raw_material_search' value='" + (material.raw_material_name || '') + "' placeholder='" + i18n.searchRawMaterial + "' autocomplete='off' required>"+
                                   "<input type='hidden' name='r_material_id[]' class='r_material_id' value='" + rMaterialId + "'>"+
                                "</div>"+
                               "</td>"+
                               "<td>"+
                                 "<input type='number' name='material_quantity[]' value='" + material.quantity.toFixed(2) + "' placeholder='" + i18n.quantity + "' class='form-control' min='0' step='any' required/>"+
                               "</td>"+
                               "<td>"+
                                 "<input type='text' name='unit[]' value='" + (material.unit || '') + "' placeholder='" + i18n.unit + "' class='form-control'/>"+
                               "</td>"+
                               "<td>"+
                                 "<input type='number' name='material_cost[]' value='" + (material.raw_material_cump || 0) + "' placeholder='" + i18n.cost + "' class='form-control' min='0' step='any' required/>"+
                               "</td>"+
                               "<td>"+
                                 "<button type='button' class='btn btn-danger remove-tr'>" + i18n.delete + "</button>"+
                               "</td>"+
                           "</tr>";
                var newRow = $(markup);
                $("#rawMaterialsTable tbody").append(newRow);
                initRawMaterialAutocomplete(newRow.find('.ajax-ac-wrap'));
            }
        }

        calculateTotalCost();
    }

    // Calculate total cost for a specific row
    function calculateRowTotal(row, type) {
        if (type === 'item') {
            var qty = parseFloat(row.find('input[name="item_quantity[]"]').val() || 0);
            var cost = parseFloat(row.find('input[name="cost_price[]"]').val() || 0);
            // Recalculate all raw materials when item quantity changes
            recalculateAllRawMaterials();
        } else if (type === 'material') {
            var qty = parseFloat(row.find('input[name="material_quantity[]"]').val() || 0);
            var cost = parseFloat(row.find('input[name="material_cost[]"]').val() || 0);
            // You can add a total column if needed
        }
        calculateTotalCost();
    }

    function calculateTotalCost() {
        var laborCost = parseFloat($('#estimated_labor_cost').val() || 0);
        var overheadCost = parseFloat($('#estimated_overhead_cost').val() || 0);
        var materialCost = 0;
        
        $('input[name="material_quantity[]"]').each(function() {
            var qty = parseFloat($(this).val() || 0);
            var cost = parseFloat($(this).closest('tr').find('input[name="material_cost[]"]').val() || 0);
            materialCost += qty * cost;
        });
        
        var total = laborCost + materialCost + overheadCost;
        $('#total_estimated_cost').val(total.toFixed(2));
        
        // Calculate total quantity of finished goods
        var totalFinishedGoodsQuantity = 0;
        $('#finishedGoodsTable tr').each(function() {
            if ($(this).find('th').length > 0) {
                return; // Skip header
            }
            var itemId = $(this).find('.item_id').val();
            if (itemId) {
                var qty = parseFloat($(this).find('input[name="item_quantity[]"]').val() || 0);
                totalFinishedGoodsQuantity += qty;
            }
        });
        
        // Distribute total cost to finished goods proportionally
        if (totalFinishedGoodsQuantity > 0 && total > 0) {
            var costPerUnit = total / totalFinishedGoodsQuantity;
            
            $('#finishedGoodsTable tr').each(function() {
                if ($(this).find('th').length > 0) {
                    return; // Skip header
                }
                var itemId = $(this).find('.item_id').val();
                if (itemId) {
                    var qty = parseFloat($(this).find('input[name="item_quantity[]"]').val() || 0);
                    if (qty > 0) {
                        // Set cost_price = Total Estimated Cost (same for all finished goods)
                        // Or distribute proportionally: cost_price = (total / totalQuantity) * itemQuantity
                        // Based on user requirement: cost_price should equal Total Estimated Cost
                        var costInput = $(this).find('input[name="cost_price[]"]');
                        costInput.val(total.toFixed(2));
                    }
                }
            });
        }
    }

    // Calculate totals when inputs change
    $(document).on('input', '#estimated_labor_cost, #estimated_overhead_cost', function() {
        calculateTotalCost();
    });

    $(document).on('input', 'input[name="material_quantity[]"], input[name="material_cost[]"]', function() {
        var row = $(this).closest('tr');
        calculateRowTotal(row, 'material');
    });

    $(document).on('input', 'input[name="item_quantity[]"]', function() {
        var row = $(this).closest('tr');
        // Recalculate all raw materials when quantity changes
        recalculateAllRawMaterials();
        calculateRowTotal(row, 'item');
    });

    $(document).on('input', 'input[name="cost_price[]"]', function() {
        var row = $(this).closest('tr');
        calculateRowTotal(row, 'item');
    });

    $('#production_date').on('change', function () {
        var planDate = $(this).val();
        if (!planDate) return;
        var now = new Date();
        var pad = function (n) { return String(n).padStart(2, '0'); };
        var start = new Date(planDate + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes()));
        var end = new Date(start.getTime());
        end.setDate(end.getDate() + 1);
        var fmt = function (d) {
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        };
        $('#planned_start_time').val(fmt(start));
        $('#planned_finish_time').val(fmt(end));
    });

    $('#production_order_form').on('submit', function(e) {
        if (!confirm(i18n.confirmSave)) {
            e.preventDefault();
            return false;
        }
    });
</script>
@endsection

