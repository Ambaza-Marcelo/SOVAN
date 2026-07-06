<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialCategory extends Model
{
    //
    protected $fillable = ['name'];


    public function rawMaterial(){
        return $this->hasMany('App\Models\RawMaterial','rmcategory_id');
    }
}
