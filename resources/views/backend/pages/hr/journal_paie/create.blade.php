<div class="modal fade" id="journalPaieModal" tabindex="-1" role="dialog" aria-labelledby="journalPaieModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="journalPaieModalLabel">Nouveau Journal de Paie</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('admin.hr-journal-paies.store') }}" method="POST">
        @csrf
      <div class="modal-body">
                    <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group has-feedback">
                                        <label for="title">Titre du Journal<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="title" value="{{ old('title') }}" data-journal-paie-title="true" placeholder="Ex:Journal de Paie du 01 Janvier au 31 Janvier" required>
                                    </div>
                                </div>
                        </div>
                        <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="date_debut">Date Début<span class="text-danger"></span></label>
                                        <input autofocus type="date" class="form-control" name="date_debut" value="{{ old('date_debut') }}" data-journal-paie-start="true" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="date_fin">Date Fin<span class="text-danger"></span></label>
                                        <input autofocus type="date" class="form-control" name="date_fin" value="{{ old('date_fin') }}" data-journal-paie-end="true" required>
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

@once
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const formatDateForInput = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const formatDateForTitle = (date) => {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        };

        const setMonthRange = (startInput, endInput, titleInput, baseDate) => {
            const year = baseDate.getFullYear();
            const monthIndex = baseDate.getMonth();
            const firstDay = new Date(year, monthIndex, 1);
            const lastDay = new Date(year, monthIndex + 1, 0);
            startInput.value = formatDateForInput(firstDay);
            endInput.value = formatDateForInput(lastDay);
            if (titleInput) {
                titleInput.value = `payment journal from ${formatDateForTitle(firstDay)} to ${formatDateForTitle(lastDay)}`;
            }
        };

        const startInputs = document.querySelectorAll('input[data-journal-paie-start]');

        startInputs.forEach((startInput) => {
            const form = startInput.closest('form');
            if (!form) {
                return;
            }

            const endInput = form.querySelector('input[data-journal-paie-end]');
            const titleInput = form.querySelector('input[data-journal-paie-title]');
            if (!endInput) {
                return;
            }

            const syncRangeFromStart = () => {
                const baseValue = startInput.value ? new Date(startInput.value) : new Date();
                if (isNaN(baseValue.getTime())) {
                    setMonthRange(startInput, endInput, titleInput, new Date());
                    return;
                }

                setMonthRange(startInput, endInput, titleInput, baseValue);
            };

            const enforceEndDate = () => {
                const baseValue = startInput.value ? new Date(startInput.value) : new Date();
                if (isNaN(baseValue.getTime())) {
                    setMonthRange(startInput, endInput, titleInput, new Date());
                    return;
                }

                setMonthRange(startInput, endInput, titleInput, baseValue);
            };

            if (!startInput.value) {
                setMonthRange(startInput, endInput, titleInput, new Date());
            } else {
                syncRangeFromStart();
            }

            startInput.addEventListener('change', syncRangeFromStart);
            endInput.addEventListener('change', enforceEndDate);
        });

        const editModalElement = document.getElementById('editerJournalPaieModal');
        if (editModalElement && typeof window.jQuery !== 'undefined') {
            window.jQuery(editModalElement).on('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }

                const form = editModalElement.querySelector('form');
                if (!form) {
                    return;
                }

                const startInput = form.querySelector('input[data-journal-paie-start]');
                const endInput = form.querySelector('input[data-journal-paie-end]');
                const titleInput = form.querySelector('input[data-journal-paie-title]');
                if (!startInput || !endInput) {
                    return;
                }

                const actionTemplate = form.dataset.actionTemplate || '';
                const journalId = trigger.getAttribute('data-id');
                const dateDebut = trigger.getAttribute('data-date-debut');
                const dateFin = trigger.getAttribute('data-date-fin');

                if (actionTemplate && journalId) {
                    const baseAction = actionTemplate.endsWith('/') ? actionTemplate.slice(0, -1) : actionTemplate;
                    form.setAttribute('action', `${baseAction}/${journalId}`);
                }

                const baseDate = dateDebut ? new Date(dateDebut) : new Date();
                setMonthRange(startInput, endInput, titleInput, baseDate);

                if (dateDebut) {
                    startInput.value = dateDebut;
                }

                if (dateFin) {
                    endInput.value = dateFin;
                }

                const finalBase = startInput.value ? new Date(startInput.value) : baseDate;
                setMonthRange(startInput, endInput, titleInput, finalBase);
            });
        }
    });
</script>
@endonce
