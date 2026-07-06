<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountLedger extends Model
{
    //
    protected $table = 'account_ledgers';

    protected $fillable = [
        'date',
        'fiscal_year',
        'credit',
        'debit',
        'balance',
        'account_id',
        'exercise_id'
    ];

    public function account(){
        return $this->belongsTo('App\Models\Accounting\AccountAccount');
    }

}
