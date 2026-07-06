<?php

namespace App\Exports;

use App\Models\MaterialBigReport;
use App\Support\StockReportContext;
use App\Support\StockReportFilter;
use App\Support\StockReportInventoryNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MaterialMdStoreReportExport implements FromCollection, WithMapping, WithHeadings
{
    protected function filterConfig(): array
    {
        return StockReportContext::filterConfig('material_big');
    }

    public function collection()
    {
        $request = request();
        if (!StockReportFilter::requiresDateRange($request)) {
            return collect();
        }

        $query = MaterialBigReport::with('material')->select(
            DB::raw('id,created_at,material_id,code_store,quantity_stock_initial,value_stock_initial,quantity_stockin,value_stockin,quantity_reception,value_reception,quantity_transfer,value_transfer,quantity_stockout,value_stockout,quantity_stock_final,value_stock_final,type_transaction')
        );

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
        $qtyIn = $data->quantity_stockin + $qtyReception;
        $valIn = $data->value_stockin + $valReception;
        $qtyOut = $data->quantity_stockout + $qtyTransfer;
        $valOut = ($data->quantity_stockout * $cump) + ($qtyTransfer * $cump);
        $qtyFinal = ($data->quantity_stock_initial + $data->quantity_stockin + $qtyReception) - ($data->quantity_stockout + $qtyTransfer);
        $valFinal = $qtyFinal * $cump;

        return [
            $data->id ?? '',
            Carbon::parse($data->created_at)->format('d-m-Y'),
            $data->material ? $data->material->name : 'N/A',
            $data->material ? $data->material->code : '',
            $data->quantity_stock_initial,
            $data->quantity_stock_initial * $cump,
            $qtyIn,
            $valIn,
            $qtyOut,
            $valOut,
            $qtyFinal,
            $valFinal,
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
