<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedGoodsStoreDetail extends Model
{
    protected $table = 'finished_goods_store_details';

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
        'vat' => 'double',
        'item_ct' => 'double',
        'item_tl' => 'double',
        'quantity' => 'double',
        'selling_price' => 'double',
        'cump' => 'double',
        'threshold_quantity' => 'double',
        'verified' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo('App\Models\Item', 'item_id');
    }

    public function defaultAccount()
    {
        return $this->belongsTo('App\Models\Accounting\AccountAccount', 'default_account_id');
    }
}
