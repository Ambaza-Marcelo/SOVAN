<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionPlanDetail extends Model
{
    protected $table = 'productions_plan_details';

    protected $fillable = [
        'production_number',
        'production_date',
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
        'lot_number',
        'batch_number',
        'total_material_cost',
        'total_item_cost',
        'material_quantity',
        'material_cost',
        'r_material_id',
        'item_quantity',
        'cost_price',
        'item_id',
    ];

    protected $casts = [
        'production_date' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'total_material_cost' => 'double',
        'total_item_cost' => 'double',
        'material_quantity' => 'double',
        'material_cost' => 'double',
        'item_quantity' => 'double',
        'cost_price' => 'double',
    ];

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class, 'r_material_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
