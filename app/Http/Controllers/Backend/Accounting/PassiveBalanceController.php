<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountParentAccount;
use App\Models\Accounting\AccountJournalEntry;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\Currency;
use App\Models\Accounting\AccountCostCenter;
use App\Exports\Accounting\PassiveBalanceExport;
use Validator;
use PDF;
use Excel;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PassiveBalanceController extends Controller
{
    //
    public $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }

    /**
     * Get negative balance for accounts matching prefix patterns
     * Returns sum of negative balances from AccountLedger for accounts matching the prefix
     */
    private function getNegativeBalanceByPrefix($prefixes)
    {
        if (!is_array($prefixes)) {
            $prefixes = [$prefixes];
        }

        $totalBalance = 0;
        
        foreach ($prefixes as $prefix) {
            // Get account IDs matching the prefix
            $accountIds = AccountAccount::where('account_number', 'like', $prefix . '%')
                ->pluck('id');

            if ($accountIds->isEmpty()) {
                continue;
            }

            // Get the latest negative balance for each account
            foreach ($accountIds as $accountId) {
                $latestLedger = AccountLedger::where('account_id', $accountId)
                    ->where('balance', '<', 0)
                    ->orderBy('date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();
                
                if ($latestLedger && $latestLedger->balance !== null) {
                    // Use absolute value since balance is negative
                    $totalBalance += abs($latestLedger->balance);
                }
            }
        }

        return $totalBalance;
    }

    /**
     * Get negative balance for accounts matching prefix patterns (excluding specific prefix)
     */
    private function getNegativeBalanceByPrefixExcluding($prefix, $excludePrefix)
    {
        $totalBalance = 0;
        
        // Get account IDs matching the prefix but not the exclude prefix
        $accountIds = AccountAccount::where('account_number', 'like', $prefix . '%')
            ->where('account_number', 'not like', $excludePrefix . '%')
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return 0;
        }

        // Get the latest negative balance for each account
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
    }

    /**
     * Get negative balance for accounts starting with prefix but excluding multiple prefixes
     */
    private function getNegativeBalanceByPrefixExcludingMultiple($prefix, $excludePrefixes)
    {
        $totalBalance = 0;
        
        $query = AccountAccount::where('account_number', 'like', $prefix . '%');
        
        foreach ($excludePrefixes as $excludePrefix) {
            $query->where('account_number', 'not like', $excludePrefix . '%');
        }
        
        $accountIds = $query->pluck('id');

        if ($accountIds->isEmpty()) {
            return 0;
        }

        // Get the latest negative balance for each account
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
    }

    public function index()
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view passive balance sheet !');
        }

        $balanceData = $this->calculateBalanceData();

        $reportGeneratedAt = now()->format('d/m/Y H:i');
        $companyName = config('app.name', 'Entreprise');
        
        // Get period from ledgers
        $ledgers = AccountLedger::whereNotNull('date')->orderBy('date')->get();
        $periodStart = $ledgers->min('date');
        $periodEnd = $ledgers->max('date');
        $periodStartFormatted = $periodStart ? Carbon::parse($periodStart)->format('d/m/Y') : '--/--/----';
        $periodEndFormatted = $periodEnd ? Carbon::parse($periodEnd)->format('d/m/Y') : '--/--/----';

        // Get fiscal year from period end date
        $fiscalYear = $periodEnd ? Carbon::parse($periodEnd)->format('Y') : Carbon::now()->format('Y');

        return view('backend.pages.accounting.passive_balance_sheet.index', compact(
            'balanceData',
            'reportGeneratedAt',
            'companyName',
            'periodStartFormatted',
            'periodEndFormatted',
            'fiscalYear'
        ));
    }

    /**
     * Export passive balance sheet to PDF
     */
    public function exportPDF()
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export passive balance sheet !');
        }

        $balanceData = $this->calculateBalanceData();
        
        // Get period from ledgers
        $ledgers = AccountLedger::whereNotNull('date')->orderBy('date')->get();
        $periodStart = $ledgers->min('date');
        $periodEnd = $ledgers->max('date');
        $periodStartFormatted = $periodStart ? Carbon::parse($periodStart)->format('d/m/Y') : '--/--/----';
        $periodEndFormatted = $periodEnd ? Carbon::parse($periodEnd)->format('d/m/Y') : '--/--/----';
        $fiscalYear = $periodEnd ? Carbon::parse($periodEnd)->format('Y') : Carbon::now()->format('Y');
        
        $reportGeneratedAt = now()->format('d/m/Y H:i');
        $companyName = config('app.name', 'Entreprise');
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();
        
        // Transform to balanceSheetData format for PDF view
        $balanceSheetData = [];
        foreach ($balanceData as $item) {
            $balanceSheetData[] = [
                'passif' => $item['libelle'],
                'montant' => $item['montant'] ?? 0,
                'type' => $item['type'],
                'level' => $item['level'] ?? 0
            ];
        }

        $pdf = PDF::setOptions([
                'defaultFont' => 'dejavu sans',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ])->loadView('backend.pages.accounting.document.passive_balance_sheet', compact(
            'balanceSheetData',
            'reportGeneratedAt',
            'companyName',
            'periodStartFormatted',
            'periodEndFormatted',
            'fiscalYear',
            'setting'
        ))->setPaper('a4', 'portrait');

        $fileName = 'BILAN_PASSIF_' . $fiscalYear . '_' . now()->format('YmdHis') . '.pdf';
        Storage::put('public/pdf/accounting/bilan_passif/' . $fileName, $pdf->output());

        return $pdf->download($fileName);
    }

    /**
     * Export passive balance sheet to Excel
     */
    public function exportExcel()
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export passive balance sheet !');
        }

        $ledgers = AccountLedger::whereNotNull('date')->orderBy('date')->get();
        $periodEnd = $ledgers->max('date');
        $fiscalYear = $periodEnd ? Carbon::parse($periodEnd)->format('Y') : Carbon::now()->format('Y');

        return Excel::download(new PassiveBalanceExport, 'BILAN_PASSIF_' . $fiscalYear . '.xlsx');
    }

    /**
     * Calculate balance data (refactored from index method to avoid duplication)
     */
    private function calculateBalanceData()
    {
        $balanceData = [];

        // ========== CAPITAUX PROPRES ==========
        $balanceData[] = [
            'libelle' => 'CAPITAUX PROPRES',
            'montant' => 0,
            'type' => 'title',
            'level' => 0
        ];

        // Capital social (ou Personnel) - 101 ou 103
        $capitalSocial = $this->getNegativeBalanceByPrefix(['101', '103']);
        $balanceData[] = [
            'libelle' => 'Capital social (ou Personnel)',
            'montant' => $capitalSocial,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Primes liées au capital social - 102
        $primesCapital = $this->getNegativeBalanceByPrefix('102');
        $balanceData[] = [
            'libelle' => 'Primes liées au capital social',
            'montant' => $primesCapital,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Réserves réglementaires ou statutaires - 111
        $reservesReglementaires = $this->getNegativeBalanceByPrefix('111');
        $balanceData[] = [
            'libelle' => 'Réserves réglementaires ou statutaires',
            'montant' => $reservesReglementaires,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Réserves libres/ Réserves consolidées - 112 ou 116 ou 117
        $reservesLibres = $this->getNegativeBalanceByPrefix(['112', '116', '117']);
        $balanceData[] = [
            'libelle' => 'Réserves libres/ Réserves consolidées',
            'montant' => $reservesLibres,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Ecarts de réévaluation - 132
        $ecartsReevaluation = $this->getNegativeBalanceByPrefix('132');
        $balanceData[] = [
            'libelle' => 'Ecarts de réévaluation',
            'montant' => $ecartsReevaluation,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Report à nouveau et autres ajustements - 104/105/106/107/108/109/120 ou 13 (sauf 132)
        $reportANouveau1 = $this->getNegativeBalanceByPrefix(['104', '105', '106', '107', '108', '109', '120']);
        $reportANouveau2 = $this->getNegativeBalanceByPrefixExcluding('13', '132');
        $reportANouveau = $reportANouveau1 + $reportANouveau2;
        $balanceData[] = [
            'libelle' => 'Report à nouveau et autres ajustements ou éléments sur capitaux propres',
            'montant' => $reportANouveau,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Part de la société consolidante (pas de compte spécifié)
        $balanceData[] = [
            'libelle' => 'Part de la société consolidante',
            'montant' => 0,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Part des minoritaires (pas de compte spécifié)
        $balanceData[] = [
            'libelle' => 'Part des minoritaires',
            'montant' => 0,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Calculate TOTAL CAPITAUX PROPRES
        $totalCapitauxPropres = $capitalSocial + $primesCapital + $reservesReglementaires + 
                               $reservesLibres + $ecartsReevaluation + $reportANouveau;
        $balanceData[] = [
            'libelle' => 'TOTAL CAPITAUX PROPRES',
            'montant' => $totalCapitauxPropres,
            'type' => 'subtotal',
            'level' => 0
        ];

        // ========== PASSIFS NON-COURANTS ==========
        $balanceData[] = [
            'libelle' => 'PASSIFS NON-COURANTS',
            'montant' => 0,
            'type' => 'title',
            'level' => 0
        ];

        // Provisions - 19
        $provisions = $this->getNegativeBalanceByPrefix('19');
        $balanceData[] = [
            'libelle' => 'Provisions',
            'montant' => $provisions,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Subventions d'équipement (part non amortie) - 141 ou 142
        $subventionsEquipement = $this->getNegativeBalanceByPrefix(['141', '142']);
        $balanceData[] = [
            'libelle' => 'Subventions d\'équipement (part non amortie)',
            'montant' => $subventionsEquipement,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Emprunts obligatoires - 16
        $empruntsObligatoires = $this->getNegativeBalanceByPrefix('16');
        $balanceData[] = [
            'libelle' => 'Emprunts obligatoires',
            'montant' => $empruntsObligatoires,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Dettes sur contrats de location de financement - 178
        $dettesLocation = $this->getNegativeBalanceByPrefix('178');
        $balanceData[] = [
            'libelle' => 'Dettes sur contrats de location de financement',
            'montant' => $dettesLocation,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Autres emprunts et dettes assimilées - 171/172/173/174/175/176
        $autresEmprunts = $this->getNegativeBalanceByPrefix(['171', '172', '173', '174', '175', '176']);
        $balanceData[] = [
            'libelle' => 'Autres emprunts et dettes assimilées',
            'montant' => $autresEmprunts,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Impôts différés passifs - 144
        $impotsDifferesPassifs = $this->getNegativeBalanceByPrefix('144');
        $balanceData[] = [
            'libelle' => 'Impôts différés passifs',
            'montant' => $impotsDifferesPassifs,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Autres passifs non courants - 148
        $autresPassifsNonCourants = $this->getNegativeBalanceByPrefix('148');
        $balanceData[] = [
            'libelle' => 'Autres passifs non courants',
            'montant' => $autresPassifsNonCourants,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Calculate TOTAL PASSIFS NON-COURANTS
        $totalPassifsNonCourants = $provisions + $subventionsEquipement + $empruntsObligatoires + 
                                  $dettesLocation + $autresEmprunts + $impotsDifferesPassifs + 
                                  $autresPassifsNonCourants;
        $balanceData[] = [
            'libelle' => 'TOTAL PASSIFS NON-COURANTS',
            'montant' => $totalPassifsNonCourants,
            'type' => 'subtotal',
            'level' => 0
        ];

        // ========== PASSIFS COURANTS ==========
        $balanceData[] = [
            'libelle' => 'PASSIFS COURANTS',
            'montant' => 0,
            'type' => 'title',
            'level' => 0
        ];

        // Fournisseurs - 40
        $fournisseurs = $this->getNegativeBalanceByPrefix('40');
        $balanceData[] = [
            'libelle' => 'Fournisseurs',
            'montant' => $fournisseurs,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Clients - avance et acomptes reçus - 41 (balance négative)
        $clientsAvances = $this->getNegativeBalanceByPrefix('41');
        $balanceData[] = [
            'libelle' => 'Clients - avance et acomptes reçus',
            'montant' => $clientsAvances,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Etats et organismes africains ou internationaux - 434 ou 437
        $etatsOrganismes = $this->getNegativeBalanceByPrefix(['434', '437']);
        $balanceData[] = [
            'libelle' => 'Etats et organismes africains ou internationaux',
            'montant' => $etatsOrganismes,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Associés et sociétés apparentées - 44
        $associesPassifs = $this->getNegativeBalanceByPrefix('44');
        $balanceData[] = [
            'libelle' => 'Associés et sociétés apparentées',
            'montant' => $associesPassifs,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Impôts et taxes - 431/432/433
        $impotsTaxes = $this->getNegativeBalanceByPrefix(['431', '432', '433']);
        $balanceData[] = [
            'libelle' => 'Impôts et taxes',
            'montant' => $impotsTaxes,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Passifs non-courants : part à court terme - 464
        $passifsCourtTerme = $this->getNegativeBalanceByPrefix('464');
        $balanceData[] = [
            'libelle' => 'Passifs non-courants : part à court terme',
            'montant' => $passifsCourtTerme,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Autres dettes et créances court terme - 462/467/468
        $autresDettesCourtTerme = $this->getNegativeBalanceByPrefix(['462', '467', '468']);
        $balanceData[] = [
            'libelle' => 'Autres dettes et créances court terme',
            'montant' => $autresDettesCourtTerme,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Comptes de régularisation passif - 471
        $regularisationPassif = $this->getNegativeBalanceByPrefix('471');
        $balanceData[] = [
            'libelle' => 'Comptes de régularisation passif',
            'montant' => $regularisationPassif,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Concours bancaires et autres comptes financiers court terme - 53 ou 554 ou 56
        $concoursBancaires = $this->getNegativeBalanceByPrefix(['53', '554', '56']);
        $balanceData[] = [
            'libelle' => 'Concours bancaires et autres comptes financiers court terme',
            'montant' => $concoursBancaires,
            'type' => 'subtitle',
            'level' => 1
        ];

        // Calculate TOTAL PASSIFS COURANTS
        $totalPassifsCourants = $fournisseurs + $clientsAvances + $etatsOrganismes + $associesPassifs + 
                               $impotsTaxes + $passifsCourtTerme + $autresDettesCourtTerme + 
                               $regularisationPassif + $concoursBancaires;
        $balanceData[] = [
            'libelle' => 'TOTAL PASSIFS COURANTS',
            'montant' => $totalPassifsCourants,
            'type' => 'subtotal',
            'level' => 0
        ];

        // Calculate TOTAL GENERAL PASSIF
        $totalGeneralPassif = $totalPassifsNonCourants + $totalPassifsCourants;
        $balanceData[] = [
            'libelle' => 'TOTAL GENERAL PASSIF',
            'montant' => $totalGeneralPassif,
            'type' => 'total',
            'level' => 0
        ];

        return $balanceData;
    }
}
