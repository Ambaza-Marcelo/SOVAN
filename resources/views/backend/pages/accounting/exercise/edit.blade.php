
@extends('backend.layouts.master')

@section('title')
@lang('messages.Edit Currency') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
</style>
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Edit Currency')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.currencies.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Edit Currency')</span></li>
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
                    <h4 class="header-title">@lang('messages.Edit Currency')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.currencies.update',$currency->id) }}" method="POST">
                        @method('PUT')
                        @csrf
                        <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="currency_name" class="col-form-label">@lang('messages.Designation *:')</label>
                                        <select class="form-control" name="currency_name" id="currency_name">
                                        <option disabled="disabled" selected="selected">@lang('messages.Thank you to choose')</option>
                                        <option value="USD" class="form-control">@lang('messages.USD')</option>
                                        <option value="TZS" class="form-control">@lang('messages.TZS')</option>
                                    </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="amount_rate" class="col-form-label">@lang('messages.Amount rate *:')</label>
                                        <input type="number" name="amount_rate" class="form-control" id="amount_rate" value="{{ $currency->amount_rate }}" min="0" step="any">                     
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="description" class="col-form-label">@lang('messages.description:')</label>
                                        <input type="text" name="description" class="form-control" id="description" value="{{ $currency->description }}">
                                    </div>
                                </div>
                            </div>
                        <button type="submit" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.update')</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- data table end -->
        
    </div>
</div>
@endsection