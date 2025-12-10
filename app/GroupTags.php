<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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
    protected $fillable = ['tag_name', 'description', 'network_id'];

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

    /**
     * Get the network this tag belongs to (null for global tags).
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class, 'network_id', 'id');
    }

    // Scopes

    /**
     * Scope to get only global tags (no network).
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('network_id');
    }

    /**
     * Scope to get tags for a specific network.
     */
    public function scopeForNetwork(Builder $query, int $networkId): Builder
    {
        return $query->where('network_id', $networkId);
    }

    /**
     * Scope to get tags available to a network (network-specific + global).
     */
    public function scopeAvailableForNetwork(Builder $query, int $networkId): Builder
    {
        return $query->where(function ($q) use ($networkId) {
            $q->where('network_id', $networkId)
              ->orWhereNull('network_id');
        });
    }

    // Helpers

    /**
     * Check if this is a global tag.
     */
    public function isGlobal(): bool
    {
        return $this->network_id === null;
    }
}
