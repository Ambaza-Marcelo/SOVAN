<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialExtraBigStoreDetail extends Model
{
    //
    protected $fillable = [
        'name',
        'code',
        'store_signature',
        'emplacement',
        'manager',
        'quantity',
        'unit',
        'total_value',
        'purchase_price',
        'cump',
        'selling_price',
        'total_purchase_value',
        'total_cump_value',
        'total_selling_value',
        'threshold_quantity',
        'verified',
        'r_material_id',
        'created_by',
        'updated_by',
        'description',
    ];

    public function rawMaterial()
    {
        return $this->belongsTo('App\Models\RawMaterial', 'r_material_id');
    }
}
