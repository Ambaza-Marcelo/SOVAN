<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\MaterialPurchaseDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MaterialPurchaseExport implements FromCollection, WithMapping, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {

        $d1 = request()->input('date_debut') ?? request()->input('start_date');
        $d2 = request()->input('date_fin') ?? request()->input('end_date');
        if (!$d1 || !$d2) {
            return collect();
        }
        $startDate = \Carbon\Carbon::parse($d1)->format('Y-m-d');
        $endDate = \Carbon\Carbon::parse($d2)->format('Y-m-d');

        $start_date = $startDate.' 00:00:00';
        $end_date = $endDate.' 23:59:59';

        return MaterialPurchaseDetail::with(['material', 'item'])
            ->whereBetween('date', [$start_date, $end_date])
            ->orderBy('id', 'asc')
            ->get();
    }

    public function map($data) : array {

        if ($data->status == '1') {
            $status = "ENCOURS....";
        }elseif ($data->status == '-1') {
            $status = "REJETE";
        }elseif ($data->status == '2') {
            $status = "VALIDE";
        }elseif ($data->status == '3') {
            $status = "CONFIRME";
        }elseif ($data->status == '4') {
        	$status = "APPROUVE";
        }else{
            $status = "";
        }

        $libelle = $data->material ? $data->material->name : ($data->item ? $data->item->name : 'N/A');

        return [
            $data->id,
            Carbon::parse($data->date)->format('Y-m-d'),
			$data->purchase_no,
            $libelle,
            $data->quantity,
            $data->price,
            $data->price * $data->quantity,
            $status,
            $data->created_by,
            $data->description,
            $data->validated_by,
            $data->confirmed_by,
            $data->approuved_by,
            $data->rejected_by
        ] ;
 
 
    }

    public function headings() : array {
        return [
            '#',
            'Date',
            'BDA',
            'Libellé',
            'Quantité Demandé',
            'P.A Estimatif',
            'TOTAL P.A Estimatif',
            'ETAT',
            'Auteur',
            'Description',
            'Valide Par',
            'Confirme par',
            'Approuve par',
            'Rejete Par'
        ] ;
    }
}
