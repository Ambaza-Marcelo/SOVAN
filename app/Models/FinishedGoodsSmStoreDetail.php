<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedGoodsSmStoreDetail extends Model
{
    protected $table = 'finished_goods_sm_store_details';

    protected $fillable = [
        'name',
        'code',
        'specification',
        'vat',
        'item_ct',
        'item_tl',
        'code_store',
        'quantity',
        'selling_price',
        'cump',
        'taux_marge',
        'taux_reduction',
        'taux_majoration',
        'expiration_date',
        'total_cump_value',
        'status',
        'store_signature',
        'emplacement',
        'manager',
        'threshold_quantity',
        'verified',
        'created_by',
        'updated_by',
        'description',
        'default_account_id',
        'item_id',
    ];

    protected $casts = [
        'verified' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(FinishedGoodsSmStore::class, 'code_store', 'code');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
