<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountExercise extends Model
{
    protected $table = 'account_exercises';

    /** Statuts entiers (périodes / PeriodLockService) */
    public const STATUS_DRAFT = 0;
    public const STATUS_OPEN = 1;
    public const STATUS_SOFT_CLOSED = 2;
    public const STATUS_CLOSED = 3;

    /** Statuts du workflow de clôture 4 étapes (colonne closing_status) */
    public const CLOSING_OPEN = 'open';
    public const CLOSING_PREVIEW = 'preview';
    public const CLOSING_DEPRECIATION_DONE = 'depreciation_done';
    public const CLOSING_RESULT_DONE = 'result_done';
    public const CLOSING_RAN_DONE = 'ran_done';
    public const CLOSING_LOCKED = 'locked';

    /** Alias EGOTEK (closing_status) */
    public const STATUS_PREVIEW = 'preview';
    public const STATUS_DEPRECIATION_DONE = 'depreciation_done';
    public const STATUS_RESULT_DONE = 'result_done';
    public const STATUS_RAN_DONE = 'ran_done';
    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'code',
        'name',
        'label',
        'fiscal_year',
        'date_start',
        'date_end',
        'status',
        'closing_status',
        'is_current',
        'profit_account_id',
        'loss_account_id',
        'retained_earnings_account_id',
        'description',
        'posting_date',
        'document_no',
        'depreciation_total',
        'depreciation_lines',
        'result_total',
        'result_lines',
        'result_document_no',
        'ran_total',
        'ran_document_no',
        'result_is_profit',
        'notes',
        'created_by',
        'closed_by',
        'closed_at',
        'locked_by',
        'locked_at',
        'next_fiscal_year',
        'reopened_by',
        'reopened_at',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'posting_date' => 'date',
        'is_current' => 'boolean',
        'status' => 'integer',
        'closed_at' => 'datetime',
        'locked_at' => 'datetime',
        'reopened_at' => 'datetime',
        'depreciation_total' => 'decimal:2',
        'depreciation_lines' => 'integer',
        'result_total' => 'decimal:2',
        'result_lines' => 'integer',
        'ran_total' => 'decimal:2',
        'result_is_profit' => 'boolean',
    ];

    public function periods()
    {
        return $this->hasMany(AccountPeriod::class, 'exercise_id')->orderBy('period_no');
    }

    public function closingRuns()
    {
        return $this->hasMany(AccountClosingRun::class, 'exercise_id')->orderByDesc('id');
    }

    public function details()
    {
        return $this->hasMany(AccountExerciseDetail::class, 'exercise_id');
    }

    public function profitAccount()
    {
        return $this->belongsTo(AccountAccount::class, 'profit_account_id');
    }

    public function lossAccount()
    {
        return $this->belongsTo(AccountAccount::class, 'loss_account_id');
    }

    public function retainedEarningsAccount()
    {
        return $this->belongsTo(AccountAccount::class, 'retained_earnings_account_id');
    }

    public function isOpen(): bool
    {
        return (int) $this->status === self::STATUS_OPEN;
    }

    /**
     * Exercice définitivement clôturé (périodes + verrouillage année).
     */
    public function isClosed(): bool
    {
        return (int) $this->status === self::STATUS_CLOSED || $this->isLocked();
    }

    public function isLocked(): bool
    {
        return $this->closing_status === self::CLOSING_LOCKED
            || $this->closing_status === self::STATUS_LOCKED
            || !empty($this->locked_at)
            || (int) $this->status === self::STATUS_CLOSED;
    }

    public function hasDepreciationDone(): bool
    {
        return in_array($this->closing_status, [
            self::CLOSING_DEPRECIATION_DONE,
            self::CLOSING_RESULT_DONE,
            self::CLOSING_RAN_DONE,
            self::CLOSING_LOCKED,
        ], true) || (float) ($this->depreciation_lines ?? 0) > 0;
    }

    public function hasResultDone(): bool
    {
        return in_array($this->closing_status, [
            self::CLOSING_RESULT_DONE,
            self::CLOSING_RAN_DONE,
            self::CLOSING_LOCKED,
        ], true) || !empty($this->result_document_no);
    }

    public function hasRanDone(): bool
    {
        return in_array($this->closing_status, [
            self::CLOSING_RAN_DONE,
            self::CLOSING_LOCKED,
        ], true) || !empty($this->ran_document_no);
    }

    public function statusLabel(): string
    {
        $closingMap = [
            self::CLOSING_OPEN => 'Ouvert',
            self::CLOSING_PREVIEW => 'Prévisualisation',
            self::CLOSING_DEPRECIATION_DONE => 'Amortissements OK',
            self::CLOSING_RESULT_DONE => 'Résultat OK',
            self::CLOSING_RAN_DONE => 'Report à nouveau OK',
            self::CLOSING_LOCKED => 'Verrouillé',
        ];

        if (!empty($this->closing_status) && isset($closingMap[$this->closing_status])) {
            return $closingMap[$this->closing_status];
        }

        $labels = [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_OPEN => 'Ouvert',
            self::STATUS_SOFT_CLOSED => 'Pré-clôturé',
            self::STATUS_CLOSED => 'Clôturé',
        ];

        return $labels[(int) $this->status] ?? 'Inconnu';
    }

    public function statusBadgeClass(): string
    {
        if ($this->isLocked()) {
            return 'badge-dark';
        }
        if ($this->hasRanDone()) {
            return 'badge-warning';
        }
        if ($this->hasResultDone()) {
            return 'badge-warning';
        }
        if ($this->hasDepreciationDone()) {
            return 'badge-warning';
        }

        return 'badge-warning';
    }

    public function scopeLocked($query)
    {
        return $query->where(function ($q) {
            $q->where('closing_status', self::CLOSING_LOCKED)
                ->orWhere('status', self::STATUS_CLOSED)
                ->orWhereNotNull('locked_at');
        });
    }

    public function scopeOpen($query)
    {
        return $query->where('closing_status', '!=', self::CLOSING_LOCKED)
            ->where('status', '!=', self::STATUS_CLOSED);
    }

    public static function current()
    {
        return static::where('is_current', true)->where('status', '!=', self::STATUS_CLOSED)->first()
            ?: static::where('status', self::STATUS_OPEN)->orderByDesc('date_start')->first();
    }
}
