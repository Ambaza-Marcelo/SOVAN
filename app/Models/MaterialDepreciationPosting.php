<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialDepreciationPosting extends Model
{
    protected $fillable = [
        'material_id',
        'fiscal_year',
        'schedule_year',
        'amount',
        'posting_date',
        'document_no',
        'journal_entry_detail_id',
        'status',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'schedule_year' => 'integer',
        'amount' => 'decimal:2',
        'posting_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
