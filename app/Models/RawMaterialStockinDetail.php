<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialStockinDetail extends Model
{
    //
    protected $fillable = [
        'date',
        'quantity',
        'unit',
        'price_nvat',
        'vat',
        'price_wvat',
        'total_amount_purchase',
        'total_amount_selling',
        'purchase_price',
        'selling_price',
        'stockin_no',
        'receptionist',
        'handingover',
        'stockin_signature',
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
        'dest_sm_store_id',
        'dest_bg_store_id',
        'dest_extra_store_id'
    ];

    public function rawMaterialSmallStore(){
        return $this->belongsTo('App\Models\RawMaterialSmallStore');
    }

    public function rawMaterialbigStore(){
        return $this->belongsTo('App\Models\RawMaterialBigStore');
    }

    public function rawMaterialExtraStore(){
        return $this->belongsTo('App\Models\RawMaterialExtraStore');
    }

    public function rawMaterial(){
        return $this->belongsTo('App\Models\RawMaterial', 'r_material_id');
    }
}
