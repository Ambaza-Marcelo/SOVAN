<?php

namespace App\Exports;

use App\Models\RawMaterialSmallReport;
use App\Support\StockReportContext;
use App\Support\StockReportFilter;
use App\Support\StockReportInventoryNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RawMaterialSmStoreReportExport implements FromCollection, WithMapping, WithHeadings
{
    protected function filterConfig(): array
    {
        return StockReportContext::filterConfig('raw_material_small');
    }

    public function collection()
    {
        $request = request();
        if (!StockReportFilter::requiresDateRange($request)) {
            return collect();
        }

        $query = RawMaterialSmallReport::with('rawMaterial')->select(
            DB::raw('id,date,created_at,r_material_id,code_store,quantity_stock_initial,value_stock_initial,quantity_stockin,value_stockin,quantity_stockout,value_stockout,quantity_stock_final,value_stock_final,cump,type_transaction,document_no,created_by,description')
        );

        StockReportFilter::apply($query, $request, $this->filterConfig());

        return StockReportInventoryNormalizer::normalizeCollection(
            $query->orderBy('id', 'desc')->get()
        );
    }

    public function map($data): array
    {
        return [
            $data->id,
            Carbon::parse($data->created_at)->format('d/m/Y H:i:s'),
            Carbon::parse($data->date)->format('d/m/Y'),
            $data->rawMaterial ? $data->rawMaterial->name : '',
            $data->rawMaterial ? $data->rawMaterial->code : '',
            $data->quantity_stock_initial ?? '',
            ($data->rawMaterial && $data->rawMaterial->rawMaterialMeasurement) ? $data->rawMaterial->rawMaterialMeasurement->purchase_unit : '',
            $data->cump,
            $data->quantity_stockin,
            $data->quantity_stockin ?? '',
            $data->quantity_stockout,
            $data->quantity_stockout * $data->cump,
            $data->quantity_stock_final ?? '',
            $data->type_transaction,
            $data->document_no,
            $data->created_by,
            $data->description,
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Date de Saisie',
            'Date Operation',
            'Article',
            'Code',
            'Stock Initial',
            'Unité de mesure',
            'C.U.M.P',
            'Entrée',
            'Portionnage',
            'Quantité Sortie',
            'Valeur Sortie',
            'Stock Final',
            'Type Transaction',
            'No Document',
            'Auteur',
            'Description',
        ];
    }
}
