<div class="modal fade" id="childAccountModal" tabindex="-1" role="dialog" aria-labelledby="childAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="childAccountModalLabel">@lang('messages.Create Child Account')</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('admin.accounting-accounts.store') }}" method="POST">
        @csrf
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="account_name" class="col-form-label">@lang('messages.Child Account name') <strong style="color: red;">*</strong></label>
              <input type="text" name="account_name" class="form-control" id="account_name" placeholder="@lang('messages.Enter account name')" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="account_number" class="col-form-label">@lang('messages.Child Account number') <strong style="color: red;">*</strong></label>
              <input type="text" name="account_number" class="form-control" id="account_number" placeholder="@lang('messages.Enter account number')" required>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="account_type" class="col-form-label">@lang('messages.Child Account Type') <strong style="color: red;">*</strong></label>
              <select class="form-control" name="account_type" id="account_type" required>
                <option disabled="disabled" selected="selected">@lang('messages.Thank you to choose')</option>
                <option value="1">@lang('messages.accumulated_depreciation')</option>
                <option value="2">@lang('messages.Produits')</option>
                <option value="3">@lang('messages.bank')</option>
                <option value="4">@lang('messages.Petite Caisse')</option>
                <option value="5">@lang('messages.Caisse Recette')</option>
                <option value="6">@lang('messages.Caisse Devise')</option>
                <option value="7">@lang('messages.Caisse Dépense')</option>
                <option value="8">@lang('messages.current_asset')</option>
                <option value="9">@lang('messages.current_liability')</option>
                <option value="10">@lang('messages.depreciation')</option>
                <option value="11">@lang('messages.Débiteurs divers')</option>
                <option value="12">@lang('messages.Créditeurs divers')</option>
                <option value="13">@lang('messages.equity')</option>
                <option value="14">@lang('messages.expense_account')</option>
                <option value="15">@lang('messages.Clients')</option>
                <option value="16">@lang('messages.Fournisseurs')</option>
                <option value="17">@lang('messages.fixed_asset')</option>
                <option value="18">@lang('messages.Coût des Marchandises Vendues')</option>
                <option value="19">@lang('messages.Matières Premières Consommées')</option>
                <option value="20">@lang('messages.liability')</option>
                <option value="21">@lang('messages.Autres Fournitures Consommées')</option>
                <option value="22">@lang('messages.Transport Consommé')</option>
                <option value="23">@lang('messages.Autres Services Consommés')</option>
                <option value="24">@lang('messages.Autres Charges de gestion courante')</option>
                <option value="25">@lang('messages.stock')</option>
                <option value="26">@lang('messages.stock_adjustment')</option>
                <option value="27">@lang('messages.Charges du personnel')</option>
                <option value="28">@lang('messages.Intérêt Bancaire')</option>
                <option value="29">@lang('messages.tax')</option>
                <option value="30">@lang('messages.temporary')</option>
                <option value="31">@lang('messages.Personnel')</option>
                <option value="32">@lang('messages.Provisions')</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
              <label for="balance_must_be">@lang('messages.Balance must be?')<strong style="color: red;">*</strong></label>
              <div class="form-group">
                  <label class="text">@lang('messages.Debit')
                  <input type="checkbox" name="balance_must_be" value="1" class="form-control">
                  </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  <label class="text">@lang('messages.Credit')
                  <input type="checkbox" checked="checked" name="balance_must_be" value="2" class="form-control">
                  </label>
              </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
              <div class="form-group">
                  <label for="currency_id">@lang('messages.currency')<strong style="color: red;">*</strong></label>
                  <select class="form-control" name="currency_id" id="currency_id">
                      <option disabled="disabled">@lang('messages.Merci de choisir')</option>
                      @foreach($currencies as $currency)
                      <option value="{{ $currency->id }}" class="form-control" {{ strtoupper($currency->currency_name) == 'BIF' ? 'selected="selected"' : '' }}>{{ $currency->currency_name }}</option>
                      @endforeach
                  </select>
              </div>
          </div>
          <div class="col-md-6">
              <div class="form-group">
                  <label for="acc_parent_id">@lang('messages.parent_account')<strong style="color: red;">*</strong></label>
                  <select class="form-control" name="acc_parent_id" id="acc_parent_id">
                      <option disabled="disabled" {{ !isset($selected_parent_id) ? 'selected="selected"' : '' }}>@lang('messages.Merci de choisir')</option>
                      @foreach($parent_accounts as $parent_account)
                      <option value="{{ $parent_account->id }}" class="form-control" 
                              {{ (isset($selected_parent_id) && $selected_parent_id == $parent_account->id) ? 'selected="selected"' : '' }}>
                          {{ $parent_account->account_name }}
                      </option>
                      @endforeach
                  </select>
              </div>
          </div>
        </div>
        <div class="col-lg-12">
            <label for="description"> @lang('messages.description')</label>
            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Enter description')" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('messages.close')</button>
        <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary">@lang('messages.save')</button>
      </div>
      </form>
    </div>
  </div>
</div>