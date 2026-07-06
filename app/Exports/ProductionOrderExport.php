<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\ProductionOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductionOrderExport implements FromCollection, WithMapping, WithHeadings
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
        return ProductionOrder::whereBetween('production_date', [$start, $end])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function map($row): array
    {
        $status = $row->status == 1 ? 'Validé' : ($row->status == -1 ? 'Rejeté' : ($row->status == 2 ? 'Validé' : ''));
        return [
            $row->id ?? '',
            $row->production_date ? Carbon::parse($row->production_date)->format('d/m/Y') : '',
            $row->prodution_number ?? '',
            $row->production_signature ?? '',
            $status,
            $row->work_center ?? '',
            $row->total_estimated_cost ?? 0,
            $row->created_by ?? '',
        ];
    }

    public function headings(): array
    {
        return ['#', 'Date', 'Production No', 'Signature', 'Statut', 'Work Center', 'Coût total', 'Auteur'];
    }
}
