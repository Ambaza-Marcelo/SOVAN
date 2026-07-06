<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedGoodsStore extends Model
{
    protected $table = 'finished_goods_stores';

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
        return $this->belongsTo('App\Models\Accounting\AccountAccount', 'default_account_id');
    }

    public function details()
    {
        return $this->hasMany('App\Models\FinishedGoodsStoreDetail', 'code_store', 'code');
    }
}
