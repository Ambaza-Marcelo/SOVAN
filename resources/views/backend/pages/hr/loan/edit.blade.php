@extends('backend.layouts.master')

@section('title')
@lang('messages.hr_loan_edit_title') - @lang('messages.admin_panel')
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.hr_loan_edit_title')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-loans.index') }}">@lang('messages.hr_loan_loans_list')</a></li>
                    <li><span>@lang('messages.edit')</span></li>
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
                    <h4 class="header-title">@lang('messages.hr_loan_edit_request_title')</h4>
                    @include('backend.layouts.partials.messages')
                    <form action="{{ route('admin.hr-loans.update', $loan->id) }}" method="POST" id="loanForm">
                        @csrf
                        @method('PUT')
                        <div id="alert-employe-dette" class="alert alert-danger d-none" role="alert">
                            <strong>@lang('messages.hr_loan_debt_alert_title')</strong> @lang('messages.hr_loan_debt_alert_edit')
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date" class="col-form-label">@lang('messages.hr_loan_date_label') <strong style="color: red;">*</strong></label>
                                    <input type="date" name="date" class="form-control" id="date" value="{{ $loan->date }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" style="position: relative;">
                                    <label for="employe_search">@lang('messages.hr_loan_employee')</label>
                                    <input type="text" class="form-control" id="employe_search" placeholder="{{ __('messages.hr_loan_search_employee_placeholder') }}" autocomplete="off" value="@if($loan->employe){{ $loan->employe->matricule_no }} - {{ $loan->employe->firstname }} {{ $loan->employe->lastname }}@endif" required>
                                    <input type="hidden" name="employe_id" id="employe_id" value="{{ $loan->employe_id }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount_loan" class="col-form-label">@lang('messages.hr_loan_amount_label') <strong style="color: red;">*</strong></label>
                                    <input type="number" name="amount_loan" class="form-control" id="amount_loan" step="0.01" min="0" value="{{ $loan->amount_loan }}" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nbre_tranche" class="col-form-label">@lang('messages.hr_loan_installments_label') <strong style="color: red;">*</strong></label>
                                    <input type="number" name="nbre_tranche" class="form-control" id="nbre_tranche" min="1" max="3" value="{{ $loan->nbre_tranche }}" required>
                                    <small class="form-text text-muted">@lang('messages.hr_loan_max_months_help')</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="amount_to_pay_1m" class="col-form-label">@lang('messages.hr_loan_amount_month1')</label>
                                    <input type="number" name="amount_to_pay_1m" class="form-control" id="amount_to_pay_1m" step="0.01" min="0" value="{{ $loan->amount_to_pay_1m }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="amount_to_pay_2m" class="col-form-label">@lang('messages.hr_loan_amount_month2')</label>
                                    <input type="number" name="amount_to_pay_2m" class="form-control" id="amount_to_pay_2m" step="0.01" min="0" value="{{ $loan->amount_to_pay_2m }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="amount_to_pay_3m" class="col-form-label">@lang('messages.hr_loan_amount_month3')</label>
                                    <input type="number" name="amount_to_pay_3m" class="form-control" id="amount_to_pay_3m" step="0.01" min="0" value="{{ $loan->amount_to_pay_3m }}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description" class="col-form-label">@lang('messages.hr_loan_description_label')</label>
                                    <textarea name="description" class="form-control" id="description" rows="3" placeholder="{{ __('messages.hr_loan_description_placeholder') }}">{{ old('description', $loan->description) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                            <a href="{{ route('admin.hr-loans.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
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
@include('backend.layouts.partials.ajax_autocomplete')
<script>
window.hrLoanI18n = @json(['outstandingBadge' => __('messages.hr_loan_outstanding_badge')]);
$(document).ready(function() {
    var employeIdsWithOutstandingLoan = @json($employeIdsWithOutstandingLoan ?? []);

    attachAjaxAutocomplete({
        $input: $('#employe_search'),
        $hidden: $('#employe_id'),
        url: '{{ route("admin.autocomplete.employees") }}',
        minChars: 1,
        onSelect: function(item) {
            var id = parseInt(item.id, 10);
            if (employeIdsWithOutstandingLoan.indexOf(id) !== -1) {
                $('#alert-employe-dette').removeClass('d-none');
                $('#employe_id').val('');
                $('#loanForm button[type="submit"]').prop('disabled', true);
            } else {
                $('#alert-employe-dette').addClass('d-none');
                $('#loanForm button[type="submit"]').prop('disabled', false);
            }
        }
    });

    $('#employe_search').on('input', function() {
        if (!$(this).val()) {
            $('#alert-employe-dette').addClass('d-none');
            $('#loanForm button[type="submit"]').prop('disabled', false);
        }
    });

    function calculateAmounts() {
        var amountLoan = parseFloat($('#amount_loan').val()) || 0;
        var nbreTranche = parseInt($('#nbre_tranche').val()) || 3;

        if (amountLoan > 0 && nbreTranche > 0) {
            var amountPerMonth = amountLoan / nbreTranche;

            if (nbreTranche == 3) {
                $('#amount_to_pay_1m').val(amountPerMonth.toFixed(2));
                $('#amount_to_pay_2m').val(amountPerMonth.toFixed(2));
                $('#amount_to_pay_3m').val(amountPerMonth.toFixed(2));
            } else if (nbreTranche == 2) {
                $('#amount_to_pay_1m').val(amountPerMonth.toFixed(2));
                $('#amount_to_pay_2m').val(amountPerMonth.toFixed(2));
                $('#amount_to_pay_3m').val(0);
            } else {
                $('#amount_to_pay_1m').val(amountLoan.toFixed(2));
                $('#amount_to_pay_2m').val(0);
                $('#amount_to_pay_3m').val(0);
            }
        } else {
            $('#amount_to_pay_1m').val(0);
            $('#amount_to_pay_2m').val(0);
            $('#amount_to_pay_3m').val(0);
        }
    }

    $('#amount_loan, #nbre_tranche').on('input change', function() {
        calculateAmounts();
    });
});
</script>
@endsection
