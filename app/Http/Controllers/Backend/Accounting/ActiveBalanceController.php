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
use App\Exports\Accounting\ActiveBalanceExport;
use Validator;
use PDF;
use Excel;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ActiveBalanceController extends Controller
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
     * Get positive balance for accounts matching prefix patterns
     * Returns sum of positive balances from AccountLedger for accounts matching the prefix
     */
    private function getPositiveBalanceByPrefix($prefixes)
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

            // Get the latest positive balance for each account
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
    }

    /**
     * Get positive balance for accounts matching prefix patterns (excluding specific prefix)
     * Used for cases like "23 except 238"
     */
    private function getPositiveBalanceByPrefixExcluding($prefix, $excludePrefix)
    {
        $totalBalance = 0;
        
        // Get account IDs matching the prefix but not the exclude prefix
        $accountIds = AccountAccount::where('account_number', 'like', $prefix . '%')
            ->where('account_number', 'not like', $excludePrefix . '%')
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return 0;
        }

        // Get the latest positive balance for each account
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
    }

    public function index()
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view active balance sheet !');
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

        return view('backend.pages.accounting.active_balance_sheet.index', compact(
            'balanceData',
            'reportGeneratedAt',
            'companyName',
            'periodStartFormatted',
            'periodEndFormatted',
            'fiscalYear'
        ));
    }

    /**
     * Export active balance sheet to PDF
     */
    public function exportPDF()
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export active balance sheet !');
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
                'actif' => $item['libelle'],
                'montant_brut' => $item['montant_brut'] ?? 0,
                'amortissement' => $item['amortissement'] ?? 0,
                'type' => $item['type'],
                'level' => $item['level'] ?? 0
            ];
        }

        $pdf = PDF::setOptions([
                'defaultFont' => 'dejavu sans',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ])->loadView('backend.pages.accounting.document.active_balance_sheet', compact(
            'balanceSheetData',
            'reportGeneratedAt',
            'companyName',
            'periodStartFormatted',
            'periodEndFormatted',
            'fiscalYear',
            'setting'
        ))->setPaper('a4', 'landscape');

        $fileName = 'BILAN_ACTIF_' . $fiscalYear . '_' . now()->format('YmdHis') . '.pdf';
        Storage::put('public/pdf/accounting/bilan_actif/' . $fileName, $pdf->output());

        return $pdf->download($fileName);
    }

    /**
     * Export active balance sheet to Excel
     */
    public function exportExcel()
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export active balance sheet !');
        }

        $ledgers = AccountLedger::whereNotNull('date')->orderBy('date')->get();
        $periodEnd = $ledgers->max('date');
        $fiscalYear = $periodEnd ? Carbon::parse($periodEnd)->format('Y') : Carbon::now()->format('Y');

        return Excel::download(new ActiveBalanceExport, 'BILAN_ACTIF_' . $fiscalYear . '.xlsx');
    }

    /**
     * Calculate balance data (refactored from index method to avoid duplication)
     */
    private function calculateBalanceData()
    {
        $balanceData = [];

        // ========== ACTIFS NON-COURANTS ==========
        $balanceData[] = [
            'libelle' => 'ACTIFS NON-COURANTS',
            'montant_brut' => 0,
            'amortissement' => 0,
            'type' => 'title',
            'level' => 0
        ];
        
        // --- Immobilisations Incorporelles et assimiles ---
        $balanceData[] = [
            'libelle' => 'Immobilisations Incorporelles et assimiles',
            'montant_brut' => 0,
            'amortissement' => 0,
            'type' => 'title',
            'level' => 1
        ];
        
        // Ecarts d'acquisition (goodwill) - 201
        $ecartsAcquisition = $this->getPositiveBalanceByPrefix('201');
        $balanceData[] = [
            'libelle' => 'Ecarts d\'acquisition (goodwill)',
            'montant_brut' => $ecartsAcquisition,
            'amortissement' => 0,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Depenses de developpement - 203 (gross), 280/290 (depreciation)
        $depensesDeveloppementBrut = $this->getPositiveBalanceByPrefix('203');
        $depensesDeveloppementAmort = $this->getPositiveBalanceByPrefix(['280', '290']);
        $balanceData[] = [
            'libelle' => 'Depenses de developpement',
            'montant_brut' => $depensesDeveloppementBrut,
            'amortissement' => $depensesDeveloppementAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Autres Immobilisations incorporelles - 204/205/206/208 (gross), 280/290 (depreciation)
        $autresImmobIncorpBrut = $this->getPositiveBalanceByPrefix(['204', '205', '206', '208']);
        $autresImmobIncorpAmort = $this->getPositiveBalanceByPrefix(['280', '290']);
        $balanceData[] = [
            'libelle' => 'Autres Immobilisations incorporelles',
            'montant_brut' => $autresImmobIncorpBrut,
            'amortissement' => $autresImmobIncorpAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // --- Immobilisations corporelles ---
        $balanceData[] = [
            'libelle' => 'Immobilisations corporelles',
            'montant_brut' => 0,
            'amortissement' => 0,
            'type' => 'title',
            'level' => 1
        ];
        
        // Terrains - 21 (gross, but exclude 201, 203, 204, 205, 206, 208), 281/291 (depreciation)
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
        
        $terrainsAmort = $this->getPositiveBalanceByPrefix(['281', '291']);
        $balanceData[] = [
            'libelle' => 'Terrains',
            'montant_brut' => $terrainsBrut,
            'amortissement' => $terrainsAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Autres Immobilisations corporelles - 22 (gross), 282/292 (depreciation)
        $autresImmobCorpBrut = $this->getPositiveBalanceByPrefix('22');
        $autresImmobCorpAmort = $this->getPositiveBalanceByPrefix(['282', '292']);
        $balanceData[] = [
            'libelle' => 'Autres Immobilisations corporelles',
            'montant_brut' => $autresImmobCorpBrut,
            'amortissement' => $autresImmobCorpAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Immobilisations corporelles en cours - 23 (except 238) (gross), 293 (depreciation)
        $immobCorpEnCoursBrut = $this->getPositiveBalanceByPrefixExcluding('23', '238');
        $immobCorpEnCoursAmort = $this->getPositiveBalanceByPrefix('293');
        $balanceData[] = [
            'libelle' => 'Immobilisations corporelles en cours',
            'montant_brut' => $immobCorpEnCoursBrut,
            'amortissement' => $immobCorpEnCoursAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Avances-acomptes sur immobilisations en cours - 238 (gross), 294 (depreciation)
        $avancesAcomptesBrut = $this->getPositiveBalanceByPrefix('238');
        $avancesAcomptesAmort = $this->getPositiveBalanceByPrefix('294');
        $balanceData[] = [
            'libelle' => 'Avances-acomptes sur immobilisations en cours',
            'montant_brut' => $avancesAcomptesBrut,
            'amortissement' => $avancesAcomptesAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // --- Immobilisations financieres ---
        $balanceData[] = [
            'libelle' => 'Immobilisations financieres',
            'montant_brut' => 0,
            'amortissement' => 0,
            'type' => 'title',
            'level' => 1
        ];
        
        // Prets et autres creances long terme - 25 (gross), 295 (depreciation)
        $pretsLongTermeBrut = $this->getPositiveBalanceByPrefix('25');
        $pretsLongTermeAmort = $this->getPositiveBalanceByPrefix('295');
        $balanceData[] = [
            'libelle' => 'Prets et autres creances long terme',
            'montant_brut' => $pretsLongTermeBrut,
            'amortissement' => $pretsLongTermeAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Titres mis en equivalence - 263 (gross only)
        $titresEquivalence = $this->getPositiveBalanceByPrefix('263');
        $balanceData[] = [
            'libelle' => 'Titres mis en equivalence',
            'montant_brut' => $titresEquivalence,
            'amortissement' => 0,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Autres actifs financiers non courants - 261/265/268/269 (gross), 296/297 (depreciation)
        $autresActifsFinBrut = $this->getPositiveBalanceByPrefix(['261', '265', '268', '269']);
        $autresActifsFinAmort = $this->getPositiveBalanceByPrefix(['296', '297']);
        $balanceData[] = [
            'libelle' => 'Autres actifs financiers non courants',
            'montant_brut' => $autresActifsFinBrut,
            'amortissement' => $autresActifsFinAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Impots differes actifs - 143 (gross only)
        $impotsDifferes = $this->getPositiveBalanceByPrefix('143');
        $balanceData[] = [
            'libelle' => 'Impots differes actifs',
            'montant_brut' => $impotsDifferes,
            'amortissement' => 0,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Calculate TOTAL ACTIFS NON-COURANTS
        $totalActifsNonCourantsBrut = 0;
        $totalActifsNonCourantsAmort = 0;
        foreach ($balanceData as $item) {
            if ($item['type'] === 'subtitle') {
                $totalActifsNonCourantsBrut += $item['montant_brut'];
                $totalActifsNonCourantsAmort += $item['amortissement'];
            }
        }
        $balanceData[] = [
            'libelle' => 'TOTAL ACTIFS NON-COURANTS',
            'montant_brut' => $totalActifsNonCourantsBrut,
            'amortissement' => $totalActifsNonCourantsAmort,
            'type' => 'subtotal',
            'level' => 1
        ];

        // ========== ACTIFS COURANTS ==========
        $balanceData[] = [
            'libelle' => 'ACTIFS COURANTS',
            'montant_brut' => 0,
            'amortissement' => 0,
            'type' => 'title',
            'level' => 0
        ];
        
        // Stocks et en cours - 3 (gross), 39 (depreciation)
        $stocksBrut = $this->getPositiveBalanceByPrefix('3');
        $stocksAmort = $this->getPositiveBalanceByPrefix('39');
        $balanceData[] = [
            'libelle' => 'Stocks et en cours',
            'montant_brut' => $stocksBrut,
            'amortissement' => $stocksAmort,
            'type' => 'subtitle',
            'level' => 1
        ];

        // --- Creances et emplois assimiles ---
        $balanceData[] = [
            'libelle' => 'Creances et emplois assimiles',
            'montant_brut' => 0,
            'amortissement' => 0,
            'type' => 'title',
            'level' => 1
        ];
        
        // Fournisseurs - avances et acomptes verses - 40 (gross), 49 (depreciation)
        $fournisseursAvancesBrut = $this->getPositiveBalanceByPrefix('40');
        $fournisseursAvancesAmort = $this->getPositiveBalanceByPrefix('49');
        $balanceData[] = [
            'libelle' => 'Fournisseurs - avances et acomptes verses',
            'montant_brut' => $fournisseursAvancesBrut,
            'amortissement' => $fournisseursAvancesAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Clients - 41 (gross), 49 (depreciation)
        $clientsBrut = $this->getPositiveBalanceByPrefix('41');
        $clientsAmort = $this->getPositiveBalanceByPrefix('49');
        $balanceData[] = [
            'libelle' => 'Clients',
            'montant_brut' => $clientsBrut,
            'amortissement' => $clientsAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Associes et societes apparentes - 44 (gross), 49 (depreciation)
        $associesBrut = $this->getPositiveBalanceByPrefix('44');
        $associesAmort = $this->getPositiveBalanceByPrefix('49');
        $balanceData[] = [
            'libelle' => 'Associes et societes apparentes',
            'montant_brut' => $associesBrut,
            'amortissement' => $associesAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Impots et Taxes - 431/432/433 (gross), 49 (depreciation)
        $impotsTaxesBrut = $this->getPositiveBalanceByPrefix(['431', '432', '433']);
        $impotsTaxesAmort = $this->getPositiveBalanceByPrefix('49');
        $balanceData[] = [
            'libelle' => 'Impots et Taxes',
            'montant_brut' => $impotsTaxesBrut,
            'amortissement' => $impotsTaxesAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Prets a long et moyen terme - part a moins d'un an - 465 (gross), 49 (depreciation)
        $pretsMoinsUnAnBrut = $this->getPositiveBalanceByPrefix('465');
        $pretsMoinsUnAnAmort = $this->getPositiveBalanceByPrefix('49');
        $balanceData[] = [
            'libelle' => 'Prets a long et moyen terme - part a moins d\'un an',
            'montant_brut' => $pretsMoinsUnAnBrut,
            'amortissement' => $pretsMoinsUnAnAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Autres debiteurs a moins d'un an - 42/45/435/46/437 (gross), 49 (depreciation)
        $autresDebiteursBrut = $this->getPositiveBalanceByPrefix(['42', '45', '435', '46', '437']);
        $autresDebiteursAmort = $this->getPositiveBalanceByPrefix('49');
        $balanceData[] = [
            'libelle' => 'Autres debiteurs a moins d\'un an',
            'montant_brut' => $autresDebiteursBrut,
            'amortissement' => $autresDebiteursAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Comptes de regularisation actif - 471 (gross only)
        $regularisationActif = $this->getPositiveBalanceByPrefix('471');
        $balanceData[] = [
            'libelle' => 'Comptes de regularisation actif',
            'montant_brut' => $regularisationActif,
            'amortissement' => 0,
            'type' => 'subtitle',
            'level' => 2
        ];

        // --- Disponibilite et assimiles ---
        $balanceData[] = [
            'libelle' => 'Disponibilite et assimiles',
            'montant_brut' => 0,
            'amortissement' => 0,
            'type' => 'title',
            'level' => 1
        ];
        
        // Placements et autres actifs financiers courant - 52/54 (gross), 59 (depreciation)
        $placementsBrut = $this->getPositiveBalanceByPrefix(['52', '54']);
        $placementsAmort = $this->getPositiveBalanceByPrefix('59');
        $balanceData[] = [
            'libelle' => 'Placements et autres actifs financiers courant',
            'montant_brut' => $placementsBrut,
            'amortissement' => $placementsAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Tresorerie et equivalents de tresorerie - 55/56/57 (gross), 59 (depreciation)
        $tresorerieBrut = $this->getPositiveBalanceByPrefix(['55', '56', '57']);
        $tresorerieAmort = $this->getPositiveBalanceByPrefix('59');
        $balanceData[] = [
            'libelle' => 'Tresorerie et equivalents de tresorerie',
            'montant_brut' => $tresorerieBrut,
            'amortissement' => $tresorerieAmort,
            'type' => 'subtitle',
            'level' => 2
        ];

        // Calculate TOTAL ACTIFS COURANTS
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
        $balanceData[] = [
            'libelle' => 'TOTAL ACTIFS COURANTS',
            'montant_brut' => $totalActifsCourantsBrut,
            'amortissement' => $totalActifsCourantsAmort,
            'type' => 'subtotal',
            'level' => 1
        ];

        // Calculate TOTAL GENERAL ACTIF
        $totalGeneralBrut = $totalActifsNonCourantsBrut + $totalActifsCourantsBrut;
        $totalGeneralAmort = $totalActifsNonCourantsAmort + $totalActifsCourantsAmort;
        $balanceData[] = [
            'libelle' => 'TOTAL GENERAL ACTIF',
            'montant_brut' => $totalGeneralBrut,
            'amortissement' => $totalGeneralAmort,
            'type' => 'total',
            'level' => 0
        ];

        return $balanceData;
    }
}
