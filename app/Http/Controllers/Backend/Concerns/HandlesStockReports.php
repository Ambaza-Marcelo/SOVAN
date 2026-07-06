<?php

namespace App\Http\Controllers\Backend\Concerns;

use App\Support\StockReportFilter;
use App\Support\StockReportInventoryNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HandlesStockReports
{
    protected function stockReportFilterConfig(): array
    {
        return [
            'date_column' => 'date',
            'item_column' => null,
            'store_column' => 'code_store',
            'default_limit' => 100,
        ];
    }

    protected function stockReportFilterConfigWithStores($stores): array
    {
        return array_merge($this->stockReportFilterConfig(), [
            'registered_store_codes' => $stores->pluck('code')->filter()->values()->all(),
        ]);
    }

    protected function stockReportViewData(Request $request, $stores): array
    {
        $config = $this->stockReportFilterConfigWithStores($stores);

        return [
            'stores' => $stores,
            'articles' => StockReportFilter::selectedArticlesForView($request, $config),
        ];
    }

    protected function orderStockReportById(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('id', $direction);
    }

    protected function normalizeStockReportRows($rows)
    {
        return StockReportInventoryNormalizer::normalizeCollection($rows);
    }

    protected function validateStockReportDates(Request $request, string $redirectRoute)
    {
        if (!StockReportFilter::requiresDateRange($request)) {
            return redirect()->route($redirectRoute)
                ->with('error', __('messages.export_select_date_range'));
        }

        return null;
    }

    protected function mergeExportRequest(Request $request): Request
    {
        $request->merge(['export_mode' => true]);

        return $request;
    }
}
