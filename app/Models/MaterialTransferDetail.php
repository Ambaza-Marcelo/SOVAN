<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialTransferDetail extends Model
{
    //
    protected $table = 'material_transfer_details';

    protected $fillable = [
        'date',
        'quantity_requisitioned',
        'quantity_transfered',
        'unit',
        'price',
        'transfer_no',
        'requisition_no',
        'transfer_signature',
        'description',
        'rejected_motif',
        'total_value_requisitioned',
        'total_value_transfered',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'rejected_by',
        'reseted_by',
        'status',
        'material_id',
        'origin_store_id',
        'origin_extra_store_id',
        'destination_store_id',
        'dest_bg_store_id',
    ];

    public function originStore(){
        return $this->belongsTo('App\Models\MaterialBigStore', 'origin_store_id');
    }
    
    public function origineStore(){
        return $this->belongsTo('App\Models\MaterialBigStore', 'origin_store_id');
    }
    
    public function destinationStore(){
        return $this->belongsTo('App\Models\MaterialSmallStore', 'destination_store_id');
    }

    public function originExtraStore(){
        return $this->belongsTo('App\Models\MaterialExtraBigStore', 'origin_extra_store_id');
    }
    
    public function destinationBigStore(){
        return $this->belongsTo('App\Models\MaterialBigStore', 'dest_bg_store_id');
    }

    public function material(){
        return $this->belongsTo('App\Models\Material');
    }
}
