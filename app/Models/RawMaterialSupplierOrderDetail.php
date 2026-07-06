<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialSupplierOrderDetail extends Model
{
    //
    protected $fillable = [
        'date',
        'quantity',
        'unit',
        'purchase_price',
        'order_no',
        'order_signature',
        'description',
        'rejected_motif',
        'total_value',
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
        'r_material_id',
    ];

    public function supplier(){
        return $this->belongsTo('App\Models\Supplier');
    }

    public function rawMaterial(){
        return $this->belongsTo('App\Models\RawMaterial', 'r_material_id');
    }
}
