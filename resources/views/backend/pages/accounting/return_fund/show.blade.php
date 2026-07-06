
@extends('backend.layouts.master')

@section('title')
@lang('messages.Détails retour de fonds') - @lang('messages.admin_panel')
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Détails retour de fonds')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.accounting-return-fund.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Détails retour de fonds')</span></li>
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
                    <h4 class="header-title float-left">@lang('messages.Détails du retour de fonds - N°:') {{ $return_fund->return_no }}</h4>
                    <div class="float-right mb-2">
                        <a href="{{ route('admin.accounting-return-fund.index') }}" class="btn btn-secondary">@lang('messages.back')</a>
                        @if (Auth::guard('admin')->user()->can('fund_transfer.view'))
                            <a href="{{ route('admin.accounting-return-fund.print', $return_fund->id) }}" class="btn btn-info" target="_blank">
                                <i class="fa fa-print"></i> Imprimer
                            </a>
                        @endif
                        @if($return_fund->status != '3' && Auth::guard('admin')->user()->can('fund_transfer.edit'))
                            <a href="{{ route('admin.accounting-return-fund.edit', $return_fund->id) }}" class="btn btn-primary">@lang('messages.edit')</a>
                        @endif
                    </div>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials.messages')
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">@lang('messages.Informations de base')</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <th width="40%">@lang('messages.N° Retour:')</th>
                                            <td>{{ $return_fund->return_no }}</td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.N° Sortie:')</th>
                                            <td>{{ $return_fund->cash_outflow_no ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Date:')</th>
                                            <td>{{ \Carbon\Carbon::parse($return_fund->date)->format('d/m/Y H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Montant Sorti:')</th>
                                            <td><strong>{{ number_format($return_fund->total_amount_picked, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Montant Retourné:')</th>
                                            <td><strong>{{ number_format($return_fund->total_amount_returned, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Status:')</th>
                                            <td>
                                                @if($return_fund->status == '0')
                                                    <span class="badge badge-secondary">@lang('messages.Brouillon')</span>
                                                @elseif($return_fund->status == '1')
                                                    <span class="badge badge-info">@lang('messages.Validé')</span>
                                                @elseif($return_fund->status == '2')
                                                    <span class="badge badge-warning">@lang('messages.Confirmé')</span>
                                                @elseif($return_fund->status == '3')
                                                    <span class="badge badge-success">@lang('messages.Approuvé')</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($return_fund->description)
                                        <tr>
                                            <th>@lang('messages.Description:')</th>
                                            <td>{{ $return_fund->description }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">@lang('messages.Informations comptables')</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <th width="40%">@lang('messages.Employé:')</th>
                                            <td>
                                                @if($return_fund->employe)
                                                    {{ $return_fund->employe->firstname }} {{ $return_fund->employe->lastname }}
                                                @else
                                                    <span class="text-danger">@lang('messages.N/A')</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>@lang('messages.Compte:')</th>
                                            <td>
                                                @if($return_fund->account)
                                                    {{ $return_fund->account->account_number }} - {{ $return_fund->account->account_name }}
                                                @else
                                                    <span class="text-danger">@lang('messages.N/A')</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($return_fund->return_signature)
                                        <tr>
                                            <th>@lang('messages.Signature:')</th>
                                            <td>{{ $return_fund->return_signature }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">@lang('messages.Informations d\'approbation')</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        @if($return_fund->created_by)
                                        <tr>
                                            <th width="40%">@lang('messages.Créé par:')</th>
                                            <td>{{ $return_fund->created_by }}</td>
                                        </tr>
                                        @endif
                                        @if($return_fund->validated_by)
                                        <tr>
                                            <th>@lang('messages.Validé par:')</th>
                                            <td>{{ $return_fund->validated_by }}</td>
                                        </tr>
                                        @endif
                                        @if($return_fund->confirmed_by)
                                        <tr>
                                            <th>@lang('messages.Confirmé par:')</th>
                                            <td>{{ $return_fund->confirmed_by }}</td>
                                        </tr>
                                        @endif
                                        @if($return_fund->approuved_by)
                                        <tr>
                                            <th>@lang('messages.Approuvé par:')</th>
                                            <td>{{ $return_fund->approuved_by }}</td>
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
                                    @if($return_fund->status == '0' && Auth::guard('admin')->user()->can('fund_transfer.validate'))
                                        <form action="{{ route('admin.accounting-return-fund.validate', $return_fund->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="submit" class="btn btn-info" onclick="return confirm(@json(__('messages.accounting_validate_return_fund_confirm')))">
                                                Valider
                                            </button>
                                        </form>
                                    @endif
                                    
                                    @if($return_fund->status == '1' && Auth::guard('admin')->user()->can('fund_transfer.confirm'))
                                        <form action="{{ route('admin.accounting-return-fund.confirm', $return_fund->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="submit" class="btn btn-warning" onclick="return confirm(@json(__('messages.accounting_confirm_return_fund_confirm')))">
                                                Confirmer
                                            </button>
                                        </form>
                                    @endif
                                    
                                    @if($return_fund->status == '2' && Auth::guard('admin')->user()->can('fund_transfer.approuve'))
                                        <form action="{{ route('admin.accounting-return-fund.approuve', $return_fund->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="submit" class="btn btn-success" onclick="return confirm(@json(__('messages.accounting_approve_return_fund_confirm')))">
                                                Approuver
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

