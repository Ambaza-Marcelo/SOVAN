<?php

namespace App\Exports\Accounting;

use Carbon\Carbon;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountLedger;
use App\Http\Controllers\Backend\Accounting\AccountLedgerController;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BalanceExport implements FromCollection, WithMapping, WithHeadings
{
    protected $balanceData;

    public function __construct(?array $accountIds = null)
    {
        $controller = new AccountLedgerController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('calculateBalanceData');
        $method->setAccessible(true);
        $this->balanceData = $method->invoke($controller, $accountIds);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->balanceData['accountsData'];
    }

    public function map($row): array
    {
        return [
            $row['account_number'],
            $row['account_name'] ?: 'Compte non défini',
            $row['movement_debit'] ?? 0,
            $row['movement_credit'] ?? 0,
            $row['balance_debit'] ?? 0,
            $row['balance_credit'] ?? 0,
        ];
    }

    public function headings(): array
    {
        return [
            'Numéro de compte',
            'Intitulé des comptes',
            'Mouvements - Débit',
            'Mouvements - Crédit',
            'Soldes - Débit',
            'Soldes - Crédit'
        ];
    }
}
