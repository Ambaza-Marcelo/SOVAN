<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountExercise;
use App\Models\Accounting\AccountExerciseDetail;
use App\Models\Accounting\AccountPeriod;
use App\Models\Accounting\AccountSetting;
use App\Models\MaterialDepreciationPosting;
use App\Services\Accounting\DepreciationClosingBridge;
use App\Services\Accounting\YearClosingService;
use App\Services\Accounting\YearEndClosingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Gestion des exercices et clôture comptable 4 étapes (EGOTEK).
 * Permissions : account_exercise.*
 */
class AccountExerciseController extends Controller
{
    /** @var \App\Models\Admin|null */
    public $user;

    /** @var YearClosingService */
    protected $closingService;

    public function __construct(YearClosingService $closingService)
    {
        $this->closingService = $closingService;
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }

    protected function canViewClose(): bool
    {
        if (is_null($this->user)) {
            return false;
        }

        return $this->user->can('account_exercise.view')
            || $this->user->can('account_exercise.close')
            || $this->user->can('account_setting.view')
            || $this->user->can('account_journal_entry.view');
    }

    protected function canExecuteClose(): bool
    {
        if (is_null($this->user)) {
            return false;
        }

        return $this->user->can('account_exercise.close')
            || $this->user->can('account_exercise.approuve')
            || $this->user->can('account_journal_entry.create');
    }

    /**
     * Tableau de bord clôture (4 étapes).
     */
    public function index(DepreciationClosingBridge $bridge, YearEndClosingService $yearEnd)
    {
        if (!$this->canViewClose()) {
            abort(403, 'Non autorisé à consulter la clôture comptable.');
        }

        $exercises = AccountExercise::orderByDesc('fiscal_year')->get();
        $activeYear = $bridge->resolveActiveFiscalYear();
        $pendingTotal = $bridge->totalCharge($activeYear);
        $pendingCount = $bridge->preview($activeYear)->count();
        $exercise = AccountExercise::where('fiscal_year', (string) $activeYear)->first();
        $settings = AccountSetting::where('active', 1)->first();

        $resultPreview = null;
        $ranPreview = null;
        $settingsOk = false;
        $settingsError = null;
        try {
            $yearEnd->requireClosingAccounts();
            $settingsOk = true;
            $resultPreview = $yearEnd->previewResult($activeYear);
            if ($exercise && $exercise->hasResultDone() && !$exercise->hasRanDone()) {
                $ranPreview = $yearEnd->previewRan($activeYear);
            }
        } catch (\Throwable $e) {
            $settingsOk = false;
            $settingsError = $e->getMessage();
        }

        return view('backend.pages.accounting.exercise.index', compact(
            'exercises',
            'activeYear',
            'pendingTotal',
            'pendingCount',
            'exercise',
            'settings',
            'settingsOk',
            'resultPreview',
            'ranPreview'
        ) + ['settingsError' => $settingsError]);
    }

    public function create()
    {
        if (is_null($this->user) || !$this->user->can('account_exercise.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer un exercice comptable.');
        }

        $accounts = AccountAccount::orderBy('account_number')->get();
        $setting = AccountSetting::where('active', 1)->first();

        return view('backend.pages.accounting.exercise.create', compact('accounts', 'setting'));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_exercise.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer un exercice comptable.');
        }

        $request->validate([
            'fiscal_year' => 'required|string|max:10|unique:account_exercises,fiscal_year',
            'name' => 'required|string|max:255',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'profit_account_id' => 'nullable|exists:account_accounts,id',
            'loss_account_id' => 'nullable|exists:account_accounts,id',
            'retained_earnings_account_id' => 'nullable|exists:account_accounts,id',
        ]);

