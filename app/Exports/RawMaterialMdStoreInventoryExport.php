<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\RawMaterialBigStoreInventoryDetail;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RawMaterialMdStoreInventoryExport implements FromCollection, WithMapping, WithHeadings
{

    protected $code;

    function __construct($code) {
        $this->code = $code;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return RawMaterialBigStoreInventoryDetail::where('inventory_no',$this->code)->get();
    }

    public function map($data) : array {
        return [
            $data->id,
            $data->rawMaterial->name,
            $data->rawMaterial->code,
            $data->rawMaterial->rawMaterialMeasurement->purchase_unit,
            $data->quantity,
            $data->purchase_price,
            $data->total_purchase_value,
            $data->new_quantity,
            $data->new_purchase_price,
            $data->new_total_purchase_value,
            $data->new_quantity - $data->quantity,
        ] ;
 
 
    }

    public function headings() : array {
        return [
            '#',
            'Article',
            'Code',
            'Unite de Mesure',
            'Qté Théorique',
            'PA Théorique',
            'Valeur Stock Actuelle',
            'Qté Physique',
            'Nouveau PA',
            'Nouvelle V du stock',
            'Relicat'
        ] ;
    }
}