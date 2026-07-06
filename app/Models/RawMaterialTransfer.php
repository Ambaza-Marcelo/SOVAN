<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialTransfer extends Model
{
    //
    protected $table = 'raw_material_transfers';


    protected $fillable = [
        'date',
        'transfer_no',
        'requisition_no',
        'transfer_signature',
        'description',
        'rejected_motif',
        'created_by',
        'updated_by',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'rejected_by',
        'reseted_by',
        'status',
        'origin_store_id',
        'origin_extra_store_id',
        'destination_store_id',
        'dest_bg_store_id',
    ];

    public function originStore(){
        return $this->belongsTo('App\Models\RawMaterialBigStore', 'origin_store_id');
    }

    public function originExtraStore(){
        return $this->belongsTo('App\Models\RawMaterialExtraBigStore', 'origin_extra_store_id');
    }

    public function destinationStore(){
        return $this->belongsTo('App\Models\RawMaterialSmallStore', 'destination_store_id');
    }

    public function destinationBigStore(){
        return $this->belongsTo('App\Models\RawMaterialBigStore', 'dest_bg_store_id');
    }
}
