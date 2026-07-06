<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountPaymentAdvance extends Model
{
    //
    protected $fillable = [
        'date',
        'code',
        'payment_type',
        'amount',
        'description',
        'status',
        'contact_name',
        'contact_tel',
        'created_by',
        'confirmed_by',
        'approuved_by',
        'client_id',
        'document',
        'account_id',
        'advance_account_id'
    ];

    protected $casts = [
        'date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function client(){
        return $this->belongsTo('App\Models\EGRClient', 'client_id');
    }

    public function account(){
        return $this->belongsTo('App\Models\Accounting\AccountAccount', 'account_id');
    }

    public function advanceAccount(){
        return $this->belongsTo('App\Models\Accounting\AccountAccount', 'advance_account_id');
    }
}
