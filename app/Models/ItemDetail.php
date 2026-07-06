<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemDetail extends Model
{
    //
    protected $table = 'item_details';

    protected $fillable = [
        'name',
        'code',
        'specification',
        'quantity',
        'selling_price',
        'cump',
        'threshold_quantity',
        'expiration_date',
        'status',
        'updated_by',
        'created_by',
        'fcategory_id',
        'r_material_id'
    ];

    public function rawMaterialCategory()
    {
        return $this->belongsTo('App\Models\RawMaterialCategory', 'fcategory_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo('App\Models\RawMaterial', 'r_material_id');
    }


    public function deliveryNoteDetail(){
        return $this->hasMany('App\Models\DeliveryNoteDetail','item_id');
    }

    public function proformaInvoiceDetail(){
        return $this->hasMany('App\Models\ProformaInvoiceDetail','item_id');
    }

    public function invoiceDetail(){
        return $this->hasMany('App\Models\InvoiceDetail','item_id');
    }
}
