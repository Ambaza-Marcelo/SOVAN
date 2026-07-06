<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountAccount;

class GoodsStore extends Model
{
    protected $table = 'goods_stores';

    protected $fillable = [
        'name',
        'code',
        'store_signature',
        'emplacement',
        'manager',
        'verified',
        'created_by',
        'updated_by',
        'description',
        'default_account_id',
    ];

    protected $casts = [
        'verified' => 'boolean',
    ];

    public function defaultAccount()
    {
        return $this->belongsTo(AccountAccount::class, 'default_account_id');
    }

    public function details()
    {
        return $this->hasMany(GoodsStoreDetail::class, 'code_store', 'code');
    }

    public function stockins()
    {
        return $this->hasMany(GoodsStockin::class, 'goods_store_id');
    }

    public function stockouts()
    {
        return $this->hasMany(GoodsStockout::class, 'goods_store_id');
    }

    public function inventories()
    {
        return $this->hasMany(GoodsStoreInventory::class, 'store_id');
    }
}
