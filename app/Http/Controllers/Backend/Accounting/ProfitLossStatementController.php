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
use App\Exports\Accounting\ProfitLossStatementBalanceExport;
use Validator;
use PDF;
use Excel;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProfitLossStatementController extends Controller
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
     * Get balance for accounts matching a prefix pattern
     */
    private function getAccountBalanceByPrefix($prefix)
    {
        $accounts = AccountAccount::where('account_number', 'like', $prefix . '%')
            ->pluck('id');

        if ($accounts->isEmpty()) {
            return 0;
        }

        // Get the latest balance for each account
        $totalBalance = 0;
        
        foreach ($accounts as $accountId) {
            $latestLedger = AccountLedger::where('account_id', $accountId)
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
     * Calculate statement data (refactored to avoid duplication)
     */
    private function calculateStatementData()
    {
        // Calculate balances for each category
        $chiffreAffaires = $this->getAccountBalanceByPrefix('70') + $this->getAccountBalanceByPrefix('71');
        $variationStocks = $this->getAccountBalanceByPrefix('72');
        $productionImmobilisee = $this->getAccountBalanceByPrefix('73');
        $subventionExploitation = $this->getAccountBalanceByPrefix('76');
        
        // Production de l'exercice
        $productionExercice = $chiffreAffaires + $variationStocks + $productionImmobilisee + $subventionExploitation;
        
        // Consommation
        $achatsConsommes = $this->getAccountBalanceByPrefix('60');
        $servicesExterieurs = $this->getAccountBalanceByPrefix('61') + 
                             $this->getAccountBalanceByPrefix('62') + 
                             $this->getAccountBalanceByPrefix('63');
        
        // Charges de personnel et impôts
        $chargesPersonnel = $this->getAccountBalanceByPrefix('65');
        $impotsTaxes = $this->getAccountBalanceByPrefix('66');
        
        // Consommation de l'exercice (includes all: achats, services, charges, taxes)
        $consommationExercice = $achatsConsommes + $servicesExterieurs + $chargesPersonnel + $impotsTaxes;
        
        // Valeur ajoutée d'exploitation = Production - Consommation totale
        $valeurAjouteeExploitation = $productionExercice - $consommationExercice;
        
        // Excédent brut d'exploitation (same as valeur ajoutée in this structure)
        $excedentBrutExploitation = $valeurAjouteeExploitation;
        
        // Autres produits et charges opérationnels
        $autresProduitsOperationnels = $this->getAccountBalanceByPrefix('74') + $this->getAccountBalanceByPrefix('77');
        $autresChargesOperationnelles = $this->getAccountBalanceByPrefix('64');
        $dotationsAmortissements = $this->getAccountBalanceByPrefix('68');
        $reprisePertesValeur = $this->getAccountBalanceByPrefix('78');
        
        // Résultat opérationnel
        $resultatOperationnel = $excedentBrutExploitation + 
                               $autresProduitsOperationnels - 
                               $autresChargesOperationnelles - 
                               $dotationsAmortissements + 
                               $reprisePertesValeur;
        
        // Charges financières
        $chargesFinancieres = $this->getAccountBalanceByPrefix('67');
        
        // Résultat ordinaire avant impôts
        $resultatOrdinaireAvantImpots = $resultatOperationnel - $chargesFinancieres;
        
        // Impôts exigibles de la période (to be calculated based on account rules)
        $impotsExigibles = 0; // This might need to be calculated differently
        
        // Résultat après impôt
        $resultatApresImpot = $resultatOrdinaireAvantImpots - $impotsExigibles;

        // Build the statement data (translated labels)
        $statementData = [
            // Production section
            ['libelle' => __('messages.pl_turnover'), 'montant' => abs($chiffreAffaires), 'type' => 'item'],
            ['libelle' => __('messages.pl_stock_variation'), 'montant' => abs($variationStocks), 'type' => 'item'],
            ['libelle' => __('messages.pl_capitalized_production'), 'montant' => abs($productionImmobilisee), 'type' => 'item'],
            ['libelle' => __('messages.pl_operating_subsidies'), 'montant' => abs($subventionExploitation), 'type' => 'item'],
            ['libelle' => __('messages.pl_production_of_period'), 'montant' => abs($productionExercice), 'type' => 'subtotal'],
            
            // Consommation section
            ['libelle' => __('messages.pl_consumed_purchases'), 'montant' => abs($achatsConsommes), 'type' => 'item'],
            ['libelle' => __('messages.pl_external_services'), 'montant' => abs($servicesExterieurs), 'type' => 'item'],
            ['libelle' => __('messages.pl_consumption_of_period'), 'montant' => abs($consommationExercice), 'type' => 'subtotal'],
            
            // Charges section (listed under Consommation title)
            ['libelle' => __('messages.pl_staff_expenses'), 'montant' => abs($chargesPersonnel), 'type' => 'item'],
            ['libelle' => __('messages.pl_taxes'), 'montant' => abs($impotsTaxes), 'type' => 'item'],
            
            // Valeur ajoutée
            ['libelle' => __('messages.pl_added_value'), 'montant' => abs($valeurAjouteeExploitation), 'type' => 'subtotal'],
            
            // Excédent brut
            ['libelle' => __('messages.pl_ebitda'), 'montant' => abs($excedentBrutExploitation), 'type' => 'subtotal'],
            
            // Autres produits et charges
            ['libelle' => __('messages.pl_other_operating_income'), 'montant' => abs($autresProduitsOperationnels), 'type' => 'item'],
            ['libelle' => __('messages.pl_other_operating_expenses'), 'montant' => abs($autresChargesOperationnelles), 'type' => 'item'],
            ['libelle' => __('messages.pl_depreciation_expense'), 'montant' => abs($dotationsAmortissements), 'type' => 'item'],
            ['libelle' => __('messages.pl_reversal_impairment'), 'montant' => abs($reprisePertesValeur), 'type' => 'item'],
            
            // Résultat opérationnel
            ['libelle' => __('messages.pl_operating_result'), 'montant' => abs($resultatOperationnel), 'type' => 'subtotal'],
            
            // Charges financières
            ['libelle' => __('messages.pl_finance_costs'), 'montant' => abs($chargesFinancieres), 'type' => 'item'],
            
            // Résultat ordinaire
            ['libelle' => __('messages.pl_ordinary_result_before_tax'), 'montant' => abs($resultatOrdinaireAvantImpots), 'type' => 'subtotal'],
            
            // Impôts
            ['libelle' => __('messages.pl_tax_expense'), 'montant' => abs($impotsExigibles), 'type' => 'item'],
            
            // Résultat final
            ['libelle' => __('messages.pl_result_after_tax'), 'montant' => abs($resultatApresImpot), 'type' => 'total'],
        ];

        return $statementData;
    }

    public function index()
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view profit and loss statement !');
        }

        $statementData = $this->calculateStatementData();

        $reportGeneratedAt = now()->format('d/m/Y H:i');
        $companyName = config('app.name', 'Entreprise');
        
        // Get period from ledgers
        $ledgers = AccountLedger::whereNotNull('date')->orderBy('date')->get();
        $periodEnd = $ledgers->max('date');
        $fiscalYear = $periodEnd ? Carbon::parse($periodEnd)->format('Y') : Carbon::now()->format('Y');

        return view('backend.pages.accounting.profit_loss_statement.index', compact(
            'statementData',
            'reportGeneratedAt',
            'companyName',
            'fiscalYear'
        ));
    }

    /**
     * Export profit and loss statement to PDF
     */
    public function exportPDF()
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export profit and loss statement !');
        }

        $statementData = $this->calculateStatementData();
        
        // Get period from ledgers
        $ledgers = AccountLedger::whereNotNull('date')->orderBy('date')->get();
        $periodEnd = $ledgers->max('date');
        $fiscalYear = $periodEnd ? Carbon::parse($periodEnd)->format('Y') : Carbon::now()->format('Y');
        
        $reportGeneratedAt = now()->format('d/m/Y H:i');
        $companyName = config('app.name', 'Entreprise');
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();

        $pdf = PDF::setOptions([
                'defaultFont' => 'dejavu sans',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ])->loadView('backend.pages.accounting.document.profit_loss_statement', compact(
            'statementData',
            'reportGeneratedAt',
            'companyName',
            'fiscalYear',
            'setting'
        ))->setPaper('a4', 'portrait');

        $fileName = 'COMPTE_RESULTAT_' . $fiscalYear . '_' . now()->format('YmdHis') . '.pdf';
        Storage::put('public/pdf/accounting/compte_resultat/' . $fileName, $pdf->output());

        return $pdf->download($fileName);
    }

    /**
     * Export profit and loss statement to Excel
     */
    public function exportExcel()
    {
        if (is_null($this->user) || !$this->user->can('account_journal_entry.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export profit and loss statement !');
        }

        $ledgers = AccountLedger::whereNotNull('date')->orderBy('date')->get();
        $periodEnd = $ledgers->max('date');
        $fiscalYear = $periodEnd ? Carbon::parse($periodEnd)->format('Y') : Carbon::now()->format('Y');

        return Excel::download(new ProfitLossStatementBalanceExport, 'COMPTE_RESULTAT_' . $fiscalYear . '.xlsx');
    }

}
