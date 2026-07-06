<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialSmallStore extends Model
{
    //
    protected $fillable = [
        'name',
        'code',
        'store_signature',
        'emplacement',
        'manager',
        'verified',
        'created_by',
        'updated_by',
        'description',
        'default_account_id',
    ];

    public function defaultAccount()
    {
        return $this->belongsTo('App\Models\Accounting\AccountAccount', 'default_account_id');
    }
}
