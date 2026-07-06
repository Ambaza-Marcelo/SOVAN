<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\RawMaterialReceptionDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RawMaterialReceptionExport implements FromCollection, WithMapping, WithHeadings
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

        return RawMaterialReceptionDetail::with(['rawMaterial.rawMaterialMeasurement', 'supplier'])
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

        if (!empty($data->supplier_id)) {
            $supplier = $data->supplier->supplier_name;
        }else{
            $supplier = "";
        }

        return [
            $data->id,
            Carbon::parse($data->date)->format('Y-m-d'),
			$data->purchase_no,
			$data->order_no,
			$data->reception_no,
			$data->invoice_no,
            $supplier,
            $data->handingover,
            $data->receptionist,
            $data->rawMaterial ? $data->rawMaterial->name : 'N/A',
            $data->quantity_ordered,
            $data->quantity_received,
            ($data->rawMaterial && $data->rawMaterial->rawMaterialMeasurement) ? $data->rawMaterial->rawMaterialMeasurement->purchase_unit : '',
            $data->purchase_price,
            $data->price_nvat,
            $data->vat,
            $data->vat_rate,
            $data->price_wvat,
            $status,
            $data->created_by,
            $data->validated_by,
            $data->confirmed_by,
            $data->approuved_by,
            $data->rejected_by,
            $data->description
        ] ;
 
 
    }

    public function headings() : array {
        return [
            '#',
            'Date',
            'BDA',
            'BC',
            'No Reception',
            'No Facture',
            'Nom du Fournisseur',
            'Remettant',
            'Receptionniste',
            'Libellé',
            'Quantité Commandé',
            'Quantité Recu',
            'Unité de mesure',
            'PU HTVA',
            'PT HTVA',
            'TVA',
            'TAUX TVA',
            'TVAC',
            'ETAT',
            'Auteur',
            'Valide Par',
            'Confirme par',
            'Approuve par',
            'Rejete Par',
            'Description'
        ] ;
    }
}