<script>
    $(document).ready(function() {
        // Charger les employés en fonction du département sélectionné
        $('#departement_id').on('change', function() {
            var departementId = $(this).val();
            var employeSelect = $('#employe_id');
            
            if (departementId) {
                // Activer le select employé
                employeSelect.prop('disabled', false);
                
                // Recharger la page avec le département sélectionné pour charger les employés
                var currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('departement_id', departementId);
                currentUrl.searchParams.delete('employe_id'); // Réinitialiser l'employé
                window.location.href = currentUrl.toString();
            } else {
                // Désactiver le select employé
                employeSelect.prop('disabled', true);
                employeSelect.html('<option value="">Sélectionner un employé</option>');
            }
        });

        // Gestion des boutons radio de shift avec style visuel
        $('.shift-option').on('click', function() {
            var radio = $(this).find('input[type="radio"]');
            var container = $(this).closest('.day-column');
            
            // Désélectionner toutes les options de ce jour
            container.find('.shift-option').removeClass('selected');
            
            // Sélectionner l'option cliquée
            $(this).addClass('selected');
            radio.prop('checked', true);
        });

        // Initialiser les sélections visuelles au chargement
        $('.day-column').each(function() {
            var checkedRadio = $(this).find('input[type="radio"]:checked');
            if (checkedRadio.length) {
                checkedRadio.closest('.shift-option').addClass('selected');
            }
        });

        // Format de la semaine (week input)
        $('input[type="week"]').on('change', function() {
            // S'assurer que la valeur est au format correct
            var weekValue = $(this).val();
            if (weekValue && !weekValue.includes('-W')) {
                // Convertir le format si nécessaire
                var parts = weekValue.split('-W');
                if (parts.length === 2) {
                    $(this).val(weekValue);
                }
            }
        });
    });

    // Fonction pour valider le formulaire avant soumission
    function validateTimeTableForm() {
        var employeId = $('#employe_id').val();
        var departementId = $('#departement_id').val();
        var week = $('#week').val();

        if (!departementId) {
            alert('Veuillez sélectionner un département!');
            return false;
        }

        if (!employeId) {
            alert('Veuillez sélectionner un employé!');
            return false;
        }

        if (!week) {
            alert('Veuillez sélectionner une semaine!');
            return false;
        }

        return true;
    }

    // Ajouter la validation au formulaire de création/modification
    $('form[method="POST"]').on('submit', function(e) {
        if (!validateTimeTableForm()) {
            e.preventDefault();
            return false;
        }
    });
</script>
