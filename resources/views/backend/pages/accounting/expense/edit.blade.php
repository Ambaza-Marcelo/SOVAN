
@extends('backend.layouts.master')

@section('title')
@lang('messages.Edit expense') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Edit expense')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.accounting-accounts.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Edit expense')</span></li>
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
                    <h4 class="header-title">@lang('messages.Edit expense')</h4>
                    @include('backend.layouts.partials.messages')
                    
        <form action="{{ route('admin.accounting-expenses.update',$expense->id) }}" method="POST">
            @method('PUT')
            @csrf
        <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="name" class="col-form-label">@lang('messages.Expense name') <strong style="color: red;">*</strong></label>
              <input type="text" name="name" class="form-control" id="name" value="{{ $expense->name }}" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="cost_price" class="col-form-label">@lang('messages.Expense Cost') <strong style="color: red;">*</strong></label>
              <input type="text" name="cost_price" class="form-control" id="cost_price" value="{{ $expense->cost_price }}" required>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
              <div class="form-group">
                  <label for="expense_category_id">@lang('messages.category')<strong style="color: red;">*</strong></label>
                  <select class="form-control" name="expense_category_id" id="expense_category_id">
                      <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                      @foreach($expense_categories as $expenseCategory)
                      <option value="{{ $expenseCategory->id }}" class="form-control" {{ $expense->expense_category_id == $expenseCategory->id ? 'selected' : '' }}>{{ $expenseCategory->name }}</option>
                      @endforeach
                  </select>
              </div>
          </div>
          <div class="col-md-6">
              <div class="form-group" style="position: relative;">
                  <label for="account_search">@lang('messages.account')<strong style="color: red;">*</strong></label>
                  @php
                      $selectedAccount = $accounts->firstWhere('id', $expense->default_account_id);
                      $accountDisplay = $selectedAccount ? $selectedAccount->account_number . ' - ' . $selectedAccount->account_name : '';
                  @endphp
                  <input type="text" class="form-control account-autocomplete" id="account_search" placeholder="@lang('messages.Rechercher un compte')" value="{{ $accountDisplay }}" autocomplete="off" required>
                  <input type="hidden" name="default_account_id" id="default_account_id" value="{{ $expense->default_account_id }}" required>
              </div>
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
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
@include('backend.layouts.partials.ajax_autocomplete')
<script type="text/javascript">
    $(document).ready(function() {
        attachAjaxAutocomplete({
            $input: $('#account_search'),
            $hidden: $('#default_account_id'),
            url: '{{ route("admin.accounting-accounts.search") }}',
            minChars: 1
        });
    });
</script>
@endsection