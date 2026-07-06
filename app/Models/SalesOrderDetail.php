<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderDetail extends Model
{
    protected $table = 'sales_order_details';

    protected $fillable = [
        'sales_order_number',
        'sales_order_date',
        'tp_type',
        'tp_name',
        'tp_TIN',
        'tp_trade_number',
        'tp_phone_number',
        'tp_address_province',
        'tp_address_commune',
        'tp_address_quartier',
        'tp_address_avenue',
        'tp_address_rue',
        'tp_address_number',
        'vat_taxpayer',
        'ct_taxpayer',
        'tl_taxpayer',
        'tp_fiscal_center',
        'tp_activity_sector',
        'tp_legal_form',
        'payment_type',
        'cancelled_invoice_ref',
        'cancelled_invoice',
        'cn_motif',
        'sales_order_currency',
        'document_no',
        'document',
        'auteur',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'reseted_by',
        'rejected_by',
        'cancelled_by',
        'sales_order_registered_number',
        'sales_order_registered_date',
        'sales_order_signature_date',
        'sales_order_signature',
        'sales_order_identifier',
        'description',
        'etat',
        'flag1',
        'flag2',
        'client_id',
        'item_id',
        'item_designation',
        'item_quantity',
        'item_price',
        'item_price_nvat',
        'vat',
        'item_price_wvat',
        'item_total_amount',
    ];

    protected $casts = [
        'sales_order_date' => 'datetime',
        'sales_order_signature_date' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(EGRClient::class, 'client_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_number', 'sales_order_number');
    }
}
