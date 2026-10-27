<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountSetting;
use App\Models\Accounting\AccountCurrency;

class AccountSettingController extends Controller
{
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
        if (is_null($this->user) || !$this->user->can('account_setting.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser le paramètre! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $settings = AccountSetting::all();

        return view('backend.pages.accounting.account_setting.index', compact('settings'));
    }

    public function create(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_setting.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer le paramètre! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $accounts = AccountAccount::orderBy('account_number', 'asc')->get();
        $currencies = AccountCurrency::all();

        return view('backend.pages.accounting.account_setting.create', compact('accounts', 'currencies'));
    }

    /**
     * Libellés affichés dans les messages de validation à la place des noms techniques de champs.
     */
    protected function validationAttributes(): array
    {
        $labels = [
            'date' => 'messages.Date',
            'code' => 'messages.Code',
            'fiscal_year' => 'messages.Fiscal Year',
            'active' => 'messages.Status',
            'currency_id' => 'messages.Currency',
            'vat_collec_account_id' => 'messages.VAT Collected Account',
            'vat_deduc_account_id' => 'messages.VAT Deductible Account',
            'f_selling_account_id' => 'messages.Finished Goods Selling Account',
            'f_sm_selling_account_id' => 'messages.Finished Goods SM Selling Account',
            'service_selling_account_id' => 'messages.Service Selling Account',
            'goods_selling_account_id' => 'messages.Goods Selling Account',
            'f_saj_account_id' => 'messages.Finished Goods SAJ Account',
            'f_sp_account_id' => 'messages.Finished Goods SP Account',
            'f_sv_account_id' => 'messages.Finished Goods SV Account',
            'f_sc_account_id' => 'messages.Finished Goods SC Account',
            'f_eaj_account_id' => 'messages.Finished Goods EAJ Account',
            'm_purchase_account_id' => 'messages.Material Purchase Account',
            'm_saj_account_id' => 'messages.Material SAJ Account',
            'm_sp_account_id' => 'messages.Material SP Account',
            'm_sv_account_id' => 'messages.Material SV Account',
            'm_sc_account_id' => 'messages.Material SC Account',
            'm_eaj_account_id' => 'messages.Material EAJ Account',
            'raw_mat_purchase_account_id' => 'messages.Raw Material Purchase Account',
            'raw_mat_saj_account_id' => 'messages.Raw Material SAJ Account',
            'raw_mat_sp_account_id' => 'messages.Raw Material SP Account',
            'raw_mat_sv_account_id' => 'messages.Raw Material SV Account',
            'raw_mat_sc_account_id' => 'messages.Raw Material SC Account',
            'raw_mat_eaj_account_id' => 'messages.Raw Material EAJ Account',
            'basic_salary_account_id' => 'messages.Basic Salary Account',
            'f_allowance_account_id' => 'messages.F Allowance Account',
            'h_allowance_account_id' => 'messages.H Allowance Account',
            't_allowance_account_id' => 'messages.T Allowance Account',
            'o_allowance_account_id' => 'messages.O Allowance Account',
            'gross_salary_account_id' => 'messages.Gross Salary Account',
            'ded1_salary_account_id' => 'messages.Deduction 1 Salary Account',
            'ded2_salary_account_id' => 'messages.Deduction 2 Salary Account',
            'cot_employee_account_id' => 'messages.COT Employee Account',
            'cot_employer_account_id' => 'messages.COT Employer Account',
            'tax_salary_account_id' => 'messages.Tax Salary Account',
            'med_care_salary_account_id' => 'messages.Medical Care Salary Account',
            'insurance_employee_account_id' => 'messages.Insurance Employee Account',
            'insurance_employer_account_id' => 'messages.Insurance Employer Account',
            'loan_salary_account_id' => 'messages.Loan Salary Account',
            'net_salary_account_id' => 'messages.Net Salary Account',
            'temp_account_id' => 'messages.Temporary Account',
            'adv_payment_account_id' => 'messages.Advance payment Account',
            'prod_bg_account_id' => 'messages.Main stock',
            'prod_sm_account_id' => 'messages.Small production stock',
        ];

        $attributes = array_map(function ($key) {
            return __($key);
        }, $labels);

        return array_merge($attributes, [
            'result_account_id' => 'Compte de résultat (12)',
            'ran_profit_account_id' => 'Report à nouveau bénéfice (120)',
            'ran_loss_account_id' => 'Report à nouveau perte (129)',
            'profit_account_id' => 'Compte bénéfice',
            'loss_account_id' => 'Compte perte',
            'retained_earnings_account_id' => 'Report à nouveau',
        ]);
    }

    protected function validationMessages(): array
    {
        return [
            'required' => 'Le champ « :attribute » est obligatoire.',
        ];
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_setting.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer le paramètre! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $request->validate([
            'date' => 'required',
            'fiscal_year' => 'required',
            'active' => 'required',
            'currency_id' => 'required',
            'vat_collec_account_id' => 'required',
            'vat_deduc_account_id' => 'required',
            'f_selling_account_id' => 'required',
            'f_sm_selling_account_id' => 'required',
            'service_selling_account_id' => 'required',
            'goods_selling_account_id' => 'required',
            'f_saj_account_id' => 'required',
            'f_sp_account_id' => 'required',
            'f_sv_account_id' => 'required',
            'f_sc_account_id' => 'required',
            'f_eaj_account_id' => 'required',
            'm_purchase_account_id' => 'required',
            'm_saj_account_id' => 'required',
            'm_sp_account_id' => 'required',
            'm_sv_account_id' => 'required',
            'm_sc_account_id' => 'required',
            'm_eaj_account_id' => 'required',
            'raw_mat_purchase_account_id' => 'nullable',
            'raw_mat_saj_account_id' => 'nullable',
            'raw_mat_sp_account_id' => 'nullable',
            'raw_mat_sv_account_id' => 'nullable',
            'raw_mat_sc_account_id' => 'nullable',
            'raw_mat_eaj_account_id' => 'nullable',
            'basic_salary_account_id' => 'required',
            'f_allowance_account_id' => 'required',
            'h_allowance_account_id' => 'required',
            't_allowance_account_id' => 'required',
            'o_allowance_account_id' => 'nullable',
            'gross_salary_account_id' => 'nullable',
            'ded1_salary_account_id' => 'nullable',
            'ded2_salary_account_id' => 'nullable',
            'cot_employee_account_id' => 'required',
            'cot_employer_account_id' => 'required',
            'tax_salary_account_id' => 'required',
            'med_care_salary_account_id' => 'nullable',
            'insurance_employee_account_id' => 'nullable',
            'insurance_employer_account_id' => 'nullable',
            'loan_salary_account_id' => 'nullable',
            'net_salary_account_id' => 'required',
            'temp_account_id' => 'nullable',
            'adv_payment_account_id' => 'nullable',
            'prod_bg_account_id' => 'nullable',
            'prod_sm_account_id' => 'nullable',
        ], $this->validationMessages(), $this->validationAttributes());

        $latest = AccountSetting::latest()->first();
        $code = $latest ? str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT) : str_pad(1, 4, '0', STR_PAD_LEFT);

        $setting = new AccountSetting();
        $setting->date = $request->date;
        $setting->code = $code;
        $setting->fiscal_year = $request->fiscal_year;
        $setting->active = $request->active;
        $setting->vat_collec_account_id = $request->vat_collec_account_id;
        $setting->vat_deduc_account_id = $request->vat_deduc_account_id;
        $setting->f_selling_account_id = $request->f_selling_account_id;
        $setting->f_sm_selling_account_id = $request->f_sm_selling_account_id;
        $setting->service_selling_account_id = $request->service_selling_account_id;
        $setting->goods_selling_account_id = $request->goods_selling_account_id;
        $setting->f_saj_account_id = $request->f_saj_account_id;
        $setting->f_sp_account_id = $request->f_sp_account_id;
        $setting->f_sv_account_id = $request->f_sv_account_id;
        $setting->f_sc_account_id = $request->f_sc_account_id;
        $setting->f_eaj_account_id = $request->f_eaj_account_id;
        $setting->m_purchase_account_id = $request->m_purchase_account_id;
        $setting->m_saj_account_id = $request->m_saj_account_id;
        $setting->m_sp_account_id = $request->m_sp_account_id;
        $setting->m_sv_account_id = $request->m_sv_account_id;
        $setting->m_sc_account_id = $request->m_sc_account_id;
        $setting->m_eaj_account_id = $request->m_eaj_account_id;
        $setting->raw_mat_purchase_account_id = $request->raw_mat_purchase_account_id;
        $setting->raw_mat_saj_account_id = $request->raw_mat_saj_account_id;
        $setting->raw_mat_sp_account_id = $request->raw_mat_sp_account_id;
        $setting->raw_mat_sv_account_id = $request->raw_mat_sv_account_id;
        $setting->raw_mat_sc_account_id = $request->raw_mat_sc_account_id;
        $setting->raw_mat_eaj_account_id = $request->raw_mat_eaj_account_id;
        $setting->basic_salary_account_id = $request->basic_salary_account_id;
        $setting->f_allowance_account_id = $request->f_allowance_account_id;
        $setting->h_allowance_account_id = $request->h_allowance_account_id;
        $setting->t_allowance_account_id = $request->t_allowance_account_id;
        $setting->o_allowance_account_id = $request->o_allowance_account_id;
        $setting->gross_salary_account_id = $request->gross_salary_account_id;
        $setting->ded1_salary_account_id = $request->ded1_salary_account_id;
        $setting->ded2_salary_account_id = $request->ded2_salary_account_id;
        $setting->cot_employee_account_id = $request->cot_employee_account_id;
        $setting->cot_employer_account_id = $request->cot_employer_account_id;
        $setting->tax_salary_account_id = $request->tax_salary_account_id;
        $setting->med_care_salary_account_id = $request->med_care_salary_account_id;
        $setting->insurance_employee_account_id = $request->insurance_employee_account_id;
        $setting->insurance_employer_account_id = $request->insurance_employer_account_id;
        $setting->loan_salary_account_id = $request->loan_salary_account_id;
        $setting->net_salary_account_id = $request->net_salary_account_id;
        $setting->temp_account_id = $request->temp_account_id;
        $setting->adv_payment_account_id = $request->adv_payment_account_id;
        $setting->prod_bg_account_id = $request->prod_bg_account_id;
        $setting->prod_sm_account_id = $request->prod_sm_account_id;
        $setting->currency_id = $request->currency_id;
        $setting->bank_statements_source = $request->bank_statements_source;
        $setting->exercise_id = $request->exercise_id;
        $setting->save();

        session()->flash('success', 'paramètre est créé !!');

        return redirect()->route('admin.accounting-settings.index');
    }

