<?php

namespace App\Exports;

use App\Models\RawMaterialBigReport;
use App\Support\StockReportContext;
use App\Support\StockReportFilter;
use App\Support\StockReportInventoryNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RawMaterialMdStoreReportExport implements FromCollection, WithMapping, WithHeadings
{
    protected function filterConfig(): array
    {
        return StockReportContext::filterConfig('raw_material_big');
    }

    public function collection()
    {
        $request = request();
        if (!StockReportFilter::requiresDateRange($request)) {
            return collect();
        }

        $query = RawMaterialBigReport::with('rawMaterial')->select(
            DB::raw('id,created_at,date,created_by,r_material_id,code_store,document_no,type_transaction,quantity_stock_initial,cump,value_stock_initial,quantity_stockin,value_stockin,quantity_reception,value_reception,quantity_transfer,value_transfer,transfer_no,quantity_stockout,value_stockout,quantity_stock_final,value_stock_final,description')
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
            $data->rawMaterial ? $data->rawMaterial->name : 'N/A',
            $data->rawMaterial ? $data->rawMaterial->code : '',
            $data->quantity_stock_initial,
            $data->cump,
            ($data->quantity_stock_initial * $data->cump),
            $data->quantity_stockin + $data->quantity_reception,
            ($data->quantity_stockin + $data->quantity_reception) * $data->cump,
            $data->quantity_stockout + $data->quantity_transfer,
            (($data->quantity_stockout * $data->cump) + ($data->quantity_transfer * $data->cump)),
            ($data->quantity_stock_initial + $data->quantity_stockin + $data->quantity_reception) - ($data->quantity_stockout + $data->quantity_transfer),
            ((($data->quantity_stock_initial + $data->quantity_stockin + $data->quantity_reception) - ($data->quantity_stockout + $data->quantity_transfer)) * $data->cump),
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
            'Quantite Stock Initial',
            'C.U.M.P',
            'Valeur Stock Initial',
            'Quantite Entree',
            'Valeur Entree',
            'Quantite Sortie',
            'Valeur Sortie',
            'Quantite Stock Final',
            'Valeur Stock Final',
            'Type Transaction',
            'No Document',
            'Auteur',
            'Description',
        ];
    }
}
