
@extends('backend.layouts.master')

@section('title')
@lang('messages.goods_store_edit_title') - @lang('messages.admin_panel')
@endsection

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .form-group label { font-weight: 600; margin-bottom: 8px; color: #333; }
    .account-autocomplete-wrap { position: relative; }
    .account-results-dropdown {
        position: absolute; top: 100%; left: 0; right: 0; z-index: 1000;
        background: #fff; border: 1px solid #ced4da; border-top: none;
        max-height: 220px; overflow-y: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .account-results-dropdown .account-item { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee; }
    .account-results-dropdown .account-item:hover { background: #f8f9fa; }
</style>
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.goods_store_edit_title')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.goods-store.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Modifier')</span></li>
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
                    <h4 class="header-title">@lang('messages.goods_store_edit_title')</h4>
                    @include('backend.layouts.partials.messages')

                    <form action="{{ route('admin.goods-store.update', $goods_store->id) }}" method="POST" id="updateForm">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">@lang('messages.Désignation') <strong style="color: red;">*</strong></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $goods_store->name) }}" required>
                                    @error('name')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="emplacement">@lang('messages.Emplacement') <strong style="color: red;">*</strong></label>
                                    <input type="text" class="form-control @error('emplacement') is-invalid @enderror" id="emplacement" name="emplacement" value="{{ old('emplacement', $goods_store->emplacement) }}" required>
                                    @error('emplacement')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="manager">@lang('messages.Gestionnaire')</label>
                                    <input type="text" class="form-control" id="manager" name="manager" value="{{ old('manager', $goods_store->manager) }}">
                                </div>
                            </div>
                            @php
                                $selectedAccountLabel = $goods_store->defaultAccount ? $goods_store->defaultAccount->account_number . ' - ' . $goods_store->defaultAccount->account_name : '';
                            @endphp
                            <div class="col-md-6">
                                <div class="form-group account-autocomplete-wrap">
                                    <label for="account_search">@lang('messages.Compte') <strong style="color: red;">*</strong></label>
                                    <input type="text" class="form-control @error('default_account_id') is-invalid @enderror" id="account_search" placeholder="@lang('messages.Rechercher un compte (numéro ou nom)...')" autocomplete="off" value="{{ old('account_search', $selectedAccountLabel) }}">
                                    <input type="hidden" name="default_account_id" id="default_account_id" required value="{{ old('default_account_id', $goods_store->default_account_id) }}">
                                    <div id="account_results" class="account-results-dropdown"></div>
                                    @error('default_account_id')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description">@lang('messages.Description')</label>
                                    <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $goods_store->description) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary pr-4 pl-4"><i class="fa fa-save"></i> @lang('messages.update')</button>
                            <a href="{{ route('admin.goods-store.index') }}" class="btn btn-secondary pr-4 pl-4"><i class="fa fa-times"></i> {{ __('messages.cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script>
$(document).ready(function() {
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
    $('#updateForm').on('submit', function(e) {
        if (!$('#default_account_id').val()) { e.preventDefault(); $('#account_search').addClass('is-invalid'); return false; }
    });
});
</script>
@endsection
