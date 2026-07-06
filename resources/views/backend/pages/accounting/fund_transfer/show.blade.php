
@extends('backend.layouts.master')

@section('title')
@lang('messages.fund transfer details') - @lang('messages.admin_panel')
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.fund transfer details')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.accounting-fund-transfer.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.fund transfer details')</span></li>
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
                    <h4 class="header-title float-left">Fund Transfer Details - Code: {{ $fund_transfer->code }}</h4>
                    <div class="float-right mb-2">
                        <a href="{{ route('admin.accounting-fund-transfer.index') }}" class="btn btn-secondary">@lang('messages.back')</a>
                        @if (Auth::guard('admin')->user()->can('fund_transfer.view'))
                            <a href="{{ route('admin.accounting-fund-transfer.print', $fund_transfer->id) }}" class="btn btn-info" target="_blank">
                                <i class="fa fa-print"></i> Print
                            </a>
                        @endif
                        @if($fund_transfer->status == 0 && Auth::guard('admin')->user()->can('fund_transfer.edit'))
                            <a href="{{ route('admin.accounting-fund-transfer.edit', $fund_transfer->id) }}" class="btn btn-primary">@lang('messages.edit')</a>
                        @endif
                    </div>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials.messages')
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">@lang('messages.Basic Information')</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <th width="40%">@lang('messages.Code:')</th>
                                            <td>{{ $fund_transfer->code }}</td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Date:')</th>
                                            <td>{{ \Carbon\Carbon::parse($fund_transfer->date)->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Transfer Type:')</th>
                                            <td>
                                                <span class="badge badge-{{ $fund_transfer->transfer_type == 'INTERNAL' ? 'info' : 'warning' }}">
                                                    {{ $fund_transfer->transfer_type }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Amount:')</th>
                                            <td><strong>{{ number_format($fund_transfer->amount, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Status:')</th>
                                            <td>
                                                @if($fund_transfer->status == 0)
                                                    <span class="badge badge-secondary">@lang('messages.Draft')</span>
                                                @elseif($fund_transfer->status == 1)
                                                    <span class="badge badge-info">@lang('messages.Validated')</span>
                                                @elseif($fund_transfer->status == 2)
                                                    <span class="badge badge-warning">@lang('messages.Confirmed')</span>
                                                @elseif($fund_transfer->status == 3)
                                                    <span class="badge badge-success">@lang('messages.Approved')</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($fund_transfer->description)
                                        <tr>
                                            <th>@lang('messages.Description:')</th>
                                            <td>{{ $fund_transfer->description }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">@lang('messages.Account Information')</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <th width="40%">@lang('messages.Origin Account:')</th>
                                            <td>
                                                @if($fund_transfer->originAccount)
                                                    {{ $fund_transfer->originAccount->account_number }} - {{ $fund_transfer->originAccount->account_name }}
                                                @else
                                                    <span class="text-danger">@lang('messages.N/A')</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Destination Account:')</th>
                                            <td>
                                                @if($fund_transfer->DestinationAccount)
                                                    {{ $fund_transfer->DestinationAccount->account_number }} - {{ $fund_transfer->DestinationAccount->account_name }}
                                                @else
                                                    <span class="text-danger">@lang('messages.N/A')</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">@lang('messages.Approval Information')</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        @if($fund_transfer->created_by)
                                        <tr>
                                            <th width="40%">@lang('messages.Created By:')</th>
                                            <td>{{ $fund_transfer->created_by }}</td>
                                        </tr>
                                        @endif
                                        @if($fund_transfer->confirmed_by)
                                        <tr>
                                            <th>@lang('messages.Confirmed By:')</th>
                                            <td>{{ $fund_transfer->confirmed_by }}</td>
                                        </tr>
                                        @endif
                                        @if($fund_transfer->approuved_by)
                                        <tr>
                                            <th>@lang('messages.Approved By:')</th>
                                            <td>{{ $fund_transfer->approuved_by }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">@lang('messages.Actions')</h5>
                                </div>
                                <div class="card-body">
                                    @if($fund_transfer->status == 0 && Auth::guard('admin')->user()->can('fund_transfer.validate'))
                                        <form action="{{ route('admin.accounting-fund-transfer.validate', $fund_transfer->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="submit" class="btn btn-info" onclick="return confirm(@json(__('messages.accounting_validate_fund_transfer_confirm')))">
                                                Validate
                                            </button>
                                        </form>
                                    @endif
                                    
                                    @if($fund_transfer->status == 1 && Auth::guard('admin')->user()->can('fund_transfer.confirm'))
                                        <form action="{{ route('admin.accounting-fund-transfer.confirm', $fund_transfer->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="submit" class="btn btn-warning" onclick="return confirm(@json(__('messages.accounting_confirm_fund_transfer_confirm')))">
                                                Confirm
                                            </button>
                                        </form>
                                    @endif
                                    
                                    @if($fund_transfer->status == 2 && Auth::guard('admin')->user()->can('fund_transfer.approuve'))
                                        <form action="{{ route('admin.accounting-fund-transfer.approuve', $fund_transfer->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="submit" class="btn btn-success" onclick="return confirm(@json(__('messages.accounting_approve_fund_transfer_confirm')))">
                                                Approve
                                            </button>
                                        </form>
                                    @endif
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

