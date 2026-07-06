<?php

namespace App\Services\Stock;

class StockCumpCalculator
{
    /**
     * Weighted-average CUMP after a stock-in movement.
     *
     * @return array{
     *     quantity_initial: float,
     *     value_initial: float,
     *     quantity_in: float,
     *     value_in: float,
     *     quantity_total: float,
     *     cump: float,
     *     value_total: float
     * }
     */
    public static function weightedAverageOnStockIn(
        float $quantityInitial,
        float $valueInitial,
        float $quantityIn,
        float $unitCost,
        ?float $fallbackCump = null
    ): array {
        $quantityIn = max(0, $quantityIn);
        $quantityInitial = max(0, $quantityInitial);
        $valueInitial = max(0, $valueInitial);

        $quantityTotal = $quantityInitial + $quantityIn;
        $valueIn = $quantityIn * $unitCost;

        if ($quantityTotal > 0) {
            $cump = ($valueInitial + $valueIn) / $quantityTotal;
        } else {
            $cump = $fallbackCump ?? $unitCost;
        }

        return [
            'quantity_initial' => $quantityInitial,
            'value_initial' => $valueInitial,
            'quantity_in' => $quantityIn,
            'value_in' => $valueIn,
            'quantity_total' => $quantityTotal,
            'cump' => $cump,
            'value_total' => $quantityTotal * $cump,
        ];
    }

    /**
     * Remaining quantity and value after a stock-out (CUMP unchanged).
     *
     * @return array{quantity_final: float, value_final: float, cump: float}
     */
    public static function stockoutRemaining(float $quantityInitial, float $quantityOut, float $cump): array
    {
        $quantityFinal = $quantityInitial - $quantityOut;

        return [
            'quantity_final' => $quantityFinal,
            'value_final' => $quantityFinal * $cump,
            'cump' => $cump,
        ];
    }

    /**
     * Resolve total stock value from store detail fields.
     */
    public static function initialValueFromStore(float $quantity, ?float $cump, ?float $totalCumpValue = null): float
    {
        if ($totalCumpValue !== null && (float) $totalCumpValue > 0) {
            return (float) $totalCumpValue;
        }

        return $quantity * (float) ($cump ?? 0);
    }

    /**
     * Effective unit cost when master CUMP is missing or zero.
     */
    public static function resolveUnitCost(?float $cump, ?float $purchasePrice = null, ?float $costPrice = null): float
    {
        if ($cump !== null && (float) $cump > 0) {
            return (float) $cump;
        }

        if ($purchasePrice !== null && (float) $purchasePrice > 0) {
            return (float) $purchasePrice;
        }

        return (float) ($costPrice ?? 0);
    }
}
