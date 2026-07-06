<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialExtraBigReport extends Model
{
    //
    protected $fillable = [
        'r_material_id',
        'quantity_stock_initial',
        'code_store',
        'value_stock_initial',
        'quantity_stockin',
        'value_stockin',
        'quantity_transfer',
        'value_transfer',
        'stock_total',
        'quantity_stockout',
        'value_stockout',
        'quantity_sold',
        'value_sold',
        'quantity_reception',
        'value_reception',
        'quantity_stock_final',
        'value_stock_final',
        'stockin_no',
        'reception_no',
        'stockout_no',
        'invoice_no',
        'transfer_no',
        'return_no',
        'inventory_no',
        'destination',
        'asker',
        'origine_facture',
        'commande_cuisine_no',
        'commande_boisson_no',
        'table_no',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'rejected_by',
        'employe_id',
        'type_transaction',
        'document_no',
        'cump',
        'date',
        'description',
    ];

    public function employe(){
        return $this->belongsTo('App\Models\Employe');
    }

    public function rawMaterial(){
        return $this->belongsTo('App\Models\RawMaterial', 'r_material_id');
    }
}
