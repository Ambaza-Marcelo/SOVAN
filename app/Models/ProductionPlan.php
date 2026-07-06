<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionPlan extends Model
{
    protected $table = 'productions_plans';

    protected $fillable = [
        'prodution_plan_number',
        'production_plan_date',
        'start_date',
        'end_date',
        'production_signature',
        'status',
        'description',
        'created_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'reseted_by',
        'rejected_by',
        'rejected_motif',
        'cancelled_by',
        'updated_by',
        'flag1',
        'flag2',
        'work_center',
        'operation_sequence',
        'routing_notes',
        'estimated_labor_cost',
        'estimated_material_cost',
        'estimated_overhead_cost',
        'total_estimated_cost',
        'planned_start_time',
        'planned_finish_time',
        'actual_start_time',
        'actual_finish_time',
    ];

    protected $casts = [
        'production_plan_date' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'planned_start_time' => 'datetime',
        'planned_finish_time' => 'datetime',
        'actual_start_time' => 'datetime',
        'actual_finish_time' => 'datetime',
        'estimated_labor_cost' => 'double',
        'estimated_material_cost' => 'double',
        'estimated_overhead_cost' => 'double',
        'total_estimated_cost' => 'double',
    ];
}
