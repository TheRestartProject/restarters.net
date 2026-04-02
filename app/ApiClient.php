<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'token_hash',
        'token_hint',
        'scopes',
        'allowed_origins',
        'allowed_network_ids',
        'rate_limit_per_minute',
        'active',
        'expires_at',
        'last_used_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'scopes' => 'array',
        'allowed_origins' => 'array',
        'allowed_network_ids' => 'array',
        'active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function hasScope($scope)
    {
        return in_array($scope, $this->scopes ?: [], true);
    }

    public function hasExpired()
    {
        return $this->expires_at && Carbon::now()->greaterThan($this->expires_at);
    }
}
