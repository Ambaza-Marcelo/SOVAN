<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsStockoutDetail extends Model
{
    protected $table = 'goods_stockout_details';

    protected $fillable = [
        'date',
        'quantity',
        'stockout_no',
        'stockout_signature',
        'requisition_no',
        'asker',
        'destination',
        'store_type',
        'item_movement_type',
        'description',
        'rejected_motif',
        'cost_price',
        'total_cost_value',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'rejected_by',
        'reseted_by',
        'status',
        'goods_store_id',
        'item_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'quantity' => 'double',
        'cost_price' => 'double',
        'total_cost_value' => 'double',
    ];

    public function stockout()
    {
        return $this->belongsTo(GoodsStockout::class, 'stockout_no', 'stockout_no');
    }

    public function goodsStore()
    {
        return $this->belongsTo(GoodsStore::class, 'goods_store_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
