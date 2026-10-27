<?php

namespace App\Exports\Immobilisations;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Onglet cascade des plans d'amortissement (lisible pour un comptable).
 * Chaque immobilisation : en-tête + tableau annuel + total annuités (=SUM).
 */
class ImmobilisationsPlansSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    /** @var Collection */
    protected $rows;

    /** @var array<int, array{annuite_col:string, first:int, last:int, total:int}> */
    protected $sumRanges = [];

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function title(): string
    {
        return 'Plans amortissement';
    }

    public function array(): array
    {
        $data = [];
        $data[] = ['PLANS D\'AMORTISSEMENT LINÉAIRE (convention 360 jours)'];
        $data[] = [];
        $excelRow = 3; // 1-based after title + blank

        foreach ($this->rows as $asset) {
            $data[] = [
                'Code : ' . $asset['code'],
                $asset['designation'],
                'Catégorie : ' . $asset['categorie'],
                'Acquisition : ' . $asset['date_acquisition'],
                'Mode : ' . $asset['mode'],
                'Durée : ' . $asset['duree'] . ' an(s)',
            ];
            $excelRow++;

            $data[] = [
                'Année',
                'Base de calcul',
                'Annuité (dotation)',
                'Amortissements cumulés',
                'VNC fin de période',
                'Jours (prorata)',
            ];
            $headerRow = $excelRow;
            $excelRow++;

            $plan = $asset['plan'] ?? [];
            if (empty($plan)) {
                $data[] = ['—', 'Plan non disponible (mise en service non renseignée)', '', '', '', ''];
                $excelRow++;
                $data[] = [];
                $excelRow++;
                continue;
            }

            $firstPlanRow = $excelRow;
            foreach ($plan as $line) {
                $data[] = [
                    (int) $line['year'],
                    (float) $line['base'],
                    (float) $line['annuite'],
                    (float) $line['cumul'],
                    (float) $line['vnc'],
                    (int) $line['days'] . (!empty($line['is_prorata']) ? ' (prorata)' : ''),
                ];
                $excelRow++;
            }
            $lastPlanRow = $excelRow - 1;

            $data[] = [
                'TOTAL ANNUITÉS',
                '',
                0, // remplacé par =SUM dans AfterSheet
                '',
                '',
                '',
            ];
            $totalRow = $excelRow;
            $this->sumRanges[] = [
                'first' => $firstPlanRow,
                'last' => $lastPlanRow,
                'total' => $totalRow,
                'header' => $headerRow,
            ];
            $excelRow++;

            $data[] = [];
            $excelRow++;
        }

        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

                foreach ($this->sumRanges as $range) {
                    $sheet->getStyle('A' . $range['header'] . ':F' . $range['header'])->getFont()->setBold(true);
                    $sheet->getStyle('A' . $range['header'] . ':F' . $range['header'])->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E8F5E9');

                    $sheet->setCellValue(
                        'C' . $range['total'],
                        '=SUM(C' . $range['first'] . ':C' . $range['last'] . ')'
                    );
                    $sheet->getStyle('A' . $range['total'] . ':F' . $range['total'])->getFont()->setBold(true);

                    $sheet->getStyle('B' . $range['first'] . ':E' . $range['total'])
                        ->getNumberFormat()->setFormatCode('#,##0.00');

                    $sheet->getStyle('A' . $range['header'] . ':F' . $range['total'])
                        ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
            },
        ];
    }
}
