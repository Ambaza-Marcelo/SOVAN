<div class="modal fade" id="expenseModal" tabindex="-1" role="dialog" aria-labelledby="expenseModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="expenseModalLabel">@lang('messages.New Category')</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('admin.expense-categories.store') }}" method="POST">
        @csrf
      <div class="modal-body">
          <div class="form-group">
            <label for="name" class="col-form-label">@lang('messages.Designation:')</label>
            <input type="text" name="name" class="form-control" id="name">
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