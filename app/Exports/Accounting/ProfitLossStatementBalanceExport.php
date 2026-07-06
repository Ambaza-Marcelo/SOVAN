<?php

namespace App\Exports\Accounting;

use Carbon\Carbon;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountLedger;
use App\Http\Controllers\Backend\Accounting\ProfitLossStatementController;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProfitLossStatementBalanceExport implements FromCollection, WithMapping, WithHeadings
{
    protected $statementData;

    public function __construct()
    {
        // Use the controller's method to calculate statement data
        $controller = new ProfitLossStatementController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('calculateStatementData');
        $method->setAccessible(true);
        $this->statementData = $method->invoke($controller);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->statementData);
    }

    public function map($row): array
    {
        return [
            $row['libelle'],
            $row['montant'] ?? 0,
        ];
    }

    public function headings(): array
    {
        return [
            'LIBELLÉS',
            'MONTANT'
        ];
    }
}
