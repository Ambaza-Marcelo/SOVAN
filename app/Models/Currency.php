<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'currency_name',
        'symbol',
        'rounding',
        'decimal_places',
        'active',
        'position',
        'currency_unit_label',
        'currency_subunit_label',
    ];

    protected $casts = [
        'active' => 'boolean',
        'rounding' => 'float',
        'decimal_places' => 'float',
    ];
}
