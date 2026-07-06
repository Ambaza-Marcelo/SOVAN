<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Events\RealTimeMessage;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\RawMaterialReceptionDetail;
use App\Models\MaterialReceptionDetail;
use App\Models\MaterialPurchase;
use App\Models\MaterialSupplierOrder;
use App\Models\MaterialReception;
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterialSupplierOrder;
use App\Models\RawMaterialReception;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\AccountJournalEntryDetail;
use App\Models\Accounting\AccountSetting;
use App\Models\Accounting\CashOutFlowRequest;
use App\Models\Accounting\CashOutFlow;
use App\Models\Accounting\ReturnFund;
use App\Models\HrPaiement;
use App\Models\ProductionPlan;
use App\Models\RawMaterialRequisition;
use App\Models\RawMaterialTransfer;
use App\Models\ProductionOrder;
use App\Models\ProformaInvoice;
use App\Models\SalesOrder;
use App\Models\DeliveryNote;

class DashboardController extends Controller
{
    public $user;

    /**
     * Montant HT d'une ligne de réception (colonnes stockées en chaîne).
     */
    private function purchaseAmountSql(): string
    {
        return 'CAST(COALESCE(NULLIF(total_amount_purchase, ""), NULLIF(total_amount_received, ""), NULLIF(total_value, ""), 0) AS DECIMAL(20,2))';
    }

