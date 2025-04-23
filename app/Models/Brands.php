<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

class Brands extends Model
{
    protected $table = 'brands';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['brand_name'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
