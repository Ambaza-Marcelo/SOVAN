<?php

namespace App\Exports;

use App\Models\MaterialSmallReport;
use App\Support\StockReportContext;
use App\Support\StockReportFilter;
use App\Support\StockReportInventoryNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MaterialSmStoreReportExport implements FromCollection, WithMapping, WithHeadings
{
    protected function filterConfig(): array
    {
        return StockReportContext::filterConfig('material_small');
    }

    public function collection()
    {
        $request = request();
        if (!StockReportFilter::requiresDateRange($request)) {
            return collect();
        }

        $query = MaterialSmallReport::with('material');

        StockReportFilter::apply($query, $request, $this->filterConfig());

        return StockReportInventoryNormalizer::normalizeCollection(
            $query->orderBy('id', 'desc')->get()
        );
    }

    public function map($data): array
    {
        $cump = $data->material ? ($data->material->cump ?? 0) : 0;
        $qtyReception = $data->quantity_reception ?? 0;
        $valReception = $data->value_reception ?? 0;
        $qtyTransfer = $data->quantity_transfer ?? 0;
        $qtyOut = $data->quantity_stockout + $qtyTransfer;
        $qtyFinal = ($data->quantity_stock_initial + $data->quantity_stockin + $qtyReception) - ($data->quantity_stockout + $qtyTransfer);

        return [
            $data->id ?? '',
            Carbon::parse($data->created_at)->format('d/m/Y'),
            $data->material ? $data->material->name : 'N/A',
            $data->material ? $data->material->code : '',
            $data->quantity_stock_initial,
            ($data->quantity_stock_initial * $cump),
            $data->quantity_stockin + $qtyReception,
            ($data->value_stockin + $valReception),
            $qtyOut,
            ($data->quantity_stockout * $cump) + ($qtyTransfer * $cump),
            $qtyFinal,
            $qtyFinal * $cump,
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Article',
            'Code',
            'Quantite Stock Initial',
            'Valeur Stock Initial',
            'Quantite Entree',
            'Valeur Entree',
            'Quantite Sortie',
            'Valeur Sortie',
            'Quantite Stock Final',
            'Valeur Stock Final',
        ];
    }
}
