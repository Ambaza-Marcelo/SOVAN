<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Stock\SauJournalRectificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SauRectificationController extends Controller
{
    public $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }

    protected function canView(): bool
    {
        return $this->canApply();
    }

    protected function canApply(): bool
    {
        return $this->user && $this->user->can('account_journal_entry.edit');
    }

    public function index(Request $request, SauJournalRectificationService $service)
    {
        if (!$this->canView()) {
            abort(403, 'Permission « modifier les écritures comptables » requise pour la rectification des sorties.');
        }

        $documentNo = $request->input('document_no') ?: null;
        $plans = $service->plan($documentNo);

        $accountIds = $service->genericAccountIds();
        foreach ($plans as $plan) {
            foreach ($plan['lines'] as $line) {
                foreach ($line['allocations'] as $allocation) {
                    $accountIds[] = $allocation['account_id'];
                }
            }
        }
        $accountLabels = $service->accountLabels($accountIds);

        $readyCount = count(array_filter($plans, function ($plan) {
            return $plan['status'] === SauJournalRectificationService::STATUS_READY;
        }));

        return view('backend.pages.accounting.sau_rectification.index', [
            'plans' => $plans,
            'documentNo' => $documentNo,
            'accountLabels' => $accountLabels,
            'genericAccountIds' => $service->genericAccountIds(),
            'readyCount' => $readyCount,
            'canApply' => $this->canApply(),
        ]);
    }

    public function apply(Request $request, SauJournalRectificationService $service)
    {
        if (!$this->canApply()) {
            abort(403, 'Permission « modifier les écritures comptables » requise pour la rectification des sorties.');
        }

        $request->validate(['confirm' => 'accepted']);

        $documentNo = $request->input('document_no') ?: null;
        $summary = $service->apply($documentNo);

        $message = sprintf(
            'Rectification terminée : %d pièce(s) rectifiée(s), %d ligne(s) réimputée(s), %d pièce(s) ignorée(s).',
            $summary['applied'],
            $summary['lines'],
            $summary['skipped']
        );

        if (!empty($summary['errors'])) {
            session()->flash('error', $message . ' Erreurs : ' . implode(' ; ', $summary['errors']));
        } else {
            session()->flash('success', $message);
        }

        return redirect()->route('admin.accounting-sau-rectification.index', array_filter(['document_no' => $documentNo]));
    }
}
