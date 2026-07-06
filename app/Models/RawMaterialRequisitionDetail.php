<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialRequisitionDetail extends Model
{
    //
    protected $fillable = [
        'date',
        'quantity_requisitioned',
        'quantity_received',
        'unit',
        'price',
        'requisition_no',
        'requisition_signature',
        'description',
        'rejected_motif',
        'total_value_requisitioned',
        'total_value_received',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'rejected_by',
        'reseted_by',
        'status',
        'r_material_id',
        'type_store',
        'code_store',
    ];

    public function rawMaterial(){
        return $this->belongsTo('App\Models\RawMaterial', 'r_material_id');
    }
}
