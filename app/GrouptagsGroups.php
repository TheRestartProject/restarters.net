<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * [theGroup description]
     * A Group Tag Group has One Group.
     *
     * @author Christopher Kelker - @date 2019-03-21
     * @editor  Christopher Kelker
     * @version 1.0.0
     * @return  [type]
     */
    public function theGroup(): HasOne
    {
        return $this->hasOne(Group::class, 'idgroups', 'group');
    }
}
