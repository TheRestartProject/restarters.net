<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\HasOne;
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
    public function skillName(): HasOne
    {
        return $this->hasOne(\App\Skills::class, 'id', 'skill');
    }
}
