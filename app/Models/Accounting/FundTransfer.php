<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class FundTransfer extends Model
{
    //
    protected $fillable = [
        'date',
        'code',
        'transfer_type',
        'amount',
        'description',
        'status',
        'created_by',
        'confirmed_by',
        'approuved_by',
        'origin_account_id',
        'destination_account_id'
    ];

    public function originAccount(){
        return $this->belongsTo('App\Models\Accounting\AccountAccount', 'origin_account_id');
    }

    public function DestinationAccount(){
        return $this->belongsTo('App\Models\Accounting\AccountAccount', 'destination_account_id');
    }
}
