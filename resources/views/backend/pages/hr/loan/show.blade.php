@extends('backend.layouts.master')

@section('title')
@lang('messages.hr_loan_view_title') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .info-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }
    .info-value {
        color: #212529;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
        margin-bottom: 15px;
        border-left: 3px solid #007bff;
    }
    .nav-tabs .nav-link.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }
    .tab-content {
        border: 1px solid #dee2e6;
        border-top: none;
        padding: 20px;
        background-color: white;
    }
    .status-badge {
        display: inline-block;
        padding: 8px 15px;
        border-radius: 4px;
        font-weight: 500;
    }
    .status-pending {
        background-color: #ffc107;
        color: #212529;
    }
    .status-validated {
        background-color: #17a2b8;
        color: white;
    }
    .status-confirmed {
        background-color: #28a745;
        color: white;
    }
    .status-approved {
        background-color: #6c757d;
        color: white;
    }
    .payment-status-pending {
        background-color: #ffc107;
        color: #212529;
    }
    .payment-status-first {
        background-color: #17a2b8;
        color: white;
    }
    .payment-status-second {
        background-color: #007bff;
        color: white;
    }
    .payment-status-completed {
        background-color: #28a745;
        color: white;
    }
</style>
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.hr_loan_view_title')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-loans.index') }}">@lang('messages.hr_loan_loans_list')</a></li>
                    <li><span>@lang('messages.hr_loan_breadcrumb_view')</span></li>
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
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title">@lang('messages.hr_loan_show_request_title')</h4>
                        <div>
                            @if ($loan->status == 3 && Auth::guard('admin')->user()->can('account_cash_ouflow_request.create'))
                                <a href="{{ route('admin.cash-out-flow-request.loan-create', $loan->id) }}" class="btn btn-primary">
                                    <i class="fa fa-money-bill"></i> @lang('messages.hr_loan_cash_out_request')
                                </a>
                            @endif
                            @if ($loan->status != 4 && Auth::guard('admin')->user()->can('hr_loan.edit'))
                                <a href="{{ route('admin.hr-loans.edit', $loan->id) }}" class="btn btn-primary">
                                    <i class="fa fa-edit"></i> @lang('messages.edit')
                                </a>
                            @endif
                            <a href="{{ route('admin.hr-loans.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> @lang('messages.back_to_list')
                            </a>
                        </div>
                    </div>
                    <p class="text-muted small">@lang('messages.hr_loan_rule_show_reminder')</p>

                    <!-- Status and Actions -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="info-label">@lang('messages.hr_loan_label_status_colon')</label>
                                <div class="info-value">
                                    @if($loan->status == 0)
                                        <span class="status-badge status-pending">@lang('messages.hr_loan_status_pending')</span>
                                    @elseif($loan->status == 1)
                                        <span class="status-badge status-validated">@lang('messages.hr_loan_status_validated')</span>
                                    @elseif($loan->status == 2)
                                        <span class="status-badge status-confirmed">@lang('messages.hr_loan_status_confirmed')</span>
                                    @elseif($loan->status == 3)
                                        <span class="status-badge status-approved">@lang('messages.hr_loan_status_approved')</span>
                                    @elseif($loan->status == 4)
                                        <span class="status-badge status-approved">@lang('messages.hr_loan_status_approved_released')</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="info-label">@lang('messages.hr_loan_label_payment_colon')</label>
                                <div class="info-value">
                                    @php
                                        $flag1 = $loan->flag1 ?? '0';
                                        $flag2 = $loan->flag2 ?? '0';
                                        $flag1 = (string)$flag1;
                                        $flag2 = (string)$flag2;
                                    @endphp
                                    @if($flag1 == '0' && $flag2 == '0')
                                        <span class="status-badge payment-status-pending">@lang('messages.hr_loan_payment_pending')</span>
                                    @elseif($flag1 == '1' && $flag2 == '0')
                                        <span class="status-badge payment-status-first">@lang('messages.hr_loan_payment_first_paid')</span>
                                    @elseif($flag1 == '1' && $flag2 == '1')
                                        <span class="status-badge payment-status-second">@lang('messages.hr_loan_payment_second_paid')</span>
                                    @elseif($flag1 == '1' && $flag2 == '2')
                                        <span class="status-badge payment-status-completed">@lang('messages.hr_loan_payment_fully_paid')</span>
                                    @else
                                        <span class="status-badge payment-status-pending">@lang('messages.hr_loan_payment_pending')</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="info-label">@lang('messages.hr_loan_label_actions_colon')</label>
                                <div class="info-value">
                                    @if($loan->status == 0 && Auth::guard('admin')->user()->can('hr_loan.edit'))
                                        <form action="{{ route('admin.hr-loans.validate', $loan->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-info btn-sm">@lang('messages.hr_loan_btn_validate')</button>
                                        </form>
                                    @endif
                                    @if($loan->status == 1 && Auth::guard('admin')->user()->can('hr_loan.edit'))
                                        <form action="{{ route('admin.hr-loans.confirm', $loan->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">@lang('messages.hr_loan_btn_confirm')</button>
                                        </form>
                                    @endif
                                    @if($loan->status == 2 && Auth::guard('admin')->user()->can('hr_loan.edit'))
                                        <form action="{{ route('admin.hr-loans.approve', $loan->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">@lang('messages.hr_loan_btn_approve')</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                            <div>
                                <a href="{{ route('admin.hr-loans.printDocument',$loan->id) }}" class="btnprn"><img src="{{ asset('img/ISSh.gif') }}" width="60" title="{{ __('messages.hr_loan_print_hint') }}"></a>
                            </div>
                            <br>
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="loanTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="employee-tab" data-toggle="tab" href="#employee" role="tab">
                                <i class="fa fa-user"></i> @lang('messages.hr_loan_tab_employee')
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="loan-tab" data-toggle="tab" href="#loan" role="tab">
                                <i class="fa fa-money-bill"></i> @lang('messages.hr_loan_tab_loan')
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content">
                        <!-- Employee Information Tab -->
                        <div class="tab-pane fade show active" id="employee" role="tabpanel">
                            @if($loan->employe)
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_full_name'):</label>
                                        <div class="info-value">{{ $loan->employe->firstname }} {{ $loan->employe->lastname }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_matricule'):</label>
                                        <div class="info-value">{{ $loan->employe->matricule_no }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_phone'):</label>
                                        <div class="info-value">{{ $loan->employe->phone_no }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_email'):</label>
                                        <div class="info-value">{{ $loan->employe->mail }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_cni'):</label>
                                        <div class="info-value">{{ $loan->employe->cni }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_birthdate'):</label>
                                        <div class="info-value">
                                            @if($loan->employe->birthdate)
                                                {{ \Carbon\Carbon::parse($loan->employe->birthdate)->format('d/m/Y') }}
                                            @else
                                                @lang('messages.not_available_short')
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_department'):</label>
                                        <div class="info-value">
                                            @if($loan->employe->departement)
                                                {{ $loan->employe->departement->name }}
                                            @else
                                                @lang('messages.not_available_short')
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_service'):</label>
                                        <div class="info-value">
                                            @if($loan->employe->service)
                                                {{ $loan->employe->service->name }}
                                            @else
                                                @lang('messages.not_available_short')
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_function'):</label>
                                        <div class="info-value">
                                            @if($loan->employe->fonction)
                                                {{ $loan->employe->fonction->name }}
                                            @else
                                                @lang('messages.not_available_short')
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_grade'):</label>
                                        <div class="info-value">
                                            @if($loan->employe->grade)
                                                {{ $loan->employe->grade->name }}
                                            @else
                                                @lang('messages.not_available_short')
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_base_salary'):</label>
                                        <div class="info-value">{{ number_format($loan->employe->somme_salaire_base, 0, ',', ' ') }} FBU</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_current_residence'):</label>
                                        <div class="info-value">
                                            {{ $loan->employe->quartier_residence_actuel }},
                                            {{ $loan->employe->zone_residence_actuel }},
                                            {{ $loan->employe->commune_residence_actuel }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @else
                            <div class="alert alert-warning">@lang('messages.hr_loan_no_employee_info')</div>
                            @endif
                        </div>

                        <!-- Loan Details Tab -->
                        <div class="tab-pane fade" id="loan" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_request_date'):</label>
                                        <div class="info-value">
                                            @if($loan->date)
                                                {{ \Carbon\Carbon::parse($loan->date)->format('d/m/Y') }}
                                            @else
                                                @lang('messages.not_available_short')
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_amount_label'):</label>
                                        <div class="info-value">{{ number_format($loan->amount_loan, 2, ',', ' ') }} FBU</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_installments_count'):</label>
                                        <div class="info-value">{{ $loan->nbre_tranche }} @lang('messages.hr_loan_months_unit')</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_status_colon')</label>
                                        <div class="info-value">
                                            @if($loan->status == 0)
                                                <span class="status-badge status-pending">@lang('messages.hr_loan_status_pending')</span>
                                            @elseif($loan->status == 1)
                                                <span class="status-badge status-validated">@lang('messages.hr_loan_status_validated')</span>
                                            @elseif($loan->status == 2)
                                                <span class="status-badge status-confirmed">@lang('messages.hr_loan_status_confirmed')</span>
                                            @elseif($loan->status == 3)
                                                <span class="status-badge status-approved">@lang('messages.hr_loan_status_approved')</span>
                                            @elseif($loan->status == 4)
                                                <span class="status-badge status-approved">@lang('messages.hr_loan_status_approved_released')</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_payment_colon')</label>
                                        <div class="info-value">
                                            @php
                                                $flag1 = $loan->flag1 ?? '0';
                                                $flag2 = $loan->flag2 ?? '0';
                                                $flag1 = (string)$flag1;
                                                $flag2 = (string)$flag2;
                                            @endphp
                                            @if($flag1 == '0' && $flag2 == '0')
                                                <span class="status-badge payment-status-pending">@lang('messages.hr_loan_payment_pending')</span>
                                            @elseif($flag1 == '1' && $flag2 == '0')
                                                <span class="status-badge payment-status-first">@lang('messages.hr_loan_payment_first_paid')</span>
                                            @elseif($flag1 == '1' && $flag2 == '1')
                                                <span class="status-badge payment-status-second">@lang('messages.hr_loan_payment_second_paid')</span>
                                            @elseif($flag1 == '1' && $flag2 == '2')
                                                <span class="status-badge payment-status-completed">@lang('messages.hr_loan_payment_fully_paid')</span>
                                            @else
                                                <span class="status-badge payment-status-pending">@lang('messages.hr_loan_payment_pending')</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_amount_month1'):</label>
                                        <div class="info-value">{{ number_format($loan->amount_to_pay_1m, 2, ',', ' ') }} FBU</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_amount_month2'):</label>
                                        <div class="info-value">{{ number_format($loan->amount_to_pay_2m, 2, ',', ' ') }} FBU</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_amount_month3'):</label>
                                        <div class="info-value">{{ number_format($loan->amount_to_pay_3m, 2, ',', ' ') }} FBU</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_description_short'):</label>
                                        <div class="info-value">{{ $loan->description ?: __('messages.not_available_short') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_created_by'):</label>
                                        <div class="info-value">{{ $loan->created_by ?: __('messages.not_available_short') }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_validated_by'):</label>
                                        <div class="info-value">{{ $loan->validated_by ?: __('messages.not_available_short') }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_confirmed_by'):</label>
                                        <div class="info-value">{{ $loan->confirmed_by ?: __('messages.not_available_short') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_approved_by'):</label>
                                        <div class="info-value">{{ $loan->approuved_by ?: __('messages.not_available_short') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_created_at'):</label>
                                        <div class="info-value">{{ $loan->created_at->format('d/m/Y H:i') }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">@lang('messages.hr_loan_label_updated_at'):</label>
                                        <div class="info-value">{{ $loan->updated_at->format('d/m/Y H:i') }}</div>
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
