<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;

class EventsUsers extends Model
{

    protected $table = 'events_users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    //Table Relations

}
