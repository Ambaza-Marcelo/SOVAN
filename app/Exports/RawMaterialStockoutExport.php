<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\RawMaterialStockout;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RawMaterialStockoutExport implements FromCollection, WithMapping, WithHeadings
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
        return RawMaterialStockout::whereBetween('date', [$start, $end])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function map($row): array
    {
        $status = $row->status == 1 ? 'Encours' : ($row->status == 2 ? 'Validé' : ($row->status == 3 ? 'Confirmé' : ($row->status == 4 ? 'Approuvé' : ($row->status == -1 ? 'Rejeté' : ''))));
        return [
            $row->id ?? '',
            $row->date ? Carbon::parse($row->date)->format('d/m/Y') : '',
            $row->stockout_no ?? '',
            $row->stockout_signature ?? '',
            $status,
            $row->asker ?? '',
            $row->origin_sm_store_id ? 'Petit stock' : ($row->origin_bg_store_id ? 'Stock intermédiaire' : ''),
            $row->destination ?? '',
            $row->created_by ?? '',
            $row->description ?? '',
        ];
    }

    public function headings(): array
    {
        return ['#', 'Date', 'Stockout No', 'Signature', 'Statut', 'Demandeur', 'Stock origine', 'Destination', 'Auteur', 'Description'];
    }
}
