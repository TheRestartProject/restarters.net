<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Barrier extends Model
{
    protected $table = 'barriers';
    protected $fillable = ['id', 'barrier'];
    protected $hidden = [];
    public $timestamps = false;

    public function barriers(): BelongsToMany
    {
        return $this->belongsToMany(\App\Device::class, 'devices_barriers', 'barrier_id', 'device_id');
    }
}
