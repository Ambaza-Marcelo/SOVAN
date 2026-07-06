@php
    $itemsCodeToId = isset($items) ? $items->pluck('id', 'code') : collect();
@endphp
    var itemDetails = [
        @foreach($itemDetails as $itemDetail)
        {
            item_id: {{ (int) ($itemsCodeToId[$itemDetail->code] ?? 0) }},
            item_code: {!! json_encode($itemDetail->code) !!},
            r_material_id: {{ $itemDetail->r_material_id ?? 0 }},
            raw_material_name: {!! json_encode($itemDetail->rawMaterial ? $itemDetail->rawMaterial->name . ' (' . ($itemDetail->rawMaterial->rawMaterialMeasurement ? $itemDetail->rawMaterial->rawMaterialMeasurement->purchase_unit : 'N/A') . ')' : 'N/A') !!},
            quantity: {{ $itemDetail->quantity ?? 0 }},
            unit: {!! json_encode($itemDetail->rawMaterial && $itemDetail->rawMaterial->rawMaterialMeasurement ? $itemDetail->rawMaterial->rawMaterialMeasurement->purchase_unit : '') !!},
            raw_material_cump: {{ $itemDetail->rawMaterial ? ($itemDetail->rawMaterial->cump ?? 0) : 0 }}
        }@if(!$loop->last),@endif
        @endforeach
    ];

    function getFinishedGoodsQtyMultiplier($row) {
        var qty = parseFloat($row.find('input[name="item_quantity[]"]').val() || 0);
        return qty > 0 ? qty : 1;
    }

    function findItemDetailsForRow($row) {
        var itemId = parseInt($row.find('.item_id').val() || 0, 10);
        var itemCode = String($row.find('.item_code').val() || '').trim().toLowerCase();
        if (!itemId && !itemCode) {
            return [];
        }
        return itemDetails.filter(function(detail) {
            if (itemId > 0 && parseInt(detail.item_id || 0, 10) === itemId) {
                return true;
            }
            return String(detail.item_code || '').trim().toLowerCase() === itemCode;
        });
    }

    function onFinishedProductSelected($wrap, item) {
        $wrap.find('.item_code').val(item.code || '');
        var $row = $wrap.closest('tr');
        var $qtyInput = $row.find('input[name="item_quantity[]"]');
        if (!$qtyInput.val() || parseFloat($qtyInput.val()) <= 0) {
            $qtyInput.val(1);
        }
        recalculateAllRawMaterials();
        calculateTotalCost();
    }
