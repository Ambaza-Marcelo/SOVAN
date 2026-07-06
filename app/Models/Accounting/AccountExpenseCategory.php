<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountExpenseCategory extends Model
{
    //
    protected $table = 'account_expense_categories';

    protected $fillable = [
        'name',
    ];

    public function expense(){
        return $this->hasMany('App\Models\Accounting\AccountExpense','expense_category_id');
    }
}
