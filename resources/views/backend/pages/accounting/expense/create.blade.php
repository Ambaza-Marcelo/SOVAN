<div class="modal fade" id="expenseModal" tabindex="-1" role="dialog" aria-labelledby="expenseModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="expenseModalLabel">@lang('messages.New Expense')</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('admin.accounting-expenses.store') }}" method="POST">
        @csrf
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="name" class="col-form-label">@lang('messages.Expense name') <strong style="color: red;">*</strong></label>
              <input type="text" name="name" class="form-control" id="name" placeholder="@lang('messages.Enter expense name')" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="cost_price" class="col-form-label">@lang('messages.Expense Cost') <strong style="color: red;">*</strong></label>
              <input type="text" name="cost_price" class="form-control" id="cost_price" placeholder="@lang('messages.Enter expense cost')" required>
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
                      <option value="{{ $expenseCategory->id }}" class="form-control">{{ $expenseCategory->name }}</option>
                      @endforeach
                  </select>
              </div>
          </div>
          <div class="col-md-6">
              <div class="form-group" style="position: relative;">
                  <label for="account_search">@lang('messages.account')<strong style="color: red;">*</strong></label>
                  <input type="text" class="form-control account-autocomplete" id="account_search" placeholder="@lang('messages.Rechercher un compte')" autocomplete="off" required>
                  <input type="hidden" name="default_account_id" id="default_account_id" required>
              </div>
          </div>
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
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
@include('backend.layouts.partials.ajax_autocomplete')
<script type="text/javascript">
    function initExpenseModalAccountAutocomplete() {
        attachAjaxAutocomplete({
            $input: $('#expenseModal #account_search'),
            $hidden: $('#expenseModal #default_account_id'),
            url: '{{ route("admin.accounting-accounts.search") }}',
            minChars: 1
        });
    }

    $(document).ready(function() {
        initExpenseModalAccountAutocomplete();
    });

    $('#expenseModal').on('shown.bs.modal', function () {
        initExpenseModalAccountAutocomplete();
    });

    $('#expenseModal').on('hidden.bs.modal', function () {
        $('#expenseModal #account_search').val('');
        $('#expenseModal #default_account_id').val('');
    });
</script>
