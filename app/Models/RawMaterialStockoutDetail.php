<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialStockoutDetail extends Model
{
    //
    protected $fillable = [
        'date',
        'quantity',
        'unit',
        'price',
        'stockout_no',
        'stockout_signature',
        'requisition_no',
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
        'asker',
        'destination',
        'store_type',
        'origin_sm_store_id',
        'origin_bg_store_id',
        'origin_extra_store_id',
        'r_material_id'
    ];

    public function rawMaterial(){
        return $this->belongsTo('App\Models\RawMaterial', 'r_material_id');
    }

    public function originSmallStore(){
        return $this->belongsTo('App\Models\RawMaterialSmallStore');
    }

    public function originBigStore(){
        return $this->belongsTo('App\Models\RawMaterialBigStore');
    }

    public function originExtraStore(){
        return $this->belongsTo('App\Models\RawMaterialExtraBigStore');
    }
}
