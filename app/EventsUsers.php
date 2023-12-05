<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

class EventsUsers extends Model
{
    protected $table = 'events_users';

    protected $primaryKey = 'idevents_users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['event', 'user', 'status', 'role', 'full_name'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public $timestamps = false;

    //Table Relations
    public function role()
    {
        return $this->hasOne(\App\Role::class, 'role', 'role');
    }

    //Table Relations
    public function volunteer()
    {
        return $this->hasOne(\App\User::class, 'id', 'user');
    }

    public function event()
    {
        return $this->hasOne(\App\Party::class, 'id', 'event');
    }

    public function getFullName()
    {
        if (! is_null($this->full_name)) {
            return $this->full_name;
        } else {
            return 'Anonymous';
        }
    }
}
