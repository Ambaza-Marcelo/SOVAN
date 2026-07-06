<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsStoreReport extends Model
{
    protected $table = 'goods_store_reports';

    protected $fillable = [
        'quantity_stock_initial',
        'code_store',
        'value_stock_initial',
        'quantity_stockin',
        'value_stockin',
        'stock_total',
        'quantity_stockout',
        'value_stockout',
        'quantity_stock_final',
        'value_stock_final',
        'destination',
        'cump',
        'date',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'rejected_by',
        'description',
        'type_transaction',
        'document_no',
        'item_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'cump' => 'double',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
