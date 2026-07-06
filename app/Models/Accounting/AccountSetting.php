<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountSetting extends Model
{
    protected $table = 'account_settings';

    protected $fillable = [
        'date',
        'code',
        'fiscal_year',
        'active',
        'vat_collec_account_id',
        'vat_deduc_account_id',
        'f_selling_account_id',
        'f_sm_selling_account_id',
        'service_selling_account_id',
        'goods_selling_account_id',
        'basic_salary_account_id',
        'f_allowance_account_id',
        'h_allowance_account_id',
        't_allowance_account_id',
        'o_allowance_account_id',
        'gross_salary_account_id',
        'ded1_salary_account_id',
        'ded2_salary_account_id',
        'cot_employee_account_id',
        'cot_employer_account_id',
        'tax_salary_account_id',
        'med_care_salary_account_id',
        'insurance_employee_account_id',
        'insurance_employer_account_id',
        'loan_salary_account_id',
        'net_salary_account_id',
        'f_saj_account_id',
        'f_sp_account_id',
        'f_sv_account_id',
        'f_sc_account_id',
        'f_eaj_account_id',
        'm_purchase_account_id',
        'm_saj_account_id',
        'm_sp_account_id',
        'm_sv_account_id',
        'm_sc_account_id',
        'm_eaj_account_id',
        'raw_mat_purchase_account_id',
        'raw_mat_saj_account_id',
        'raw_mat_sp_account_id',
        'raw_mat_sv_account_id',
        'raw_mat_sc_account_id',
        'raw_mat_eaj_account_id',
        'temp_account_id',
        'adv_payment_account_id',
        'currency_id',
        'bank_statements_source',
        'exercise_id',
        'prod_bg_account_id',
        'prod_sm_account_id'
    ];

    protected $casts = [
        'date' => 'datetime',
        'active' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo('App\Models\Accounting\AccountAccount');
    }
}
