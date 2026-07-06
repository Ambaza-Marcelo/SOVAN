<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class CashOutFlow extends Model
{
    //
    protected $table = 'cash_outflows';

    protected $fillable = [
        'date',
        'cash_outlfow_no',
        'cash_outlfow_signature',
        'cash_outlfow__request_no',
        'asker',
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
        'vat_supplier_payer',
        'vat_rate',
        'supplier_id',
        'cost_center_id',
        'employe_id',
        'account_id',
        'loan_id',
        'currency_id'
    ];

    public function currency(){
        return $this->belongsTo('App\Models\Accounting\AccountCurrency');
    }

    public function supplier(){
        return $this->belongsTo('App\Models\Supplier');
    }

    public function costCenter(){
        return $this->belongsTo('App\Models\Accounting\CostCenter');
    }

    public function employe(){
        return $this->belongsTo('App\Models\HrEmploye');
    }

    public function account(){
        return $this->belongsTo('App\Models\Accounting\AccountAccount');
    }
}
