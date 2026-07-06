<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialRequisition extends Model
{
    //
    protected $fillable = [
        'date',
        'requisition_no',
        'production_plan_number',
        'requisition_signature',
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
        'type_store',
        'code_store',
        'flag1',
        'flag2',
    ];
}
