<?php

namespace App\Resolvers;

use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\UserResolver as UserResolverContract;

class UserResolver implements UserResolverContract
{
    /**
     * {@inheritdoc}
     */
    public static function resolve(Auditable $auditable = null)
    {
        // During login, Auth::check() may be false but the user is being set in the current request
        if (Auth::check()) {
            return Auth::user();
        }

        // For API requests or when user isn't authenticated yet
        return null;
    }
}