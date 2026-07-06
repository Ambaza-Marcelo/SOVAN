<?php

namespace App\Exports\Accounting;

use Carbon\Carbon;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountLedger;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ActiveBalanceExport implements FromCollection, WithMapping, WithHeadings
{
    protected $balanceData;

    public function __construct()
    {
        // We'll calculate the data in collection method
        $this->balanceData = $this->calculateBalanceData();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->balanceData);
    }

    public function map($row): array
    {
        return [
            $row['libelle'],
            $row['montant_brut'] ?? 0,
            $row['amortissement'] ?? 0,
        ];
    }

    public function headings(): array
    {
        return [
            'ACTIFS',
            'MONTANT BRUT',
            'AMORTISSEMENT/DEPRECIATION'
        ];
    }

    /**
     * Calculate balance data (same logic as controller)
     */
    private function calculateBalanceData()
    {
        $balanceData = [];

        // Helper method to get positive balance by prefix
        $getPositiveBalanceByPrefix = function($prefixes) {
            if (!is_array($prefixes)) {
                $prefixes = [$prefixes];
            }

            $totalBalance = 0;
            
            foreach ($prefixes as $prefix) {
                $accountIds = AccountAccount::where('account_number', 'like', $prefix . '%')
                    ->pluck('id');

                if ($accountIds->isEmpty()) {
                    continue;
                }

                foreach ($accountIds as $accountId) {
                    $latestLedger = AccountLedger::where('account_id', $accountId)
                        ->where('balance', '>', 0)
                        ->orderBy('date', 'desc')
                        ->orderBy('id', 'desc')
                        ->first();
                    
                    if ($latestLedger && $latestLedger->balance !== null) {
                        $totalBalance += $latestLedger->balance;
                    }
                }
            }

            return $totalBalance;
        };

        $getPositiveBalanceByPrefixExcluding = function($prefix, $excludePrefix) use ($getPositiveBalanceByPrefix) {
            $totalBalance = 0;
            
            $accountIds = AccountAccount::where('account_number', 'like', $prefix . '%')
                ->where('account_number', 'not like', $excludePrefix . '%')
                ->pluck('id');

            if ($accountIds->isEmpty()) {
                return 0;
            }

            foreach ($accountIds as $accountId) {
                $latestLedger = AccountLedger::where('account_id', $accountId)
                    ->where('balance', '>', 0)
                    ->orderBy('date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();
                
                if ($latestLedger && $latestLedger->balance !== null) {
                    $totalBalance += $latestLedger->balance;
                }
            }

            return $totalBalance;
        };

        // ========== ACTIFS NON-COURANTS ==========
        $balanceData[] = ['libelle' => 'ACTIFS NON-COURANTS', 'montant_brut' => 0, 'amortissement' => 0, 'type' => 'title', 'level' => 0];
        $balanceData[] = ['libelle' => 'Immobilisations Incorporelles et assimiles', 'montant_brut' => 0, 'amortissement' => 0, 'type' => 'title', 'level' => 1];
        
        $ecartsAcquisition = $getPositiveBalanceByPrefix('201');
        $balanceData[] = ['libelle' => 'Ecarts d\'acquisition (goodwill)', 'montant_brut' => $ecartsAcquisition, 'amortissement' => 0, 'type' => 'subtitle', 'level' => 2];

        $depensesDeveloppementBrut = $getPositiveBalanceByPrefix('203');
        $depensesDeveloppementAmort = $getPositiveBalanceByPrefix(['280', '290']);
        $balanceData[] = ['libelle' => 'Depenses de developpement', 'montant_brut' => $depensesDeveloppementBrut, 'amortissement' => $depensesDeveloppementAmort, 'type' => 'subtitle', 'level' => 2];

        $autresImmobIncorpBrut = $getPositiveBalanceByPrefix(['204', '205', '206', '208']);
        $autresImmobIncorpAmort = $getPositiveBalanceByPrefix(['280', '290']);
        $balanceData[] = ['libelle' => 'Autres Immobilisations incorporelles', 'montant_brut' => $autresImmobIncorpBrut, 'amortissement' => $autresImmobIncorpAmort, 'type' => 'subtitle', 'level' => 2];

        $balanceData[] = ['libelle' => 'Immobilisations corporelles', 'montant_brut' => 0, 'amortissement' => 0, 'type' => 'title', 'level' => 1];
        
        $terrainsAccountIds = AccountAccount::where('account_number', 'like', '21%')
            ->where('account_number', 'not like', '201%')
            ->where('account_number', 'not like', '203%')
            ->where('account_number', 'not like', '204%')
            ->where('account_number', 'not like', '205%')
            ->where('account_number', 'not like', '206%')
            ->where('account_number', 'not like', '208%')
            ->pluck('id');
        
        $terrainsBrut = 0;
        foreach ($terrainsAccountIds as $accountId) {
            $latestLedger = AccountLedger::where('account_id', $accountId)
                ->where('balance', '>', 0)
                ->orderBy('date', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            
            if ($latestLedger && $latestLedger->balance !== null) {
                $terrainsBrut += $latestLedger->balance;
            }
        }
        
        $terrainsAmort = $getPositiveBalanceByPrefix(['281', '291']);
        $balanceData[] = ['libelle' => 'Terrains', 'montant_brut' => $terrainsBrut, 'amortissement' => $terrainsAmort, 'type' => 'subtitle', 'level' => 2];

        $autresImmobCorpBrut = $getPositiveBalanceByPrefix('22');
        $autresImmobCorpAmort = $getPositiveBalanceByPrefix(['282', '292']);
        $balanceData[] = ['libelle' => 'Autres Immobilisations corporelles', 'montant_brut' => $autresImmobCorpBrut, 'amortissement' => $autresImmobCorpAmort, 'type' => 'subtitle', 'level' => 2];

        $immobCorpEnCoursBrut = $getPositiveBalanceByPrefixExcluding('23', '238');
        $immobCorpEnCoursAmort = $getPositiveBalanceByPrefix('293');
        $balanceData[] = ['libelle' => 'Immobilisations corporelles en cours', 'montant_brut' => $immobCorpEnCoursBrut, 'amortissement' => $immobCorpEnCoursAmort, 'type' => 'subtitle', 'level' => 2];

        $avancesAcomptesBrut = $getPositiveBalanceByPrefix('238');
        $avancesAcomptesAmort = $getPositiveBalanceByPrefix('294');
        $balanceData[] = ['libelle' => 'Avances-acomptes sur immobilisations en cours', 'montant_brut' => $avancesAcomptesBrut, 'amortissement' => $avancesAcomptesAmort, 'type' => 'subtitle', 'level' => 2];

        $balanceData[] = ['libelle' => 'Immobilisations financieres', 'montant_brut' => 0, 'amortissement' => 0, 'type' => 'title', 'level' => 1];
        
        $pretsLongTermeBrut = $getPositiveBalanceByPrefix('25');
        $pretsLongTermeAmort = $getPositiveBalanceByPrefix('295');
        $balanceData[] = ['libelle' => 'Prets et autres creances long terme', 'montant_brut' => $pretsLongTermeBrut, 'amortissement' => $pretsLongTermeAmort, 'type' => 'subtitle', 'level' => 2];

        $titresEquivalence = $getPositiveBalanceByPrefix('263');
        $balanceData[] = ['libelle' => 'Titres mis en equivalence', 'montant_brut' => $titresEquivalence, 'amortissement' => 0, 'type' => 'subtitle', 'level' => 2];

        $autresActifsFinBrut = $getPositiveBalanceByPrefix(['261', '265', '268', '269']);
        $autresActifsFinAmort = $getPositiveBalanceByPrefix(['296', '297']);
        $balanceData[] = ['libelle' => 'Autres actifs financiers non courants', 'montant_brut' => $autresActifsFinBrut, 'amortissement' => $autresActifsFinAmort, 'type' => 'subtitle', 'level' => 2];

        $impotsDifferes = $getPositiveBalanceByPrefix('143');
        $balanceData[] = ['libelle' => 'Impots differes actifs', 'montant_brut' => $impotsDifferes, 'amortissement' => 0, 'type' => 'subtitle', 'level' => 2];

        $totalActifsNonCourantsBrut = 0;
        $totalActifsNonCourantsAmort = 0;
        foreach ($balanceData as $item) {
            if ($item['type'] === 'subtitle') {
                $totalActifsNonCourantsBrut += $item['montant_brut'];
                $totalActifsNonCourantsAmort += $item['amortissement'];
            }
        }
        $balanceData[] = ['libelle' => 'TOTAL ACTIFS NON-COURANTS', 'montant_brut' => $totalActifsNonCourantsBrut, 'amortissement' => $totalActifsNonCourantsAmort, 'type' => 'subtotal', 'level' => 1];

        // ========== ACTIFS COURANTS ==========
        $balanceData[] = ['libelle' => 'ACTIFS COURANTS', 'montant_brut' => 0, 'amortissement' => 0, 'type' => 'title', 'level' => 0];
        
        $stocksBrut = $getPositiveBalanceByPrefix('3');
        $stocksAmort = $getPositiveBalanceByPrefix('39');
        $balanceData[] = ['libelle' => 'Stocks et en cours', 'montant_brut' => $stocksBrut, 'amortissement' => $stocksAmort, 'type' => 'subtitle', 'level' => 1];

        $balanceData[] = ['libelle' => 'Creances et emplois assimiles', 'montant_brut' => 0, 'amortissement' => 0, 'type' => 'title', 'level' => 1];
        
        $fournisseursAvancesBrut = $getPositiveBalanceByPrefix('40');
        $fournisseursAvancesAmort = $getPositiveBalanceByPrefix('49');
        $balanceData[] = ['libelle' => 'Fournisseurs - avances et acomptes verses', 'montant_brut' => $fournisseursAvancesBrut, 'amortissement' => $fournisseursAvancesAmort, 'type' => 'subtitle', 'level' => 2];

        $clientsBrut = $getPositiveBalanceByPrefix('41');
        $clientsAmort = $getPositiveBalanceByPrefix('49');
        $balanceData[] = ['libelle' => 'Clients', 'montant_brut' => $clientsBrut, 'amortissement' => $clientsAmort, 'type' => 'subtitle', 'level' => 2];

        $associesBrut = $getPositiveBalanceByPrefix('44');
        $associesAmort = $getPositiveBalanceByPrefix('49');
        $balanceData[] = ['libelle' => 'Associes et societes apparentes', 'montant_brut' => $associesBrut, 'amortissement' => $associesAmort, 'type' => 'subtitle', 'level' => 2];

        $impotsTaxesBrut = $getPositiveBalanceByPrefix(['431', '432', '433']);
        $impotsTaxesAmort = $getPositiveBalanceByPrefix('49');
        $balanceData[] = ['libelle' => 'Impots et Taxes', 'montant_brut' => $impotsTaxesBrut, 'amortissement' => $impotsTaxesAmort, 'type' => 'subtitle', 'level' => 2];

        $pretsMoinsUnAnBrut = $getPositiveBalanceByPrefix('465');
        $pretsMoinsUnAnAmort = $getPositiveBalanceByPrefix('49');
        $balanceData[] = ['libelle' => 'Prets a long et moyen terme - part a moins d\'un an', 'montant_brut' => $pretsMoinsUnAnBrut, 'amortissement' => $pretsMoinsUnAnAmort, 'type' => 'subtitle', 'level' => 2];

        $autresDebiteursBrut = $getPositiveBalanceByPrefix(['42', '45', '435', '46', '437']);
        $autresDebiteursAmort = $getPositiveBalanceByPrefix('49');
        $balanceData[] = ['libelle' => 'Autres debiteurs a moins d\'un an', 'montant_brut' => $autresDebiteursBrut, 'amortissement' => $autresDebiteursAmort, 'type' => 'subtitle', 'level' => 2];

        $regularisationActif = $getPositiveBalanceByPrefix('471');
        $balanceData[] = ['libelle' => 'Comptes de regularisation actif', 'montant_brut' => $regularisationActif, 'amortissement' => 0, 'type' => 'subtitle', 'level' => 2];

        $balanceData[] = ['libelle' => 'Disponibilite et assimiles', 'montant_brut' => 0, 'amortissement' => 0, 'type' => 'title', 'level' => 1];
        
        $placementsBrut = $getPositiveBalanceByPrefix(['52', '54']);
        $placementsAmort = $getPositiveBalanceByPrefix('59');
        $balanceData[] = ['libelle' => 'Placements et autres actifs financiers courant', 'montant_brut' => $placementsBrut, 'amortissement' => $placementsAmort, 'type' => 'subtitle', 'level' => 2];

        $tresorerieBrut = $getPositiveBalanceByPrefix(['55', '56', '57']);
        $tresorerieAmort = $getPositiveBalanceByPrefix('59');
        $balanceData[] = ['libelle' => 'Tresorerie et equivalents de tresorerie', 'montant_brut' => $tresorerieBrut, 'amortissement' => $tresorerieAmort, 'type' => 'subtitle', 'level' => 2];

        $actifsCourantsStartIndex = null;
        foreach ($balanceData as $index => $item) {
            if ($item['libelle'] === 'ACTIFS COURANTS') {
                $actifsCourantsStartIndex = $index;
                break;
            }
        }
        
        $totalActifsCourantsBrut = 0;
        $totalActifsCourantsAmort = 0;
        if ($actifsCourantsStartIndex !== null) {
            for ($i = $actifsCourantsStartIndex + 1; $i < count($balanceData); $i++) {
                if ($balanceData[$i]['type'] === 'subtitle') {
                    $totalActifsCourantsBrut += $balanceData[$i]['montant_brut'];
                    $totalActifsCourantsAmort += $balanceData[$i]['amortissement'];
                } elseif ($balanceData[$i]['type'] === 'subtotal' || $balanceData[$i]['type'] === 'total') {
                    break;
                }
            }
        }
        $balanceData[] = ['libelle' => 'TOTAL ACTIFS COURANTS', 'montant_brut' => $totalActifsCourantsBrut, 'amortissement' => $totalActifsCourantsAmort, 'type' => 'subtotal', 'level' => 1];

        $totalGeneralBrut = $totalActifsNonCourantsBrut + $totalActifsCourantsBrut;
        $totalGeneralAmort = $totalActifsNonCourantsAmort + $totalActifsCourantsAmort;
        $balanceData[] = ['libelle' => 'TOTAL GENERAL ACTIF', 'montant_brut' => $totalGeneralBrut, 'amortissement' => $totalGeneralAmort, 'type' => 'total', 'level' => 0];

        return $balanceData;
    }
}
