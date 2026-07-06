<?php

namespace App\Http\Controllers\Backend;

use App\Exports\MaterialMdStoreReportExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Backend\Concerns\HandlesStockReports;
use App\Models\MaterialBigReport;
use App\Models\MaterialBigStore;
use App\Support\StockReportContext;
use App\Support\StockReportFilter;
use Carbon\Carbon;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDF;

class MaterialBigReportController extends Controller
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
        return StockReportContext::filterConfig('material_big');
    }

    public function index(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('material_big_report.view')) {
            abort(403, 'Muradutunge !! Ntaburenganzira mufise bwo kuraba raporo,mufise ico mubaza murashobora guhamagara kuri 122 !');
        }

        if ($request->boolean('apply_filter') && !StockReportFilter::requiresDateRange($request)) {
            return redirect()->route('admin.material-big-store-report.index')
                ->with('error', __('messages.export_select_date_range'));
        }

        $stores = MaterialBigStore::all();
        $filterConfig = $this->stockReportFilterConfigWithStores($stores);

        $query = MaterialBigReport::with('material')
            ->select(DB::raw('id,created_at,material_id,code_store,quantity_stock_initial,value_stock_initial,quantity_stockin,value_stockin,quantity_stockout,value_stockout,quantity_stock_final,value_stock_final,cump,type_transaction,document_no,description'))
            ->groupBy('id', 'created_at', 'material_id', 'code_store', 'quantity_stock_initial', 'value_stock_initial', 'quantity_stockin', 'value_stockin', 'quantity_stockout', 'value_stockout', 'quantity_stock_final', 'value_stock_final', 'cump', 'type_transaction', 'document_no', 'description')
            ->orderBy('id', 'desc');

        StockReportFilter::apply($query, $request, $filterConfig);

        $datas = $this->normalizeStockReportRows($query->get());

        return view('backend.pages.material_big_store_report.index', array_merge(
            $this->stockReportViewData($request, $stores),
            compact('datas')
        ));
    }

    public function exportToPdf(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('material_big_report.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any report !');
        }

        if ($redirect = $this->validateStockReportDates($request, 'admin.material-big-store-report.index')) {
            return $redirect;
        }

        $this->mergeExportRequest($request);

        $range = StockReportFilter::dateRange($request);
        $start_date = $range[0]->format('Y-m-d H:i:s');
        $end_date = $range[1]->format('Y-m-d H:i:s');
        $code_store = $request->input('code_store');
        $store_signature = MaterialBigStore::where('code', $code_store)->value('store_signature');

        $query = MaterialBigReport::with('material')
            ->select(DB::raw('id,created_at,material_id,code_store,quantity_stock_initial,quantity_stockin,quantity_stockout,quantity_stock_final,cump,type_transaction,document_no,description'))
            ->groupBy('id', 'created_at', 'material_id', 'code_store', 'quantity_stock_initial', 'quantity_stockin', 'quantity_stockout', 'quantity_stock_final', 'cump', 'type_transaction', 'document_no', 'description')
            ->orderBy('id', 'asc');

        StockReportFilter::apply($query, $request, $this->stockReportFilterConfig());
        $datas = $this->normalizeStockReportRows($query->get());

        $setting = DB::table('settings')->orderBy('created_at', 'desc')->first();
        $dateTime = str_replace([' ', ':'], '_', Carbon::now()->toDateTimeString());
        $quantitiesOnly = true;

        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])
            ->loadView(
                'backend.pages.document.material_big_store_report',
                compact('datas', 'dateTime', 'setting', 'start_date', 'end_date', 'code_store', 'store_signature', 'quantitiesOnly'),
                [],
                'UTF-8'
            )
            ->setPaper('a4', 'landscape');

        Storage::put('public/material_big_store_report/RAPPORT_GRAND_STOCK_MATERIEL_' . $range[0]->format('Y-m-d') . '_' . $range[1]->format('Y-m-d') . '.pdf', $pdf->output());

        return $pdf->download('RAPPORT_GRAND_STOCK_MATERIEL_' . $dateTime . '.pdf');
    }

    public function print(Request $request)
    {
        return $this->exportToPdf($request);
    }

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('material_big_report.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export this report !');
        }

        if ($redirect = $this->validateStockReportDates($request, 'admin.material-big-store-report.index')) {
            return $redirect;
        }

        $this->mergeExportRequest($request);
        $range = StockReportFilter::dateRange($request);
        $filename = 'rapport_stock_intermediaire_materiels_' . $range[0]->format('Y-m-d') . '_' . $range[1]->format('Y-m-d') . '.xlsx';

        return Excel::download(new MaterialMdStoreReportExport(), $filename);
    }
}
