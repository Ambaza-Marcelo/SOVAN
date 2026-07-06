{{-- Unified AJAX autocomplete for document line forms (invoice, delivery note, credit note). --}}
@include('backend.layouts.partials.ajax_autocomplete')
<script type="text/javascript">
(function(){
    var i = {{ $initialRowIndex ?? 0 }};
    var canEdit = @json($canEdit ?? true);

    function parseBifToNumber(s) {
        if (!s) return 0;
        return parseFloat(String(s).replace(/[^\d.]/g, '')) || 0;
    }

    function escHtml(s) {
        return (s ?? '').toString()
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function calculateRowTotal(rowIndex) {
        var quantity = parseFloat(document.getElementById('quantity_' + rowIndex).value) || 0;
        var unitPrice = parseBifToNumber(document.getElementById('unit_price_' + rowIndex).value);
        var total = quantity * unitPrice;
        document.getElementById('total_price_' + rowIndex).value = total.toLocaleString() + ' BIF';
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        var grandTotal = 0;
        document.querySelectorAll('.total-price').forEach(function(input) {
            var value = input.value.replace(/[^\d]/g, '');
            if (value) grandTotal += parseFloat(value);
        });
        var el = document.getElementById('grand_total');
        if (el) el.textContent = grandTotal.toLocaleString() + ' BIF';
    }

    $(document).on('input', '.quantity-input', function() {
        calculateRowTotal(this.id.split('_')[1]);
    });

    function initItemRow(rowIndex) {
        attachAjaxAutocomplete({
            $input: $('#item_name_' + rowIndex),
            $hidden: $('#item_id_' + rowIndex),
            url: '{{ route("admin.autocomplete.invoice-items") }}',
            minChars: 1,
            renderItem: function(item) {
                var price = (item.price ?? 0);
                var typeLabel = item.type_label ? ('<span class="badge badge-secondary ml-1">' + escHtml(item.type_label) + '</span>') : '';
                var name = escHtml(item.name || item.text || '');
                return '<div><strong>' + name + '</strong>' + typeLabel +
                    ' <span class="text-muted">- ' + Number(price).toLocaleString() + ' BIF</span></div>';
            },
            onSelect: function(item) {
                var price = Number(item.price ?? 0);
                $('#unit_price_' + rowIndex).val(price.toLocaleString() + ' BIF');
                $('#item_price_' + rowIndex).val(price);
                calculateRowTotal(rowIndex);
            }
        });
    }

    if (canEdit) {
        @if(!empty($detailIndices))
        @foreach($detailIndices as $index)
        initItemRow({{ $index }});
        @endforeach
        @endif

        attachAjaxAutocomplete({
            $input: $('#client_name'),
            $hidden: $('#client_id'),
            url: '{{ route("admin.autocomplete.clients") }}',
            minChars: 1,
            renderItem: function(item) {
                var name = escHtml(item.name || item.text || '');
                var tel = item.telephone ? (' <span class="text-muted">- ' + escHtml(item.telephone) + '</span>') : '';
                return '<div><strong>' + name + '</strong>' + tel + '</div>';
            }
        });

        var i18n = {
            searchItem: @json(__('messages.Rechercher un item...')),
            enterQuantity: @json(__('messages.Entrer quantite')),
            delete: @json(__('messages.delete'))
        };

        $("#add").click(function(){
            ++i;
            var markup = "<tr>" +
                "<td><div class='ajax-ac-wrap'>" +
                "<input type='text' name='item_name[]' id='item_name_" + i + "' placeholder='" + i18n.searchItem + "' class='form-control' autocomplete='off' required />" +
                "<input type='hidden' name='item_id[]' id='item_id_" + i + "' />" +
                "<input type='hidden' name='item_price[]' id='item_price_" + i + "' />" +
                "</div></td>" +
                "<td><input type='number' name='item_quantity[]' id='quantity_" + i + "' placeholder='" + i18n.enterQuantity + "' class='form-control quantity-input' required/></td>" +
                "<td><input type='text' id='unit_price_" + i + "' class='form-control unit-price' readonly /></td>" +
                "<td><input type='text' id='total_price_" + i + "' class='form-control total-price' readonly /></td>" +
                "<td><button type='button' class='btn btn-danger remove-tr'>" + i18n.delete + "</button></td>" +
                "</tr>";
            $("#dynamicTable").append(markup);
            setTimeout(function() { initItemRow(i); }, 100);
        });

        $(document).on('click', '.remove-tr', function(){
            $(this).parents('tr').remove();
            calculateGrandTotal();
        });

        $('#dynamic_form').on('submit', function(e) {
            var missingSelection = false;
            $('[id^="item_name_"]').each(function() {
                var rowIndex = this.id.replace('item_name_', '');
                if ($(this).val() && !$('#item_id_' + rowIndex).val()) {
                    missingSelection = true;
                }
            });
            if (missingSelection) {
                e.preventDefault();
                alert(@json(__('messages.sales_order_select_item_from_list')));
                $('#save').css('visibility', 'visible');
                return false;
            }
        });
    }
})();
</script>
