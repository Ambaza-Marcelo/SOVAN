<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialExtraBigStoreInventoryDetail extends Model
{
    //
    protected $fillable = [
        'inventory_no',
        'inventory_signature',
        'date',
        'title',
        'quantity',
        'unit',
        'cump',
        'purchase_price',
        'selling_price',
        'total_purchase_value',
        'total_selling_value',
        'total_cump_value',
        'new_quantity',
        'new_unit',
        'new_cump',
        'new_purchase_price',
        'new_selling_price',
        'new_total_purchase_value',
        'new_total_selling_value',
        'new_total_cump_value',
        'relicat',
        'description',
        'status',
        'code_store',
        'rejected_motif',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'rejected_by',
        'reseted_by',
        'r_material_id',
        'store_id',
    ];

    public function rawMaterial(){
        return $this->belongsTo('App\Models\RawMaterial', 'r_material_id');
    }

    public function store(){
        return $this->belongsTo('App\Models\RawMaterialExtraBigStore');
    }
}
