<?php

namespace App\Http\Controllers\Backend;

use App\Exports\GoodsStoreReportExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Backend\Concerns\HandlesStockReports;
use App\Models\GoodsStore;
use App\Models\GoodsStoreReport;
use App\Support\StockReportContext;
use App\Support\StockReportFilter;
use Carbon\Carbon;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;

class GoodStoreReportController extends Controller
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
        return StockReportContext::filterConfig('goods');
    }

    public function index(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('goods_stock_report.view')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à voir le rapport marchandises.');
        }

        if ($request->boolean('apply_filter') && !StockReportFilter::requiresDateRange($request)) {
            return redirect()->route('admin.goods-store-report.index')
                ->with('error', __('messages.export_select_date_range'));
        }

        $stores = GoodsStore::all();
        $filterConfig = $this->stockReportFilterConfigWithStores($stores);

        $query = GoodsStoreReport::with('item')
            ->select(
                'id', 'date', 'created_at', 'item_id', 'quantity_stock_initial', 'value_stock_initial',
                'quantity_stockin', 'value_stockin', 'quantity_stockout', 'value_stockout',
                'quantity_stock_final', 'value_stock_final', 'type_transaction', 'document_no',
                'description', 'cump', 'created_by', 'code_store'
            )
            ->orderBy('id', 'desc');

        StockReportFilter::apply($query, $request, $filterConfig);

        $datas = $this->normalizeStockReportRows($query->get());

        return view('backend.pages.goods_store_report.index', array_merge(
            $this->stockReportViewData($request, $stores),
            compact('datas')
        ));
    }

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('goods_stock_report.view')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à exporter ce rapport.');
        }

        if ($redirect = $this->validateStockReportDates($request, 'admin.goods-store-report.index')) {
            return $redirect;
        }

        $this->mergeExportRequest($request);
        $range = StockReportFilter::dateRange($request);
        $filename = 'rapport_stock_marchandises_' . $range[0]->format('Y-m-d') . '_' . $range[1]->format('Y-m-d') . '.xlsx';

        return Excel::download(new GoodsStoreReportExport(), $filename);
    }

    public function exportToPdf(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('goods_stock_report.view')) {
            abort(403, 'Désolé !! Vous n\'êtes pas autorisé à exporter ce rapport.');
        }

        if ($redirect = $this->validateStockReportDates($request, 'admin.goods-store-report.index')) {
            return $redirect;
        }

        $this->mergeExportRequest($request);

        $range = StockReportFilter::dateRange($request);
        $start_date = $range[0]->format('Y-m-d H:i:s');
        $end_date = $range[1]->format('Y-m-d H:i:s');
        $code_store = $request->input('code_store');
        $store_signature = GoodsStore::where('code', $code_store)->value('store_signature');

        $query = GoodsStoreReport::with('item')
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
                'backend.pages.document.goods_store_report',
                compact('datas', 'dateTime', 'setting', 'start_date', 'end_date', 'code_store', 'store_signature', 'quantitiesOnly'),
                [],
                'UTF-8'
            )
            ->setPaper('a4', 'landscape');

        return $pdf->download('RAPPORT_STOCK_MARCHANDISES_' . $dateTime . '.pdf');
    }
}
