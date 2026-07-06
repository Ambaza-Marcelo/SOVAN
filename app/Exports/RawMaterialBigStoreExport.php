<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\RawMaterialBigStoreDetail;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RawMaterialBigStoreExport implements FromCollection, WithMapping, WithHeadings
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
        return RawMaterialBigStoreDetail::select(
                        DB::raw('id,r_material_id,quantity,unit,purchase_price,cump'))->where('r_material_id','!=','')->where('code',$this->code)->get();
    }

    public function map($data) : array {
        return [
            $data->id,
            $data->rawMaterial->name,
            $data->rawMaterial->code,
            $data->quantity,
            $data->rawMaterial->rawMaterialMeasurement->purchase_unit,
            $data->cump,
            $data->purchase_price,
            ($data->cump * $data->quantity),
        ] ;
 
 
    }

    public function headings() : array {
        return [
            '#',
            'Article',
            'Code',
            'Quantite',
            'Unité',
            'C.U.M.P',
            'P.A',
            'Total CMP',
        ] ;
    }
}
