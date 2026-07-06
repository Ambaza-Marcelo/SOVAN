<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialStockin extends Model
{
    //
    protected $fillable = [
        'date',
        'stockin_no',
        'stockin_signature',
        'receptionist',
        'handingover',
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
        'dest_sm_store_id',
        'dest_bg_store_id',
        'dest_extra_store_id'
    ];

    public function rawMaterialSmallStore(){
        return $this->belongsTo('App\Models\RawMaterialSmallStore');
    }

    public function rawMaterialbigStore(){
        return $this->belongsTo('App\Models\RawMaterialBigStore');
    }

    public function rawMaterialExtraStore(){
        return $this->belongsTo('App\Models\RawMaterialExtraStore');
    }
}
