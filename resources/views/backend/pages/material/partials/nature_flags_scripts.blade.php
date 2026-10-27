<script type="text/javascript">
(function ($) {
    function isChecked($el) {
        return $el.is(':checked');
    }

    function setRequired($fields, required) {
        $fields.each(function () {
            if (required) {
                $(this).attr('required', 'required');
            } else {
                $(this).removeAttr('required');
            }
        });
    }

    function syncMaterialNatureFlags() {
        var stockable = isChecked($('#is_stockable'));
        var amortizable = isChecked($('#is_amortizable'));

        // --- STOCKABLE ON ---
        // Affiche magasin / quantités, impose store_type + code_store
        if (stockable) {
            $('.js-stockable-section').show();
            $('#stockable-hint').show();
            $('#non-stockable-hint').hide();
            setRequired($('#store_type'), true);
            $('select[name="code_store"]').attr('required', 'required');
        } else {
            // --- STOCKABLE OFF ---
            // Masque magasin, vide les champs stock, retire required
            $('.js-stockable-section').hide();
            $('#stockable-hint').hide();
            $('#non-stockable-hint').show();
            setRequired($('#store_type, #quantity, #threshold_quantity, select[name="code_store"]'), false);
            $('#store_type').prop('selectedIndex', 0);
            $('#dynamic_big_store, #dynamic_small_store').empty().show();
        }

        // --- AMORTISSABLE ON ---
        // Affiche le bloc plan, impose date + durée, préremplit base HT
        if (amortizable) {
            $('.js-amortizable-section').show();
            $('#amortizable-hint').show();
            setRequired($('#duration_years'), true);
            setRequired($('#service_start_date'), false);

            var base = $('#depreciation_base').val();
            var pa = $('#purchase_price').val();
            if ((!base || parseFloat(base) === 0) && pa) {
                $('#depreciation_base').val(pa);
            }
            if (!$('#residual_value').val()) {
                $('#residual_value').val('0');
            }
            if (!$('#duration_years').val()) {
                $('#duration_years').val('3');
            }

            // Compte immobilisation obligatoire si amortissable
            setRequired($('#immobilization_account_id'), false);
            $('#immobilization-hint').show();
        } else {
            // --- AMORTISSABLE OFF ---
            // Masque le plan et retire les contraintes
            $('.js-amortizable-section').hide();
            $('#amortizable-hint').hide();
            $('#immobilization-hint').hide();
            setRequired($('#service_start_date, #duration_years, #depreciation_base, #residual_value'), false);
        }
    }

    $(document).ready(function () {
        $('#is_stockable, #is_amortizable').on('change', syncMaterialNatureFlags);
        $('#purchase_price').on('change blur', function () {
            if (isChecked($('#is_amortizable'))) {
                var base = $('#depreciation_base').val();
                if (!base || parseFloat(base) === 0) {
                    $('#depreciation_base').val($(this).val());
                }
            }
        });

        // Après le trigger store_type du formulaire
        setTimeout(syncMaterialNatureFlags, 80);
    });
})(jQuery);
</script>
