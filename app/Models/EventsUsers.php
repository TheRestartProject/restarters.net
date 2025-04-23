<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
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
    public function role(): HasOne
    {
        return $this->hasOne(\App\Models\Role::class, 'role', 'role');
    }

    //Table Relations
    public function volunteer(): HasOne
    {
        return $this->hasOne(\App\Models\User::class, 'id', 'user');
    }

    public function event(): HasOne
    {
        return $this->hasOne(\App\Models\Party::class, 'id', 'event');
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
