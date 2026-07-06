
@extends('backend.layouts.master')

@section('title')
@lang('messages.Creation du fournisseur') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Creation du fournisseur')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.suppliers.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Creation du fournisseur')</span></li>
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
                    <h4 class="header-title">@lang('messages.Creation du Fournisseur')</h4>
                    @include('backend.layouts.partials.messages')

                    <form action="{{ route('admin.suppliers.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tp_type">@lang('messages.Type de contribuable')</label>
                                    <select name="tp_type" id="tp_type" class="form-control" required>
                                        <option value="">@lang('messages.merci de choisir')</option>
                                        <option value="1">@lang('messages.Personne Physique')</option>
                                        <option value="2">@lang('messages.société locale')</option>
                                        <option value="3">@lang('messages.société étrangère')</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6" id="supplier_tin_group">
                                <div class="form-group">
                                    <label for="supplier_TIN">@lang('messages.NIF du Fournisseur')</label>
                                    <input type="text" class="form-control" id="supplier_TIN" name="supplier_TIN" placeholder="{{ __('messages.Entrer le NIF du Fournisseur') }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6" id="supplier_name_group">
                                <div class="form-group">
                                    <label for="supplier_name">@lang('messages.Nom du Fournisseur')<strong style="color: red;" id="supplier_name_required">*</strong></label>
                                    <input type="text" class="form-control" id="supplier_name" name="supplier_name" placeholder="{{ __('messages.Entrer le nom du Fournisseur') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="telephone">@lang('messages.Telephone du Fournisseur')</label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" placeholder="{{ __('messages.Entrer le telephone') }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_address">@lang('messages.Adresse du Fournisseur')</label>
                                    <input type="text" class="form-control" id="supplier_address" name="supplier_address" placeholder="{{ __('messages.Entrer l\'adresse du Fournisseur') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mail">@lang('messages.E-mail du Fournisseur')</label>
                                    <input type="text" class="form-control" id="mail" name="mail" placeholder="{{ __('messages.Entrer le mail') }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="vat_supplier_payer">@lang('messages.Fournisseur est assujetti a la TVA?')</label>
                                <div class="form-group">
                                    <label class="text">@lang('messages.Non assujetti')
                                        <input type="checkbox" name="vat_supplier_payer" value="0" checked="checked" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">@lang('messages.Assujetti')
                                        <input type="checkbox" name="vat_supplier_payer" value="1" class="form-control">
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group account-autocomplete-wrap">
                                    <label for="account_search">@lang('messages.account')<strong style="color: red;">*</strong></label>
                                    <input type="text" class="form-control" id="account_search" placeholder="{{ __('messages.account_search_placeholder') }}" autocomplete="off">
                                    <input type="hidden" name="account_id" id="account_id" required>
                                    <div id="account_results" class="account-results-dropdown"></div>
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
<script type="text/javascript">
    var group_ = function(el, callback) {
        el.forEach(function(checkbox) { callback(checkbox); });
    };
    group_(document.getElementsByName('vat_supplier_payer'), function(item) {
        item.onclick = function(e) {
            group_(document.getElementsByName('vat_supplier_payer'), function(it) { it.checked = false; });
            e.target.checked = true;
        };
    });

    function syncSupplierFieldsByTpType() {
        var tp = $('#tp_type').val();
        var nif = $('#supplier_TIN')[0];
        var nifGroup = $('#supplier_tin_group');
        var supplierName = $('#supplier_name')[0];
        var supplierNameRequired = $('#supplier_name_required');

        supplierName.setAttribute('required', 'required');
        supplierName.removeAttribute('readonly');
        supplierNameRequired.show();

        if (tp === '2') {
            nif.setAttribute('required', 'required');
            nifGroup.show();
        } else {
            nif.removeAttribute('required');
            nifGroup.hide();
        }
    }
    $('#tp_type').on('change', syncSupplierFieldsByTpType);
    syncSupplierFieldsByTpType();

    var i18n = {
        noAccountFound: @json(__('messages.Aucun compte trouvé'))
    };
    var accountSearchUrl = '{{ route("admin.accounting-accounts.search") }}';
    var noAccountLabel = i18n.noAccountFound;
    var accountSearchTimer;
    $('#account_search').on('input', function() {
        var q = $(this).val().trim();
        $('#account_id').val('');
        clearTimeout(accountSearchTimer);
        if (q.length < 1) { $('#account_results').hide().empty(); return; }
        accountSearchTimer = setTimeout(function() {
            $.get(accountSearchUrl, { q: q }, function(data) {
                var html = '', esc = function(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); };
                (data.results || []).forEach(function(r) {
                    var raw = (r.text || '').replace(/"/g, '&quot;');
                    html += '<div class="account-item" data-id="' + r.id + '" data-text="' + raw + '">' + esc(r.text) + '</div>';
                });
                $('#account_results').html(html || '<div class="account-item text-muted">' + i18n.noAccountFound + '</div>').show();
            });
        }, 200);
    }).on('blur', function() { setTimeout(function() { $('#account_results').hide(); }, 200); });
    $(document).on('click', '#account_results .account-item[data-id]', function() {
        var id = $(this).data('id'), text = $(this).data('text');
        $('#account_id').val(id);
        $('#account_search').val(text);
        $('#account_results').hide().empty();
    });

    function preventBack() { window.history.forward(); }
    setTimeout("preventBack()", 0);
    window.onunload = function () { null; };
</script>
@endsection
