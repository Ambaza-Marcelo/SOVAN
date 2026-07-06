<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    //
    protected $fillable = [
        'delivery_number',
        'delivery_date',
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
        'delivery_currency',
        'document_no',
        'document',
        'auteur',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'reseted_by',
        'rejected_by',
        'cancelled_by',
        'delivery_registered_number',
        'delivery_registered_date',
        'delivery_signature_date',
        'delivery_signature',
        'delivery_identifier',
        'description',
        'etat',
        'flag1',
        'flag2',
        'client_id',
    ];

    public function client(){
        return $this->belongsTo('\App\Models\EGRClient');
    }

    public function details(){
        return $this->hasMany('\App\Models\DeliveryNoteDetail', 'delivery_number', 'delivery_number');
    }

    public function proformaInvoice(){
        return $this->belongsTo('\App\Models\ProformaInvoice', 'document_no', 'proforma_number');
    }
}
