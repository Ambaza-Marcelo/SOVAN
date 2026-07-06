@extends('backend.layouts.master')

@section('title')
@lang('messages.Edit Account Setting') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label { text-transform: capitalize; }
    .nav-tabs .nav-link { border-radius: 0.25rem 0.25rem 0 0; }
    .nav-tabs .nav-link.active { background-color: #007bff; color: white; border-color: #dee2e6 #dee2e6 #fff; }
    .tab-content { border: 1px solid #dee2e6; border-top: none; padding: 20px; background: #fff; }
    .account-search-suggestions {
        position: absolute; top: 100%; left: 0; right: 0; z-index: 1050;
        max-height: 220px; overflow-y: auto; background: #fff; border: 1px solid #ced4da;
        border-top: none; border-radius: 0 0 0.375rem 0.375rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .account-search-suggestion-item {
        padding: 10px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }
    .account-search-suggestion-item:hover, .account-search-suggestion-item.highlighted { background: #f8f9fa; }
</style>
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Edit Account Setting')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.accounting-settings.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Edit Account Setting')</span></li>
                </ul>
            </div>
        </div>
        <div class="col-sm-6 clearfix">@include('backend.layouts.partials.logout')</div>
    </div>
</div>

<div class="main-content-inner">
    <div class="row">
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">@lang('messages.Edit Account Setting')</h4>
                    @include('backend.layouts.partials.messages')

                    <form action="{{ route('admin.accounting-settings.update', $setting->id) }}" method="POST" id="account-setting-form" novalidate>
                        @method('PUT')
                        @csrf

                        <ul class="nav nav-tabs" id="settingTabs" role="tablist">
                            <li class="nav-item"><a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab">@lang('messages.General')</a></li>
                            <li class="nav-item"><a class="nav-link" id="taxes-tab" data-toggle="tab" href="#taxes" role="tab">@lang('messages.Taxes Accounts')</a></li>
                            <li class="nav-item"><a class="nav-link" id="finished-tab" data-toggle="tab" href="#finished" role="tab">@lang('messages.Finished Goods Accounts')</a></li>
                            <li class="nav-item"><a class="nav-link" id="goodsservice-tab" data-toggle="tab" href="#goodsservice" role="tab">@lang('messages.Goods & Service')</a></li>
                            <li class="nav-item"><a class="nav-link" id="rawmat-tab" data-toggle="tab" href="#rawmat" role="tab">@lang('messages.Raw Material Accounts')</a></li>
                            <li class="nav-item"><a class="nav-link" id="material-tab" data-toggle="tab" href="#material" role="tab">@lang('messages.Material Accounts')</a></li>
                            <li class="nav-item"><a class="nav-link" id="payslip-tab" data-toggle="tab" href="#payslip" role="tab">@lang('messages.Pay Slip Accounts')</a></li>
                            <li class="nav-item"><a class="nav-link" id="complementary-tab" data-toggle="tab" href="#complementary" role="tab">@lang('messages.Complementary Accounts')</a></li>
                            <li class="nav-item"><a class="nav-link" id="production-tab" data-toggle="tab" href="#production" role="tab">@lang('messages.Production account')</a></li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="date">@lang('messages.Date') <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="date" name="date" value="{{ old('date', $setting->date ? (\Carbon\Carbon::parse($setting->date)->format('Y-m-d')) : '') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="code">@lang('messages.Code') <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="code" name="code" value="{{ old('code', $setting->code) }}" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fiscal_year">@lang('messages.Fiscal Year') <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="fiscal_year" name="fiscal_year" value="{{ old('fiscal_year', $setting->fiscal_year) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="active">@lang('messages.Status') <span class="text-danger">*</span></label>
                                            <select class="form-control" id="active" name="active" required>
                                                <option value="1" {{ old('active', $setting->active) == 1 ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ old('active', $setting->active) == 0 ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="currency_id">@lang('messages.Currency') <span class="text-danger">*</span></label>
                                            <select class="form-control" id="currency_id" name="currency_id" required>
                                                @foreach($currencies as $currency)
                                                    <option value="{{ $currency->id }}" {{ old('currency_id', $setting->currency_id) == $currency->id ? 'selected' : '' }}>{{ $currency->currency_name ?? $currency->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="taxes" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'vat_collec_account_id', 'label' => __('messages.VAT Collected Account'), 'required' => true, 'value_id' => old('vat_collec_account_id', $setting->vat_collec_account_id)])
                                    </div>
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'vat_deduc_account_id', 'label' => __('messages.VAT Deductible Account'), 'required' => true, 'value_id' => old('vat_deduc_account_id', $setting->vat_deduc_account_id)])
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="finished" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'f_selling_account_id', 'label' => __('messages.Finished Goods Selling Account'), 'required' => true, 'value_id' => old('f_selling_account_id', $setting->f_selling_account_id)])
                                    </div>
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'f_sm_selling_account_id', 'label' => __('messages.Finished Goods SM Selling Account'), 'required' => true, 'value_id' => old('f_sm_selling_account_id', $setting->f_sm_selling_account_id)])
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'f_saj_account_id', 'label' => __('messages.Finished Goods SAJ Account'), 'required' => true, 'value_id' => old('f_saj_account_id', $setting->f_saj_account_id)])
                                    </div>
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'f_sp_account_id', 'label' => __('messages.Finished Goods SP Account'), 'required' => true, 'value_id' => old('f_sp_account_id', $setting->f_sp_account_id)])
                                    </div>
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'f_sv_account_id', 'label' => __('messages.Finished Goods SV Account'), 'required' => true, 'value_id' => old('f_sv_account_id', $setting->f_sv_account_id)])
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'f_sc_account_id', 'label' => __('messages.Finished Goods SC Account'), 'required' => true, 'value_id' => old('f_sc_account_id', $setting->f_sc_account_id)])
                                    </div>
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'f_eaj_account_id', 'label' => __('messages.Finished Goods EAJ Account'), 'required' => true, 'value_id' => old('f_eaj_account_id', $setting->f_eaj_account_id)])
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="goodsservice" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'goods_selling_account_id', 'label' => __('messages.Goods Selling Account'), 'required' => true, 'value_id' => old('goods_selling_account_id', $setting->goods_selling_account_id)])
                                    </div>
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'service_selling_account_id', 'label' => __('messages.Service Selling Account'), 'required' => true, 'value_id' => old('service_selling_account_id', $setting->service_selling_account_id)])
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="rawmat" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'raw_mat_purchase_account_id', 'label' => __('messages.Raw Material Purchase Account'), 'required' => false, 'value_id' => old('raw_mat_purchase_account_id', $setting->raw_mat_purchase_account_id)])
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'raw_mat_saj_account_id', 'label' => __('messages.Raw Material SAJ Account'), 'required' => false, 'value_id' => old('raw_mat_saj_account_id', $setting->raw_mat_saj_account_id)])
                                    </div>
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'raw_mat_sp_account_id', 'label' => __('messages.Raw Material SP Account'), 'required' => false, 'value_id' => old('raw_mat_sp_account_id', $setting->raw_mat_sp_account_id)])
                                    </div>
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'raw_mat_sv_account_id', 'label' => __('messages.Raw Material SV Account'), 'required' => false, 'value_id' => old('raw_mat_sv_account_id', $setting->raw_mat_sv_account_id)])
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'raw_mat_sc_account_id', 'label' => __('messages.Raw Material SC Account'), 'required' => false, 'value_id' => old('raw_mat_sc_account_id', $setting->raw_mat_sc_account_id)])
                                    </div>
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'raw_mat_eaj_account_id', 'label' => __('messages.Raw Material EAJ Account'), 'required' => false, 'value_id' => old('raw_mat_eaj_account_id', $setting->raw_mat_eaj_account_id)])
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="material" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'm_purchase_account_id', 'label' => __('messages.Material Purchase Account'), 'required' => true, 'value_id' => old('m_purchase_account_id', $setting->m_purchase_account_id)])
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'm_saj_account_id', 'label' => __('messages.Material SAJ Account'), 'required' => true, 'value_id' => old('m_saj_account_id', $setting->m_saj_account_id)])
                                    </div>
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'm_sp_account_id', 'label' => __('messages.Material SP Account'), 'required' => true, 'value_id' => old('m_sp_account_id', $setting->m_sp_account_id)])
                                    </div>
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'm_sv_account_id', 'label' => __('messages.Material SV Account'), 'required' => true, 'value_id' => old('m_sv_account_id', $setting->m_sv_account_id)])
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'm_sc_account_id', 'label' => __('messages.Material SC Account'), 'required' => true, 'value_id' => old('m_sc_account_id', $setting->m_sc_account_id)])
                                    </div>
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'm_eaj_account_id', 'label' => __('messages.Material EAJ Account'), 'required' => true, 'value_id' => old('m_eaj_account_id', $setting->m_eaj_account_id)])
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="payslip" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'basic_salary_account_id', 'label' => __('messages.Basic Salary Account'), 'required' => true, 'value_id' => old('basic_salary_account_id', $setting->basic_salary_account_id)])
                                    </div>
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'f_allowance_account_id', 'label' => __('messages.F Allowance Account'), 'required' => true, 'value_id' => old('f_allowance_account_id', $setting->f_allowance_account_id)])
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'h_allowance_account_id', 'label' => __('messages.H Allowance Account'), 'required' => true, 'value_id' => old('h_allowance_account_id', $setting->h_allowance_account_id)])
                                    </div>
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 't_allowance_account_id', 'label' => __('messages.T Allowance Account'), 'required' => true, 'value_id' => old('t_allowance_account_id', $setting->t_allowance_account_id)])
                                    </div>
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'o_allowance_account_id', 'label' => __('messages.O Allowance Account'), 'required' => false, 'value_id' => old('o_allowance_account_id', $setting->o_allowance_account_id)])
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'gross_salary_account_id', 'label' => __('messages.Gross Salary Account'), 'required' => false, 'value_id' => old('gross_salary_account_id', $setting->gross_salary_account_id)])
                                    </div>
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'ded1_salary_account_id', 'label' => __('messages.Deduction 1 Salary Account'), 'required' => false, 'value_id' => old('ded1_salary_account_id', $setting->ded1_salary_account_id)])
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'ded2_salary_account_id', 'label' => __('messages.Deduction 2 Salary Account'), 'required' => false, 'value_id' => old('ded2_salary_account_id', $setting->ded2_salary_account_id)])
                                    </div>
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'cot_employee_account_id', 'label' => __('messages.COT Employee Account'), 'required' => true, 'value_id' => old('cot_employee_account_id', $setting->cot_employee_account_id)])
                                    </div>
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'cot_employer_account_id', 'label' => __('messages.COT Employer Account'), 'required' => true, 'value_id' => old('cot_employer_account_id', $setting->cot_employer_account_id)])
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'tax_salary_account_id', 'label' => __('messages.Tax Salary Account'), 'required' => true, 'value_id' => old('tax_salary_account_id', $setting->tax_salary_account_id)])
                                    </div>
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'med_care_salary_account_id', 'label' => __('messages.Medical Care Salary Account'), 'required' => false, 'value_id' => old('med_care_salary_account_id', $setting->med_care_salary_account_id)])
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'insurance_employee_account_id', 'label' => __('messages.Insurance Employee Account'), 'required' => false, 'value_id' => old('insurance_employee_account_id', $setting->insurance_employee_account_id)])
                                    </div>
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'insurance_employer_account_id', 'label' => __('messages.Insurance Employer Account'), 'required' => false, 'value_id' => old('insurance_employer_account_id', $setting->insurance_employer_account_id)])
                                    </div>
                                    <div class="col-md-4">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'loan_salary_account_id', 'label' => __('messages.Loan Salary Account'), 'required' => false, 'value_id' => old('loan_salary_account_id', $setting->loan_salary_account_id)])
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'net_salary_account_id', 'label' => __('messages.Net Salary Account'), 'required' => true, 'value_id' => old('net_salary_account_id', $setting->net_salary_account_id)])
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="complementary" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'temp_account_id', 'label' => __('messages.Temporary Account'), 'required' => false, 'value_id' => old('temp_account_id', $setting->temp_account_id)])
                                    </div>
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'adv_payment_account_id', 'label' => __('messages.Advance payment Account'), 'required' => false, 'value_id' => old('adv_payment_account_id', $setting->adv_payment_account_id)])
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="production" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'prod_bg_account_id', 'label' => __('messages.Main stock'), 'required' => false, 'value_id' => old('prod_bg_account_id', $setting->prod_bg_account_id)])
                                    </div>
                                    <div class="col-md-6">
                                        @include('backend.pages.accounting.account_setting._account_search', ['name' => 'prod_sm_account_id', 'label' => __('messages.Small production stock'), 'required' => false, 'value_id' => old('prod_sm_account_id', $setting->prod_sm_account_id)])
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.update')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    var searchUrl = '{{ route("admin.accounting-accounts.search") }}';
    var searchTimeout;
    $(document).ready(function() {
        $(document).on('input', '.account-search-input', function() {
            var $input = $(this), $wrapper = $input.closest('.position-relative'), $hidden = $wrapper.find('.account-search-id'), $suggestions = $wrapper.find('.account-search-suggestions'), term = $input.val().trim();
            if (term.length < 1) { $suggestions.hide().empty(); $hidden.val(''); return; }
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                $.get(searchUrl, { q: term }, function(res) {
                    var results = (res.results || []);
                    $suggestions.empty();
                    if (results.length === 0) {
                        $suggestions.append('<div class="account-search-suggestion-item text-muted">@lang('messages.No account found')</div>');
                    } else {
                        results.slice(0, 15).forEach(function(item) {
                            var $item = $('<div class="account-search-suggestion-item" data-id="' + item.id + '" data-text="' + (item.text || '').replace(/"/g, '&quot;') + '">' + (item.text || '') + '</div>');
                            $item.on('click', function() {
                                $input.val($(this).data('text'));
                                $hidden.val($(this).data('id'));
                                $suggestions.hide().empty();
                            });
                            $suggestions.append($item);
                        });
                    }
                    $suggestions.show();
                });
            }, 300);
        });
        $(document).on('keydown', '.account-search-input', function(e) {
            var $wrapper = $(this).closest('.position-relative'), $suggestions = $wrapper.find('.account-search-suggestions'), $items = $suggestions.find('.account-search-suggestion-item[data-id]'), $hl = $items.filter('.highlighted');
            if (e.which === 40) {
                e.preventDefault();
                if ($hl.length) { var $next = $hl.next('.account-search-suggestion-item[data-id]'); $hl.removeClass('highlighted'); ($next.length ? $next : $items.first()).addClass('highlighted'); } else { $items.first().addClass('highlighted'); }
            } else if (e.which === 38) {
                e.preventDefault();
                if ($hl.length) { var $prev = $hl.prev('.account-search-suggestion-item[data-id]'); $hl.removeClass('highlighted'); ($prev.length ? $prev : $items.last()).addClass('highlighted'); }
            } else if (e.which === 13 && $hl.length) { e.preventDefault(); $hl.trigger('click'); } else if (e.which === 27) { $suggestions.hide(); }
        });
        $(document).on('mouseenter', '.account-search-suggestion-item[data-id]', function() { $(this).siblings().removeClass('highlighted'); $(this).addClass('highlighted'); });
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.account-search-input, .account-search-suggestions').length) { $('.account-search-suggestions').hide(); }
        });

        function accountSettingActivateTab(el) {
            var $pane = $(el).closest('.tab-pane');
            if ($pane.length) {
                var pid = $pane.attr('id');
                if (pid) {
                    $('a.nav-link[href="#' + pid + '"]').tab('show');
                }
            }
        }

        function accountSettingShowInvalid(el, customMsg) {
            accountSettingActivateTab(el);
            var run = function() {
                if (customMsg) {
                    el.setCustomValidity(customMsg);
                    el.reportValidity();
                    el.setCustomValidity('');
                } else {
                    el.reportValidity();
                }
                el.focus();
            };
            if ($(el).closest('.tab-pane').length && !$(el).closest('.tab-pane').hasClass('active')) {
                setTimeout(run, 150);
            } else {
                run();
            }
        }

        $('#account-setting-form').on('submit', function(e) {
            var form = this;
            var msgPick = @json(__('messages.Please select an account from the suggestions list.'));

            var badAccount = null;
            $('.account-search-wrapper').each(function() {
                var inp = $(this).find('.account-search-input')[0];
                var hid = $(this).find('.account-search-id')[0];
                if (!inp || !inp.required) {
                    return true;
                }
                if (!hid || !String(hid.value || '').trim()) {
                    badAccount = inp;
                    return false;
                }
            });
            if (badAccount) {
                e.preventDefault();
                accountSettingShowInvalid(badAccount, msgPick);
                return false;
            }

            var req = form.querySelectorAll('[required]');
            for (var i = 0; i < req.length; i++) {
                var f = req[i];
                if (!f.checkValidity()) {
                    e.preventDefault();
                    accountSettingShowInvalid(f, null);
                    return false;
                }
            }
        });
    });
})();
</script>
@endsection
