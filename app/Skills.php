<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;

class Skills extends Model
{

    protected $table = 'skills';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['skill_name', 'category', 'description'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    //Table Relations


    // Setters


    //Getters


}
