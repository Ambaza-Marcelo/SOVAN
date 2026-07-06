<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialExtraBigStoreDetail extends Model
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
        'threshold_quantity',
        'verified',
        'material_id',
        'created_by',
        'updated_by',
        'description',
        'purchase_price',
        'selling_price',
        'cost_price',
        'cump',
        'total_purchase_value',
        'total_selling_value',
        'total_cost_value',
        'total_cump_value',
    ];

    public function material(){
        return $this->belongsTo('App\Models\Material');
    }
}
