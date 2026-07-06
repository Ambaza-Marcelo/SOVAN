<?php

namespace App\Http\Controllers\Backend;

use App\Exports\FinishedGoodReportExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Backend\Concerns\HandlesStockReports;
use App\Models\FinishedGoodReport;
use App\Models\FinishedGoodsStore;
use App\Support\StockReportContext;
use App\Support\StockReportFilter;
use Carbon\Carbon;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;

class FinishedGoodReportController extends Controller
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
        return StockReportContext::filterConfig('finished_goods');
    }

    public function index(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_stock.view')) {
            abort(403, 'Muradutunge !! Ntaburenganzira mufise bwo kuraba raporo,mufise ico mubaza murashobora guhamagara kuri 122 !');
        }

        if ($request->boolean('apply_filter') && !StockReportFilter::requiresDateRange($request)) {
            return redirect()->route('admin.finished-good-store-report.index')
                ->with('error', __('messages.export_select_date_range'));
        }

        $stores = FinishedGoodsStore::all();
        $filterConfig = $this->stockReportFilterConfigWithStores($stores);

        $query = FinishedGoodReport::with('item')
            ->select(
                'id', 'date', 'created_at', 'item_id', 'code_store',
                'quantity_stock_initial', 'value_stock_initial',
                'quantity_stockin', 'value_stockin', 'quantity_stockout', 'value_stockout',
                'quantity_stock_final', 'value_stock_final', 'type_transaction', 'document_no',
                'description', 'cump', 'created_by'
            )
            ->orderBy('id', 'desc');

        StockReportFilter::apply($query, $request, $filterConfig);

        $datas = $this->normalizeStockReportRows($query->get());

        return view('backend.pages.finished_goods_report.index', array_merge(
            $this->stockReportViewData($request, $stores),
            compact('datas')
        ));
    }

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_stock.view')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à exporter ce rapport.');
        }

        if ($redirect = $this->validateStockReportDates($request, 'admin.finished-good-store-report.index')) {
            return $redirect;
        }

        $this->mergeExportRequest($request);
        $range = StockReportFilter::dateRange($request);
        $filename = 'rapport_stock_produits_finis_' . $range[0]->format('Y-m-d') . '_' . $range[1]->format('Y-m-d') . '.xlsx';

        return Excel::download(new FinishedGoodReportExport(), $filename);
    }

    public function exportToPdf(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('finished_product_stock.view')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à exporter ce rapport.');
        }

        if ($redirect = $this->validateStockReportDates($request, 'admin.finished-good-store-report.index')) {
            return $redirect;
        }

        $this->mergeExportRequest($request);

        $range = StockReportFilter::dateRange($request);
        $start_date = $range[0]->format('Y-m-d H:i:s');
        $end_date = $range[1]->format('Y-m-d H:i:s');
        $code_store = $request->input('code_store');
        $store_signature = FinishedGoodsStore::where('code', $code_store)->value('store_signature');

        $query = FinishedGoodReport::with('item')
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
                'backend.pages.document.finished_good_store_report',
                compact('datas', 'dateTime', 'setting', 'start_date', 'end_date', 'code_store', 'store_signature', 'quantitiesOnly'),
                [],
                'UTF-8'
            )
            ->setPaper('a4', 'landscape');

        return $pdf->download('RAPPORT_STOCK_PRODUITS_FINIS_' . $dateTime . '.pdf');
    }
}
