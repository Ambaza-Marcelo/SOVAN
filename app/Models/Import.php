<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    protected $table = 'imports';

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
        'transport_cost',
        'clearance_cost',
        'total_amount',
        'status',
        'goods_store_id',
        'finished_g_store_id',
        'finished_g_sm_store_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'nombre_par_paquet' => 'float',
        'transport_cost' => 'float',
        'clearance_cost' => 'float',
        'total_amount' => 'float',
        'goods_store_id' => 'integer',
        'finished_g_store_id' => 'integer',
        'finished_g_sm_store_id' => 'integer',
    ];

    public const STORE_KIND_GOODS = 'goods';
    public const STORE_KIND_FINISHED_GOODS = 'finished_goods';
    public const STORE_KIND_FINISHED_GOODS_SM = 'finished_goods_sm';

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

    public function details()
    {
        return $this->hasMany(ImportDetail::class, 'import_no', 'import_no');
    }
}
