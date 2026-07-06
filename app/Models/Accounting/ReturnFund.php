<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class ReturnFund extends Model
{
    protected $table = 'return_funds';

    protected $fillable = [
        'date',
        'return_no',
        'cash_outflow_no',
        'description',
        'rejected_motif',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'rejected_by',
        'reseted_by',
        'status',
        'flag1',
        'flag2',
        'total_amount_picked',
        'total_amount_returned',
        'return_signature',
        'employe_id',
        'account_id'
    ];

    protected $casts = [
        'date' => 'datetime',
        'total_amount_picked' => 'decimal:2',
        'total_amount_returned' => 'decimal:2',
    ];

    public function employe(){
        return $this->belongsTo('App\Models\HrEmploye', 'employe_id');
    }

    public function account(){
        return $this->belongsTo('App\Models\Accounting\AccountAccount', 'account_id');
    }
}
