<?php

namespace App\Http\Controllers\Backend;

use App\Exports\Immobilisations\ImmobilisationsExport;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AmortissementService;
use Auth;
use Excel;
use Illuminate\Http\Request;
use PDF;

class ImmobilisationExportController extends Controller
{
    /** @var \App\Models\User|null */
    public $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }

    /**
     * Export Excel : onglet Registre + onglet Plans d'amortissement (cascade).
     */
    public function exportExcel(Request $request, AmortissementService $amortissement)
    {
        if (is_null($this->user) || !$this->user->can('material.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export immobilisations !');
        }

        $asOfYear = (int) ($request->input('year') ?: date('Y'));
        $rows = $amortissement->buildRegister($asOfYear);
        $exportedAt = now()->format('d/m/Y H:i');

        return Excel::download(
            new ImmobilisationsExport($rows, $exportedAt),
            'registre_immobilisations_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    /**
     * Export PDF paysage : registre + plans détaillés.
     */
    public function exportPdf(Request $request, AmortissementService $amortissement)
    {
        if (is_null($this->user) || !$this->user->can('material.view')) {
            abort(403, 'Sorry !! You are Unauthorized to export immobilisations !');
        }

        $asOfYear = (int) ($request->input('year') ?: date('Y'));
        $rows = $amortissement->buildRegister($asOfYear);
        $setting = Setting::first();
        $exportedAt = now()->format('d/m/Y H:i');

        $pdf = PDF::setOptions([
            'defaultFont' => 'dejavu sans',
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ])->loadView('exports.immobilisations-pdf', compact('rows', 'setting', 'exportedAt', 'asOfYear'))
          ->setPaper('a4', 'landscape');

        // Rendu puis pagination « Page X sur Y » (appliquée à la sortie)
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->get_font('DejaVu Sans');
        $canvas->page_text(
            720,
            570,
            'Page {PAGE_NUM} sur {PAGE_COUNT}',
            $font,
            8,
            [0.2, 0.2, 0.2]
        );

        return $pdf->download('registre_immobilisations_' . now()->format('Ymd_His') . '.pdf');
    }
}
