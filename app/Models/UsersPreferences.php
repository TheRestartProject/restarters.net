<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersPreferences extends Model
{
    public $timestamps = false;
    protected $table = 'users_preferences';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['preference_id', 'user_id'];
}
