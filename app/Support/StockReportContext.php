<?php

namespace App\Support;

use App\Models\FinishedGoodReport;
use App\Models\FinishedGoodsSmStore;
use App\Models\FinishedGoodsSmStoreDetail;
use App\Models\FinishedGoodsSmStoreReport;
use App\Models\FinishedGoodsStore;
use App\Models\FinishedGoodsStoreDetail;
use App\Models\GoodsStore;
use App\Models\GoodsStoreDetail;
use App\Models\GoodsStoreReport;
use App\Models\Item;
use App\Models\Material;
use App\Models\MaterialBigReport;
use App\Models\MaterialBigStore;
use App\Models\MaterialBigStoreDetail;
use App\Models\MaterialExtraBigReport;
use App\Models\MaterialExtraBigStore;
use App\Models\MaterialExtraBigStoreDetail;
use App\Models\MaterialSmallReport;
use App\Models\MaterialSmallStore;
use App\Models\MaterialSmallStoreDetail;
use App\Models\RawMaterial;
use App\Models\RawMaterialBigReport;
use App\Models\RawMaterialBigStore;
use App\Models\RawMaterialBigStoreDetail;
use App\Models\RawMaterialExtraBigReport;
use App\Models\RawMaterialExtraBigStore;
use App\Models\RawMaterialExtraBigStoreDetail;
use App\Models\RawMaterialSmallReport;
use App\Models\RawMaterialSmallStore;
use App\Models\RawMaterialSmallStoreDetail;

class StockReportContext
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'finished_goods' => [
                'date_column' => 'date',
                'scope' => 'item',
                'entity_relation' => 'item',
                'report_entity_column' => 'item_id',
                'store_column' => 'code_store',
                'report_model' => FinishedGoodReport::class,
                'entity_model' => Item::class,
                'store_model' => FinishedGoodsStore::class,
                'inventory_model' => FinishedGoodsStoreDetail::class,
                'inventory_store_column' => 'code_store',
                'inventory_entity_column' => 'item_id',
            ],
            'finished_goods_sm' => [
                'date_column' => 'date',
                'scope' => 'item',
                'entity_relation' => 'item',
                'report_entity_column' => 'item_id',
                'store_column' => 'code_store',
                'report_model' => FinishedGoodsSmStoreReport::class,
                'entity_model' => Item::class,
                'store_model' => FinishedGoodsSmStore::class,
                'inventory_model' => FinishedGoodsSmStoreDetail::class,
                'inventory_store_column' => 'code_store',
                'inventory_entity_column' => 'item_id',
            ],
            'goods' => [
                'date_column' => 'date',
                'scope' => 'item',
                'entity_relation' => 'item',
                'report_entity_column' => 'item_id',
                'store_column' => 'code_store',
                'report_model' => GoodsStoreReport::class,
                'entity_model' => Item::class,
                'store_model' => GoodsStore::class,
                'inventory_model' => GoodsStoreDetail::class,
                'inventory_store_column' => 'code_store',
                'inventory_entity_column' => 'item_id',
            ],
            'material_big' => [
                'date_column' => 'created_at',
                'scope' => 'material',
                'entity_relation' => 'material',
                'report_entity_column' => 'material_id',
                'store_column' => 'code_store',
                'report_model' => MaterialBigReport::class,
                'entity_model' => Material::class,
                'store_model' => MaterialBigStore::class,
                'inventory_model' => MaterialBigStoreDetail::class,
                'inventory_store_column' => 'code',
                'inventory_entity_column' => 'material_id',
            ],
            'material_extra_big' => [
                'date_column' => 'created_at',
                'scope' => 'material',
                'entity_relation' => 'material',
                'report_entity_column' => 'material_id',
                'store_column' => 'code_store',
                'report_model' => MaterialExtraBigReport::class,
                'entity_model' => Material::class,
                'store_model' => MaterialExtraBigStore::class,
                'inventory_model' => MaterialExtraBigStoreDetail::class,
                'inventory_store_column' => 'code',
                'inventory_entity_column' => 'material_id',
            ],
            'material_small' => [
                'date_column' => 'created_at',
                'scope' => 'material',
                'entity_relation' => 'material',
                'report_entity_column' => 'material_id',
                'store_column' => 'code_store',
                'report_model' => MaterialSmallReport::class,
                'entity_model' => Material::class,
                'store_model' => MaterialSmallStore::class,
                'inventory_model' => MaterialSmallStoreDetail::class,
                'inventory_store_column' => 'code',
                'inventory_entity_column' => 'material_id',
            ],
            'raw_material_big' => [
                'date_column' => 'date',
                'scope' => 'raw_material',
                'entity_relation' => 'rawMaterial',
                'report_entity_column' => 'r_material_id',
                'store_column' => 'code_store',
                'report_model' => RawMaterialBigReport::class,
                'entity_model' => RawMaterial::class,
                'store_model' => RawMaterialBigStore::class,
                'inventory_model' => RawMaterialBigStoreDetail::class,
                'inventory_store_column' => 'code',
                'inventory_entity_column' => 'r_material_id',
            ],
            'raw_material_extra_big' => [
                'date_column' => 'date',
                'scope' => 'raw_material',
                'entity_relation' => 'rawMaterial',
                'report_entity_column' => 'r_material_id',
                'store_column' => 'code_store',
                'report_model' => RawMaterialExtraBigReport::class,
                'entity_model' => RawMaterial::class,
                'store_model' => RawMaterialExtraBigStore::class,
                'inventory_model' => RawMaterialExtraBigStoreDetail::class,
                'inventory_store_column' => 'code',
                'inventory_entity_column' => 'r_material_id',
            ],
            'raw_material_small' => [
                'date_column' => 'date',
                'scope' => 'raw_material',
                'entity_relation' => 'rawMaterial',
                'report_entity_column' => 'r_material_id',
                'store_column' => 'code_store',
                'report_model' => RawMaterialSmallReport::class,
                'entity_model' => RawMaterial::class,
                'store_model' => RawMaterialSmallStore::class,
                'inventory_model' => RawMaterialSmallStoreDetail::class,
                'inventory_store_column' => 'code',
                'inventory_entity_column' => 'r_material_id',
            ],
        ];
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /**
     * Config for StockReportFilter::apply().
     */
    public static function filterConfig(string $context, array $overrides = []): array
    {
        $ctx = self::get($context);
        if (!$ctx) {
            return $overrides;
        }

        return array_merge([
            'date_column' => $ctx['date_column'],
            'item_column' => $ctx['report_entity_column'],
            'store_column' => $ctx['store_column'],
            'report_model' => $ctx['report_model'],
            'entity_model' => $ctx['entity_model'],
            'store_model' => $ctx['store_model'],
            'store_context' => $context,
            'default_limit' => 100,
        ], $overrides);
    }
}
