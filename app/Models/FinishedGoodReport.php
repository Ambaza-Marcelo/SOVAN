<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedGoodReport extends Model
{
    protected $table = 'finished_goods_reports';

    protected $fillable = [
        'quantity_stock_initial',
        'code_store',
        'value_stock_initial',
        'quantity_stockin',
        'value_stockin',
        'stock_total',
        'quantity_stockout',
        'value_stockout',
        'quantity_stock_final',
        'value_stock_final',
        'destination',
        'cump',
        'date',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'rejected_by',
        'description',
        'type_transaction',
        'document_no',
        'item_id',
    ];

    protected $casts = [
        'quantity_stock_initial' => 'double',
        'value_stock_initial' => 'double',
        'quantity_stockin' => 'double',
        'value_stockin' => 'double',
        'stock_total' => 'double',
        'quantity_stockout' => 'double',
        'value_stockout' => 'double',
        'quantity_stock_final' => 'double',
        'value_stock_final' => 'double',
        'cump' => 'double',
        'date' => 'datetime',
    ];

    /**
     * Get the item (finished good) associated with this report
     */
    public function item()
    {
        return $this->belongsTo('App\Models\Item', 'item_id');
    }

    /**
     * Get the finished goods store associated with this report
     */
    public function finishedGoodsStore()
    {
        return $this->belongsTo('App\Models\FinishedGoodsStore', 'code_store', 'code');
    }

    /**
     * Scope to filter by transaction type
     */
    public function scopeByTransactionType($query, $type)
    {
        return $query->where('type_transaction', $type);
    }

    /**
     * Scope to filter by document number
     */
    public function scopeByDocumentNo($query, $documentNo)
    {
        return $query->where('document_no', $documentNo);
    }

    /**
     * Scope to filter by store code
     */
    public function scopeByStoreCode($query, $codeStore)
    {
        return $query->where('code_store', $codeStore);
    }

    /**
     * Scope to filter by item
     */
    public function scopeByItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Get total stock movement (stockin - stockout)
     */
    public function getTotalMovementAttribute()
    {
        $stockin = floatval($this->quantity_stockin ?? 0);
        $stockout = floatval($this->quantity_stockout ?? 0);
        return $stockin - $stockout;
    }

    /**
     * Get total value movement (value_stockin - value_stockout)
     */
    public function getTotalValueMovementAttribute()
    {
        $valueStockin = floatval($this->value_stockin ?? 0);
        $valueStockout = floatval($this->value_stockout ?? 0);
        return $valueStockin - $valueStockout;
    }
}
