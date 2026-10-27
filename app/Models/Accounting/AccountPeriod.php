<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountPeriod extends Model
{
    protected $table = 'account_periods';

    public const STATUS_OPEN = 0;
    public const STATUS_LOCKED = 1;
    public const STATUS_CLOSED = 2;

    protected $fillable = [
        'exercise_id',
        'period_no',
        'name',
        'date_start',
        'date_end',
        'status',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'status' => 'integer',
        'period_no' => 'integer',
        'locked_at' => 'datetime',
    ];

    public function exercise()
    {
        return $this->belongsTo(AccountExercise::class, 'exercise_id');
    }

    public function isWritable(): bool
    {
        return (int) $this->status === self::STATUS_OPEN;
    }

    public function statusLabel(): string
    {
        $labels = [
            self::STATUS_OPEN => 'Ouvert',
            self::STATUS_LOCKED => 'Verrouillé',
            self::STATUS_CLOSED => 'Clôturé',
        ];

        return $labels[(int) $this->status] ?? 'Inconnu';
    }
}
