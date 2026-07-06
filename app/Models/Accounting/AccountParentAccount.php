<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountParentAccount extends Model
{
    //
    protected $table = 'account_parent_accounts';

    protected $fillable = [
        'account_name',
        'account_number',
        'account_type',
        'balance_must_be',
        'description',
        'currency_id',
    ];

    public function currency(){
        return $this->belongsTo('App\Models\Accounting\AccountCurrency');
    }

    public function account(){
        return $this->hasMany('App\Models\Accounting\AccountAccount','acc_parent_id')->orderBy('account_number', 'asc');
    }
}
