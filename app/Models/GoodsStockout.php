<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsStockout extends Model
{
    protected $table = 'goods_stockouts';

    protected $fillable = [
        'date',
        'stockout_no',
        'stockout_signature',
        'requisition_no',
        'asker',
        'destination',
        'store_type',
        'item_purchase_or_sale_currency',
        'item_movement_type',
        'description',
        'rejected_motif',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'rejected_by',
        'reseted_by',
        'status',
        'goods_store_id',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function goodsStore()
    {
        return $this->belongsTo(GoodsStore::class, 'goods_store_id');
    }

    public function details()
    {
        return $this->hasMany(GoodsStockoutDetail::class, 'stockout_no', 'stockout_no');
    }
}
