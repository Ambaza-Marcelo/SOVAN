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
        ]);

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
        ]);

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
