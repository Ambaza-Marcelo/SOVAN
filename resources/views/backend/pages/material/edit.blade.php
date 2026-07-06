
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="quantity">@lang('messages.quantity')</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" value="{{ $material->quantity }}" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="threshold_quantity">@lang('messages.threshold_quantity')</label>
                                    <input type="number" class="form-control" id="threshold_quantity" name="threshold_quantity" value="{{ $material->threshold_quantity }}" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="purchase_price">@lang('messages.purchase_price')<strong style="color: red;">*</strong></label>
                                    <input type="number" class="form-control" id="purchase_price" name="purchase_price" value="{{ $material->purchase_price }}" min="0">
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="store_type">@lang('messages.Store Type')<strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="store_type" id="store_type">
                                        <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                        <option value="0" class="form-control">@lang('messages.Grand Stock')</option>
                                        <option value="1" class="form-control">@lang('messages.Stock Intermediaire')</option>
                                        <option value="2" class="form-control">@lang('messages.Petit Stock')</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                @php
                                    $selectedAccount = $accounts->firstWhere('id', old('default_account_id', $material->default_account_id));
                                    $selectedAccountLabel = $selectedAccount ? $selectedAccount->account_number . ' - ' . $selectedAccount->account_name : '';
                                @endphp
                                <div class="form-group account-autocomplete-wrap">
                                    <label for="account_search">@lang('messages.account')<strong style="color: red;">*</strong></label>
                                    <input type="text" class="form-control" id="account_search" placeholder="Rechercher un compte (numéro ou nom)..." autocomplete="off" value="{{ old('account_search', $selectedAccountLabel) }}">
                                    <input type="hidden" name="default_account_id" id="default_account_id" required value="{{ old('default_account_id', $material->default_account_id) }}">
                                    <div id="account_results" class="account-results-dropdown"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6" id="dynamic_big_store">
                                
                            </div>
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

    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () {
        null
    };


</script>
@endsection
