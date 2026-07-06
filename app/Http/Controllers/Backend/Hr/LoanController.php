<?php

namespace App\Http\Controllers\Backend\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\HrLoan;
use App\Models\HrEmploye;
use Carbon\Carbon;
use Validator;
use PDF;

class LoanController extends Controller
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

    public function index()
    {
        if (is_null($this->user) || !$this->user->can('hr_loan.view')) {
            abort(403, 'Sorry!! you are Unauthorized to view loans');
        }

        $loans = HrLoan::with('employe')->orderBy('created_at', 'desc')->get();
        $employes = HrEmploye::all();

        return view('backend.pages.hr.loan.index',compact('loans','employes'));
    }

    public function create()
    {
        if (is_null($this->user) || !$this->user->can('hr_loan.create')) {
            abort(403, 'Sorry!! you are Unauthorized to create loan');
        }

        $employes = HrEmploye::all();
        $employeIdsWithOutstandingLoan = $this->getEmployeIdsWithOutstandingLoan();

        return view('backend.pages.hr.loan.create', compact('employes', 'employeIdsWithOutstandingLoan'));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_loan.create')) {
            abort(403, 'Sorry!! you are Unauthorized to create loan');
        }

        $request->validate([
            'date' => 'required|date',
            'amount_loan' => 'required|numeric|min:0',
            'nbre_tranche' => 'required|numeric|min:1|max:3',
            'employe_id' => 'required|exists:hr_employes,id',
            'description' => 'nullable|string',
        ]);

        if (HrLoan::hasOutstandingLoan((int) $request->employe_id)) {
            session()->flash('error', 'Cet employé possède déjà une dette en attente de paiement ou partiellement payée. Il ne peut pas prendre une nouvelle avance tant que toutes les tranches ne sont pas soldées.');
            return back()->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            $data['created_by'] = $this->user->name;
            $data['status'] = 0; // Pending

            // Calculate amounts based on nbre_tranche
            $amountPerMonth = $data['amount_loan'] / $data['nbre_tranche'];
            
            if ($data['nbre_tranche'] == 3) {
                $data['amount_to_pay_1m'] = $amountPerMonth;
                $data['amount_to_pay_2m'] = $amountPerMonth;
                $data['amount_to_pay_3m'] = $amountPerMonth;
            } elseif ($data['nbre_tranche'] == 2) {
                $data['amount_to_pay_1m'] = $amountPerMonth;
                $data['amount_to_pay_2m'] = $amountPerMonth;
                $data['amount_to_pay_3m'] = 0;
            } else { // nbre_tranche == 1
                $data['amount_to_pay_1m'] = $data['amount_loan'];
                $data['amount_to_pay_2m'] = 0;
                $data['amount_to_pay_3m'] = 0;
            }

            HrLoan::create($data);

            DB::commit();
            session()->flash('success', 'Loan has been created successfully !!');
            return redirect()->route('admin.hr-loans.index');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Something went wrong!! '.$e->getMessage());
            return back();
        }
    }

    public function show($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_loan.view')) {
            abort(403, 'Sorry!! you are Unauthorized to view loan');
        }

        $loan = HrLoan::with('employe')->findOrFail($id);

        return view('backend.pages.hr.loan.show', compact('loan'));
    }

    public function loanDocument($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_loan.view')) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }
        

        $loan = HrLoan::where('id',$id)->first();

        $setting = DB::table('settings')->orderBy('created_at','desc')->first();


        $pdf = PDF::loadView('backend.pages.hr.document.avance_sur_salaire',compact('loan','setting'));
        // download pdf file
        return $pdf->download('AVANCE SUR SALAIRE '.$loan->employe->firstname.' '.$loan->employe->lastname.'.pdf');
    }

    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_loan.edit')) {
            abort(403, 'Sorry!! you are Unauthorized to edit loan');
        }

        $loan = HrLoan::with('employe')->findOrFail($id);

        // Check if loan can be edited (status 0, 1, or 2)
        if ($loan->status == 3) {
            session()->flash('error', 'Cannot edit loan that is already approved!');
            return redirect()->route('admin.hr-loans.index');
        }

        $employes = HrEmploye::all();
        $employeIdsWithOutstandingLoan = $this->getEmployeIdsWithOutstandingLoan((int) $id);

        return view('backend.pages.hr.loan.edit', compact('loan', 'employes', 'employeIdsWithOutstandingLoan'));
    }

    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('hr_loan.edit')) {
            abort(403, 'Sorry!! you are Unauthorized to update loan');
        }

        $loan = HrLoan::findOrFail($id);

        // Check if loan can be edited (status 0, 1, or 2)
        if ($loan->status == 3) {
            session()->flash('error', 'Cannot update loan that is already approved!');
            return redirect()->route('admin.hr-loans.index');
        }

        $request->validate([
            'date' => 'required|date',
            'amount_loan' => 'required|numeric|min:0',
            'nbre_tranche' => 'required|numeric|min:1|max:3',
            'employe_id' => 'required|exists:hr_employes,id',
            'description' => 'nullable|string',
        ]);

        if (HrLoan::hasOutstandingLoan((int) $request->employe_id, (int) $id)) {
            session()->flash('error', 'L\'employé sélectionné possède déjà une autre dette en attente ou partiellement payée. Une nouvelle avance n\'est possible que lorsque toutes les tranches sont soldées.');
            return back()->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $request->all();

            // Calculate amounts based on nbre_tranche
            $amountPerMonth = $data['amount_loan'] / $data['nbre_tranche'];
            
            if ($data['nbre_tranche'] == 3) {
                $data['amount_to_pay_1m'] = $amountPerMonth;
                $data['amount_to_pay_2m'] = $amountPerMonth;
                $data['amount_to_pay_3m'] = $amountPerMonth;
            } elseif ($data['nbre_tranche'] == 2) {
                $data['amount_to_pay_1m'] = $amountPerMonth;
                $data['amount_to_pay_2m'] = $amountPerMonth;
                $data['amount_to_pay_3m'] = 0;
            } else { // nbre_tranche == 1
                $data['amount_to_pay_1m'] = $data['amount_loan'];
                $data['amount_to_pay_2m'] = 0;
                $data['amount_to_pay_3m'] = 0;
            }

            $loan->update($data);

            DB::commit();
            session()->flash('success', 'Loan has been updated successfully !!');
            return redirect()->route('admin.hr-loans.index');
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Something went wrong!! '.$e->getMessage());
            return back();
        }
    }

    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_loan.delete')) {
            abort(403, 'Sorry!! you are Unauthorized to delete loan');
        }

        $loan = HrLoan::findOrFail($id);

        // Check if loan can be deleted (status 0, 1, or 2)
        if ($loan->status == 3) {
            session()->flash('error', 'Cannot delete loan that is already approved!');
            return redirect()->route('admin.hr-loans.index');
        }

        try {
            $loan->delete();
            session()->flash('success', 'Loan has been deleted successfully !!');
        } catch (\Exception $e) {
            session()->flash('error', 'Something went wrong!! '.$e->getMessage());
        }

        return redirect()->route('admin.hr-loans.index');
    }

    public function validateLoan($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_loan.edit')) {
            abort(403, 'Sorry!! you are Unauthorized to validate loan');
        }

        try {
            DB::beginTransaction();

            $loan = HrLoan::findOrFail($id);
            $loan->update([
                'status' => 1,
                'validated_by' => $this->user->name
            ]);

            DB::commit();
            session()->flash('success', 'Loan has been validated successfully !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Something went wrong!! '.$e->getMessage());
            return back();
        }
    }

    public function confirm($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_loan.edit')) {
            abort(403, 'Sorry!! you are Unauthorized to confirm loan');
        }

        try {
            DB::beginTransaction();

            $loan = HrLoan::findOrFail($id);
            $loan->update([
                'status' => 2,
                'confirmed_by' => $this->user->name
            ]);

            DB::commit();
            session()->flash('success', 'Loan has been confirmed successfully !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Something went wrong!! '.$e->getMessage());
            return back();
        }
    }

    public function approve($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_loan.edit')) {
            abort(403, 'Sorry!! you are Unauthorized to approve loan');
        }

        try {
            DB::beginTransaction();

            $loan = HrLoan::findOrFail($id);
            $loan->update([
                'status' => 3,
                'approuved_by' => $this->user->name
            ]);

            DB::commit();
            session()->flash('success', 'Loan has been approved successfully !!');
            return back();
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Something went wrong!! '.$e->getMessage());
            return back();
        }
    }

    /**
     * Retourne les IDs des employés qui ont au moins une avance en cours (status 0,1,2,3,4) non totalement payée.
     * @param int|null $excludeLoanId Exclure ce prêt du calcul (pour l'édition).
     */
    protected function getEmployeIdsWithOutstandingLoan(?int $excludeLoanId = null): array
    {
        $loans = HrLoan::whereIn('status', HrLoan::activeStatuses())->get();
        if ($excludeLoanId !== null) {
            $loans = $loans->filter(function (HrLoan $loan) use ($excludeLoanId) {
                return (int) $loan->id !== $excludeLoanId;
            });
        }
        return $loans->filter(function (HrLoan $loan) {
            return !$loan->isFullyPaid();
        })
            ->pluck('employe_id')
            ->unique()
            ->values()
            ->toArray();
    }
}
