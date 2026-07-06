<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsStockinDetail extends Model
{
    protected $table = 'goods_stockin_details';

    protected $fillable = [
        'date',
        'quantity',
        'total_cost_value',
        'cost_price',
        'stockin_no',
        'receptionist',
        'handingover',
        'origin',
        'item_purchase_or_sale_currency',
        'item_movement_type',
        'stockin_signature',
        'store_type',
        'description',
        'rejected_motif',
        'total_value',
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
    ];

    public function stockin()
    {
        return $this->belongsTo(GoodsStockin::class, 'stockin_no', 'stockin_no');
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
