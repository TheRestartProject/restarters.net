<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupTags extends Model
{
    use HasFactory;

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
    public function group_tags(): BelongsToMany
    {
        return $this->belongsToMany(\App\GrouptagsGroups::class);
    }

    //Table Relations
    public function groupTagGroups(): HasMany
    {
        return $this->hasMany(GrouptagsGroups::class, 'group_tag', 'id');
    }

    // Setters

    //Getters
}
