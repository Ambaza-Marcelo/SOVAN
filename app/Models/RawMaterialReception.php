<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialReception extends Model
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
        'dest_extra_store_id'
    ];

    public function supplier(){
        return $this->belongsTo('App\Models\Supplier');
    }

    public function destinationStore(){
        // FK réelle dans la table: destination_store_id
        return $this->belongsTo('App\Models\RawMaterialBigStore', 'destination_store_id');
    }

    public function destinationExtraStore(){
        // FK réelle dans la table: dest_extra_store_id
        return $this->belongsTo('App\Models\RawMaterialExtraBigStore', 'dest_extra_store_id');
    }
}
