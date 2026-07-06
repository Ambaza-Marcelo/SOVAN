@extends('backend.layouts.master')

@section('title')
@lang('messages.View Account Setting') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .info-label { font-weight: 600; color: #495057; margin-bottom: 5px; }
    .info-value { color: #212529; padding: 10px; background-color: #f8f9fa; border-radius: 4px; margin-bottom: 15px; border-left: 3px solid #007bff; }
    .nav-tabs .nav-link.active { background-color: #007bff; color: white; border-color: #007bff; }
    .tab-content { border: 1px solid #dee2e6; border-top: none; padding: 20px; background-color: white; }
    .status-badge { display: inline-block; padding: 8px 15px; border-radius: 4px; font-weight: 500; }
    .status-active { background-color: #28a745; color: white; }
    .status-inactive { background-color: #dc3545; color: white; }
</style>
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.View Account Setting')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.accounting-settings.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.View Account Setting')</span></li>
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
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title">Account Setting Details - {{ $setting->code }}</h4>
                        <div>
                            @if (Auth::guard('admin')->user()->can('account_setting.edit'))
                                <a href="{{ route('admin.accounting-settings.edit', $setting->id) }}" class="btn btn-primary"><i class="fa fa-edit"></i> @lang('messages.edit')</a>
                            @endif
                            <a href="{{ route('admin.accounting-settings.index') }}" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> @lang('messages.Back to List')</a>
                        </div>
                    </div>

                    <ul class="nav nav-tabs" id="accountSettingTabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab"><i class="fa fa-info-circle"></i> @lang('messages.General')</a></li>
                        <li class="nav-item"><a class="nav-link" id="vat-tab" data-toggle="tab" href="#vat" role="tab"><i class="fa fa-percent"></i> @lang('messages.Taxes Accounts')</a></li>
                        <li class="nav-item"><a class="nav-link" id="finished-tab" data-toggle="tab" href="#finished" role="tab"><i class="fa fa-box-open"></i> @lang('messages.Finished Goods Accounts')</a></li>
                        <li class="nav-item"><a class="nav-link" id="goodsservice-tab" data-toggle="tab" href="#goodsservice" role="tab"><i class="fa fa-tags"></i> @lang('messages.Goods & Service')</a></li>
                        <li class="nav-item"><a class="nav-link" id="rawmat-tab" data-toggle="tab" href="#rawmat" role="tab"><i class="fa fa-cubes"></i> @lang('messages.Raw Material Accounts')</a></li>
                        <li class="nav-item"><a class="nav-link" id="material-tab" data-toggle="tab" href="#material" role="tab"><i class="fa fa-box"></i> @lang('messages.Material Accounts')</a></li>
                        <li class="nav-item"><a class="nav-link" id="payslip-tab" data-toggle="tab" href="#payslip" role="tab"><i class="fa fa-money-check-alt"></i> @lang('messages.Pay Slip')</a></li>
                        <li class="nav-item"><a class="nav-link" id="temp-tab" data-toggle="tab" href="#temp" role="tab"><i class="fa fa-wallet"></i> @lang('messages.Complementary')</a></li>
                        <li class="nav-item"><a class="nav-link" id="production-tab" data-toggle="tab" href="#production" role="tab"><i class="fa fa-industry"></i> @lang('messages.Production account')</a></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Code:')</label>
                                        <div class="info-value">{{ $setting->code }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Date:')</label>
                                        <div class="info-value">{{ $setting->date ? \Carbon\Carbon::parse($setting->date)->format('d/m/Y') : 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Fiscal Year:')</label>
                                        <div class="info-value">{{ $setting->fiscal_year }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Status:')</label>
                                        <div class="info-value">
                                            @if($setting->active)
                                                <span class="status-badge status-active">@lang('messages.Active')</span>
                                            @else
                                                <span class="status-badge status-inactive">@lang('messages.Inactive')</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Currency:')</label>
                                        <div class="info-value">
                                            @if($setting->currency_id)
                                                @php $currency = $currencies->firstWhere('id', $setting->currency_id); @endphp
                                                {{ $currency ? $currency->currency_name : 'N/A' }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="vat" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.VAT Collected Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->vat_collec_account_id ? App\Models\Accounting\AccountAccount::find($setting->vat_collec_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.VAT Deductible Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->vat_deduc_account_id ? App\Models\Accounting\AccountAccount::find($setting->vat_deduc_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="finished" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Finished Goods Selling Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->f_selling_account_id ? App\Models\Accounting\AccountAccount::find($setting->f_selling_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Finished Goods SM Selling Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->f_sm_selling_account_id ? App\Models\Accounting\AccountAccount::find($setting->f_sm_selling_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Finished Goods SAJ Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->f_saj_account_id ? App\Models\Accounting\AccountAccount::find($setting->f_saj_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Finished Goods SP Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->f_sp_account_id ? App\Models\Accounting\AccountAccount::find($setting->f_sp_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Finished Goods SV Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->f_sv_account_id ? App\Models\Accounting\AccountAccount::find($setting->f_sv_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Finished Goods SC Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->f_sc_account_id ? App\Models\Accounting\AccountAccount::find($setting->f_sc_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Finished Goods EAJ Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->f_eaj_account_id ? App\Models\Accounting\AccountAccount::find($setting->f_eaj_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="goodsservice" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Goods Selling Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->goods_selling_account_id ? App\Models\Accounting\AccountAccount::find($setting->goods_selling_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Service Selling Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->service_selling_account_id ? App\Models\Accounting\AccountAccount::find($setting->service_selling_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="rawmat" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Raw Material Purchase Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->raw_mat_purchase_account_id ? App\Models\Accounting\AccountAccount::find($setting->raw_mat_purchase_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Raw Material SAJ Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->raw_mat_saj_account_id ? App\Models\Accounting\AccountAccount::find($setting->raw_mat_saj_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Raw Material SP Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->raw_mat_sp_account_id ? App\Models\Accounting\AccountAccount::find($setting->raw_mat_sp_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Raw Material SV Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->raw_mat_sv_account_id ? App\Models\Accounting\AccountAccount::find($setting->raw_mat_sv_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Raw Material SC Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->raw_mat_sc_account_id ? App\Models\Accounting\AccountAccount::find($setting->raw_mat_sc_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Raw Material EAJ Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->raw_mat_eaj_account_id ? App\Models\Accounting\AccountAccount::find($setting->raw_mat_eaj_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="material" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Material Purchase Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->m_purchase_account_id ? App\Models\Accounting\AccountAccount::find($setting->m_purchase_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Material SAJ Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->m_saj_account_id ? App\Models\Accounting\AccountAccount::find($setting->m_saj_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Material SP Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->m_sp_account_id ? App\Models\Accounting\AccountAccount::find($setting->m_sp_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Material SV Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->m_sv_account_id ? App\Models\Accounting\AccountAccount::find($setting->m_sv_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Material SC Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->m_sc_account_id ? App\Models\Accounting\AccountAccount::find($setting->m_sc_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Material EAJ Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->m_eaj_account_id ? App\Models\Accounting\AccountAccount::find($setting->m_eaj_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="payslip" role="tabpanel">
                            @php
                                $payslipFields = [
                                    'basic_salary_account_id' => 'Basic Salary Account',
                                    'f_allowance_account_id' => 'F Allowance Account',
                                    'h_allowance_account_id' => 'H Allowance Account',
                                    't_allowance_account_id' => 'T Allowance Account',
                                    'o_allowance_account_id' => 'O Allowance Account',
                                    'gross_salary_account_id' => 'Gross Salary Account',
                                    'ded1_salary_account_id' => 'Deduction 1 Salary Account',
                                    'ded2_salary_account_id' => 'Deduction 2 Salary Account',
                                    'cot_employee_account_id' => 'COT Employee Account',
                                    'cot_employer_account_id' => 'COT Employer Account',
                                    'tax_salary_account_id' => 'Tax Salary Account',
                                    'med_care_salary_account_id' => 'Medical Care Salary Account',
                                    'insurance_employee_account_id' => 'Insurance Employee Account',
                                    'insurance_employer_account_id' => 'Insurance Employer Account',
                                    'loan_salary_account_id' => 'Loan Salary Account',
                                    'net_salary_account_id' => 'Net Salary Account',
                                ];
                            @endphp
                            <div class="row">
                                @foreach($payslipFields as $field => $label)
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="info-label">{{ __($label) }}:</label>
                                            <div class="info-value">
                                                @php $account = $setting->$field ? App\Models\Accounting\AccountAccount::find($setting->$field) : null; @endphp
                                                {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="tab-pane fade" id="temp" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Temporary Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->temp_account_id ? App\Models\Accounting\AccountAccount::find($setting->temp_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.Advance payment Account:')</label>
                                        <div class="info-value">
                                            @php $account = $setting->adv_payment_account_id ? App\Models\Accounting\AccountAccount::find($setting->adv_payment_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="production" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">{{ __('messages.Main stock') }}:</label>
                                        <div class="info-value">
                                            @php $account = $setting->prod_bg_account_id ? App\Models\Accounting\AccountAccount::find($setting->prod_bg_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">{{ __('messages.Small production stock') }}:</label>
                                        <div class="info-value">
                                            @php $account = $setting->prod_sm_account_id ? App\Models\Accounting\AccountAccount::find($setting->prod_sm_account_id) : null; @endphp
                                            {{ $account ? $account->account_number . ' - ' . $account->account_name : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
