<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\ProductionPlan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductionPlanExport implements FromCollection, WithMapping, WithHeadings
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
        return ProductionPlan::whereBetween('production_plan_date', [$start, $end])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function map($row): array
    {
        $status = $row->status == 2 ? 'Validé' : ($row->status == -1 ? 'Rejeté' : ($row->status == 3 ? 'Confirmé' : ($row->status == 4 ? 'Approuvé' : 'En cours')));
        return [
            $row->id ?? '',
            $row->production_plan_date ? Carbon::parse($row->production_plan_date)->format('d/m/Y') : '',
            $row->prodution_plan_number ?? '',
            $row->production_signature ?? '',
            $status,
            $row->total_estimated_cost ?? 0,
            $row->created_by ?? '',
        ];
    }

    public function headings(): array
    {
        return ['#', 'Date', 'N° Plan', 'Signature', 'État', 'Coût total', 'Auteur'];
    }
}
