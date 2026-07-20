<?php

namespace App\Http\Controllers;

use App\Helpers\Fixometer;
use Auth;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Party;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Guard an Administrator-only action. Returns a 403 JSON response when the
     * current user is not an Administrator, or null when they are - so callers
     * do `if ($resp = $this->requireAdministrator()) { return $resp; }`.
     *
     * Extracted from ~17 byte-identical inline checks across the admin CRUD
     * controllers (2026-07 API audit - a copy that gets missed silently
     * exposes a mutation).
     */
    protected function requireAdministrator(): ?JsonResponse
    {
        // getUser(), not Auth::user(): the latter is null when the caller
        // authenticates with ?api_token= (the legacy api guard), so an
        // Administrator using a token was told Forbidden. getUser() is this
        // class's own resolver for exactly that - session, then sanctum, then
        // the api guard.
        if (!Fixometer::hasRole($this->getUser(), 'Administrator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return null;
    }

    /**
     * Resolve the authenticated user, accepting a session login, a Sanctum
     * bearer token (the Nuxt SPA), or the legacy api guard. Throws
     * AuthenticationException (401) if none authenticate.
     *
     * Hoisted here from four byte-identical private copies in the API
     * controllers (Device/Event/Group/Alert) - see the 2026-07 API audit.
     */
    protected function getUser()
    {
        // We want to allow this call to work if a) we are logged in as a user, or b) we have a valid API token.
        //
        // This is a slightly odd thing to do, but it is necessary to get both the PHPUnit tests and the
        // real client use of the API to work.
        $user = Auth::user();

        if (!$user) {
            // SPA bearer tokens authenticate via the sanctum guard.
            $user = auth('sanctum')->user();
        }

        if (!$user) {
            $user = auth('api')->user();
        }

        if (!$user) {
            throw new AuthenticationException();
        }

        return $user;
    }
}
