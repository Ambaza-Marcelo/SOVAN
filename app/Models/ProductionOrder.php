<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    protected $table = 'production_orders';

    protected $fillable = [
        'prodution_number',
        'production_date',
        'start_date',
        'end_date',
        'production_signature',
        'finished_goods_store_type',
        'finished_g_store_id',
        'finished_g_sm_store_id',
        'status',
        'description',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'reseted_by',
        'rejected_by',
        'rejected_motif',
        'cancelled_by',
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
        'production_date' => 'datetime',
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

    /**
     * Get all production order details
     */
    public function details()
    {
        return $this->hasMany('App\Models\ProductionOrderDetail', 'production_number', 'prodution_number');
    }

    /**
     * Get raw materials details (food items)
     */
    public function rawMaterials()
    {
        return $this->hasMany('App\Models\ProductionOrderDetail', 'production_number', 'prodution_number')
            ->whereNotNull('r_material_id');
    }

    /**
     * Get finished goods details (item products)
     */
    public function finishedGoods()
    {
        return $this->hasMany('App\Models\ProductionOrderDetail', 'production_number', 'prodution_number')
            ->whereNotNull('item_id');
    }

    /**
     * Check if production order is active
     */
    public function isActive()
    {
        return $this->status == '1' || $this->status == 1;
    }

    /**
     * Check if production order is completed
     */
    public function isCompleted()
    {
        return $this->status == '2' || $this->status == 2;
    }

    /**
     * Check if production order is cancelled
     */
    public function isCancelled()
    {
        return $this->status == '-1' || $this->status == -1 || !empty($this->cancelled_by);
    }

    /**
     * Scope to get only active production orders
     */
    public function scopeActive($query)
    {
        return $query->where('status', '1');
    }

    /**
     * Scope to get only completed production orders
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', '2');
    }
}
