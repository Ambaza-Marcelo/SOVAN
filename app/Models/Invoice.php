<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    //
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
        'customer_name',
        'invoice_signature',
        'invoice_signature_date',
        'document_no',
        'document',
        'auteur',
        'etat',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'description',
        'client_id',
        'account_id',
    ];

    public function client(){
        return $this->belongsTo('\App\Models\EGRClient');
    }

    public function details(){
        return $this->hasMany('\App\Models\InvoiceDetail', 'invoice_number', 'invoice_number');
    }

    public function deliveryNote(){
        return $this->belongsTo('\App\Models\DeliveryNote', 'document_no', 'delivery_number');
    }
}
