<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrTimeTable extends Model
{
    //
    protected $table = 'hr_time_tables';

    protected $fillable = [
        'day',
        'week',
        'month',
        'date',
        'shift_name',
        'employe_id',
    ];

    public function employe()
    {
        return $this->belongsTo('App\Models\HrEmploye', 'employe_id');
    }
}
