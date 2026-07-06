<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StockReportArticleSearch
{
    public static function search(Request $request): array
    {
        $term = trim((string) $request->get('q', ''));
        $codeStore = trim((string) $request->get('code_store', ''));
        $storeContext = (string) $request->get('store_context', '');
        $limit = (int) $request->get('limit', 30);

        if ($limit < 1) {
            $limit = 30;
        }
        if ($limit > 50) {
            $limit = 50;
        }

        if ($codeStore === '' || $term === '') {
            return [];
        }

        $ctx = StockReportContext::get($storeContext);
        if (!$ctx) {
            return [];
        }

        $filterConfig = StockReportContext::filterConfig($storeContext);
        $registeredCodes = StockReportFilter::registeredStoreCodes($filterConfig);

        $fromMovements = self::searchInReportMovements($ctx, $filterConfig, $codeStore, $registeredCodes, $term, $limit);
        if (!empty($fromMovements)) {
            return $fromMovements;
        }

        return self::searchInInventory($ctx, $codeStore, $term, $limit);
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @param  array<string, mixed>  $filterConfig
     */
    protected static function searchInReportMovements(
        array $ctx,
        array $filterConfig,
        string $codeStore,
        array $registeredCodes,
        string $term,
        int $limit
    ): array {
        $reportModel = $ctx['report_model'];
        $entityColumn = $ctx['report_entity_column'];
        $storeColumn = $ctx['store_column'];
        $relation = $ctx['entity_relation'];

        $storeCodes = StockReportFilter::resolveStoreCodes($codeStore, array_merge($filterConfig, [
            'registered_store_codes' => $registeredCodes,
        ]));

        /** @var Builder $query */
        $query = $reportModel::query()
            ->whereIn($storeColumn, $storeCodes)
            ->whereNotNull($entityColumn)
            ->where($entityColumn, '!=', '');

        if ($term !== '') {
            $query->whereHas($relation, function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('code', 'like', '%' . $term . '%');
            });
        }

        $entityIds = $query
            ->select($entityColumn)
            ->distinct()
            ->limit($limit * 3)
            ->pluck($entityColumn)
            ->filter()
            ->unique()
            ->take($limit)
            ->values()
            ->all();

        if (empty($entityIds)) {
            return [];
        }

        return self::loadEntityResults($ctx, $entityIds, $term, $limit);
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    protected static function searchInInventory(array $ctx, string $codeStore, string $term, int $limit): array
    {
        $inventoryModel = $ctx['inventory_model'];
        $entityColumn = $ctx['inventory_entity_column'];
        $storeColumn = $ctx['inventory_store_column'];

        /** @var Builder $query */
        $query = $inventoryModel::query()
            ->where($storeColumn, $codeStore)
            ->whereNotNull($entityColumn)
            ->where($entityColumn, '!=', '');

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('code', 'like', '%' . $term . '%');
            });
        }

        $entityIds = $query
            ->orderBy('name')
            ->limit($limit)
            ->pluck($entityColumn)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($entityIds)) {
            return [];
        }

        return self::loadEntityResults($ctx, $entityIds, $term, $limit);
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @param  array<int, mixed>  $entityIds
     */
    protected static function loadEntityResults(array $ctx, array $entityIds, string $term, int $limit): array
    {
        $entityModel = $ctx['entity_model'];

        $query = $entityModel::query()->whereIn('id', $entityIds);

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('code', 'like', '%' . $term . '%');
            });
        }

        return $query
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'code'])
            ->map(function ($row) {
                $label = trim(($row->code ?? '') . ' - ' . ($row->name ?? ''));

                return [
                    'id' => (int) $row->id,
                    'text' => $label !== '-' ? $label : ($row->name ?? ''),
                    'name' => $row->name ?? '',
                    'code' => $row->code ?? '',
                    'source' => 'stock_report',
                ];
            })
            ->values()
            ->all();
    }
}
