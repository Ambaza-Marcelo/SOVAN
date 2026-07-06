<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountJournalEntry extends Model
{
    //
    protected $table = 'account_journal_entries';

    protected $fillable = [
        'name',
        'date',
        'code',
        'type',
        'fiscal_year',
        'description',
        'active',
        'loss_account_id',
        'profit_account_id',
        'account_id'
    ];

    public function account(){
        return $this->belongsTo('App\Models\Accounting\AccountAccount');
    }
}
