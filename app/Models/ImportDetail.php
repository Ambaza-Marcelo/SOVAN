<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportDetail extends Model
{
    protected $table = 'import_details';

    protected $fillable = [
        'date',
        'import_no',
        'import_signature',
        'store_type',
        'receptionist',
        'handingover',
        'origin',
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
        'reference_dmc',
        'rubrique_tarifaire',
        'nombre_par_paquet',
        'description_paquet',
        'status',
        'item_quantity',
        'item_measurement_unit',
        'purchase_price',
        'total_purchase_value',
        'transport_cost',
        'clearance_cost',
        'total_amount',
        'goods_store_id',
        'finished_g_store_id',
        'finished_g_sm_store_id',
        'item_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'nombre_par_paquet' => 'float',
        'item_quantity' => 'float',
        'purchase_price' => 'float',
        'total_purchase_value' => 'float',
        'transport_cost' => 'float',
        'clearance_cost' => 'float',
        'total_amount' => 'float',
        'goods_store_id' => 'integer',
        'finished_g_store_id' => 'integer',
        'finished_g_sm_store_id' => 'integer',
        'item_id' => 'integer',
    ];

    public function import()
    {
        return $this->belongsTo(Import::class, 'import_no', 'import_no');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function goodsStore()
    {
        return $this->belongsTo(GoodsStore::class, 'goods_store_id');
    }

    public function finishedGoodsStore()
    {
        return $this->belongsTo(FinishedGoodsStore::class, 'finished_g_store_id');
    }

    public function finishedGoodsSmStore()
    {
        return $this->belongsTo(FinishedGoodsSmStore::class, 'finished_g_sm_store_id');
    }
}
