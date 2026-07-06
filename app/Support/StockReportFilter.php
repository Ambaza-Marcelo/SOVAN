<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StockReportFilter
{
    /**
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}|null
     */
    public static function dateRange(Request $request): ?array
    {
        $start = $request->input('date_debut') ?? $request->input('start_date');
        $end = $request->input('date_fin') ?? $request->input('end_date');

        if (!$start || !$end) {
            return null;
        }

        return [
            Carbon::parse($start)->startOfDay(),
            Carbon::parse($end)->endOfDay(),
        ];
    }

    public static function requiresDateRange(Request $request): bool
    {
        return self::dateRange($request) !== null;
    }

    /**
     * @return array<int, string>
     */
    public static function registeredStoreCodes(array $config): array
    {
        if (!empty($config['registered_store_codes'])) {
            return array_values(array_filter($config['registered_store_codes']));
        }

        $storeModel = $config['store_model'] ?? null;
        if ($storeModel && class_exists($storeModel)) {
            return $storeModel::query()->pluck('code')->filter()->values()->all();
        }

        return [];
    }

    /**
     * Inclut les codes stock « orphelins » présents dans le rapport mais absents du référentiel.
     *
     * @return array<int, string>
     */
    public static function resolveStoreCodes(string $selectedCode, array $config): array
    {
        $selectedCode = trim($selectedCode);
        if ($selectedCode === '') {
            return [];
        }

        $storeColumn = $config['store_column'] ?? 'code_store';
        $reportModel = $config['report_model'] ?? null;
        $registeredCodes = self::registeredStoreCodes($config);
        $codes = [$selectedCode];

        if (!$reportModel || !class_exists($reportModel)) {
            return $codes;
        }

        if (empty($registeredCodes) || in_array($selectedCode, $registeredCodes, true)) {
            $orphans = $reportModel::query()
                ->whereNotNull($storeColumn)
                ->where($storeColumn, '!=', '')
                ->when(!empty($registeredCodes), function ($query) use ($storeColumn, $registeredCodes) {
                    $query->whereNotIn($storeColumn, $registeredCodes);
                })
                ->distinct()
                ->pluck($storeColumn)
                ->filter()
                ->values()
                ->all();

            if (!empty($orphans)) {
                $codes = array_values(array_unique(array_merge($codes, $orphans)));
            }
        }

        return $codes;
    }

    public static function selectedArticlesForView(Request $request, array $config): Collection
    {
        $articleId = (int) $request->input('article_id');
        if (!$articleId || !$request->filled('code_store')) {
            return collect();
        }

        $entityModel = $config['entity_model'] ?? null;
        if (!$entityModel || !class_exists($entityModel)) {
            return collect();
        }

        $article = $entityModel::query()->find($articleId);

        return $article ? collect([$article]) : collect();
    }

    /**
     * @param  Builder  $query
     * @param  array<string, mixed>  $config
     */
    public static function apply(Builder $query, Request $request, array $config = []): Builder
    {
        $dateColumn = $config['date_column'] ?? 'date';
        $itemColumn = $config['item_column'] ?? null;
        $storeColumn = $config['store_column'] ?? 'code_store';
        $defaultLimit = $config['default_limit'] ?? 100;

        $range = self::dateRange($request);

        if ($range) {
            $query->whereBetween($dateColumn, $range);
        } elseif (!$request->boolean('apply_filter') && !$request->boolean('export_mode')) {
            $query->limit($defaultLimit);
        }

        if ($storeColumn && $request->filled('code_store')) {
            $codes = self::resolveStoreCodes((string) $request->code_store, $config);
            if (!empty($codes)) {
                $query->whereIn($storeColumn, $codes);
            } else {
                $query->where($storeColumn, $request->code_store);
            }
        }

        $articleId = $request->input('article_id');
        if ($itemColumn && $articleId && $request->filled('code_store')) {
            $query->where($itemColumn, $articleId);
        }

        if ($request->filled('document_no')) {
            $query->where('document_no', 'like', '%' . trim((string) $request->document_no) . '%');
        }

        return $query;
    }
}
