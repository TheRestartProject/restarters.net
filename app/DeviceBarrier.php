<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeviceBarrier extends Model
{
    protected $table = 'devices_barriers';
    protected $fillable = ['device_id', 'barrier'];
    protected $hidden = [];
    public $timestamps = false;
}
