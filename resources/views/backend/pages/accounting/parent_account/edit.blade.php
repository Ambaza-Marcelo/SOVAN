
@extends('backend.layouts.master')

@section('title')
@lang('messages.Edit parent account') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Edit parent account')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.accounting-parent-accounts.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Edit account')</span></li>
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
                    <h4 class="header-title">@lang('messages.Edit parent account')</h4>
                    @include('backend.layouts.partials.messages')
                    
        <form action="{{ route('admin.accounting-parent-accounts.update',$account->id) }}" method="POST">
            @method('PUT')
            @csrf
        <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="account_name" class="col-form-label">@lang('messages.Parent Account name *:')</label>
              <input type="text" name="account_name" class="form-control" id="account_name" value="{{ $account->account_name }}" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="account_number" class="col-form-label">@lang('messages.Parent Account number *:')</label>
              <input type="text" name="account_number" class="form-control" id="account_number" value="{{ $account->account_number }}" required>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label for="account_type" class="col-form-label">@lang('messages.Parent Account Type *:')</label>
              <select class="form-control" name="account_type" id="account_type">
                <option disabled="disabled" selected="selected">@lang('messages.Thank you to choose')</option>
                <option value="1" {{ $account->account_type == '1' ? 'selected' : '' }}>@lang('messages.accumulated_depreciation')</option>
                <option value="2" {{ $account->account_type == '2' ? 'selected' : '' }}>@lang('messages.Produits')</option>
                <option value="3" {{ $account->account_type == '3' ? 'selected' : '' }}>@lang('messages.bank')</option>
                <option value="4" {{ $account->account_type == '4' ? 'selected' : '' }}>@lang('messages.Petite Caisse')</option>
                <option value="5" {{ $account->account_type == '5' ? 'selected' : '' }}>@lang('messages.Caisse Recette')</option>
                <option value="6" {{ $account->account_type == '6' ? 'selected' : '' }}>@lang('messages.Caisse Devise')</option>
                <option value="7" {{ $account->account_type == '7' ? 'selected' : '' }}>@lang('messages.Caisse Dépense')</option>
                <option value="8" {{ $account->account_type == '8' ? 'selected' : '' }}>@lang('messages.current_asset')</option>
                <option value="9" {{ $account->account_type == '9' ? 'selected' : '' }}>@lang('messages.current_liability')</option>
                <option value="10" {{ $account->account_type == '10' ? 'selected' : '' }}>@lang('messages.depreciation')</option>
                <option value="11" {{ $account->account_type == '11' ? 'selected' : '' }}>@lang('messages.Débiteurs divers')</option>
                <option value="12" {{ $account->account_type == '12' ? 'selected' : '' }}>@lang('messages.Créditeurs divers')</option>
                <option value="13" {{ $account->account_type == '13' ? 'selected' : '' }}>@lang('messages.equity')</option>
                <option value="14" {{ $account->account_type == '14' ? 'selected' : '' }}>@lang('messages.expense_account')</option>
                <option value="15" {{ $account->account_type == '15' ? 'selected' : '' }}>@lang('messages.Clients')</option>
                <option value="16" {{ $account->account_type == '16' ? 'selected' : '' }}>@lang('messages.Fournisseurs')</option>
                <option value="17" {{ $account->account_type == '17' ? 'selected' : '' }}>@lang('messages.fixed_asset')</option>
                <option value="18" {{ $account->account_type == '18' ? 'selected' : '' }}>@lang('messages.Coût des Marchandises Vendues')</option>
                <option value="19" {{ $account->account_type == '19' ? 'selected' : '' }}>@lang('messages.Matières Premières Consommées')</option>
                <option value="20" {{ $account->account_type == '20' ? 'selected' : '' }}>@lang('messages.liability')</option>
                <option value="21" {{ $account->account_type == '21' ? 'selected' : '' }}>@lang('messages.Autres Fournitures Consommées')</option>
                <option value="22" {{ $account->account_type == '22' ? 'selected' : '' }}>@lang('messages.Transport Consommé')</option>
                <option value="23" {{ $account->account_type == '23' ? 'selected' : '' }}>@lang('messages.Autres Services Consommés')</option>
                <option value="24" {{ $account->account_type == '24' ? 'selected' : '' }}>@lang('messages.Autres Charges de gestion courante')</option>
                <option value="25" {{ $account->account_type == '25' ? 'selected' : '' }}>@lang('messages.stock')</option>
                <option value="26" {{ $account->account_type == '26' ? 'selected' : '' }}>@lang('messages.stock_adjustment')</option>
                <option value="27" {{ $account->account_type == '27' ? 'selected' : '' }}>@lang('messages.Charges du personnel')</option>
                <option value="28" {{ $account->account_type == '28' ? 'selected' : '' }}>@lang('messages.Intérêt Bancaire')</option>
                <option value="29" {{ $account->account_type == '29' ? 'selected' : '' }}>@lang('messages.tax')</option>
                <option value="30" {{ $account->account_type == '30' ? 'selected' : '' }}>@lang('messages.temporary')</option>
                <option value="31" {{ $account->account_type == '31' ? 'selected' : '' }}>@lang('messages.Personnel')</option>
                <option value="32" {{ $account->account_type == '32' ? 'selected' : '' }}>@lang('messages.Provisions')</option>
              </select>
            </div>
          </div>
          <div class="col-md-4">
              <label for="balance_must_be">@lang('messages.Balance must be?')</label>
              <div class="form-group">
                  <label class="text">Debit
                  <input type="checkbox" name="balance_must_be" value="1" class="form-control">
                  </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  <label class="text">Credit
                  <input type="checkbox" checked="checked" name="balance_must_be" value="2" class="form-control">
                  </label>
              </div>
          </div>
          <div class="col-md-4">
              <div class="form-group">
                  <label for="currency_id">@lang('messages.currency')<strong style="color: red;">*</strong></label>
                  <select class="form-control" name="currency_id" id="currency_id">
                      <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                      @foreach($currencies as $currency)
                      <option value="{{ $currency->id }}" class="form-control" {{ $account->currency_id == $currency->id ? 'selected' : '' }}>{{ $currency->currency_name }}</option>
                      @endforeach
                  </select>
              </div>
          </div>
        </div>
        <div class="col-lg-12">
            <label for="description"> @lang('messages.description')</label>
            <textarea class="form-control" name="description" id="description"> {{ $account->description }}
            </textarea>
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