<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteCreditDetail extends Model
{
    protected $table = 'note_credit_details';

    protected static function boot()
    {
        parent::boot();
        static::saving(function (self $model) {
            $model->invoice_type = 'FA';
            $model->type_facture = 'FA';
        });
    }

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'sale_date',
        'type_facture',
        'tp_type',
        'tp_name',
        'tp_TIN',
        'invoice_type',
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
        'invoice_ref',
        'cn_motif',
        'code_store',
        'document_no',
        'invoice_currency',
        'electronic_signature',
        'invoice_registered_number',
        'invoice_registered_date',
        'invoice_signature_date',
        'invoice_signature',
        'item_designation',
        'item_quantity',
        'item_price',
        'item_ct',
        'item_tl',
        'item_tsce_tax',
        'item_ott_tax',
        'item_price_nvat',
        'vat',
        'item_price_wvat',
        'item_total_amount',
        'auteur',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'reseted_by',
        'rejected_by',
        'cancelled_by',
        'etat',
        'paid_either',
        'statut',
        'statut_paied',
        'taux_reduction',
        'montant_reduction',
        'montant_total_reduction',
        'note_reduction',
        'note_credit',
        'description',
        'cump',
        'total_cump_value',
        'invoice_identifier',
        'item_id',
        'client_id',
    ];

    protected $casts = [
        'invoice_date' => 'datetime',
        'sale_date' => 'datetime',
        'invoice_signature_date' => 'datetime',
        'item_quantity' => 'float',
        'item_price' => 'float',
        'item_ct' => 'float',
        'item_tl' => 'float',
        'item_tsce_tax' => 'float',
        'item_ott_tax' => 'float',
        'item_price_nvat' => 'float',
        'vat' => 'float',
        'item_price_wvat' => 'float',
        'item_total_amount' => 'float',
        'cump' => 'float',
        'total_cump_value' => 'float',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(EGRClient::class, 'client_id');
    }

    public function noteCredit(): BelongsTo
    {
        return $this->belongsTo(NoteCredit::class, 'invoice_number', 'invoice_number');
    }
}
