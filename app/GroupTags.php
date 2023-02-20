<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupTags extends Model
{
    use HasFactory;

    // This is a magic value which is set on the live system, and is used to identify inactive groups.
    // It's not ideal to have this hardcoded, but the data exists.
    const INACTIVE = 10;

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
        return $this->belongsToMany(\App\GrouptagsGroups::class);
    }

    //Table Relations
    public function groupTagGroups()
    {
        return $this->hasMany(GrouptagsGroups::class, 'group_tag', 'id');
    }

    // Setters

    //Getters
}
