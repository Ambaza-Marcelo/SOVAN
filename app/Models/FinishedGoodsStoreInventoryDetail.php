<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedGoodsStoreInventoryDetail extends Model
{
    protected $table = 'finished_goods_store_inventory_details';

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

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function store()
    {
        return $this->belongsTo(FinishedGoodsStore::class, 'store_id');
    }
}
