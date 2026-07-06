<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedGoodsStockoutDetail extends Model
{
    protected $table = 'finished_goods_stockout_details';

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
        'finished_g_store_id',
        'finished_g_sm_store_id',
        'item_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'quantity' => 'double',
        'cost_price' => 'double',
        'total_cost_value' => 'double',
    ];

    public function finishedGoodsStockout()
    {
        return $this->belongsTo('App\Models\FinishedGoodsStockout', 'stockout_no', 'stockout_no');
    }

    public function finishedGoodsStore()
    {
        return $this->belongsTo('App\Models\FinishedGoodsStore', 'finished_g_store_id');
    }

    public function finishedGoodsSmStore()
    {
        return $this->belongsTo('App\Models\FinishedGoodsSmStore', 'finished_g_sm_store_id');
    }

    public function item()
    {
        return $this->belongsTo('App\Models\Item', 'item_id');
    }
}
