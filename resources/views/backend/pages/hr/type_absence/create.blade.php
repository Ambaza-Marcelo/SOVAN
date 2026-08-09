<div class="modal fade" id="typeAbsenceModal" tabindex="-1" role="dialog" aria-labelledby="typeAbsenceModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="typeAbsenceModalLabel">Nouveau Motif Absence</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('admin.hr-type-absences.store') }}" method="POST">
        @csrf
      <div class="modal-body">
          <div class="form-group">
            <label for="name" class="col-form-label">Désignation *:</label>
            <input type="text" name="name" class="form-control" id="name" required maxlength="255">
          </div>
          <div class="form-group">
            <label for="type" class="col-form-label">Type:</label>
            <input type="text" name="type" class="form-control" id="type" maxlength="255">
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('messages.close')</button>
        <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
      </div>
      </form>
    </div>
  </div>
</div>
