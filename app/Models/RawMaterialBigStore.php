<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialBigStore extends Model
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
        'default_account_id',
    ];

    public function rawMaterialTransfer(){
        return $this->hasMany('App\Models\RawMaterialTransfer','origin_store_id');
    }

    public function rawMaterialTransferDetail(){
        return $this->hasMany('App\Models\RawMaterialTransferDetail','origin_store_id');
    }

    public function barristTransfer(){
        return $this->hasMany('App\Models\BarristTransfer','origin_fstore_id');
    }

    public function barristTransferDetail(){
        return $this->hasMany('App\Models\BarristTransferDetail','origin_fstore_id');
    }

    public function rawMaterialReception(){
        return $this->hasMany('App\Models\RawMaterialReception','destination_store_id');
    }

    public function rawMaterialReceptionDetail(){
        return $this->hasMany('App\Models\RawMaterialReceptionDetail','destination_store_id');
    }

    public function defaultAccount()
    {
        return $this->belongsTo('App\Models\Accounting\AccountAccount', 'default_account_id');
    }
}