    public function show($id)
    {
        if (is_null($this->user) || !$this->user->can('account_setting.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le paramètre! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $setting = AccountSetting::findOrFail($id);
        $accounts = AccountAccount::orderBy('account_number', 'asc')->get();
        $currencies = AccountCurrency::all();

        return view('backend.pages.accounting.account_setting.show', compact('setting', 'accounts', 'currencies'));
    }

    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('account_setting.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le paramètre! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $setting = AccountSetting::findOrFail($id);
        $accounts = AccountAccount::orderBy('account_number', 'asc')->get();
        $currencies = AccountCurrency::all();

        return view('backend.pages.accounting.account_setting.edit', compact('setting', 'accounts', 'currencies'));
    }

    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('account_setting.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le paramètre! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $request->validate([
            'date' => 'required',
            'code' => 'required',
            'fiscal_year' => 'required',
            'active' => 'required',
            'currency_id' => 'required',
            'vat_collec_account_id' => 'required',
            'vat_deduc_account_id' => 'required',
            'f_selling_account_id' => 'required',
            'f_sm_selling_account_id' => 'required',
            'service_selling_account_id' => 'required',
            'goods_selling_account_id' => 'required',
            'f_saj_account_id' => 'required',
            'f_sp_account_id' => 'required',
            'f_sv_account_id' => 'required',
            'f_sc_account_id' => 'required',
            'f_eaj_account_id' => 'required',
            'm_purchase_account_id' => 'required',
            'm_saj_account_id' => 'required',
            'm_sp_account_id' => 'required',
            'm_sv_account_id' => 'required',
            'm_sc_account_id' => 'required',
            'm_eaj_account_id' => 'required',
            'raw_mat_purchase_account_id' => 'nullable',
            'raw_mat_saj_account_id' => 'nullable',
            'raw_mat_sp_account_id' => 'nullable',
            'raw_mat_sv_account_id' => 'nullable',
            'raw_mat_sc_account_id' => 'nullable',
            'raw_mat_eaj_account_id' => 'nullable',
            'basic_salary_account_id' => 'required',
            'f_allowance_account_id' => 'required',
            'h_allowance_account_id' => 'required',
            't_allowance_account_id' => 'required',
            'o_allowance_account_id' => 'nullable',
            'gross_salary_account_id' => 'nullable',
            'ded1_salary_account_id' => 'nullable',
            'ded2_salary_account_id' => 'nullable',
            'cot_employee_account_id' => 'required',
            'cot_employer_account_id' => 'required',
            'tax_salary_account_id' => 'required',
            'med_care_salary_account_id' => 'nullable',
            'insurance_employee_account_id' => 'nullable',
            'insurance_employer_account_id' => 'nullable',
            'loan_salary_account_id' => 'nullable',
            'net_salary_account_id' => 'required',
            'temp_account_id' => 'nullable',
            'adv_payment_account_id' => 'nullable',
            'prod_bg_account_id' => 'nullable',
            'prod_sm_account_id' => 'nullable',
            'result_account_id' => 'nullable|exists:account_accounts,id',
            'ran_profit_account_id' => 'nullable|exists:account_accounts,id',
            'ran_loss_account_id' => 'nullable|exists:account_accounts,id',
            'profit_account_id' => 'nullable|exists:account_accounts,id',
            'loss_account_id' => 'nullable|exists:account_accounts,id',
            'retained_earnings_account_id' => 'nullable|exists:account_accounts,id',
        ], $this->validationMessages(), $this->validationAttributes());

        $setting = AccountSetting::findOrFail($id);

        $setting->date = $request->date;
        $setting->code = $request->code;
        $setting->fiscal_year = $request->fiscal_year;
        $setting->active = $request->active;
        $setting->vat_collec_account_id = $request->vat_collec_account_id;
        $setting->vat_deduc_account_id = $request->vat_deduc_account_id;
        $setting->f_selling_account_id = $request->f_selling_account_id;
        $setting->f_sm_selling_account_id = $request->f_sm_selling_account_id;
        $setting->service_selling_account_id = $request->service_selling_account_id;
        $setting->goods_selling_account_id = $request->goods_selling_account_id;
        $setting->f_saj_account_id = $request->f_saj_account_id;
        $setting->f_sp_account_id = $request->f_sp_account_id;
        $setting->f_sv_account_id = $request->f_sv_account_id;
        $setting->f_sc_account_id = $request->f_sc_account_id;
        $setting->f_eaj_account_id = $request->f_eaj_account_id;
        $setting->m_purchase_account_id = $request->m_purchase_account_id;
        $setting->m_saj_account_id = $request->m_saj_account_id;
        $setting->m_sp_account_id = $request->m_sp_account_id;
        $setting->m_sv_account_id = $request->m_sv_account_id;
        $setting->m_sc_account_id = $request->m_sc_account_id;
        $setting->m_eaj_account_id = $request->m_eaj_account_id;
        $setting->raw_mat_purchase_account_id = $request->raw_mat_purchase_account_id;
        $setting->raw_mat_saj_account_id = $request->raw_mat_saj_account_id;
        $setting->raw_mat_sp_account_id = $request->raw_mat_sp_account_id;
        $setting->raw_mat_sv_account_id = $request->raw_mat_sv_account_id;
        $setting->raw_mat_sc_account_id = $request->raw_mat_sc_account_id;
        $setting->raw_mat_eaj_account_id = $request->raw_mat_eaj_account_id;
        $setting->basic_salary_account_id = $request->basic_salary_account_id;
        $setting->f_allowance_account_id = $request->f_allowance_account_id;
        $setting->h_allowance_account_id = $request->h_allowance_account_id;
        $setting->t_allowance_account_id = $request->t_allowance_account_id;
        $setting->o_allowance_account_id = $request->o_allowance_account_id;
        $setting->gross_salary_account_id = $request->gross_salary_account_id;
        $setting->ded1_salary_account_id = $request->ded1_salary_account_id;
        $setting->ded2_salary_account_id = $request->ded2_salary_account_id;
        $setting->cot_employee_account_id = $request->cot_employee_account_id;
        $setting->cot_employer_account_id = $request->cot_employer_account_id;
        $setting->tax_salary_account_id = $request->tax_salary_account_id;
        $setting->med_care_salary_account_id = $request->med_care_salary_account_id;
        $setting->insurance_employee_account_id = $request->insurance_employee_account_id;
        $setting->insurance_employer_account_id = $request->insurance_employer_account_id;
        $setting->loan_salary_account_id = $request->loan_salary_account_id;
        $setting->net_salary_account_id = $request->net_salary_account_id;
        $setting->temp_account_id = $request->temp_account_id;
        $setting->adv_payment_account_id = $request->adv_payment_account_id;
        $setting->prod_bg_account_id = $request->prod_bg_account_id;
        $setting->prod_sm_account_id = $request->prod_sm_account_id;
        $setting->result_account_id = $request->result_account_id ?: null;
        $setting->ran_profit_account_id = $request->ran_profit_account_id ?: null;
        $setting->ran_loss_account_id = $request->ran_loss_account_id ?: null;
        $setting->profit_account_id = $request->profit_account_id ?: $setting->profit_account_id;
        $setting->loss_account_id = $request->loss_account_id ?: $setting->loss_account_id;
        $setting->retained_earnings_account_id = $request->retained_earnings_account_id ?: $setting->retained_earnings_account_id;
        $setting->currency_id = $request->currency_id;
        $setting->bank_statements_source = $request->bank_statements_source;
        $setting->exercise_id = $request->exercise_id;
        $setting->save();

        session()->flash('success', 'paramètre est modifié !!');
        return redirect()->route('admin.accounting-settings.index');
    }

    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('account_setting.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer le paramètre! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $setting = AccountSetting::findOrFail($id);
        $setting->delete();
        session()->flash('success', 'paramètre est supprimé !!');
        return redirect()->back();
    }
}
