<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedGoodsStockinDetail extends Model
{
    protected $table = 'finished_goods_stockin_details';

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
        'finished_g_store_id',
        'finished_g_sm_store_id',
        'item_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'quantity' => 'double',
        'total_cost_value' => 'double',
        'cost_price' => 'double',
        'total_value' => 'double',
    ];

    public function finishedGoodsStockin()
    {
        return $this->belongsTo('App\Models\FinishedGoodsStockin', 'stockin_no', 'stockin_no');
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
