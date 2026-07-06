<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    //
    protected $table = 'items';

    protected $fillable = [
        'name',
        'code',
        'specification',
        'vat',
        'code_store',
        'quantity',
        'selling_price',
        'cump',
        'threshold_quantity',
        'expiration_date',
        'status',
        'store_type',
        'updated_by',
        'created_by',
        'default_account_id',
        'fcategory_id',
    ];

    /** 1 = stock des produits finis, 2 = stock des marchandises */
    const STORE_TYPE_FINISHED_GOODS = '1';
    const STORE_TYPE_GOODS = '2';

    public function rawMaterialCategory(){
        return $this->belongsTo('App\Models\RawMaterialCategory', 'fcategory_id');
    }


    public function deliveryNoteDetail(){
        return $this->hasMany('App\Models\DeliveryNoteDetail','item_id');
    }

    public function proformaInvoiceDetail(){
        return $this->hasMany('App\Models\ProformaInvoiceDetail','item_id');
    }

    public function invoiceDetail(){
        return $this->hasMany('App\Models\InvoiceDetail','item_id');
    }

    public function finishedGoodsStoreDetail(){
        return $this->hasOne('App\Models\FinishedGoodsStoreDetail','item_id');
    }

    public function finishedGoodsSmStoreDetail(){
        return $this->hasOne('App\Models\FinishedGoodsSmStoreDetail','item_id');
    }

    public function goodsStoreDetail(){
        return $this->hasOne('App\Models\GoodsStoreDetail','item_id');
    }

    public function isFinishedGoods(): bool
    {
        return $this->store_type === self::STORE_TYPE_FINISHED_GOODS;
    }

    public function isGoods(): bool
    {
        return $this->store_type === self::STORE_TYPE_GOODS;
    }

    public function defaultAccount(){
        return $this->belongsTo('App\Models\Accounting\AccountAccount', 'default_account_id');
    }

    /**
     * Check if item is active / usable.
     *
     * Backward compatibility:
     * - Older data may use string values: 'active' / 'inactive'
     * - Newer data may use numeric values: '1' (stockable) / '0' (non stockable)
     */
    public function isActive()
    {
        return $this->status === 'active'
            || $this->status === 1
            || $this->status === '1'
            || $this->status === null;
    }

    /**
     * Check if item is inactive / not usable.
     */
    public function isInactive()
    {
        return $this->status === 'inactive'
            || $this->status === 0
            || $this->status === '0';
    }

    /**
     * Scope to get only active items
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'active')
              ->orWhere('status', 1)
              ->orWhere('status', '1')
              ->orWhereNull('status');
        });
    }

    /**
     * Scope to get only inactive items
     */
    public function scopeInactive($query)
    {
        return $query->where(function($q) {
            $q->where('status', 'inactive')
              ->orWhere('status', 0)
              ->orWhere('status', '0');
        });
    }

    /**
     * Articles facturables : produits finis + marchandises (stockables et non stockables).
     */
    public function scopeBillable($query)
    {
        return $query->whereIn('store_type', [
            self::STORE_TYPE_FINISHED_GOODS,
            self::STORE_TYPE_GOODS,
        ])->where(function ($q) {
            $q->where(function ($stockable) {
                $stockable->where('status', 'active')
                    ->orWhere('status', 1)
                    ->orWhere('status', '1')
                    ->orWhereNull('status');
            })->orWhere(function ($nonStockGoods) {
                $nonStockGoods->where('store_type', self::STORE_TYPE_GOODS)
                    ->where(function ($s) {
                        $s->where('status', 0)->orWhere('status', '0');
                    });
            });
        });
    }

    public function isNonStockableGoods(): bool
    {
        return $this->isGoods()
            && ($this->status === 0 || $this->status === '0');
    }

    public function billableTypeLabel(): string
    {
        if ($this->isGoods()) {
            return $this->isNonStockableGoods() ? 'March. NS' : 'March.';
        }

        return 'PF';
    }
}
