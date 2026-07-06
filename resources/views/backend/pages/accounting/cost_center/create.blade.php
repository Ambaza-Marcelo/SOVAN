<div class="modal fade" id="currencyModal" tabindex="-1" role="dialog" aria-labelledby="currencyModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="currencyModalLabel">@lang('messages.New Currency')</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('admin.currencies.store') }}" method="POST">
        @csrf
      <div class="modal-body">
          <div class="form-group">
            <label for="currency_name" class="col-form-label">@lang('messages.Designation *:')</label>
                    <select class="form-control" name="currency_name" id="currency_name">
                      <option disabled="disabled" selected="selected">@lang('messages.Thank you to choose')</option>
                      <option value="USD" class="form-control">@lang('messages.USD')</option>
                      <option value="TZS" class="form-control">@lang('messages.TZS')</option>
                    </select>
          </div>
          <div class="form-group">
            <label for="amount_rate" class="col-form-label">@lang('messages.Amount rate *:')</label>
            <input type="number" name="amount_rate" class="form-control" id="amount_rate" min="0" step="any">
          </div>
          <div class="form-group">
            <label for="description" class="col-form-label">@lang('messages.Description:')</label>
            <input type="text" name="description" class="form-control" id="description">
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