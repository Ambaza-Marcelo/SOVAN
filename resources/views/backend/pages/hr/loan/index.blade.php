
@extends('backend.layouts.master')

@section('title')
@lang('messages.hr_loan_loans_list') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <!-- Start datatable css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.hr_loan_loans_list')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('messages.list')</span></li>
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
                    <h4 class="header-title float-left">@lang('messages.hr_loan_loans_list')</h4>
                    <p class="text-muted small mb-0">@lang('messages.hr_loan_rule_list_help')</p>
                    <p class="float-right mb-2">
                            <a class="btn btn-success text-white" href="{{ route('admin.hr-loans.create') }}"><i class="fa fa-plus-square" title="{{ __('messages.hr_loan_icon_add') }}" aria-hidden="false"></i></a>
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">@lang('messages.hr_loan_table_num')</th>
                                    <th width="10%">@lang('messages.hr_loan_table_date')</th>
                                    <th width="18%">@lang('messages.hr_loan_table_employee')</th>
                                    <th width="12%">@lang('messages.hr_loan_table_amount')</th>
                                    <th width="8%">@lang('messages.hr_loan_table_installments')</th>
                                    <th width="10%">@lang('messages.hr_loan_table_status')</th>
                                    <th width="15%">@lang('messages.hr_loan_table_payment_state')</th>
                                    <th width="20%">@lang('messages.hr_loan_table_action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($loans as $loan)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>
                                        @if($loan->date)
                                            {{ \Carbon\Carbon::parse($loan->date)->format('d/m/Y') }}
                                        @else
                                            @lang('messages.not_available_short')
                                        @endif
                                    </td>
                                    <td>
                                        @if($loan->employe)
                                            {{ $loan->employe->matricule_no }} - {{ $loan->employe->firstname }} {{ $loan->employe->lastname }}
                                        @else
                                            @lang('messages.not_available_short')
                                        @endif
                                    </td>
                                    <td>{{ number_format($loan->amount_loan, 2, ',', ' ') }} FBU</td>
                                    <td>{{ $loan->nbre_tranche }} @lang('messages.hr_loan_months_unit')</td>
                                    <td>
                                        @if($loan->status == 0)
                                            <span class="badge badge-warning">@lang('messages.hr_loan_status_pending')</span>
                                        @elseif($loan->status == 1)
                                            <span class="badge badge-info">@lang('messages.hr_loan_status_validated')</span>
                                        @elseif($loan->status == 2)
                                            <span class="badge badge-success">@lang('messages.hr_loan_status_confirmed')</span>
                                        @elseif($loan->status == 3)
                                            <span class="badge badge-success">@lang('messages.hr_loan_status_approved')</span>
                                        @elseif($loan->status == 4)
                                            <span class="badge badge-success">@lang('messages.hr_loan_status_approved_released')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $flag1 = $loan->flag1 ?? '0';
                                            $flag2 = $loan->flag2 ?? '0';
                                            $flag1 = (string)$flag1;
                                            $flag2 = (string)$flag2;
                                        @endphp
                                        @if($flag1 == '0' && $flag2 == '0')
                                            <span class="badge badge-warning">@lang('messages.hr_loan_payment_pending')</span>
                                        @elseif($flag1 == '1' && $flag2 == '0')
                                            <span class="badge badge-info">@lang('messages.hr_loan_payment_first_paid')</span>
                                        @elseif($flag1 == '1' && $flag2 == '1')
                                            <span class="badge badge-primary">@lang('messages.hr_loan_payment_second_paid')</span>
                                        @elseif($flag1 == '1' && $flag2 == '2')
                                            <span class="badge badge-success">@lang('messages.hr_loan_payment_fully_paid')</span>
                                        @else
                                            <span class="badge badge-secondary">@lang('messages.hr_loan_payment_pending')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('hr_loan.view'))
                                            <a class="btn btn-info text-white btn-sm" href="{{ route('admin.hr-loans.show', $loan->id) }}">
                                                <i class="fa fa-eye" title="{{ __('messages.hr_loan_icon_view') }}" aria-hidden="false"></i>
                                            </a>
                                        @endif

                                        @if ($loan->status != 3 && Auth::guard('admin')->user()->can('hr_loan.edit'))
                                            <a class="btn btn-success text-white btn-sm" href="{{ route('admin.hr-loans.edit', $loan->id) }}">
                                                <i class="fa fa-edit" title="{{ __('messages.hr_loan_icon_edit') }}" aria-hidden="false"></i>
                                            </a>
                                        @endif

                                        @if ($loan->status == 3 && Auth::guard('admin')->user()->can('account_cash_ouflow_request.create'))
                                            <a class="btn btn-primary text-white btn-sm" href="{{ route('admin.cash-out-flow-request.loan-create', $loan->id) }}" title="{{ __('messages.hr_loan_cash_out_request') }}">
                                                <i class="fa fa-money-bill"></i> @lang('messages.hr_loan_cash_out_request')
                                            </a>
                                        @endif

                                        @if ($loan->status != 3 && Auth::guard('admin')->user()->can('hr_loan.delete'))
                                            <a class="btn btn-danger text-white btn-sm" href="{{ route('admin.hr-loans.destroy', $loan->id) }}"
                                            onclick="event.preventDefault(); if(confirm(@json(__('messages.hr_loan_delete_confirm')))) { document.getElementById('delete-form-{{ $loan->id }}').submit(); }">
                                                <i class="fa fa-trash" title="{{ __('messages.hr_loan_icon_delete') }}" aria-hidden="false"></i>
                                            </a>

                                            <form id="delete-form-{{ $loan->id }}" action="{{ route('admin.hr-loans.destroy', $loan->id) }}" method="POST" style="display: none;">
                                                @method('DELETE')
                                                @csrf
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                               @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- data table end -->

    </div>
</div>
@endsection


@section('scripts')
     <!-- Start datatable js -->
     <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>

     <script>
         if ($('#dataTable').length) {
            $('#dataTable').DataTable({
                responsive: true
            });
        }

     </script>
@endsection
