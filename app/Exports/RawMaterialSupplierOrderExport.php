<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\RawMaterialSupplierOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RawMaterialSupplierOrderExport implements FromCollection, WithMapping, WithHeadings
{
    public function collection()
    {
        $d1 = request()->input('date_debut') ?? request()->input('start_date');
        $d2 = request()->input('date_fin') ?? request()->input('end_date');
        if (!$d1 || !$d2) {
            return collect();
        }
        $start = Carbon::parse($d1)->startOfDay();
        $end = Carbon::parse($d2)->endOfDay();
        return RawMaterialSupplierOrder::with('supplier')->whereBetween('date', [$start, $end])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function map($row): array
    {
        $status = $row->status == 1 ? 'Encours' : ($row->status == 2 ? 'Validé' : ($row->status == 3 ? 'Confirmé' : ($row->status == 4 ? 'Approuvé' : ($row->status == -1 ? 'Rejeté' : 'Reception totale'))));
        return [
            $row->id ?? '',
            $row->date ? Carbon::parse($row->date)->format('d/m/Y') : '',
            $row->order_no ?? '',
            $row->order_signature ?? '',
            $row->purchase_no ?? '',
            $row->supplier ? $row->supplier->supplier_name : '',
            $status,
            $row->description ?? '',
            $row->created_by ?? '',
        ];
    }

    public function headings(): array
    {
        return ['#', 'Date', 'Order No', 'Signature', 'Purchase No', 'Fournisseur', 'Statut', 'Description', 'Auteur'];
    }
}
