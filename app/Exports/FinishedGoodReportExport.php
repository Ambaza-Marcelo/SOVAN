<?php

namespace App\Exports;

use App\Models\FinishedGoodReport;
use App\Support\StockReportContext;
use App\Support\StockReportFilter;
use App\Support\StockReportInventoryNormalizer;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FinishedGoodReportExport implements FromCollection, WithMapping, WithHeadings
{
    protected function filterConfig(): array
    {
        return StockReportContext::filterConfig('finished_goods');
    }

    public function collection()
    {
        $request = request();
        if (!StockReportFilter::requiresDateRange($request)) {
            return collect();
        }

        $query = FinishedGoodReport::with('item')
            ->orderBy('id', 'desc');

        StockReportFilter::apply($query, $request, $this->filterConfig());

        return StockReportInventoryNormalizer::normalizeCollection($query->get());
    }

    public function map($data): array
    {
        return [
            $data->id ?? '',
            $data->date ? Carbon::parse($data->date)->format('d/m/Y') : ($data->created_at ? Carbon::parse($data->created_at)->format('d/m/Y') : ''),
            $data->code_store ?? '-',
            $data->item ? $data->item->name : 'N/A',
            $data->item ? $data->item->code : 'N/A',
            number_format($data->quantity_stock_initial ?? 0, 2),
            number_format($data->value_stock_initial ?? 0, 2),
            number_format($data->quantity_stockin ?? 0, 2),
            number_format($data->value_stockin ?? 0, 2),
            number_format($data->quantity_stockout ?? 0, 2),
            number_format($data->value_stockout ?? 0, 2),
            number_format($data->quantity_stock_final ?? 0, 2),
            number_format($data->value_stock_final ?? 0, 2),
            number_format($data->cump ?? 0, 2),
            $data->type_transaction ?? 'N/A',
            $data->document_no ?? 'N/A',
            $data->description ?? '-',
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Code magasin',
            'Article',
            'Code',
            'Qté Stock Initial',
            'Val. Stock Initial',
            'Qté Entrée',
            'Val. Entrée',
            'Qté Sortie',
            'Val. Sortie',
            'Qté Stock Final',
            'Val. Stock Final',
            'C.U.M.P',
            'Type Transaction',
            'No Document',
            'Description',
        ];
    }
}
