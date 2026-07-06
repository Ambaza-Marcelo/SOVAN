<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class CashOutFlowDetail extends Model
{
    //
    protected $table = 'cash_outflow_details';

    protected $fillable = [
        'date',
        'cash_outflow_no',
        'cash_outflow_signature',
        'cash_outflow_request_no',
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
        'currency_id',
        'quantity',
        'cost_price',
        'cost_nvat',
        'vat',
        'cost_wvat',
        'loan_id',
        'r_material_id',
        'material_id',
        'item_id',
        'expense_id'
    ];

    public function item(){
        return $this->belongsTo('App\Models\Item');
    }

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

    public function expense(){
        return $this->belongsTo('App\Models\Accounting\AccountExpense');
    }

    public function rawMaterial(){
        return $this->belongsTo('App\Models\RawMaterial', 'r_material_id');
    }

    public function material(){
        return $this->belongsTo('App\Models\Material');
    }

    public function loan(){
        return $this->belongsTo('App\Models\HrLoan');
    }
}
