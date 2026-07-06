<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialBigReport extends Model
{
    //
    protected $fillable = [
        'r_material_id',
        'quantity_stock_initial',
        'code_store',
        'value_stock_initial',
        'quantity_stockin',
        'value_stockin',
        'stock_total',
        'quantity_stockout',
        'value_stockout',
        'quantity_stock_final',
        'value_stock_final',
        'destination',
        'asker',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'rejected_by',
        'type_transaction',
        'document_no',
        'cump',
        'date',
        'description',
    ];

    public function rawMaterial(){
        return $this->belongsTo('App\Models\RawMaterial', 'r_material_id');
    }
}