        try {
            DB::beginTransaction();

            if ($request->has('is_current')) {
                AccountExercise::query()->update(['is_current' => false]);
            }

            $exercise = AccountExercise::create([
                'code' => 'EX' . $request->fiscal_year,
                'name' => $request->name,
                'label' => $request->name,
                'fiscal_year' => $request->fiscal_year,
                'date_start' => $request->date_start,
                'date_end' => $request->date_end,
                'status' => AccountExercise::STATUS_OPEN,
                'closing_status' => AccountExercise::CLOSING_OPEN,
                'is_current' => $request->has('is_current'),
                'profit_account_id' => $request->profit_account_id ?: null,
                'loss_account_id' => $request->loss_account_id ?: null,
                'retained_earnings_account_id' => $request->retained_earnings_account_id ?: null,
                'description' => $request->description,
                'created_by' => $this->user->name,
            ]);

            $this->closingService->ensureMonthlyPeriods($exercise);

            if ($exercise->is_current) {
                $setting = AccountSetting::where('active', 1)->first();
                if ($setting) {
                    $setting->fiscal_year = $exercise->fiscal_year;
                    $setting->exercise_id = $exercise->id;
                    if ($exercise->profit_account_id) {
                        $setting->profit_account_id = $exercise->profit_account_id;
                        if (empty($setting->result_account_id)) {
                            $setting->result_account_id = $exercise->profit_account_id;
                        }
                    }
                    if ($exercise->loss_account_id) {
                        $setting->loss_account_id = $exercise->loss_account_id;
                    }
                    if ($exercise->retained_earnings_account_id) {
                        $setting->retained_earnings_account_id = $exercise->retained_earnings_account_id;
                        if (empty($setting->ran_profit_account_id)) {
                            $setting->ran_profit_account_id = $exercise->retained_earnings_account_id;
                        }
                    }
                    $setting->save();
                }
            }

            DB::commit();
            session()->flash('success', 'Exercice créé et périodes mensuelles générées.');
            return redirect()->route('admin.accounting-exercises.show', $exercise->id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Étape 1 — prévisualisation amortissements.
     */
    public function preview(Request $request, DepreciationClosingBridge $bridge)
    {
        if (!$this->canViewClose()) {
            abort(403, 'Non autorisé à consulter la clôture comptable.');
        }

        $fiscalYear = (int) ($request->input('fiscal_year') ?: $bridge->resolveActiveFiscalYear());
        $postingDate = $request->filled('posting_date')
            ? Carbon::parse($request->input('posting_date'))
            : Carbon::create($fiscalYear, 12, 31);

        $candidates = $bridge->previewAll($fiscalYear, $postingDate);
        $pending = $candidates->where('already_posted', false)->values();
        $posted = $candidates->where('already_posted', true)->values();
        $totalPending = round((float) $pending->sum('amount'), 2);
        $exercise = AccountExercise::where('fiscal_year', (string) $fiscalYear)->first();
        $settings = AccountSetting::where('active', 1)->first();

        return view('backend.pages.accounting.exercise.preview', compact(
            'fiscalYear',
            'postingDate',
            'candidates',
            'pending',
            'posted',
            'totalPending',
            'exercise',
            'settings'
        ));
    }

    /**
     * Étape 1 — exécuter amortissements.
     */
    public function close(Request $request, DepreciationClosingBridge $bridge)
    {
        if (!$this->canExecuteClose()) {
            abort(403, 'Non autorisé à exécuter la clôture comptable.');
        }

        $request->validate([
            'fiscal_year' => 'required|integer|min:2000|max:2100',
            'posting_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
            'confirm' => 'accepted',
        ], [
            'confirm.accepted' => 'Vous devez confirmer la clôture des dotations d\'amortissement.',
        ]);

        $fiscalYear = (int) $request->input('fiscal_year');
        $postingDate = Carbon::parse($request->input('posting_date'));

        try {
            $result = $bridge->executeDepreciationClosing(
                $fiscalYear,
                $this->user->name,
                $postingDate,
                $request->input('notes')
            );

            session()->flash(
                'success',
                sprintf(
                    'Étape 1 OK — %d : %d dotation(s) pour %s BIF (pièce %s).',
                    $fiscalYear,
                    $result['posted_count'],
                    number_format($result['total'], 0, ',', ' '),
                    $result['document_no']
                )
            );

            return redirect()->route('admin.accounting-exercises.show', $result['exercise']->id);
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
            return redirect()
                ->route('admin.accounting-exercises.preview', [
                    'fiscal_year' => $fiscalYear,
                    'posting_date' => $postingDate->format('Y-m-d'),
                ])
                ->withInput();
        }
    }

    /**
     * Étape 2 — prévisualisation clôture résultat.
     */
    public function previewResult(Request $request, DepreciationClosingBridge $bridge, YearEndClosingService $yearEnd)
    {
        if (!$this->canViewClose()) {
            abort(403, 'Non autorisé à consulter la clôture comptable.');
        }

        $fiscalYear = (int) ($request->input('fiscal_year') ?: $bridge->resolveActiveFiscalYear());
        $postingDate = $request->filled('posting_date')
            ? Carbon::parse($request->input('posting_date'))
            : Carbon::create($fiscalYear, 12, 31);
        $exercise = AccountExercise::where('fiscal_year', (string) $fiscalYear)->first();

        try {
            $preview = $yearEnd->previewResult($fiscalYear);
            $settingsOk = true;
            $settingsError = null;
            $yearEnd->requireClosingAccounts();
        } catch (\Throwable $e) {
            $preview = [
                'charges' => collect(),
                'products' => collect(),
                'charges_total' => 0,
                'products_total' => 0,
                'net' => 0,
                'is_profit' => true,
                'result_account' => null,
                'lines' => collect(),
            ];
            $settingsOk = false;
            $settingsError = $e->getMessage();
        }

        return view('backend.pages.accounting.exercise.preview_result', compact(
            'fiscalYear',
            'postingDate',
            'exercise',
            'preview',
            'settingsOk',
            'settingsError'
        ));
    }

    /**
     * Étape 2 — exécuter clôture résultat.
     */
    public function closeResult(Request $request, YearEndClosingService $yearEnd)
    {
        if (!$this->canExecuteClose()) {
            abort(403, 'Non autorisé à exécuter la clôture comptable.');
        }

        $request->validate([
            'fiscal_year' => 'required|integer|min:2000|max:2100',
            'posting_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
            'confirm' => 'accepted',
        ], [
            'confirm.accepted' => 'Confirmez la clôture des charges et produits.',
        ]);

        $fiscalYear = (int) $request->input('fiscal_year');
        $postingDate = Carbon::parse($request->input('posting_date'));

        try {
            $yearEnd->ensureExercise($fiscalYear, $this->user->name);
            $result = $yearEnd->executeResultClosing(
                $fiscalYear,
                $this->user->name,
                $postingDate,
                $request->input('notes')
            );

            session()->flash(
                'success',
                sprintf(
                    'Étape 2 OK — Résultat %s : %s BIF (%s), pièce %s.',
                    $fiscalYear,
                    number_format($result['net'], 0, ',', ' '),
                    $result['is_profit'] ? 'bénéfice' : 'perte',
                    $result['document_no']
                )
            );

            return redirect()->route('admin.accounting-exercises.show', $result['exercise']->id);
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
            return redirect()
                ->route('admin.accounting-exercises.preview-result', [
                    'fiscal_year' => $fiscalYear,
                    'posting_date' => $postingDate->format('Y-m-d'),
                ])
                ->withInput();
        }
    }

    /**
     * Étape 3 — prévisualisation report à nouveau.
     */
    public function previewRan(Request $request, DepreciationClosingBridge $bridge, YearEndClosingService $yearEnd)
    {
        if (!$this->canViewClose()) {
            abort(403, 'Non autorisé à consulter la clôture comptable.');
        }

        $fiscalYear = (int) ($request->input('fiscal_year') ?: $bridge->resolveActiveFiscalYear());
        $postingDate = $request->filled('posting_date')
            ? Carbon::parse($request->input('posting_date'))
            : Carbon::create($fiscalYear, 12, 31);
        $exercise = AccountExercise::where('fiscal_year', (string) $fiscalYear)->first();

        try {
            $preview = $yearEnd->previewRan($fiscalYear);
            $settingsError = null;
        } catch (\Throwable $e) {
            $preview = [
                'amount' => 0,
                'is_profit' => true,
                'result_balance' => 0,
                'result_account' => null,
                'ran_account' => null,
            ];
            $settingsError = $e->getMessage();
        }

        return view('backend.pages.accounting.exercise.preview_ran', compact(
            'fiscalYear',
            'postingDate',
            'exercise',
            'preview',
            'settingsError'
        ));
    }

    /**
     * Étape 3 — exécuter report à nouveau.
     */
    public function closeRan(Request $request, YearEndClosingService $yearEnd)
    {
        if (!$this->canExecuteClose()) {
            abort(403, 'Non autorisé à exécuter la clôture comptable.');
        }

        $request->validate([
            'fiscal_year' => 'required|integer|min:2000|max:2100',
            'posting_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
            'confirm' => 'accepted',
        ], [
            'confirm.accepted' => 'Confirmez le report à nouveau.',
        ]);

        $fiscalYear = (int) $request->input('fiscal_year');
        $postingDate = Carbon::parse($request->input('posting_date'));

        try {
            $result = $yearEnd->executeRanClosing(
                $fiscalYear,
                $this->user->name,
                $postingDate,
                $request->input('notes')
            );

            session()->flash(
                'success',
                sprintf(
                    'Étape 3 OK — Report à nouveau %s : %s BIF (%s), pièce %s.',
                    $fiscalYear,
                    number_format($result['amount'], 0, ',', ' '),
                    $result['is_profit'] ? 'bénéfice' : 'perte',
                    $result['document_no']
                )
            );

            return redirect()->route('admin.accounting-exercises.show', $result['exercise']->id);
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
            return redirect()
                ->route('admin.accounting-exercises.preview-ran', [
                    'fiscal_year' => $fiscalYear,
                    'posting_date' => $postingDate->format('Y-m-d'),
                ])
                ->withInput();
        }
    }

    /**
     * Étape 4 — verrouiller la période.
     */
    public function lock(Request $request, YearEndClosingService $yearEnd)
    {
        if (!$this->canExecuteClose()) {
            abort(403, 'Non autorisé à verrouiller l\'exercice.');
        }

        $request->validate([
            'fiscal_year' => 'required|integer|min:2000|max:2100',
            'advance_year' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
            'confirm' => 'accepted',
        ], [
            'confirm.accepted' => 'Confirmez le verrouillage de l\'exercice.',
        ]);

        $fiscalYear = (int) $request->input('fiscal_year');
        $advance = $request->has('advance_year');

        try {
            $result = $yearEnd->lockPeriod(
                $fiscalYear,
                $this->user->name,
                $advance,
                $request->input('notes')
            );

            $msg = 'Étape 4 OK — Exercice '.$fiscalYear.' verrouillé.';
            if (!empty($result['next_year'])) {
                $msg .= ' Exercice actif passé à '.$result['next_year'].'.';
            }
            session()->flash('success', $msg);

            return redirect()->route('admin.accounting-exercises.show', $result['exercise']->id);
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
            return redirect()->route('admin.accounting-exercises.index');
        }
    }

    /**
     * Détail d'un exercice.
     */
    public function show($id, DepreciationClosingBridge $bridge)
    {
        if (!$this->canViewClose()) {
            abort(403, 'Non autorisé à consulter la clôture comptable.');
        }

        $exercise = AccountExercise::with(['periods'])->findOrFail($id);
        $fiscalYear = (int) $exercise->fiscal_year;
        $postings = MaterialDepreciationPosting::with('material')
            ->where('fiscal_year', $fiscalYear)
            ->orderBy('id')
            ->get();
        $pending = $bridge->preview($fiscalYear);
        $details = AccountExerciseDetail::with('account')
            ->where('exercise_id', $exercise->id)
            ->orderBy('id')
            ->get();

        return view('backend.pages.accounting.exercise.show', compact(
            'exercise',
            'postings',
            'pending',
            'fiscalYear',
            'details'
        ));
    }

    /**
     * Verrouillage manuel d'une période mensuelle (conservé).
     */
    public function lockPeriod($periodId)
    {
        if (is_null($this->user) || !$this->user->can('account_exercise.close')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de verrouiller une période.');
        }

        $period = AccountPeriod::findOrFail($periodId);

        try {
            $this->closingService->lockPeriod($period, $this->user->name);
            session()->flash('success', 'Période « ' . $period->name . ' » verrouillée.');
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }

        return back();
    }
}
