<?php

namespace App\Exports\Accounting;

use App\Models\Accounting\AccountParentAccount;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ChartAccount implements FromCollection, WithStyles, WithColumnWidths, WithEvents
{
    /**
     * Indices des lignes des comptes principaux (parent) pour le style vert (1-based).
     * @var array
     */
    protected $parentRowIndices = [];

    /**
     * Nombre total de lignes de données (titre + comptes).
     * @var int
     */
    protected $totalRows = 0;

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $setting = Setting::first();
        $companyName = $setting ? ($setting->name ?? $setting->company_name ?? 'MUSUMBA ELCOM') : 'MUSUMBA ELCOM';
        $title = 'PLAN COMPTABLE DE LA SOCIETE ' . strtoupper($companyName);

        $rows = collect([
            [$title, ''], // Ligne de titre (sera fusionnée A1:B1 dans AfterSheet)
        ]);

        $parentAccounts = AccountParentAccount::with(['account' => function ($q) {
            $q->orderBy('account_number', 'asc');
        }])->orderBy('account_number', 'asc')->get();

        $currentRow = 2; // Après la ligne de titre

        foreach ($parentAccounts as $parent) {
            $rows->push([$parent->account_number, $parent->account_name]);
            $this->parentRowIndices[] = $currentRow;
            $currentRow++;

            foreach ($parent->account as $child) {
                $rows->push([$child->account_number, $child->account_name]);
                $currentRow++;
            }
        }

        $this->totalRows = $currentRow - 1;

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];

        $lightGreen = 'C6EFCE'; // Vert clair comme sur le modèle
        foreach ($this->parentRowIndices as $rowIndex) {
            $styles[$rowIndex] = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $lightGreen],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];
        }

        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 65,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells('A1:B1');

                // Bordures sur toute la plage de données
                $lastRow = $this->totalRows > 0 ? $this->totalRows : 2;
                $range = 'A1:B' . $lastRow;
                $sheet->getStyle($range)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}
