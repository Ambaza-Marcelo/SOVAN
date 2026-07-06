<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\RawMaterialSmallStoreDetail;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RawMaterialSmallStoreExport implements FromCollection, WithMapping, WithHeadings
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
        return RawMaterialSmallStoreDetail::select(
                        DB::raw('id,r_material_id,quantity,unit,cump,cump'))->where('r_material_id','!=','')->where('code',$this->code)->get();
    }

    public function map($data) : array {
        return [
            $data->id,
            $data->rawMaterial->name,
            $data->rawMaterial->code,
            $data->quantity,
            $data->rawMaterial->rawMaterialMeasurement->production_unit,
            number_format($data->cump,0,',',' '),
            number_format(($data->cump * $data->quantity),0,',',' '),
        ] ;
 
 
    }

    public function headings() : array {
        return [
            '#',
            'Article',
            'Code',
            'Quantite',
            'Unité',
            'CUMP',
            'Total CMP',
        ] ;
    }
}
