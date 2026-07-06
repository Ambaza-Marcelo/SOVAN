<?php

namespace App\Exports;

use App\Models\MaterialExtraBigReport;
use App\Support\StockReportContext;
use App\Support\StockReportFilter;
use App\Support\StockReportInventoryNormalizer;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MaterialExtraBigStoreReportExport implements FromCollection, WithMapping, WithHeadings
{
    protected function filterConfig(): array
    {
        return StockReportContext::filterConfig('material_extra_big');
    }

    public function collection()
    {
        $request = request();
        if (!StockReportFilter::requiresDateRange($request)) {
            return collect();
        }

        $query = MaterialExtraBigReport::with('material')
            ->orderBy('id', 'desc');

        StockReportFilter::apply($query, $request, $this->filterConfig());

        return StockReportInventoryNormalizer::normalizeCollection($query->get());
    }

    public function map($data): array
    {
        $cump = $data->material ? ($data->material->cump ?? 0) : 0;

        return [
            $data->id ?? '',
            Carbon::parse($data->created_at)->format('d-m-Y'),
            $data->material ? $data->material->name : 'N/A',
            $data->material ? $data->material->code : '',
            $data->quantity_stock_initial,
            $data->quantity_stock_initial * $cump,
            $data->quantity_stockin,
            $data->value_stockin,
            $data->quantity_stockout,
            $data->quantity_stockout * $cump,
            $data->quantity_stock_final,
            $data->value_stock_final,
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
