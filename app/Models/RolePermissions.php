<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermissions extends Model
{
    protected $table = 'roles_permissions';
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

    // Setters

    //Getters
}
