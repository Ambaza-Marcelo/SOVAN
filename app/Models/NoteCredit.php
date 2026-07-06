<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NoteCredit extends Model
{
    protected $table = 'note_credits';

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
        'type_facture',
        'invoice_date',
        'sale_date',
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
        'invoice_currency',
        'code_store',
        'document_no',
        'auteur',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'reseted_by',
        'rejected_by',
        'cancelled_by',
        'electronic_signature',
        'invoice_registered_number',
        'invoice_registered_date',
        'invoice_signature_date',
        'invoice_signature',
        'invoice_identifier',
        'note_reduction',
        'note_credit',
        'note_recouvrement',
        'description',
        'etat',
        'paid_either',
        'statut',
        'statut_paied',
        'client_id',
    ];

    protected $casts = [
        'invoice_date' => 'datetime',
        'sale_date' => 'datetime',
        'invoice_signature_date' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(EGRClient::class, 'client_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(NoteCreditDetail::class, 'invoice_number', 'invoice_number');
    }
}
