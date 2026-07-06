<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialReception extends Model
{
    //
    protected $fillable = [
        'date',
        'reception_no',
        'reception_signature',
        'invoice_no',
        'order_no',
        'receptionist',
        'handingover',
        'vat_taxpayer',
        'vat_supplier_payer',
        'invoice_currency',
        'waybill',
        'description',
        'rejected_motif',
        'start_date',
        'end_date',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'rejected_by',
        'reseted_by',
        'status',
        'supplier_id',
        'destination_store_id',
        'dest_extra_store_id',
        'type_store',
        'code_store',
    ];

    const TYPE_STORE_EXTRA_BIG = '0';
    const TYPE_STORE_BIG = '1';
    const TYPE_STORE_GOODS = '2';

    public function supplier(){
        return $this->belongsTo('App\Models\Supplier');
    }

    public function destinationStore(){
        // FK réelle dans la table: destination_store_id
        return $this->belongsTo('App\Models\MaterialBigStore', 'destination_store_id');
    }

    public function destinationExtraStore(){
        // FK réelle dans la table: dest_extra_store_id
        return $this->belongsTo('App\Models\MaterialExtraBigStore', 'dest_extra_store_id');
    }

    public function destinationLabel(): string
    {
        $parts = [];

        if (!empty($this->code_store)) {
            $goodsStore = GoodsStore::where('code', $this->code_store)->first();
            $label = $goodsStore
                ? trim(($goodsStore->code ?? '') . ' - ' . ($goodsStore->name ?? ''))
                : $this->code_store;
            $parts[] = $label . ' (' . __('messages.goods_store_index_title') . ')';
        }

        if ($this->destinationExtraStore) {
            $parts[] = trim(($this->destinationExtraStore->code ?? '') . ' ' . ($this->destinationExtraStore->name ?? ''))
                . ' (' . __('messages.Grand Stock') . ')';
        } elseif ($this->destinationStore) {
            $parts[] = trim(($this->destinationStore->code ?? '') . ' ' . ($this->destinationStore->name ?? ''))
                . ' (' . __('messages.Stock Intermediaire') . ')';
        }

        return !empty($parts) ? implode(' / ', array_filter($parts)) : __('messages.reception_destination_unspecified');
    }
}
