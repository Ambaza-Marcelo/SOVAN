<?php

namespace App\Support;

class StockInventoryReportBuilder
{
    /**
     * Inventaire = remplacement du stock théorique par le stock physique.
     * L'entrée inventaire (quantité comptée) doit être égale au stock final.
     */
    public static function movementPayload(
        float $theoreticalQuantity,
        float $physicalQuantity,
        float $cump,
        ?float $valueInitial = null
    ): array {
        $valueInitial = $valueInitial ?? ($theoreticalQuantity * $cump);
        $valueFinal = $physicalQuantity * $cump;

        return [
            'quantity_stock_initial' => $theoreticalQuantity,
            'value_stock_initial' => $valueInitial,
            'quantity_stockin' => $physicalQuantity,
            'value_stockin' => $valueFinal,
            'stock_total' => $physicalQuantity,
            'quantity_stockout' => 0,
            'value_stockout' => 0,
            'quantity_stock_final' => $physicalQuantity,
            'value_stock_final' => $valueFinal,
            'cump' => $cump,
        ];
    }
}
