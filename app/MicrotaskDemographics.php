<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MicrotaskDemographics extends Model
{
    protected $table = 'microtask_demographics';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['session_id', 'ip_address', 'age', 'country', 'task'];
}
