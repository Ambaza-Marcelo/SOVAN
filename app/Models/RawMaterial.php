<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    //
    protected $table = 'raw_materials';

    protected $fillable = [
        'name',
        'code',
        'specification',
        'quantity',
        'unit',
        'purchase_price',
        'cost_price',
        'selling_price',
        'cump',
        'threshold_quantity',
        'expiration_date',
        'status',
        'updated_by',
        'created_by',
        'rmcategory_id',
        'rm_measurement_id',
        'default_account_id',
        'code_store',
        'store_type'
    ];

    public function rawMaterialCategory()
    {
        return $this->belongsTo('App\Models\RawMaterialCategory', 'rmcategory_id');
    }

    public function rawMaterialMeasurement()
    {
        return $this->belongsTo('App\Models\RawMaterialMeasurement', 'rm_measurement_id');
    }

    public function defaultAccount()
    {
        return $this->belongsTo('App\Models\Accounting\AccountAccount', 'default_account_id');
    }

    public function itemDetail()
    {
        return $this->hasMany('App\Models\ItemDetail', 'r_material_id');
    }

    public function rawMaterialStockinDetail(){
        return $this->hasMany('App\Models\RawMaterialStockinDetail','r_material_id');
    }

    public function rawMaterialStockoutDetail(){
        return $this->hasMany('App\Models\RawMaterialStockoutDetail','r_material_id');
    }

    public function rawMaterialBigStoreInventoryDetail(){
        return $this->hasMany('App\Models\RawMaterialBigStoreInventoryDetail','r_material_id');
    }

    public function rawMaterialBigStoreDetail(){
        return $this->hasMany('App\Models\RawMaterialBigStoreDetail','r_material_id');
    }

    public function rawMaterialSmallStoreDetail(){
        return $this->hasMany('App\Models\RawMaterialSmallStoreDetail','r_material_id');
    }

    public function rawMaterialSmallStoreInventoryDetail(){
        return $this->hasMany('App\Models\RawMaterialSmallStoreInventoryDetail','r_material_id');
    }

    public function rawMaterialReceptionDetail(){
        return $this->hasMany('App\Models\RawMaterialReceptionDetail','r_material_id');
    }

    public function rawMaterialTransferDetail(){
        return $this->hasMany('App\Models\RawMaterialTransferDetail','r_material_id');
    }

    public function rawMaterialBigReport(){
        return $this->hasMany('App\Models\RawMaterialBigReport','r_material_id');
    }

    public function rawMaterialSmallReport(){
        return $this->hasMany('App\Models\RawMaterialSmallReport','r_material_id');
    }

    public function cashOutFlowRequestDetail(){
        return $this->hasMany('App\Models\CashOutFlowRequestDetail','r_material_id');
    }

    public function cashOutFlowDetail(){
        return $this->hasMany('App\Models\CashOutFlowDetail','r_material_id');
    }

    /**
     * Check if raw material is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if raw material is inactive
     */
    public function isInactive()
    {
        return $this->status === 'inactive' || $this->status === null;
    }

    /**
     * Scope to get only active raw materials
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only inactive raw materials
     */
    public function scopeInactive($query)
    {
        return $query->where(function($q) {
            $q->where('status', 'inactive')->orWhereNull('status');
        });
    }
}
