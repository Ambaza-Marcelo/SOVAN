<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOrderDetail extends Model
{
    protected $table = 'production_order_details';

    protected $fillable = [
        'production_number',
        'production_date',
        'start_date',
        'end_date',
        'production_signature',
        'status',
        'description',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'reseted_by',
        'rejected_by',
        'cancelled_by',
        // Raw materials
        'material_quantity',
        'unit',
        'material_cost',
        'total_material_cost',
        'lot_number',
        'batch_number',
        'r_material_id',
        // Finished goods (Item)
        'item_quantity',
        'cost_price',
        'total_item_cost',
        'item_id',
    ];

    protected $casts = [
        'production_date' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'material_quantity' => 'double',
        'material_cost' => 'double',
        'total_material_cost' => 'double',
        'item_quantity' => 'double',
        'cost_price' => 'double',
        'total_item_cost' => 'double',
    ];

    /**
     * Get the production order this detail belongs to
     */
    public function productionOrder()
    {
        return $this->belongsTo('App\Models\ProductionOrder', 'production_number', 'prodution_number');
    }

    /**
     * Get the raw material for this detail
     */
    public function rawMaterial()
    {
        return $this->belongsTo('App\Models\RawMaterial', 'r_material_id');
    }

    /**
     * Get the finished good (Item) for this detail
     */
    public function item()
    {
        return $this->belongsTo('App\Models\Item', 'item_id');
    }

    /**
     * Check if this detail is for raw materials
     */
    public function isRawMaterial()
    {
        return !empty($this->r_material_id);
    }

    /**
     * Check if this detail is for finished goods
     */
    public function isFinishedGood()
    {
        return !empty($this->item_id);
    }

    /**
     * Get total material cost (quantity * cost)
     */
    public function getTotalMaterialCostAttribute()
    {
        if ($this->isRawMaterial()) {
            return $this->material_quantity * $this->material_cost;
        }
        return 0;
    }

    /**
     * Get total item cost (quantity * cost_price)
     */
    public function getTotalItemCostAttribute()
    {
        if ($this->isFinishedGood()) {
            return $this->item_quantity * $this->cost_price;
        }
        return 0;
    }

    /**
     * Scope to get only raw materials
     */
    public function scopeRawMaterials($query)
    {
        return $query->whereNotNull('r_material_id');
    }

    /**
     * Scope to get only finished goods
     */
    public function scopeFinishedGoods($query)
    {
        return $query->whereNotNull('item_id');
    }
}
