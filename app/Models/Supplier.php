<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    //
    protected $fillable=[
    	'supplier_name',
        'telephone',
        'mail',
        'supplier_TIN',
        'supplier_address',
        'vat_supplier_payer',
        'company',
        'etat',
        'autre',
        'total_amount_paied',
        'total_amount_credit',
        'avalise_par',
        'date',
    ];

    public function account(){
    	return $this->belongsTo('App\Models\Accounting\AccountAccount');
    }

    public function drinkSupplierOrder(){
    	return $this->hasMany('App\Models\DrinkSupplierOrder','supplier_id');
    }

    public function drinkSupplierOrderDetail(){
        return $this->hasMany('App\Models\DrinkSupplierOrderDetail','supplier_id');
    }

    public function rawMaterialSupplierOrder(){
        return $this->hasMany('App\Models\RawMaterialSupplierOrder','supplier_id');
    }

    public function rawMaterialSupplierOrderDetail(){
        return $this->hasMany('App\Models\RawMaterialSupplierOrderDetail','supplier_id');
    }

    public function materialSupplierOrder(){
        return $this->hasMany('App\Models\MaterialSupplierOrder','supplier_id');
    }
    public function materialSupplierOrderDetail(){
        return $this->hasMany('App\Models\MaterialSupplierOrderDetail','supplier_id');
    }

    public function drinkReception(){
        return $this->hasMany('App\Models\DrinkReception','supplier_id');
    }

    public function drinkReceptionDetail(){
        return $this->hasMany('App\Models\DrinkReceptionDetail','supplier_id');
    }

    public function rawMaterialReception(){
        return $this->hasMany('App\Models\RawMaterialReception','supplier_id');
    }

    public function rawMaterialReceptionDetail(){
        return $this->hasMany('App\Models\RawMaterialReceptionDetail','supplier_id');
    }

    public function materialReception(){
        return $this->hasMany('App\Models\MaterialReception','supplier_id');
    }
    public function materialReceptionDetail(){
        return $this->hasMany('App\Models\MaterialReceptionDetail','supplier_id');
    }

    public function cashOutFlowRequest(){
        return $this->hasMany('App\Models\CashOutFlowRequest','supplier_id');
    }

    public function cashOutFlowRequestDetail(){
        return $this->hasMany('App\Models\CashOutFlowRequestDetail','supplier_id');
    }

    public function cashOutFlow(){
        return $this->hasMany('App\Models\CashOutFlow','supplier_id');
    }

    public function cashOutFlowDetail(){
        return $this->hasMany('App\Models\CashOutFlowDetail','supplier_id');
    }
}
