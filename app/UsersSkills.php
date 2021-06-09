<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

class UsersSkills extends Model
{
    protected $table = 'users_skills';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['skill', 'user'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public $timestamps = false;

    //Table Relations
    public function skillName()
    {
        return $this->hasOne(\App\Skills::class, 'id', 'skill');
    }
}
