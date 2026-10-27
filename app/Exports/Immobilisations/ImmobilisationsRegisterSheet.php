<?php

namespace App\Exports\Immobilisations;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Onglet résumé du registre des immobilisations.
 * Totaux via formules Excel natives (=SUM).
 */
class ImmobilisationsRegisterSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    /** @var Collection */
    protected $rows;

    /** @var string */
    protected $exportedAt;

    public function __construct(Collection $rows, string $exportedAt)
    {
        $this->rows = $rows;
        $this->exportedAt = $exportedAt;
    }

    public function title(): string
    {
        return 'Registre';
    }

    public function array(): array
    {
        $data = [];
        $data[] = ['REGISTRE DES IMMOBILISATIONS'];
        $data[] = ['Extraction : ' . $this->exportedAt];
        $data[] = [];
        $data[] = [
            'Code immo',
            'Désignation',
            'Catégorie',
            'Date d\'acquisition',
            'Valeur d\'origine (HT)',
            'Mode',
            'Durée (ans)',
            'Amort. cumulés',
            'VNC',
            'Compte immobilisation',
            'En service',
        ];

        foreach ($this->rows as $row) {
            $data[] = [
                $row['code'],
                $row['designation'],
                $row['categorie'],
                $row['date_acquisition'],
                (float) $row['valeur_origine'],
                $row['mode'],
                (int) $row['duree'],
                (float) $row['amortissements_cumules'],
                (float) $row['vnc'],
                $row['compte_immo'],
                !empty($row['en_service']) ? 'Oui' : 'Non',
            ];
        }

        // Ligne totaux : formules =SUM placées dans AfterSheet (besoin du n° de ligne)
        $data[] = [
            '',
            '',
            '',
            'TOTAUX',
            0,
            '',
            '',
            0,
            0,
            '',
            '',
        ];

        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $count = $this->rows->count();
                $headerRow = 4;
                $firstDataRow = 5;
                $totalRow = $headerRow + 1 + $count; // ligne TOTAUX
                $lastDataRow = $count > 0 ? ($totalRow - 1) : null;

                $sheet->mergeCells('A1:K1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A' . $headerRow . ':K' . $headerRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $headerRow . ':K' . $headerRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D9EAF7');

                if ($count > 0 && $lastDataRow !== null) {
                    $sheet->setCellValue('E' . $totalRow, '=SUM(E' . $firstDataRow . ':E' . $lastDataRow . ')');
                    $sheet->setCellValue('H' . $totalRow, '=SUM(H' . $firstDataRow . ':H' . $lastDataRow . ')');
                    $sheet->setCellValue('I' . $totalRow, '=SUM(I' . $firstDataRow . ':I' . $lastDataRow . ')');
                    $sheet->getStyle('E' . $firstDataRow . ':E' . $totalRow)
                        ->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('H' . $firstDataRow . ':I' . $totalRow)
                        ->getNumberFormat()->setFormatCode('#,##0.00');
                }

                $sheet->getStyle('A' . $totalRow . ':K' . $totalRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $headerRow . ':K' . $totalRow)
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('E' . $firstDataRow . ':I' . $totalRow)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}
