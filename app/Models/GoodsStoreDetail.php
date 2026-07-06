<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountAccount;

class GoodsStoreDetail extends Model
{
    protected $table = 'goods_store_details';

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
        'quantity' => 'double',
        'selling_price' => 'double',
        'cump' => 'double',
        'vat' => 'double',
        'item_ct' => 'double',
        'item_tl' => 'double',
        'threshold_quantity' => 'double',
        'verified' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(GoodsStore::class, 'code_store', 'code');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function defaultAccount()
    {
        return $this->belongsTo(AccountAccount::class, 'default_account_id');
    }
}
