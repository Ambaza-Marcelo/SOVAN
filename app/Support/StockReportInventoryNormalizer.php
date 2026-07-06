<?php

namespace App\Support;

use Illuminate\Support\Collection;

class StockReportInventoryNormalizer
{
    public static function isInventoryType($type): bool
    {
        return strtoupper(trim((string) $type)) === 'INVENTAIRE';
    }

    /**
     * @param  object|array  $row
     * @return object|array
     */
    public static function normalizeRow($row)
    {
        $type = is_array($row) ? ($row['type_transaction'] ?? null) : ($row->type_transaction ?? null);
        if (!self::isInventoryType($type)) {
            return $row;
        }

        $cump = (float) (is_array($row) ? ($row['cump'] ?? 0) : ($row->cump ?? 0));
        $physical = (float) (is_array($row)
            ? ($row['quantity_stock_final'] ?? $row['quantity_stockin'] ?? 0)
            : ($row->quantity_stock_final ?? $row->quantity_stockin ?? 0));

        if ($physical <= 0) {
            $initial = (float) (is_array($row) ? ($row['quantity_stock_initial'] ?? 0) : ($row->quantity_stock_initial ?? 0));
            $stockin = (float) (is_array($row) ? ($row['quantity_stockin'] ?? 0) : ($row->quantity_stockin ?? 0));
            $stockout = (float) (is_array($row) ? ($row['quantity_stockout'] ?? 0) : ($row->quantity_stockout ?? 0));
            $physical = $initial + $stockin - $stockout;
        }

        $valueFinal = $physical * $cump;

        if (is_array($row)) {
            $row['quantity_stockin'] = $physical;
            $row['value_stockin'] = $valueFinal;
            $row['quantity_stockout'] = 0;
            $row['value_stockout'] = 0;
            $row['quantity_stock_final'] = $physical;
            $row['value_stock_final'] = $valueFinal;

            return $row;
        }

        $row->quantity_stockin = $physical;
        $row->value_stockin = $valueFinal;
        $row->quantity_stockout = 0;
        $row->value_stockout = 0;
        $row->quantity_stock_final = $physical;
        $row->value_stock_final = $valueFinal;

        return $row;
    }

    public static function normalizeCollection($rows): Collection
    {
        if ($rows instanceof Collection) {
            return $rows->map(function ($row) {
                return self::normalizeRow($row);
            });
        }

        return collect($rows)->map(function ($row) {
            return self::normalizeRow($row);
        });
    }
}
