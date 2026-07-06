<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialSmallStoreDetail extends Model
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
        'total_cump_value',
        'cump',
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
