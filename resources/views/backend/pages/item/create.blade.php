
@extends('backend.layouts.master')

@section('title')
@lang('messages.article_create') - @lang('messages.admin_panel')
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
    .raw-material-autocomplete-wrap { position: relative; }
    .raw-material-results {
        position: absolute; top: 100%; left: 0; right: 0; z-index: 1000;
        background: #fff; border: 1px solid #ced4da; border-top: none;
        max-height: 220px; overflow-y: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .raw-material-results .raw-material-item {
        padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee;
    }
    .raw-material-results .raw-material-item:hover { background: #f8f9fa; }
    .raw-material-results .raw-material-item:last-child { border-bottom: none; }
</style>
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.article_create')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.items.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.article_create')</span></li>
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
                    <h4 class="header-title">@lang('messages.article_create')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.items.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">@lang('messages.item')<strong style="color: red;">*</strong></label>
                                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter Name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="store_type">@lang('messages.store_type')<strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="store_type" id="store_type" required>
                                        <option value="">@lang('messages.Thank you to choose')</option>
                                        <option value="1">{{ __('messages.store_type_finished_goods') }}</option>
                                        <option value="2">{{ __('messages.store_type_goods') }}</option>
                                    </select>
                                    <small class="form-text text-muted">@lang('messages.store_type_help')</small>
                                </div>
                            </div>
                        </div>
                        <div class="row" id="finished_goods_store_row" style="display: none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="finished_goods_store_selection">@lang('messages.finished_goods_store_selection')<strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="finished_goods_store_selection" id="finished_goods_store_selection">
                                        <option value="">@lang('messages.Thank you to choose')</option>
                                        @foreach($finishedGoodsStores as $store)
                                            <option value="fg:{{ $store->code }}">{{ $store->name }} ({{ __('messages.finished_goods_store_menu') }})</option>
                                        @endforeach
                                        @foreach($finishedGoodsSmStores as $store)
                                            <option value="fgsm:{{ $store->code }}">{{ $store->name }} ({{ __('messages.finished_goods_sm_store_menu') }})</option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">@lang('messages.finished_goods_store_selection_help')</small>
                                </div>
                            </div>
                        </div>
                        <div class="row" id="status_row" style="display: none;">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">@lang('messages.Status') (stock marchandises)</label>
                                    <select class="form-control" name="status" id="status">
                                        <option value="1">@lang('messages.Stockable')</option>
                                        <option value="0">@lang('messages.Non stockable')</option>
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
                                    <input type="number" class="form-control" id="cump" name="cump"  min="0" step="any">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="selling_price">@lang('messages.selling_price')<strong style="color: red;">*</strong></label>
                                    <input type="number" class="form-control" id="selling_price" name="selling_price" placeholder="Enter selling price" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group account-autocomplete-wrap">
                                    <label for="account_search">@lang('messages.account')<strong style="color: red;">*</strong></label>
                                    <input type="text" class="form-control" id="account_search" placeholder="Rechercher un compte (numéro ou nom)..." autocomplete="off">
                                    <input type="hidden" name="default_account_id" id="default_account_id" required>
                                    <div id="account_results" class="account-results-dropdown"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fcategory_id">@lang('messages.category')</label>
                                    <select class="form-control" name="fcategory_id" id="fcategory_id">
                                        <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{$category->name}}</option>
                                @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="specification">@lang('messages.specification')</label>
                                    <input type="text" class="form-control" id="specification" name="specification" placeholder="Enter Specification">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="vat">@lang('messages.Taux TVA')</label>
                                    <select class="form-control" name="vat" id="vat" required>
                                        <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                        <option value="0" class="form-control">0%</option>
                                        <option value="10" class="form-control">10%</option>
                                        <option value="18" class="form-control">18%</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div id="raw_materials_section" class="raw-materials-section mt-4" style="display: none;">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fa fa-list-alt"></i> @lang('messages.Matières premières attachées')</h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">@lang('messages.raw_materials_used_help') Vous pouvez <strong>@lang('messages.modifier')</strong> @lang('messages.une ligne (matière ou quantité),') <strong>@lang('messages.enlever')</strong> @lang('messages.une ligne avec le bouton supprimer, ou') <strong>@lang('messages.ajouter')</strong> @lang('messages.d\'autres matières avec le bouton « Ajouter ».')</p>
                                    <table class="table table-bordered" id="dynamicTable">  
                                        <tr>
                                            <th>@lang('messages.item') (Matière première)</th>
                                            <th>@lang('messages.quantity')</th>
                                            <th>@lang('messages.Action')</th>
                                        </tr>
                                        <tr class="raw-material-row" data-row="0">  
                                            <td>
                                                <div class="raw-material-autocomplete-wrap">
                                                    <input type="text" class="form-control raw-material-search food-ingredient" placeholder="{{ __('messages.type_to_search_material') }}" autocomplete="off" data-row="0">
                                                    <input type="hidden" name="r_material_id[]" class="raw-material-id" value="" data-row="0">
                                                    <div class="raw-material-results" style="display: none;"></div>
                                                </div>
                                            </td>  
                                            <td>
                                                <input type='number' name='quantity[]' step='any' min='0' placeholder='Enter Quantity' class='form-control quantity-input' data-row="0" />
                                            </td> 
                                            <td><button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button></td>  
                                        </tr>  
                                    </table>
                                </div>
                            </div>
                        </div>
                        <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.save')</button>
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
        // Show/hide raw materials section based on store_type (1 = produits finis = show, 2 = marchandises = hide)
        function toggleRawMaterialsSection() {
            var st = $('#store_type').val();
            var $section = $('#raw_materials_section');
            var $rMaterials = $section.find('.raw-material-id');
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
                $('#finished_goods_store_selection').prop('required', false).val('');
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
        
        // Calculate CUMP on page load
        calculateTotalCump();
    });

    var i = 0;
    @php
        $rawMaterialPlaceholder = __('messages.type_to_search_material');
        $noResultLabel = __('messages.Aucun résultat');
        $noAccountLabel = __('messages.Aucun compte trouvé');
        $rawMaterialsForJs = $raw_materials->map(function ($r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'cump' => $r->cump ?? 0,
                'equivalent' => $r->rawMaterialMeasurement ? (float) $r->rawMaterialMeasurement->equivalent : 1,
            ];
        })->values();
    @endphp
    var rawMaterialPlaceholder = @json($rawMaterialPlaceholder);
    var rawMaterialsData = @json($rawMaterialsForJs);
    var noResultLabel = @json($noResultLabel);
    var noAccountLabel = @json($noAccountLabel);

    function escHtml(s) {
        return (s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Function to calculate total CUMP (uses row data-cump, data-equivalent and quantity)
    function calculateTotalCump() {
        var totalCump = 0;
        $('#raw_materials_section .raw-material-row').each(function() {
            var $row = $(this);
            var rowIndex = $row.data('row');
            var cump = parseFloat($row.data('cump')) || 0;
            var equivalent = parseFloat($row.data('equivalent')) || 1;
            var quantity = parseFloat($row.find('.quantity-input').val()) || 0;
            if ($row.find('.raw-material-id').val() && quantity > 0) {
                totalCump += (cump / equivalent) * quantity;
            }
        });
        var finalCump = totalCump * 1.15;
        $('#cump').val(finalCump.toFixed(2));
        var sellingPriceBase = finalCump * 1.60;
        var sellingPriceRounded = Math.ceil(sellingPriceBase / 5000) * 5000;
        $('#selling_price').val(sellingPriceRounded);
    }
    
    // Raw material search: on input, filter and show dropdown
    var rawSearchTimer;
    $(document).on('input', '.raw-material-search', function() {
        var $input = $(this);
        var $wrap = $input.closest('.raw-material-autocomplete-wrap');
        var $results = $wrap.find('.raw-material-results');
        var $hidden = $wrap.find('.raw-material-id');
        var q = $input.val().trim().toLowerCase();
        clearTimeout(rawSearchTimer);
        if (q.length < 1) {
            $results.hide().empty();
            $hidden.val('');
            $input.closest('tr').removeData('cump').removeData('equivalent');
            calculateTotalCump();
            return;
        }
        rawSearchTimer = setTimeout(function() {
            var matches = rawMaterialsData.filter(function(r) {
                return (r.name || '').toLowerCase().indexOf(q) !== -1;
            });
            var html = '';
            if (matches.length === 0) {
                html = '<div class="raw-material-item text-muted">' + noResultLabel + '</div>';
            } else {
                matches.forEach(function(r) {
                    html += '<div class="raw-material-item" data-id="' + r.id + '" data-name="' + escHtml(r.name) + '" data-cump="' + r.cump + '" data-equivalent="' + r.equivalent + '">' + escHtml(r.name) + '</div>';
                });
            }
            $results.html(html).show();
        }, 150);
    });
    
    $(document).on('blur', '.raw-material-search', function() {
        var $wrap = $(this).closest('.raw-material-autocomplete-wrap');
        setTimeout(function() { $wrap.find('.raw-material-results').hide(); }, 200);
    });
    
    $(document).on('click', '.raw-material-results .raw-material-item[data-id]', function() {
        var $item = $(this);
        var $wrap = $item.closest('.raw-material-autocomplete-wrap');
        var $row = $wrap.closest('tr');
        $wrap.find('.raw-material-id').val($item.data('id'));
        $wrap.find('.raw-material-search').val($item.data('name'));
        $wrap.find('.raw-material-results').hide().empty();
        $row.data('cump', $item.data('cump')).data('equivalent', $item.data('equivalent'));
        calculateTotalCump();
    });
    
    $(document).on('input change', '.quantity-input', function() {
        calculateTotalCump();
    });
       
    $("#add").click(function(){
        ++i;
        var markup = "<tr class='raw-material-row' data-row='" + i + "'>"+
            "<td><div class='raw-material-autocomplete-wrap'>"+
                "<input type='text' class='form-control raw-material-search food-ingredient' placeholder='" + rawMaterialPlaceholder.replace(/'/g, "&#39;") + "' autocomplete='off' data-row='" + i + "'>"+
                "<input type='hidden' name='r_material_id[]' class='raw-material-id' value='' data-row='" + i + "'>"+
                "<div class='raw-material-results' style='display:none;'></div>"+
            "</div></td>"+
            "<td><input type='number' name='quantity[]' step='any' min='0' placeholder='Enter Quantity' class='form-control quantity-input' data-row='" + i + "' /></td>"+
            "<td><button type='button' class='btn btn-danger remove-tr'>@lang('messages.delete')</button></td>"+
            "</tr>";
        $("#dynamicTable").append(markup);
        toggleRawMaterialsSection();
    });
   
    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
         calculateTotalCump(); // Recalculate after removing a row
    }); 

    var accountSearchUrl = '{{ route("admin.accounting-accounts.search") }}';
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