    /**
     * Somme des mouvements comptables d'une classe (6* charges, 7* produits) pour l'exercice.
     */
    private function sumClassMovementsForYear(string $accountPrefix, $year): float
    {
        $query = AccountJournalEntryDetail::query()
            ->whereYear('date', $year)
            ->whereHas('account', function ($q) use ($accountPrefix) {
                $q->where('account_number', 'like', $accountPrefix . '%');
            });

        if ($accountPrefix === '7') {
            $net = (float) ($query->selectRaw('SUM(COALESCE(credit, 0)) - SUM(COALESCE(debit, 0)) as total')
                ->value('total') ?? 0);
        } else {
            $net = (float) ($query->selectRaw('SUM(COALESCE(debit, 0)) - SUM(COALESCE(credit, 0)) as total')
                ->value('total') ?? 0);
        }

        return abs($net);
    }

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }


    public function index(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('dashboard.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view dashboard !');
        }

        // Exercices disponibles (grand livre, écritures, ventes, réceptions)
        $exercises = collect()
            ->merge(AccountLedger::select('fiscal_year')->distinct()->pluck('fiscal_year'))
            ->merge(AccountJournalEntryDetail::selectRaw('YEAR(date) as year')->distinct()->pluck('year'))
            ->merge(InvoiceDetail::selectRaw('YEAR(invoice_date) as year')->distinct()->pluck('year'))
            ->merge(RawMaterialReceptionDetail::selectRaw('YEAR(date) as year')->distinct()->pluck('year'))
            ->merge(MaterialReceptionDetail::selectRaw('YEAR(date) as year')->distinct()->pluck('year'))
            ->filter()
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values();

        $defaultExercise = AccountSetting::where('active', 1)->value('fiscal_year')
            ?? $exercises->first()
            ?? (int) date('Y');

        $selectedExercise = (int) $request->get('exercise', $defaultExercise);

        $purchaseAmountSql = $this->purchaseAmountSql();

        // Achats matières premières (réceptions approuvées uniquement)
        $rawMaterialPurchaseBaseQuery = RawMaterialReceptionDetail::whereYear('date', $selectedExercise)
            ->where('status', '4');
        $rawMaterialPurchasePaidQuery = (clone $rawMaterialPurchaseBaseQuery)->where('flag2', '1');
        $rawMaterialPurchaseUnpaidQuery = (clone $rawMaterialPurchaseBaseQuery)->where('flag2', '0');

        $rawMaterialPurchases = [
            'paid_count'   => (clone $rawMaterialPurchasePaidQuery)->count(),
            'paid_sum'     => (clone $rawMaterialPurchasePaidQuery)->sum(DB::raw($purchaseAmountSql)),
            'unpaid_count' => (clone $rawMaterialPurchaseUnpaidQuery)->count(),
            'unpaid_sum'   => (clone $rawMaterialPurchaseUnpaidQuery)->sum(DB::raw($purchaseAmountSql)),
        ];

        // Achats Matériels (réceptions approuvées uniquement)
        $materialPurchaseBaseQuery = MaterialReceptionDetail::whereYear('date', $selectedExercise)
            ->where('status', '4');
        $materialPurchasePaidQuery = (clone $materialPurchaseBaseQuery)->where('flag2', '1');
        $materialPurchaseUnpaidQuery = (clone $materialPurchaseBaseQuery)->where('flag2', '0');

        $materialPurchases = [
            'paid_count'   => (clone $materialPurchasePaidQuery)->count(),
            'paid_sum'     => (clone $materialPurchasePaidQuery)->sum(DB::raw($purchaseAmountSql)),
            'unpaid_count' => (clone $materialPurchaseUnpaidQuery)->count(),
            'unpaid_sum'   => (clone $materialPurchaseUnpaidQuery)->sum(DB::raw($purchaseAmountSql)),
        ];

        // Ventes CASH / CREDIT
        $salesBaseQuery = InvoiceDetail::whereYear('invoice_date', $selectedExercise)
            ->where('etat', '!=', '0')
            ->where('etat', '!=', '-1');

        $cashSalesQuery = (clone $salesBaseQuery)->where('etat', '1');
        $creditSalesQuery = (clone $salesBaseQuery)->where('etat', '01');

        $cashSales = [
            'count' => (clone $cashSalesQuery)->distinct()->count('invoice_number'),
            'sum'   => (clone $cashSalesQuery)->sum('item_price_nvat'),
        ];

        $creditSales = [
            'count' => (clone $creditSalesQuery)->distinct()->count('invoice_number'),
            'sum'   => (clone $creditSalesQuery)->sum('item_price_nvat'),
        ];

        // Produits (comptes 7*) et charges (comptes 6*) : mouvements de l'exercice (HT)
        $products = $this->sumClassMovementsForYear('7', $selectedExercise);
        $charges = $this->sumClassMovementsForYear('6', $selectedExercise);
        $profit = $products - $charges;

        $total_roles = count(Role::select('id')->get());
        $total_admins = count(Admin::select('id')->get());
        $total_permissions = count(Permission::select('id')->get());

        // Operations not yet approved (status before final approval)
        $pendingApprovalStatuses = [1, 2, 3, -3];

        $materialProcurement = [
            'purchase_requests' => MaterialPurchase::whereIn('status', $pendingApprovalStatuses)->count(),
            'purchase_orders' => MaterialSupplierOrder::whereIn('status', $pendingApprovalStatuses)->count(),
            'purchase_receipts' => MaterialReception::whereIn('status', $pendingApprovalStatuses)->count(),
        ];

        $rawMaterialProcurement = [
            'purchase_requests' => RawMaterialPurchase::whereIn('status', $pendingApprovalStatuses)->count(),
            'purchase_orders' => RawMaterialSupplierOrder::whereIn('status', $pendingApprovalStatuses)->count(),
            'purchase_receipts' => RawMaterialReception::whereIn('status', $pendingApprovalStatuses)->count(),
        ];

        $paymentProcess = [
            'cash_outflow_requests' => CashOutFlowRequest::whereIn('status', $pendingApprovalStatuses)->count(),
            'cash_outflows' => CashOutFlow::whereIn('status', $pendingApprovalStatuses)->count(),
            'return_funds' => ReturnFund::whereIn('status', ['0', '1', '2'])->count(),
        ];

        // Approved documents awaiting supplier payment (cash via order, credit via reception)
        $awaitingPayment = [
            'material_orders_cash' => MaterialSupplierOrder::where('status', 4)->where('flag2', 0)->count(),
            'raw_material_orders_cash' => RawMaterialSupplierOrder::where('status', 4)->where('flag2', 0)->count(),
            'material_receptions_credit' => MaterialReception::where('status', 4)->where('flag2', 0)->count(),
            'raw_material_receptions_credit' => RawMaterialReception::where('status', 4)->where('flag2', 0)->count(),
        ];

        $productionProcess = [
            'production_plans' => ProductionPlan::whereIn('status', [0, 2, 3])->count(),
            'requisitions' => RawMaterialRequisition::whereIn('status', $pendingApprovalStatuses)->count(),
            'transfers' => RawMaterialTransfer::whereIn('status', $pendingApprovalStatuses)->count(),
            'production_orders' => ProductionOrder::whereIn('status', [0, 1, 2])->count(),
        ];

        $salesProcess = [
            'proforma_invoices' => ProformaInvoice::whereIn('etat', ['0', '1', '2'])->count(),
            'sales_orders' => SalesOrder::whereIn('etat', ['0', '1', '2'])->count(),
            'invoices_in_progress' => Invoice::where('etat', '0')->count(),
            'delivery_notes' => DeliveryNote::whereIn('etat', ['0', '1', '2'])->count(),
        ];

        return view('backend.pages.dashboard.index',
            compact(
                'total_admins', 
                'total_roles', 
                'total_permissions',
                'exercises',
                'selectedExercise',
                'rawMaterialPurchases',
                'materialPurchases',
                'cashSales',
                'creditSales',
                'products',
                'charges',
                'profit',
                'materialProcurement',
                'rawMaterialProcurement',
                'paymentProcess',
                'awaitingPayment',
                'productionProcess',
                'salesProcess'
            ));
    }

    public function changeLang(Request $request){
        \App::setlocale($request->lang);
        session()->put("locale",$request->lang);
        event(new RealTimeMessage('Hello World'));

        return redirect()->back();
    }
}
