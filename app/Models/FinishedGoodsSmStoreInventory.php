<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedGoodsSmStoreInventory extends Model
{
    protected $table = 'finished_goods_sm_store_inventories';

    protected $fillable = [
        'inventory_no',
        'code_store',
        'inventory_signature',
        'date',
        'title',
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
        'store_id',
    ];

    public function details()
    {
        return $this->hasMany(FinishedGoodsSmStoreInventoryDetail::class, 'inventory_no', 'inventory_no');
    }

    public function store()
    {
        return $this->belongsTo(FinishedGoodsSmStore::class, 'store_id');
    }
}
