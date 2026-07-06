<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountCurrency extends Model
{
    //
    protected $table = 'currencies';

    protected $fillable = [
        'currency_name',
        'symbol',
        'rounding',
        'decimal_places',
        'active',
        'position',
        'currency_unit_label'
    ];

    public function account(){
        return $this->hasMany('App\Models\Accounting\AccountAccount','currency_id');
    }

    public function parentAccount(){
        return $this->hasMany('App\Models\Accounting\AccountParentAccount','currency_id');
    }
}
