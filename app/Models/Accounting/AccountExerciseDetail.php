<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountExerciseDetail extends Model
{
    protected $table = 'account_exercise_details';

    protected $fillable = [
        'exercise_id',
        'step',
        'account_id',
        'document_no',
        'libelle',
        'debit',
        'credit',
        'fiscal_year',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function exercise()
    {
        return $this->belongsTo(AccountExercise::class, 'exercise_id');
    }

    public function account()
    {
        return $this->belongsTo(AccountAccount::class, 'account_id');
    }
}
