<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\RawMaterialRequisition;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RawMaterialRequisitionExport implements FromCollection, WithMapping, WithHeadings
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
        return RawMaterialRequisition::whereBetween('date', [$start, $end])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function map($row): array
    {
        $status = $row->status == 2 ? 'Validé' : ($row->status == -1 ? 'Rejeté' : 'En cours');
        return [
            $row->id ?? '',
            $row->date ? Carbon::parse($row->date)->format('d/m/Y') : '',
            $row->requisition_no ?? '',
            $row->requisition_signature ?? '',
            $status,
            $row->description ?? '',
            $row->created_by ?? '',
        ];
    }

    public function headings(): array
    {
        return ['#', 'Date', 'Requisition No', 'Signature', 'Statut', 'Description', 'Auteur'];
    }
}
