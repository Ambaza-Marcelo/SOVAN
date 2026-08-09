<div class="modal fade" id="groupeIndemniteModal" tabindex="-1" role="dialog" aria-labelledby="groupeIndemniteModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="groupeIndemniteModalLabel">Nouveau Groupe Indemnité</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('admin.hr-groupe-indemnites.store') }}" method="POST">
        @csrf
      <div class="modal-body">
          <div class="form-group">
            <label for="designation" class="col-form-label">Designation *:</label>
            <input type="text" name="designation" class="form-control" id="designation">
          </div>
          <div class="form-group">
            <label for="classe_inferieure" class="col-form-label">Classe Inférieure *:</label>
            <input type="text" name="classe_inferieure" class="form-control" id="classe_inferieure">
          </div>
          <div class="form-group">
            <label for="classe_superieure" class="col-form-label">Classe Supérieure *:</label>
            <input type="text" name="classe_superieure" class="form-control" id="classe_superieure">
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
