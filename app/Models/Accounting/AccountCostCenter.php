<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountCostCenter extends Model
{
    //
    protected $table = 'account_cost_centers';

    protected $fillable = [
        'name',
        'default_account_id'
    ];

    public function account(){
        return $this->belongsTo('App\Models\Accounting\AccountAccount');
    }
}
