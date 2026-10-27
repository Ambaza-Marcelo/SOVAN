<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountClosingRun extends Model
{
    protected $table = 'account_closing_runs';

    public const TYPE_PERIOD_LOCK = 'period_lock';
    public const TYPE_YEAR_CLOSE = 'year_close';
    public const TYPE_YEAR_REOPEN = 'year_reopen';
    public const TYPE_OPENING_BALANCE = 'opening_balance';

    public const STATUS_PENDING = 0;
    public const STATUS_DONE = 1;
    public const STATUS_FAILED = -1;

    protected $fillable = [
        'exercise_id',
        'period_id',
        'type',
        'status',
        'journal_entry_id',
        'document_no',
        'notes',
        'snapshot',
        'created_by',
    ];

    protected $casts = [
        'status' => 'integer',
        'snapshot' => 'array',
    ];

    public function exercise()
    {
        return $this->belongsTo(AccountExercise::class, 'exercise_id');
    }

    public function period()
    {
        return $this->belongsTo(AccountPeriod::class, 'period_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(AccountJournalEntry::class, 'journal_entry_id');
    }
}
