<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsStoreInventoryDetail extends Model
{
    protected $table = 'goods_store_inventory_details';

    protected $fillable = [
        'inventory_no',
        'code_store',
        'inventory_signature',
        'date',
        'title',
        'quantity',
        'unit',
        'cump',
        'purchase_price',
        'selling_price',
        'total_purchase_value',
        'total_selling_value',
        'total_cump_value',
        'new_quantity',
        'new_unit',
        'new_cump',
        'new_purchase_price',
        'new_selling_price',
        'new_total_purchase_value',
        'new_total_selling_value',
        'new_total_cump_value',
        'relicat',
        'description',
        'status',
        'rejected_motif',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'rejected_by',
        'reseted_by',
        'item_id',
        'store_id',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function inventory()
    {
        return $this->belongsTo(GoodsStoreInventory::class, 'inventory_no', 'inventory_no');
    }

    public function goodsStore()
    {
        return $this->belongsTo(GoodsStore::class, 'store_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
