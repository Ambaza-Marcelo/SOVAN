<?php

namespace App\Http\Controllers\Backend;

use App\Exports\RawMaterialExtraBigStoreReportExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Backend\Concerns\HandlesStockReports;
use App\Models\RawMaterialExtraBigReport;
use App\Models\RawMaterialExtraBigStore;
use App\Support\StockReportContext;
use App\Support\StockReportFilter;
use Carbon\Carbon;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDF;

class RawMaterialExtraBigReportController extends Controller
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
        return StockReportContext::filterConfig('raw_material_extra_big');
    }

    public function index(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_report.view')) {
            abort(403, 'Muradutunge !! Ntaburenganzira mufise bwo kuraba raporo,mufise ico mubaza murashobora guhamagara kuri 122 !');
        }

        if ($request->boolean('apply_filter') && !StockReportFilter::requiresDateRange($request)) {
            return redirect()->route('admin.raw-material-extra-big-store-report.index')
                ->with('error', __('messages.export_select_date_range'));
        }

        $stores = RawMaterialExtraBigStore::all();
        $filterConfig = $this->stockReportFilterConfigWithStores($stores);

        $query = RawMaterialExtraBigReport::with('rawMaterial')
            ->select(DB::raw('id,created_at,r_material_id,code_store,quantity_stock_initial,value_stock_initial,quantity_stockin,value_stockin,quantity_stockout,value_stockout,quantity_stock_final,value_stock_final,document_no,description,created_by,type_transaction'))
            ->groupBy('id', 'created_at', 'r_material_id', 'code_store', 'quantity_stock_initial', 'value_stock_initial', 'quantity_stockin', 'value_stockin', 'quantity_stockout', 'value_stockout', 'quantity_stock_final', 'value_stock_final', 'document_no', 'created_by', 'description', 'type_transaction')
            ->orderBy('id', 'desc');

        StockReportFilter::apply($query, $request, $filterConfig);

        $datas = $this->normalizeStockReportRows($query->get());

        return view('backend.pages.raw_material_extra_big_store_report.index', array_merge(
            $this->stockReportViewData($request, $stores),
            compact('datas')
        ));
    }

    public function exportToPdf(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_report.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any report !');
        }

        if ($redirect = $this->validateStockReportDates($request, 'admin.raw-material-extra-big-store-report.index')) {
            return $redirect;
        }

        $this->mergeExportRequest($request);

        $range = StockReportFilter::dateRange($request);
        $start_date = $range[0]->format('Y-m-d H:i:s');
        $end_date = $range[1]->format('Y-m-d H:i:s');
        $code_store = $request->input('code_store');
        $store_signature = RawMaterialExtraBigStore::where('code', $code_store)->value('store_signature');

        $query = RawMaterialExtraBigReport::with('rawMaterial')
            ->select(DB::raw('id,created_at,r_material_id,code_store,quantity_stock_initial,quantity_stockin,quantity_stockout,quantity_stock_final,document_no,description,created_by,type_transaction'))
            ->groupBy('id', 'created_at', 'r_material_id', 'code_store', 'quantity_stock_initial', 'quantity_stockin', 'quantity_stockout', 'quantity_stock_final', 'document_no', 'created_by', 'description', 'type_transaction')
            ->orderBy('id', 'asc');

        StockReportFilter::apply($query, $request, $this->stockReportFilterConfig());
        $datas = $this->normalizeStockReportRows($query->get());

        $setting = DB::table('settings')->orderBy('created_at', 'desc')->first();
        $dateTime = str_replace([' ', ':'], '_', Carbon::now()->toDateTimeString());
        $quantitiesOnly = true;

        $pdf = PDF::setOptions(['defaultFont' => 'dejavu sans', 'isRemoteEnabled' => true])
            ->loadView(
                'backend.pages.document.raw_material_extra_big_store_report',
                compact('datas', 'dateTime', 'setting', 'start_date', 'end_date', 'code_store', 'store_signature', 'quantitiesOnly'),
                [],
                'UTF-8'
            )
            ->setPaper('a4', 'landscape');

        Storage::put('public/raw_material_extra_big_store_report/MOUVEMENT_DE_GRAND_STOCK_' . $range[0]->format('Y-m-d') . '_' . $range[1]->format('Y-m-d') . '.pdf', $pdf->output());

        return $pdf->download('MOUVEMENT_DE_GRAND_STOCK_' . $dateTime . '.pdf');
    }

    public function print(Request $request)
    {
        return $this->exportToPdf($request);
    }

    public function exportToExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('raw_material_extra_big_report.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export this report !');
        }

        if ($redirect = $this->validateStockReportDates($request, 'admin.raw-material-extra-big-store-report.index')) {
            return $redirect;
        }

        $this->mergeExportRequest($request);
        $range = StockReportFilter::dateRange($request);
        $filename = 'rapport_grand_stock_matieres_premieres_' . $range[0]->format('Y-m-d') . '_' . $range[1]->format('Y-m-d') . '.xlsx';

        return Excel::download(new RawMaterialExtraBigStoreReportExport(), $filename);
    }
}
