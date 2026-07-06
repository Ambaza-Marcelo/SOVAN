<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialMeasurement extends Model
{
    //
    protected $fillable = [
        'purchase_unit',
        'stockout_unit',
        'production_unit',
        'equivalent',
        'sub_equivalent',
    ];


    public function rawMaterial(){
        return $this->hasMany('App\Models\RawMaterial','rm_measurement_id');
    }
}
