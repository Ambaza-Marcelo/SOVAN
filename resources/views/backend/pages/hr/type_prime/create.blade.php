<div class="modal fade" id="typePrimeModal" tabindex="-1" role="dialog" aria-labelledby="typePrimeModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="typePrimeModalLabel">Nouveau Type Prime</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('admin.hr-type-primes.store') }}" method="POST">
        @csrf
      <div class="modal-body">
          <div class="form-group">
            <label for="name" class="col-form-label">Designation *:</label>
            <input type="text" name="name" class="form-control" id="name">
          </div>
          <div class="form-group">
            <label for="type" class="col-form-label">Type *:</label>
            <select name="type" id="type" class="form-control">
                <option value="imposable">Imposable</option>
                <option value="non_imposable">Non Imposable</option>
            </select>
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
