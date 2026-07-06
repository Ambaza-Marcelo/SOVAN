
@extends('backend.layouts.master')

@section('title')
@lang('messages.material_big_store_edit_title') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.material_big_store_edit_title')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.material-big-store.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.material_big_store_edit_title')</span></li>
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
                    <h4 class="header-title">@lang('messages.material_big_store_edit_title')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.material-big-store.update',$material_big_store->code) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">@lang('messages.Designation')</label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ $material_big_store->name }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="emplacement">@lang('messages.emplacement')</label>
                                    <input type="text" class="form-control" id="emplacement" name="emplacement" value="{{ $material_big_store->emplacement }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="manager">@lang('messages.Manager')</label>
                                    <input type="text" class="form-control" id="manager" name="manager" value="{{ $material_big_store->manager }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                @php
                                    $selectedAccount = $accounts->firstWhere('id', old('default_account_id', $material_big_store->default_account_id));
                                    $selectedAccountLabel = $selectedAccount ? $selectedAccount->account_number . ' - ' . $selectedAccount->account_name : '';
                                @endphp
                                <div class="form-group account-autocomplete-wrap">
                                    <label for="account_search">@lang('messages.account')<strong style="color: red;">*</strong></label>
                                    <input type="text" class="form-control" id="account_search" placeholder="{{ __('messages.account_search_placeholder') }}" autocomplete="off" value="{{ old('account_search', $selectedAccountLabel) }}">
                                    <input type="hidden" name="default_account_id" id="default_account_id" required value="{{ old('default_account_id', $material_big_store->default_account_id) }}">
                                    <div id="account_results" class="account-results-dropdown"></div>
                                </div>
                            </div>
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
                $('#account_results').html(html || '<div class="account-item text-muted">@lang('messages.Aucun compte trouvé')</div>').show();
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
