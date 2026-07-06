<script>
(function () {
    var $typeStore = $('#type_store');
    var $container = $('#dynamic_material_store');
    if (!$typeStore.length || !$container.length) {
        return;
    }

    function esc(s) {
        return (s ?? '').toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function buildOptions(stores, selectedId) {
        var html = "<option value='' disabled>@lang('messages.merci de choisir')</option>";
        (stores || []).forEach(function (store) {
            var selected = String(store.id) === String(selectedId) ? ' selected' : '';
            html += "<option value='" + esc(store.id) + "'" + selected + ">" + esc(store.label) + "</option>";
        });
        return html;
    }

    function renderMaterialStoreSelect(type) {
        $container.empty();
        if (type === '0') {
            var stores = JSON.parse($container.attr('data-extra-stores') || '[]');
            var selected = $container.attr('data-selected-extra') || '';
            $container.append(
                "<div class='form-group'>" +
                "<label for='dest_extra_store_id'>@lang('messages.Grand Stock')<strong style='color: red;'>*</strong></label>" +
                "<select name='dest_extra_store_id' id='dest_extra_store_id' class='form-control' required>" +
                buildOptions(stores, selected) +
                "</select></div>"
            );
        } else if (type === '1') {
            var stores = JSON.parse($container.attr('data-medium-stores') || '[]');
            var selected = $container.attr('data-selected-medium') || '';
            $container.append(
                "<div class='form-group'>" +
                "<label for='destination_store_id'>@lang('messages.Stock Intermediaire')<strong style='color: red;'>*</strong></label>" +
                "<select name='destination_store_id' id='destination_store_id' class='form-control' required>" +
                buildOptions(stores, selected) +
                "</select></div>"
            );
        }
    }

    $typeStore.on('change', function () {
        renderMaterialStoreSelect($(this).val());
    });

    var initial = $typeStore.val();
    if (initial === '0' || initial === '1') {
        renderMaterialStoreSelect(initial);
    }
})();
</script>
