<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    //
    public $timestamps = false; // Disable timestamps if updated_at column doesn't exist
    
    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'tp_type',
        'tp_name',
        'tp_TIN',
        'tp_trade_number',
        'tp_phone_number',
        'tp_address_commune',
        'tp_address_quartier',
        'vat_taxpayer',
        'ct_taxpayer',
        'tl_taxpayer',
        'tp_fiscal_center',
        'tp_activity_sector',
        'tp_legal_form',
        'payment_type',
        'invoice_signature',
        'invoice_signature_date',
        'invoice_signature',
        'item_designation',
        'item_quantity',
        'item_price',
        'item_ct',
        'item_tl',
        'item_price_nvat',
        'vat',
        'item_price_wvat',
        'item_total_amount',
        'document_no',
        'auteur',
        'etat',
        'item_id',
        'client_id',
    ];

    public function item(){
        return $this->belongsTo('App\Models\Item');
    }

    public function client(){
        return $this->belongsTo('\App\Models\EGRClient');
    }

    public function invoice(){
        return $this->belongsTo('\App\Models\Invoice', 'invoice_number', 'invoice_number');
    }
}
