
@extends('backend.layouts.master')

@section('title')
@lang('messages.advance payment details') - @lang('messages.admin_panel')
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.advance payment details')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.accounting-advance-payment.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.advance payment details')</span></li>
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
                    <h4 class="header-title float-left">advance payment Details - Code: {{ $advance->code }}</h4>
                    <div class="float-right mb-2">
                        <a href="{{ route('admin.accounting-advance-payment.index') }}" class="btn btn-secondary">@lang('messages.back')</a>
                        @if (Auth::guard('admin')->user()->can('advance_payment.view'))
                            <a href="{{ route('admin.accounting-advance-payment.print', $advance->id) }}" class="btn btn-info" target="_blank">
                                <i class="fa fa-print"></i> Print
                            </a>
                        @endif
                        @if($advance->status == 0 && Auth::guard('admin')->user()->can('advance_payment.edit'))
                            <a href="{{ route('admin.accounting-advance-payment.edit', $advance->id) }}" class="btn btn-primary">@lang('messages.edit')</a>
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
                                            <td>{{ $advance->code }}</td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Date:')</th>
                                            <td>{{ \Carbon\Carbon::parse($advance->date)->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Payment Type:')</th>
                                            <td>
                                                <span class="badge badge-{{ $advance->payment_type == 'INTERNAL' ? 'info' : 'warning' }}">
                                                    {{ $advance->payment_type }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Amount:')</th>
                                            <td><strong>{{ number_format($advance->amount, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Status:')</th>
                                            <td>
                                                @if($advance->status == 0)
                                                    <span class="badge badge-secondary">@lang('messages.Draft')</span>
                                                @elseif($advance->status == 1)
                                                    <span class="badge badge-info">@lang('messages.Validated')</span>
                                                @elseif($advance->status == 2)
                                                    <span class="badge badge-warning">@lang('messages.Confirmed')</span>
                                                @elseif($advance->status == 3)
                                                    <span class="badge badge-success">@lang('messages.Approved')</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($advance->description)
                                        <tr>
                                            <th>@lang('messages.Description:')</th>
                                            <td>{{ $advance->description }}</td>
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
                                            <th width="40%">@lang('messages.Client:')</th>
                                            <td>
                                                @if($advance->client)
                                                    <strong>{{ $advance->client->customer_name }}</strong>
                                                    @if($advance->client->telephone)
                                                        <br><small class="text-muted">Tel: {{ $advance->client->telephone }}</small>
                                                    @endif
                                                @else
                                                    <span class="text-danger">@lang('messages.N/A')</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Bank/Cash Account:')</th>
                                            <td>
                                                @if($advance->account)
                                                    {{ $advance->account->account_number }} - {{ $advance->account->account_name }}
                                                @else
                                                    <span class="text-danger">@lang('messages.N/A')</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Advance Account (418):')</th>
                                            <td>
                                                @if($advance->advanceAccount)
                                                    {{ $advance->advanceAccount->account_number }} - {{ $advance->advanceAccount->account_name }}
                                                @else
                                                    <span class="text-danger">@lang('messages.N/A')</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Contact Name:')</th>
                                            <td>{{ $advance->contact_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Contact Phone:')</th>
                                            <td>{{ $advance->contact_tel ?? 'N/A' }}</td>
                                        </tr>
                                        @if($advance->document)
                                        <tr>
                                            <th>@lang('messages.Document:')</th>
                                            <td>
                                                <a href="{{ route('admin.accounting-advance-payment.document.view', $advance->id) }}" target="_blank" class="btn btn-sm btn-info" title="View Document">
                                                    <i class="fa fa-eye"></i> View Document
                                                </a>
                                                <a href="{{ route('admin.accounting-advance-payment.document.download', $advance->id) }}" class="btn btn-sm btn-success ml-2" title="Download Document">
                                                    <i class="fa fa-download"></i> Download
                                                </a>
                                            </td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">@lang('messages.Approval Information')</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        @if($advance->created_by)
                                        <tr>
                                            <th width="40%">@lang('messages.Created By:')</th>
                                            <td>{{ $advance->created_by }}</td>
                                        </tr>
                                        @endif
                                        @if($advance->confirmed_by)
                                        <tr>
                                            <th>@lang('messages.Confirmed By:')</th>
                                            <td>{{ $advance->confirmed_by }}</td>
                                        </tr>
                                        @endif
                                        @if($advance->approuved_by)
                                        <tr>
                                            <th>@lang('messages.Approved By:')</th>
                                            <td>{{ $advance->approuved_by }}</td>
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
                                    @if($advance->status == 0 && Auth::guard('admin')->user()->can('advance_payment.validate'))
                                        <form action="{{ route('admin.accounting-advance-payment.validate', $advance->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="submit" class="btn btn-info" onclick="return confirm(@json(__('messages.accounting_validate_advance_payment_confirm')))">
                                                Validate
                                            </button>
                                        </form>
                                    @endif
                                    
                                    @if($advance->status == 1 && Auth::guard('admin')->user()->can('advance_payment.confirm'))
                                        <form action="{{ route('admin.accounting-advance-payment.confirm', $advance->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="submit" class="btn btn-warning" onclick="return confirm(@json(__('messages.accounting_confirm_advance_payment_confirm')))">
                                                Confirm
                                            </button>
                                        </form>
                                    @endif
                                    
                                    @if($advance->status == 2 && Auth::guard('admin')->user()->can('advance_payment.approuve'))
                                        <form action="{{ route('admin.accounting-advance-payment.approuve', $advance->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="submit" class="btn btn-success" onclick="return confirm(@json(__('messages.accounting_approve_advance_payment_confirm')))">
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

