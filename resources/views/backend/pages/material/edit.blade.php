
@extends('backend.layouts.master')

@section('title')
@lang('messages.edit') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
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
    .depreciation-kpi {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 14px 16px;
        height: 100%;
    }
    .depreciation-kpi-label { font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: .03em; }
    .depreciation-kpi-value { font-size: 20px; font-weight: 700; color: #1f2d3d; margin-top: 4px; }
    .depreciation-schedule-table tr.depreciation-current-year {
        background: rgba(23, 162, 184, 0.12) !important;
        outline: 2px solid #17a2b8;
    }
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
                    <li><a href="{{ route('admin.materials.index') }}">@lang('messages.list')</a></li>
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
                    
                    <form action="{{ route('admin.materials.update',$material->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('backend.pages.material.partials.nature_flags')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">@lang('messages.item')<strong style="color: red;">*</strong></label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ $material->name }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="unit">@lang('messages.unit')<strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="material_measurement_id" id="material_measurement_id">
                                        <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                        @foreach($material_measurements as $material_measurement)
                                        <option value="{{ $material_measurement->id }}" {{ $material->material_measurement_id == $material_measurement->id ? 'selected' : '' }}  class="form-control">{{ $material_measurement->purchase_unit }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 js-stockable-section">
                                <div class="form-group">
                                    <label for="quantity">@lang('messages.quantity')</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" value="{{ $material->quantity }}" min="0">
                                </div>
                            </div>
                            <div class="col-md-4 js-stockable-section">
                                <div class="form-group">
                                    <label for="threshold_quantity">@lang('messages.threshold_quantity')</label>
                                    <input type="number" class="form-control" id="threshold_quantity" name="threshold_quantity" value="{{ $material->threshold_quantity }}" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="purchase_price">@lang('messages.purchase_price') / Coût HT<strong style="color: red;">*</strong></label>
                                    <input type="number" step="0.01" class="form-control" id="purchase_price" name="purchase_price" value="{{ $material->purchase_price }}" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="mcategory_id">@lang('messages.category')</label>
                                    <select class="form-control" name="mcategory_id" id="mcategory_id">
                                        <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ $material->mcategory_id == $category->id ? 'selected' : '' }}>{{$category->name}}</option>
                                @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 js-stockable-section">
                                <div class="form-group">
                                    <label for="store_type">@lang('messages.Store Type')<strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="store_type" id="store_type">
                                        <option disabled="disabled" {{ is_null(old('store_type', $material->store_type)) ? 'selected' : '' }}>@lang('messages.Merci de choisir')</option>
                                        <option value="0" {{ (string) old('store_type', $material->store_type) === '0' ? 'selected' : '' }}>@lang('messages.Grand Stock')</option>
                                        <option value="1" {{ (string) old('store_type', $material->store_type) === '1' ? 'selected' : '' }}>@lang('messages.Stock Intermediaire')</option>
                                        <option value="2" {{ (string) old('store_type', $material->store_type) === '2' ? 'selected' : '' }}>@lang('messages.Petit Stock')</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                @php
                                    $selectedAccount = $accounts->firstWhere('id', old('default_account_id', $material->default_account_id));
                                    $selectedAccountLabel = $selectedAccount ? $selectedAccount->account_number . ' - ' . $selectedAccount->account_name : '';
                                @endphp
                                <div class="form-group account-autocomplete-wrap">
                                    <label for="account_search">Compte de charge (sortie)</label>
                                    <input type="text" class="form-control" id="account_search" placeholder="@lang('messages.hr_search_account_placeholder')" autocomplete="off" value="{{ old('account_search', $selectedAccountLabel) }}">
                                    <input type="hidden" name="default_account_id" id="default_account_id" value="{{ old('default_account_id', $material->default_account_id) }}">
                                    <div id="account_results" class="account-results-dropdown"></div>
                                    <small class="text-muted">Optionnel. Imputé automatiquement lors d'une sortie pour utilisation.</small>
                                </div>
                            </div>
                        </div>
                        <div class="row js-stockable-section">
                            <div class="col-md-6" id="dynamic_big_store">
                                
                            </div>
                            <div class="col-md-6" id="dynamic_small_store">
                                
                            </div>
                        </div>

                        <hr class="mt-4 js-amortizable-section">
                        <h5 class="mb-3 js-amortizable-section">Amortissement linéaire</h5>
                        <div class="js-amortizable-section">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="depreciation_base">Base d'amortissement HT</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="depreciation_base" name="depreciation_base" value="{{ old('depreciation_base', $material->depreciation_base ?? $material->purchase_price) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="residual_value">Valeur résiduelle</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="residual_value" name="residual_value" value="{{ old('residual_value', $material->residual_value ?? 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="duration_years">Durée (années)</label>
                                    <input type="number" min="1" class="form-control" id="duration_years" name="duration_years" value="{{ old('duration_years', $material->duration_years ?? 3) }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="service_start_date">Date de mise en service <small class="text-muted">(auto à l'approbation si vide)</small></label>
                                    <input type="date" class="form-control" id="service_start_date" name="service_start_date" value="{{ old('service_start_date', optional($material->service_start_date)->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-md-8">
                                @php
                                    $selectedImmob = $accounts->firstWhere('id', old('immobilization_account_id', $material->immobilization_account_id ?? null));
                                    $selectedImmobLabel = $selectedImmob ? $selectedImmob->account_number . ' - ' . $selectedImmob->account_name : '';
                                @endphp
                                <div class="form-group account-autocomplete-wrap">
                                    <label for="immobilization_account_search">Compte d'immobilisation brut (classe 2, ex: 2183 / 205)</label>
                                    <input type="text" class="form-control" id="immobilization_account_search" placeholder="@lang('messages.hr_search_account_placeholder')" autocomplete="off" value="{{ old('immobilization_account_search', $selectedImmobLabel) }}">
                                    <input type="hidden" name="immobilization_account_id" id="immobilization_account_id" value="{{ old('immobilization_account_id', $material->immobilization_account_id ?? '') }}">
                                    <div id="immobilization_account_results" class="account-results-dropdown"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                @php
                                    $selectedDepExp = $accounts->firstWhere('id', old('depreciation_expense_account_id', $material->depreciation_expense_account_id));
                                    $selectedDepExpLabel = $selectedDepExp ? $selectedDepExp->account_number . ' - ' . $selectedDepExp->account_name : '';
                                @endphp
                                <div class="form-group account-autocomplete-wrap">
                                    <label for="depreciation_expense_account_search">Compte charge d'amortissement (ex: 6811)</label>
                                    <input type="text" class="form-control" id="depreciation_expense_account_search" placeholder="@lang('messages.hr_search_account_placeholder')" autocomplete="off" value="{{ old('depreciation_expense_account_search', $selectedDepExpLabel) }}">
                                    <input type="hidden" name="depreciation_expense_account_id" id="depreciation_expense_account_id" value="{{ old('depreciation_expense_account_id', $material->depreciation_expense_account_id) }}">
                                    <div id="depreciation_expense_account_results" class="account-results-dropdown"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                @php
                                    $selectedDepAsset = $accounts->firstWhere('id', old('depreciation_asset_account_id', $material->depreciation_asset_account_id));
                                    $selectedDepAssetLabel = $selectedDepAsset ? $selectedDepAsset->account_number . ' - ' . $selectedDepAsset->account_name : '';
                                @endphp
                                <div class="form-group account-autocomplete-wrap">
                                    <label for="depreciation_asset_account_search">Compte amortissements cumulés (ex: 2805)</label>
                                    <input type="text" class="form-control" id="depreciation_asset_account_search" placeholder="@lang('messages.hr_search_account_placeholder')" autocomplete="off" value="{{ old('depreciation_asset_account_search', $selectedDepAssetLabel) }}">
                                    <input type="hidden" name="depreciation_asset_account_id" id="depreciation_asset_account_id" value="{{ old('depreciation_asset_account_id', $material->depreciation_asset_account_id) }}">
                                    <div id="depreciation_asset_account_results" class="account-results-dropdown"></div>
                                </div>
                            </div>
                        </div>

                        @include('backend.pages.material.partials.depreciation_schedule', ['material' => $material])
                        </div>

                        <button type="submit" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.update')</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- data table end -->
        
    </div>
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript">
    
    $('#store_type').change(function () { 
    $("#dynamic_big_store").empty().show();
    $("#dynamic_small_store").empty().show();
    if ($(this).val() === '0'){

        var extra_big_store = "<div class='form-group'>"+
                            "<label for='code_store'>@lang('messages.Grand Stock')<strong style='color: red;'>*</strong></label>"+
                            "<select name='code_store' class='form-control'>"+
                                "<option selected disabled>@lang('messages.merci de choisir')</option>"+
                                "@foreach($material_extra_big_stores as $material_big_store)"+
                                "<option value='{{$material_big_store->code}}'>{{ $material_big_store->code}}&nbsp;{{ $material_big_store->name}}"+
                                "@endforeach"
                            +
                        "</div>";
        
        $("#dynamic_big_store").append(extra_big_store);
        $("#dynamic_small_store").hide();
    } 
    if ($(this).val() === '1'){

        var big_store = "<div class='form-group'>"+
                            "<label for='code_store'>@lang('messages.Stock Intermediaire')<strong style='color: red;'>*</strong></label>"+
                            "<select name='code_store' class='form-control'>"+
                                "<option selected disabled>@lang('messages.merci de choisir')</option>"+
                                "@foreach($material_big_stores as $material_big_store)"+
                                "<option value='{{$material_big_store->code}}'>{{ $material_big_store->code}}&nbsp;{{ $material_big_store->name}}"+
                                "@endforeach"
                            +
                        "</div>";
        
        $("#dynamic_big_store").append(big_store);
    }
    if ($(this).val() === '2'){

        var small_store = "<div class='form-group'>"+
                            "<label for='code_store'>@lang('messages.Petit Stock')<strong style='color: red;'>*</strong></label>"+
                            "<select name='code_store' class='form-control'>"+
                                "<option selected disabled>@lang('messages.merci de choisir')</option>"+
                                "@foreach($material_small_stores as $material_small_store)"+
                                "<option value='{{$material_small_store->code}}'>{{ $material_small_store->code }}&nbsp;{{ $material_small_store->name}}</option>"+
                                "@endforeach"
                            +
                        "</div>";
        
        $("#dynamic_big_store").append(small_store);
    }

    })
    .trigger( "change" );

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

    function bindAccountSearch(inputId, hiddenId, resultsId) {
        var timer;
        $('#' + inputId).on('input', function() {
            var q = $(this).val().trim();
            $('#' + hiddenId).val('');
            clearTimeout(timer);
            if (q.length < 1) { $('#' + resultsId).hide().empty(); return; }
            timer = setTimeout(function() {
                $.get(accountSearchUrl, { q: q }, function(data) {
                    var html = '', esc = function(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); };
                    (data.results || []).forEach(function(r) {
                        var raw = (r.text || '').replace(/"/g, '&quot;');
                        html += '<div class="account-item" data-id="' + r.id + '" data-text="' + raw + '">' + esc(r.text) + '</div>';
                    });
                    $('#' + resultsId).html(html || '<div class="account-item text-muted">' + noAccountLabel + '</div>').show();
                });
            }, 200);
        }).on('blur', function() { setTimeout(function() { $('#' + resultsId).hide(); }, 200); });

        $(document).on('click', '#' + resultsId + ' .account-item[data-id]', function() {
            $('#' + hiddenId).val($(this).data('id'));
            $('#' + inputId).val($(this).data('text'));
            $('#' + resultsId).hide().empty();
        });
    }

    bindAccountSearch('depreciation_expense_account_search', 'depreciation_expense_account_id', 'depreciation_expense_account_results');
    bindAccountSearch('depreciation_asset_account_search', 'depreciation_asset_account_id', 'depreciation_asset_account_results');
    bindAccountSearch('immobilization_account_search', 'immobilization_account_id', 'immobilization_account_results');
    </script>
    @include('backend.pages.material.partials.nature_flags_scripts')
    <script type="text/javascript">
    (function highlightCurrentDepreciationYear() {
        var currentYear = new Date().getFullYear();
        $('#depreciationScheduleTable tbody tr').each(function() {
            if (parseInt($(this).data('year'), 10) === currentYear) {
                $(this).addClass('depreciation-current-year');
            }
        });
    })();

    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () {
        null
    };


</script>
@endsection
