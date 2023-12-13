<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AttPunch extends Model
{
    //
    public function employee()
    {
        return $this->belongsTo(HrEmployee::class,'employee_id','id');
    }
    public function terminal_info()
    {
        return $this->belongsTo(AttTerminal::class,'terminal_id','id');
    }
}
