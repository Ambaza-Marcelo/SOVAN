<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountExpense extends Model
{
    //
    protected $table = 'account_expenses';

    protected $fillable = [
        'name',
        'code',
        'specification',
        'quantity',
        'cost_price',
        'status',
        'total_cost',
        'updated_by',
        'created_by',
        'expense_category_id',
        'default_account_id'
    ];

    public function expenseCategory(){
        return $this->belongsTo('App\Models\Accounting\AccountExpenseCategory');
    }

    public function defaultAccount(){
        return $this->belongsTo('App\Models\Accounting\AccountAccount');
    }

    public function cashOutFlowDetail(){
        return $this->hasMany('App\Models\Accounting\CashOutFlowDetail','expense_id');
    }
}
