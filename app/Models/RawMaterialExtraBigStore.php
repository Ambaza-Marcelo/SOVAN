<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialExtraBigStore extends Model
{
    //
    protected $fillable = [
        'name',
        'code',
        'emplacement',
        'manager',
        'created_by',
        'updated_by',
        'description',
    ];

    public function rawMaterialTransfer(){
        return $this->hasMany('App\Models\RawMaterialTransfer','origin_store_id');
    }

    public function rawMaterialTransferDetail(){
        return $this->hasMany('App\Models\RawMaterialTransferDetail','origin_store_id');
    }

    public function rawMaterialReception(){
        return $this->hasMany('App\Models\RawMaterialReception','destination_store_id');
    }

    public function rawMaterialReceptionDetail(){
        return $this->hasMany('App\Models\RawMaterialReceptionDetail','destination_store_id');
    }
}
