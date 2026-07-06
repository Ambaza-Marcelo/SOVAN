<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialBigReport extends Model
{
    //
    protected $fillable = [
        'material_id',
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
        'document_no',
        'type_transaction',
        'cump',
        'description',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'rejected_by',
        'employe_id',
    ];

    public function employe(){
        return $this->belongsTo('App\Models\Employe');
    }

    public function material(){
        return $this->belongsTo('App\Models\Material');
    }
}
