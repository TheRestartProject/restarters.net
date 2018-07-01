<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;

class EventsUsers extends Model
{

    protected $table = 'events_users';

    protected $primaryKey = 'idevents_users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['event', 'user', 'status', 'role'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public $timestamps = false;

    //Table Relations
    public function role() {
      return $this->hasOne('App\Role', 'role', 'role');
    }

    //Table Relations
    public function eventUser() {
      return $this->hasOne('App\User', 'id', 'user');
    }
}
