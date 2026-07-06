<?php

namespace App\Exports\Accounting;

use Carbon\Carbon;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountLedger;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PassiveBalanceExport implements FromCollection, WithMapping, WithHeadings
{
    protected $balanceData;

    public function __construct()
    {
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
            $row['montant'] ?? 0,
        ];
    }

    public function headings(): array
    {
        return [
            'CAPITAUX PROPRES ET PASSIFS',
            'MONTANT'
        ];
    }

    /**
     * Calculate balance data (same logic as controller)
     */
    private function calculateBalanceData()
    {
        $balanceData = [];

        // Helper method to get negative balance by prefix
        $getNegativeBalanceByPrefix = function($prefixes) {
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
                        ->where('balance', '<', 0)
                        ->orderBy('date', 'desc')
                        ->orderBy('id', 'desc')
                        ->first();
                    
                    if ($latestLedger && $latestLedger->balance !== null) {
                        $totalBalance += abs($latestLedger->balance);
                    }
                }
            }

            return $totalBalance;
        };

        $getNegativeBalanceByPrefixExcluding = function($prefix, $excludePrefix) {
            $totalBalance = 0;
            
            $accountIds = AccountAccount::where('account_number', 'like', $prefix . '%')
                ->where('account_number', 'not like', $excludePrefix . '%')
                ->pluck('id');

            if ($accountIds->isEmpty()) {
                return 0;
            }

            foreach ($accountIds as $accountId) {
                $latestLedger = AccountLedger::where('account_id', $accountId)
                    ->where('balance', '<', 0)
                    ->orderBy('date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();
                
                if ($latestLedger && $latestLedger->balance !== null) {
                    $totalBalance += abs($latestLedger->balance);
                }
            }

            return $totalBalance;
        };

        // ========== CAPITAUX PROPRES ==========
        $balanceData[] = ['libelle' => 'CAPITAUX PROPRES', 'montant' => 0, 'type' => 'title', 'level' => 0];

        $capitalSocial = $getNegativeBalanceByPrefix(['101', '103']);
        $balanceData[] = ['libelle' => 'Capital social (ou Personnel)', 'montant' => $capitalSocial, 'type' => 'subtitle', 'level' => 1];

        $primesCapital = $getNegativeBalanceByPrefix('102');
        $balanceData[] = ['libelle' => 'Primes liées au capital social', 'montant' => $primesCapital, 'type' => 'subtitle', 'level' => 1];

        $reservesReglementaires = $getNegativeBalanceByPrefix('111');
        $balanceData[] = ['libelle' => 'Réserves réglementaires ou statutaires', 'montant' => $reservesReglementaires, 'type' => 'subtitle', 'level' => 1];

        $reservesLibres = $getNegativeBalanceByPrefix(['112', '116', '117']);
        $balanceData[] = ['libelle' => 'Réserves libres/ Réserves consolidées', 'montant' => $reservesLibres, 'type' => 'subtitle', 'level' => 1];

        $ecartsReevaluation = $getNegativeBalanceByPrefix('132');
        $balanceData[] = ['libelle' => 'Ecarts de réévaluation', 'montant' => $ecartsReevaluation, 'type' => 'subtitle', 'level' => 1];

        $reportANouveau1 = $getNegativeBalanceByPrefix(['104', '105', '106', '107', '108', '109', '120']);
        $reportANouveau2 = $getNegativeBalanceByPrefixExcluding('13', '132');
        $reportANouveau = $reportANouveau1 + $reportANouveau2;
        $balanceData[] = ['libelle' => 'Report à nouveau et autres ajustements ou éléments sur capitaux propres', 'montant' => $reportANouveau, 'type' => 'subtitle', 'level' => 1];

        $balanceData[] = ['libelle' => 'Part de la société consolidante', 'montant' => 0, 'type' => 'subtitle', 'level' => 1];
        $balanceData[] = ['libelle' => 'Part des minoritaires', 'montant' => 0, 'type' => 'subtitle', 'level' => 1];

        $totalCapitauxPropres = $capitalSocial + $primesCapital + $reservesReglementaires + 
                               $reservesLibres + $ecartsReevaluation + $reportANouveau;
        $balanceData[] = ['libelle' => 'TOTAL CAPITAUX PROPRES', 'montant' => $totalCapitauxPropres, 'type' => 'subtotal', 'level' => 0];

        // ========== PASSIFS NON-COURANTS ==========
        $balanceData[] = ['libelle' => 'PASSIFS NON-COURANTS', 'montant' => 0, 'type' => 'title', 'level' => 0];

        $provisions = $getNegativeBalanceByPrefix('19');
        $balanceData[] = ['libelle' => 'Provisions', 'montant' => $provisions, 'type' => 'subtitle', 'level' => 1];

        $subventionsEquipement = $getNegativeBalanceByPrefix(['141', '142']);
        $balanceData[] = ['libelle' => 'Subventions d\'équipement (part non amortie)', 'montant' => $subventionsEquipement, 'type' => 'subtitle', 'level' => 1];

        $empruntsObligatoires = $getNegativeBalanceByPrefix('16');
        $balanceData[] = ['libelle' => 'Emprunts obligatoires', 'montant' => $empruntsObligatoires, 'type' => 'subtitle', 'level' => 1];

        $dettesLocation = $getNegativeBalanceByPrefix('178');
        $balanceData[] = ['libelle' => 'Dettes sur contrats de location de financement', 'montant' => $dettesLocation, 'type' => 'subtitle', 'level' => 1];

        $autresEmprunts = $getNegativeBalanceByPrefix(['171', '172', '173', '174', '175', '176']);
        $balanceData[] = ['libelle' => 'Autres emprunts et dettes assimilées', 'montant' => $autresEmprunts, 'type' => 'subtitle', 'level' => 1];

        $impotsDifferesPassifs = $getNegativeBalanceByPrefix('144');
        $balanceData[] = ['libelle' => 'Impôts différés passifs', 'montant' => $impotsDifferesPassifs, 'type' => 'subtitle', 'level' => 1];

        $autresPassifsNonCourants = $getNegativeBalanceByPrefix('148');
        $balanceData[] = ['libelle' => 'Autres passifs non courants', 'montant' => $autresPassifsNonCourants, 'type' => 'subtitle', 'level' => 1];

        $totalPassifsNonCourants = $provisions + $subventionsEquipement + $empruntsObligatoires + 
                                  $dettesLocation + $autresEmprunts + $impotsDifferesPassifs + 
                                  $autresPassifsNonCourants;
        $balanceData[] = ['libelle' => 'TOTAL PASSIFS NON-COURANTS', 'montant' => $totalPassifsNonCourants, 'type' => 'subtotal', 'level' => 0];

        // ========== PASSIFS COURANTS ==========
        $balanceData[] = ['libelle' => 'PASSIFS COURANTS', 'montant' => 0, 'type' => 'title', 'level' => 0];

        $fournisseurs = $getNegativeBalanceByPrefix('40');
        $balanceData[] = ['libelle' => 'Fournisseurs', 'montant' => $fournisseurs, 'type' => 'subtitle', 'level' => 1];

        $clientsAvances = $getNegativeBalanceByPrefix('41');
        $balanceData[] = ['libelle' => 'Clients - avance et acomptes reçus', 'montant' => $clientsAvances, 'type' => 'subtitle', 'level' => 1];

        $etatsOrganismes = $getNegativeBalanceByPrefix(['434', '437']);
        $balanceData[] = ['libelle' => 'Etats et organismes africains ou internationaux', 'montant' => $etatsOrganismes, 'type' => 'subtitle', 'level' => 1];

        $associesPassifs = $getNegativeBalanceByPrefix('44');
        $balanceData[] = ['libelle' => 'Associés et sociétés apparentées', 'montant' => $associesPassifs, 'type' => 'subtitle', 'level' => 1];

        $impotsTaxes = $getNegativeBalanceByPrefix(['431', '432', '433']);
        $balanceData[] = ['libelle' => 'Impôts et taxes', 'montant' => $impotsTaxes, 'type' => 'subtitle', 'level' => 1];

        $passifsCourtTerme = $getNegativeBalanceByPrefix('464');
        $balanceData[] = ['libelle' => 'Passifs non-courants : part à court terme', 'montant' => $passifsCourtTerme, 'type' => 'subtitle', 'level' => 1];

        $autresDettesCourtTerme = $getNegativeBalanceByPrefix(['462', '467', '468']);
        $balanceData[] = ['libelle' => 'Autres dettes et créances court terme', 'montant' => $autresDettesCourtTerme, 'type' => 'subtitle', 'level' => 1];

        $regularisationPassif = $getNegativeBalanceByPrefix('471');
        $balanceData[] = ['libelle' => 'Comptes de régularisation passif', 'montant' => $regularisationPassif, 'type' => 'subtitle', 'level' => 1];

        $concoursBancaires = $getNegativeBalanceByPrefix(['53', '554', '56']);
        $balanceData[] = ['libelle' => 'Concours bancaires et autres comptes financiers court terme', 'montant' => $concoursBancaires, 'type' => 'subtitle', 'level' => 1];

        $totalPassifsCourants = $fournisseurs + $clientsAvances + $etatsOrganismes + $associesPassifs + 
                               $impotsTaxes + $passifsCourtTerme + $autresDettesCourtTerme + 
                               $regularisationPassif + $concoursBancaires;
        $balanceData[] = ['libelle' => 'TOTAL PASSIFS COURANTS', 'montant' => $totalPassifsCourants, 'type' => 'subtotal', 'level' => 0];

        $totalGeneralPassif = $totalPassifsNonCourants + $totalPassifsCourants;
        $balanceData[] = ['libelle' => 'TOTAL GENERAL PASSIF', 'montant' => $totalGeneralPassif, 'type' => 'total', 'level' => 0];

        return $balanceData;
    }
}
