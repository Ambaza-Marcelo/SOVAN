<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AccountJournalEntryDetail extends Model
{
    //
    protected $table = 'account_journal_entry_details';

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
        'account_id',
        'document_no',
        'libelle',
        'credit',
        'debit',
        'balance',
    ];

    public function account(){
        return $this->belongsTo('App\Models\Accounting\AccountAccount');
    }

    /**
     * Restrict to rows whose piece code and/or document number matches any token.
     *
     * @param  array<int, string>  $tokens
     */
    public function scopeWherePieceOrDocumentIn(Builder $query, array $tokens): Builder
    {
        $tokens = array_values(array_unique(array_filter(array_map('trim', $tokens))));
        if ($tokens === []) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($tokens) {
            $q->whereIn('code', $tokens)
                ->orWhereIn('document_no', $tokens);
        });
    }
}
