<?php

namespace App\Exports\Accounting;

use App\Models\Accounting\AccountJournalEntryDetail;
use App\Support\AccountingLedgerBuilder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LedgerExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $dateDebut;
    protected $dateFin;
    protected $accountIds;
    /** @var array<int, string> */
    protected $pieceDocumentTokens;
    protected $ledgerRows;

    /**
     * @param  array<int>|null  $accountIds
     * @param  array<int, string>  $pieceDocumentTokens
     */
    public function __construct($dateDebut, $dateFin, $accountIds = null, array $pieceDocumentTokens = [])
    {
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->accountIds = $accountIds;
        $this->pieceDocumentTokens = $pieceDocumentTokens;
        $this->prepareData();
    }

    protected function prepareData()
    {
        if (is_array($this->accountIds) && $this->accountIds === []) {
            $this->ledgerRows = collect([]);
            return;
        }

        $query = AccountJournalEntryDetail::with('account')
            ->whereBetween('date', [$this->dateDebut, $this->dateFin]);

        if ($this->accountIds && is_array($this->accountIds) && count($this->accountIds) > 0) {
            $query->whereIn('account_id', $this->accountIds);
        }

        if (!empty($this->pieceDocumentTokens)) {
            $query->wherePieceOrDocumentIn($this->pieceDocumentTokens);
        }

        $datas = $query->orderBy('date')->orderBy('id')->get();
        $ledgerData = AccountingLedgerBuilder::buildFromEntries($datas, __('messages.account_undefined'));

        $this->ledgerRows = collect($ledgerData['ledgerRows']);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->ledgerRows;
    }

    public function map($row): array
    {
        return [
            $row['account_label'],
            $row['operation_date'],
            $row['created_at'],
            $row['cj'],
            $row['document_no'],
            $row['label'],
            number_format($row['debit'], 0, ',', ' '),
            number_format($row['credit'], 0, ',', ' '),
            number_format($row['balance_debit'], 0, ',', ' '),
            number_format($row['balance_credit'], 0, ',', ' '),
        ];
    }

    public function headings(): array
    {
        return [
            'Compte',
            'Date d\'opération',
            'Date de création',
            'N° PIÈCE',
            'No Document',
            'Libellé écriture',
            'Mouvements - Débit',
            'Mouvements - Crédit',
            'Soldes - Débit',
            'Soldes - Crédit',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 14,
            'C' => 16,
            'D' => 8,
            'E' => 15,
            'F' => 35,
            'G' => 18,
            'H' => 18,
            'I' => 18,
            'J' => 18,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Grand Livre';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $rows = $this->ledgerRows;
                foreach ($rows as $index => $row) {
                    if (!empty($row['is_first_of_account'])) {
                        $excelRow = $index + 2;
                        $sheet->getStyle('A' . $excelRow . ':J' . $excelRow)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('E9F1FB');
                    }
                }
            },
        ];
    }
}
