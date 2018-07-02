<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;

class GrouptagsGroups extends Model
{

    protected $table = 'grouptags_groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['group_tag', 'group'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public $timestamps = false;

}
