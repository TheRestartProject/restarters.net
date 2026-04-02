<?php

namespace App\Http\Middleware;

use App\ApiClient;
use Closure;
use Illuminate\Http\Request;

class AuthenticateApiClient
{
    public function handle(Request $request, Closure $next, $requiredScope = null)
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken) {
            return $this->unauthorized();
        }

        $client = ApiClient::where('token_hash', hash('sha256', $bearerToken))->first();

        if (! $client || ! $client->active || $client->hasExpired()) {
            return $this->unauthorized();
        }

        if ($requiredScope && ! $client->hasScope($requiredScope)) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $request->attributes->set('apiClient', $client);

        $response = $next($request);

        $client->last_used_at = now();
        $client->save();

        return $response;
    }

    private function unauthorized()
    {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], 401);
    }
}
