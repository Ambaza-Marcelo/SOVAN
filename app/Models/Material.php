<?php
namespace App\Models;

use App\Services\DepreciationService;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
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
        'is_stockable',
        'updated_by',
        'created_by',
        'mcategory_id',
        'material_measurement_id',
        'default_account_id',
        'immobilization_account_id',
        'is_amortizable',
        'depreciation_base',
        'residual_value',
        'service_start_date',
        'duration_years',
        'depreciation_expense_account_id',
        'depreciation_asset_account_id',
    ];

    protected $casts = [
        'is_amortizable' => 'boolean',
        'is_stockable' => 'boolean',
        'service_start_date' => 'date',
        'depreciation_base' => 'float',
        'residual_value' => 'float',
        'purchase_price' => 'float',
        'cost_price' => 'float',
        'selling_price' => 'float',
        'quantity' => 'float',
        'threshold_quantity' => 'float',
        'cump' => 'float',
        'duration_years' => 'integer',
    ];

    /**
     * Plan d'amortissement linéaire (tableau / JSON).
     * Accessible via $material->depreciation_schedule
     */
    public function getDepreciationScheduleAttribute(): array
    {
        return app(DepreciationService::class)->generateLinearSchedule($this);
    }

    public function materialCategory()
    {
        return $this->belongsTo('App\Models\MaterialCategory', 'mcategory_id');
    }

    public function materialMeasurement()
    {
        return $this->belongsTo('App\Models\MaterialMeasurement', 'material_measurement_id');
    }

    public function stockinDetail(){
        return $this->hasMany('App\Models\StockinDetail','material_id');
    }

    public function materialStockoutDetail(){
        return $this->hasMany('App\Models\MaterialStockoutDetail','material_id');
    }

    public function materialBigStoreInventoryDetail(){
        return $this->hasMany('App\Models\MaterialBigStoreInventoryDetail','material_id');
    }

    public function materialBigStoreDetail(){
        return $this->hasMany('App\Models\MaterialBigStoreDetail','material_id');
    }

    public function materialSmallStoreDetail(){
        return $this->hasMany('App\Models\MaterialSmallStoreDetail','material_id');
    }

    public function materialSmallStoreInventoryDetail(){
        return $this->hasMany('App\Models\MaterialSmallStoreInventoryDetail','material_id');
    }

    public function materialReceptionDetail(){
        return $this->hasMany('App\Models\MaterialReceptionDetail','material_id');
    }

    public function materialTransferDetail(){
        return $this->hasMany('App\Models\MaterialTransferDetail','material_id');
    }

    public function materialReturnDetail(){
        return $this->hasMany('App\Models\MaterialReturnDetail','material_id');
    }

    public function materialBigReport(){
        return $this->hasMany('App\Models\MaterialBigReport','material_id');
    }

    public function materialSmallReport(){
        return $this->hasMany('App\Models\MaterialSmallReport','material_id');
    }

    public function cashOutFlowRequestDetail(){
        return $this->hasMany('App\Models\CashOutFlowRequestDetail','material_id');
    }

    public function cashOutFlowDetail(){
        return $this->hasMany('App\Models\CashOutFlowDetail','material_id');
    }

    public function account(){
        return $this->belongsTo('App\Models\Accounting\AccountAccount', 'default_account_id');
    }

    public function immobilizationAccount()
    {
        return $this->belongsTo('App\Models\Accounting\AccountAccount', 'immobilization_account_id');
    }

    public function depreciationExpenseAccount()
    {
        return $this->belongsTo('App\Models\Accounting\AccountAccount', 'depreciation_expense_account_id');
    }

    public function depreciationAssetAccount()
    {
        return $this->belongsTo('App\Models\Accounting\AccountAccount', 'depreciation_asset_account_id');
    }
}
