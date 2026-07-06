<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountAccount extends Model
{
    //
    protected $table = 'account_accounts';

    protected $fillable = [
        'account_name',
        'account_number',
        'account_type',
        'balance_must_be',
        'description',
        'currency_id',
        'acc_parent_id'
    ];

    public function currency(){
        return $this->belongsTo('App\Models\Accounting\AccountCurrency');
    }

    public function accParent(){
        return $this->belongsTo('App\Models\Accounting\AccountParentAccount');
    }

    public function costCenter(){
        return $this->hasMany('App\Models\Accounting\AccountCostCenter','default_account_id');
    }

    public function fundTransfer(){
        return $this->hasMany('App\Models\Accounting\FundTransfer',['origin_account_id','destination_account_id']);
    }

    public function expense(){
        return $this->hasMany('App\Models\Accounting\AccountExpense','default_account_id');
    }

    public function employe(){
        return $this->hasMany('App\Models\HrEmploye','account_id');
    }

    public function accountSetting(){
        return $this->hasMany('App\Models\Accounting\AccountSetting',[
            'vat_collec_account_id',
            'vat_deduc_account_id',
            'd_purchase_account_id',
            'd_selling_account_id',
            'd_saj_account_id',
            'd_sp_account_id',
            'd_sv_account_id',
            'd_sc_account_id',
            'd_eaj_account_id',
            'f_purchase_account_id',
            'f_selling_account_id',
            'f_saj_account_id',
            'f_sp_account_id',
            'f_sv_account_id',
            'f_sc_account_id',
            'f_eaj_account_id',
            'm_purchase_account_id',
            'm_selling_account_id',
            'm_saj_account_id',
            'm_sp_account_id',
            'm_sv_account_id',
            'm_sc_account_id',
            'm_eaj_account_id',
            'profit_account_id',
            'loss_account_id',
            'bank_account_id',
        ]);
    }

    public function supplier(){
        return $this->hasMany('App\Models\Supplier','account_id');
    }

    public function client(){
        return $this->hasMany('App\Models\EGRClient','account_id');
    }

    public function cashOutFlow(){
        return $this->hasMany('App\Models\Accounting\CashOutFlow','account_id');
    }

    public function cashOutFlowDetail(){
        return $this->hasMany('App\Models\Accounting\CashOutFlowDetail','account_id');
    }

    public function accountLedger(){
        return $this->hasMany('App\Models\Accounting\AccountLedger','account_id');
    }

    public function accountJournalEntryDetail(){
        return $this->hasMany('App\Models\Accounting\AccountJournalEntryDetail','account_id');
    }

}
