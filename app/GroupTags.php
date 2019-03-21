<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GroupTags extends Model
{
    protected $table = 'group_tags';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['tag_name', 'description'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    //Table Relations
    public function group_tags()
    {
        return $this->belongsToMany('App\GrouptagsGroups');
    }

    //Table Relations
    public function groupTagGroups()
    {
        return $this->hasMany(GrouptagsGroups::class, 'group_tag', 'id');
    }

    // Setters

    //Getters
}
