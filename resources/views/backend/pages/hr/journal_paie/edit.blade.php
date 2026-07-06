<div class="modal fade" id="editerJournalPaieModal" tabindex="-1" role="dialog" aria-labelledby="editerJournalPaieModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editerJournalPaieModalLabel">@lang('messages.modifier le Journal de Paie')</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="" method="POST" data-action-template="{{ url('admin/hr-journal-paies/update') }}">
        @csrf
        @method('PUT')
      <div class="modal-body">
                    <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group has-feedback">
                                        <label for="title">@lang('messages.Titre du Journal')<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="title" value="{{ old('title') }}" data-journal-paie-title="true" placeholder="{{ __('messages.hr_journal_paie_title_placeholder') }}" required>
                                    </div>
                                </div>
                        </div>
                        <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="date_debut">@lang('messages.Date Début')<span class="text-danger"></span></label>
                                        <input autofocus type="date" class="form-control" name="date_debut" value="{{ old('date_debut') }}" data-journal-paie-start="true" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="date_fin">@lang('messages.Date Fin')<span class="text-danger"></span></label>
                                        <input autofocus type="date" class="form-control" name="date_fin" value="{{ old('date_fin') }}" data-journal-paie-end="true" required>
                                    </div>
                                </div>
                        </div>
                    </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('messages.close')</button>
        <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary">@lang('messages.update')</button>
      </div>
      </form>
    </div>
  </div>
</div>
