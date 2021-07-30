<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeviceUrl extends Model
{
    protected $table = 'devices_urls';
    protected $fillable = ['device_id', 'url', 'source'];
    protected $hidden = [];
    public $timestamps = false;

    public function device()
    {
        return $this->hasOne(\App\Device::class, 'iddevices', 'device_id');
    }
}
