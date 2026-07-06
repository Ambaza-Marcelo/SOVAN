<?php

namespace App\Http\Controllers\Backend;

use App\Exports\FinishedGoodsSmStoreReportExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Backend\Concerns\HandlesStockReports;
use App\Models\FinishedGoodsSmStore;
use App\Models\FinishedGoodsSmStoreReport;
use App\Support\StockReportContext;
use App\Support\StockReportFilter;
use Carbon\Carbon;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;

class FinishedGoodsSmStoreReportController extends Controller
{
    use HandlesStockReports;

    public $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }

    protected function stockReportFilterConfig(): array
    {
        return StockReportContext::filterConfig('finished_goods_sm');
    }

    public function index(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_sm_stock_report.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view this SM report !');
        }

        if ($request->boolean('apply_filter') && !StockReportFilter::requiresDateRange($request)) {
            return redirect()->route('admin.finished-good-sm-store-report.index')
                ->with('error', __('messages.export_select_date_range'));
        }

        $stores = FinishedGoodsSmStore::all();
        $filterConfig = $this->stockReportFilterConfigWithStores($stores);

        $query = FinishedGoodsSmStoreReport::with('item')
            ->select(
                'id',
                'date',
                'created_at',
                'item_id',
                'code_store',
                'quantity_stock_initial',
                'value_stock_initial',
                'quantity_stockin',
                'value_stockin',
                'quantity_stockout',
                'value_stockout',
                'quantity_stock_final',
                'value_stock_final',
                'type_transaction',
                'document_no',
                'description',
                'cump',
                'created_by'
            )
            ->orderBy('id', 'desc');

        StockReportFilter::apply($query, $request, $filterConfig);

        $datas = $this->normalizeStockReportRows($query->get());

        return view('backend.pages.finished_goods_sm_store_report.index', array_merge(
            $this->stockReportViewData($request, $stores),
            compact('datas')
        ));
    }

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_sm_stock_report.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export this report !');
        }

        if ($redirect = $this->validateStockReportDates($request, 'admin.finished-good-sm-store-report.index')) {
            return $redirect;
        }

        $this->mergeExportRequest($request);
        $range = StockReportFilter::dateRange($request);
        $filename = 'rapport_stock_produits_finis_sm_' . $range[0]->format('Y-m-d') . '_' . $range[1]->format('Y-m-d') . '.xlsx';

        return Excel::download(new FinishedGoodsSmStoreReportExport(), $filename);
    }

    public function exportToPdf(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_sm_stock_report.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export this report !');
        }

        if ($redirect = $this->validateStockReportDates($request, 'admin.finished-good-sm-store-report.index')) {
            return $redirect;
        }

        $this->mergeExportRequest($request);

        $range = StockReportFilter::dateRange($request);
        $start_date = $range[0]->format('Y-m-d H:i:s');
        $end_date = $range[1]->format('Y-m-d H:i:s');
        $code_store = $request->input('code_store');
        $store_signature = FinishedGoodsSmStore::where('code', $code_store)->value('store_signature');

        $query = FinishedGoodsSmStoreReport::with('item')
            ->select(
                'id', 'date', 'created_at', 'item_id', 'code_store',
                'quantity_stock_initial', 'quantity_stockin', 'quantity_stockout',
                'quantity_stock_final', 'type_transaction', 'document_no', 'description', 'created_by'
            )
            ->orderBy('id', 'asc');

        StockReportFilter::apply($query, $request, $this->stockReportFilterConfig());
        $datas = $this->normalizeStockReportRows($query->get());

        $setting = DB::table('settings')->orderBy('created_at', 'desc')->first();
        $dateTime = str_replace([' ', ':'], '_', Carbon::now()->toDateTimeString());
        $quantitiesOnly = true;

        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])
            ->loadView(
                'backend.pages.document.finished_goods_sm_store_report',
                compact('datas', 'dateTime', 'setting', 'start_date', 'end_date', 'code_store', 'store_signature', 'quantitiesOnly'),
                [],
                'UTF-8'
            )
            ->setPaper('a4', 'landscape');

        return $pdf->download('RAPPORT_STOCK_PRODUITS_FINIS_SM_' . $dateTime . '.pdf');
    }
}
