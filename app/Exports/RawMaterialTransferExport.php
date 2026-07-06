<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\RawMaterialTransfer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RawMaterialTransferExport implements FromCollection, WithMapping, WithHeadings
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
        return RawMaterialTransfer::whereBetween('date', [$start, $end])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function map($row): array
    {
        $status = $row->status == 1 ? 'Encours' : ($row->status == 2 ? 'Validé' : ($row->status == 3 ? 'Confirmé' : ($row->status == 4 ? 'Approuvé' : ($row->status == -1 ? 'Rejeté' : ''))));
        $origin = $row->origin_store_id ? 'STOCK INTERMEDIAIRE' : 'GRAND STOCK';
        $dest = $row->destination_store_id ? 'PETIT STOCK' : 'STOCK INTERMEDIAIRE';
        return [
            $row->id ?? '',
            $row->date ? Carbon::parse($row->date)->format('d/m/Y') : '',
            $row->transfer_no ?? '',
            $row->transfer_signature ?? '',
            $row->requisition_no ?? '',
            $origin,
            $dest,
            $status,
            $row->description ?? '',
            $row->created_by ?? '',
        ];
    }

    public function headings(): array
    {
        return ['#', 'Date', 'Transfer No', 'Signature', 'Requisition No', 'Stock origine', 'Stock destination', 'Statut', 'Description', 'Auteur'];
    }
}
