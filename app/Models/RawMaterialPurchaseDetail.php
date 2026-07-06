<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialPurchaseDetail extends Model
{
    //
    protected $fillable = [
        'date',
        'quantity',
        'unit',
        'price',
        'purchase_no',
        'purchase_signature',
        'description',
        'rejected_motif',
        'total_value',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'rejected_by',
        'reseted_by',
        'status',
        'r_material_id',
        'supplier_id'
    ];

    public function rawMaterial(){
        return $this->belongsTo('App\Models\RawMaterial', 'r_material_id');
    }

    public function supplier(){
        return $this->belongsTo('App\Models\Supplier');
    }
}
