<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedGoodsStockout extends Model
{
    protected $table = 'finished_goods_stockouts';

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
        'finished_g_store_id',
        'finished_g_sm_store_id',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function finishedGoodsStore()
    {
        return $this->belongsTo('App\Models\FinishedGoodsStore', 'finished_g_store_id');
    }

    public function finishedGoodsSmStore()
    {
        return $this->belongsTo('App\Models\FinishedGoodsSmStore', 'finished_g_sm_store_id');
    }

    public function details()
    {
        return $this->hasMany('App\Models\FinishedGoodsStockoutDetail', 'stockout_no', 'stockout_no');
    }
}
