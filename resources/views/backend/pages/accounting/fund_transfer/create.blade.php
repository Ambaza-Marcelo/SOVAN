
@extends('backend.layouts.master')

@section('title')
@lang('messages.new fund transfer') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .account-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ced4da;
        border-top: none;
        border-radius: 0 0 0.375rem 0.375rem;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .account-suggestion-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f8f9fa;
        transition: background-color 0.2s;
    }
    
    .account-suggestion-item:hover {
        background-color: #f8f9fa;
    }
    
    .account-suggestion-item:last-child {
        border-bottom: none;
    }
    
    .account-suggestion-title {
        font-weight: bold;
        color: #333;
        font-size: 14px;
    }
    
    .account-suggestion-desc {
        font-size: 12px;
        color: #666;
        margin-top: 2px;
    }
    
    .account-suggestion-item.highlighted {
        background-color: #007bff;
        color: white;
    }
    
    .account-suggestion-item.highlighted .account-suggestion-title,
    .account-suggestion-item.highlighted .account-suggestion-desc {
        color: white;
    }
    
    .account-input-wrapper {
        position: relative;
    }
</style>
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.new fund transfer')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.accounting-fund-transfer.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.new fund transfer')</span></li>
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
                    <h4 class="header-title">@lang('messages.Create Fund Transfer')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.accounting-fund-transfer.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date" class="col-form-label">@lang('messages.Date') <strong style="color: red;">*</strong></label>
                                    <input type="date" name="date" class="form-control" id="date" value="{{ old('date', date('Y-m-d')) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transfer_type" class="col-form-label">@lang('messages.Transfer Type') <strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="transfer_type" id="transfer_type" required>
                                        <option value="">@lang('messages.Please select')</option>
                                        <option value="INTERNAL" {{ old('transfer_type') == 'INTERNAL' ? 'selected' : '' }}>@lang('messages.INTERNAL')</option>
                                        <option value="EXTERNAL" {{ old('transfer_type') == 'EXTERNAL' ? 'selected' : '' }}>@lang('messages.EXTERNAL')</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="origin_account_search" class="col-form-label">@lang('messages.Origin Account') <strong style="color: red;">*</strong></label>
                                    <div class="account-input-wrapper">
                                        <input type="text" 
                                               class="form-control account-input" 
                                               id="origin_account_search" 
                                               placeholder="@lang('messages.Tapez pour rechercher un compte...')" 
                                               autocomplete="off" 
                                               required>
                                        <input type="hidden" name="origin_account_id" id="origin_account_id" value="{{ old('origin_account_id') }}">
                                        <div class="account-suggestions" id="origin_account_suggestions" style="display: none;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="destination_account_search" class="col-form-label">@lang('messages.Destination Account') <strong style="color: red;">*</strong></label>
                                    <div class="account-input-wrapper">
                                        <input type="text" 
                                               class="form-control account-input" 
                                               id="destination_account_search" 
                                               placeholder="@lang('messages.Tapez pour rechercher un compte...')" 
                                               autocomplete="off" 
                                               required>
                                        <input type="hidden" name="destination_account_id" id="destination_account_id" value="{{ old('destination_account_id') }}">
                                        <div class="account-suggestions" id="destination_account_suggestions" style="display: none;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount" class="col-form-label">@lang('messages.Amount') <strong style="color: red;">*</strong></label>
                                    <input type="number" name="amount" class="form-control" id="amount" placeholder="@lang('messages.Enter amount')" step="0.01" min="0" value="{{ old('amount') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="description" class="col-form-label">@lang('messages.Description')</label>
                                    <textarea name="description" class="form-control" id="description" placeholder="@lang('messages.Enter Description')" rows="3">{{ old('description') }}</textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                            <a href="{{ route('admin.accounting-fund-transfer.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
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
    $(document).ready(function() {
        attachAjaxAutocomplete({
            $input: $('#origin_account_search'),
            $hidden: $('#origin_account_id'),
            url: '{{ route("admin.accounting-accounts.search") }}'
        });
        attachAjaxAutocomplete({
            $input: $('#destination_account_search'),
            $hidden: $('#destination_account_id'),
            url: '{{ route("admin.accounting-accounts.search") }}'
        });

        $('#transfer_type').on('change', function(){
            // keep typed values; only clear selected ids (user asked not to hide typed text)
            $('#origin_account_id').val('');
            $('#destination_account_id').val('');
        });
    });
</script>
@endsection

