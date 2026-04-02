<?php

namespace App\Http\Middleware;

use App\ApiClient;
use Closure;
use Illuminate\Http\Request;

class EnforceApiClientOrigin
{
    public function handle(Request $request, Closure $next)
    {
        /** @var ApiClient|null $client */
        $client = $request->attributes->get('apiClient');

        if (! $client) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $requestOrigin = $request->headers->get('Origin');
        $allowedOrigins = $this->normalizeOrigins($client->allowed_origins);

        if ($requestOrigin && ! empty($allowedOrigins)) {
            $normalizedRequestOrigin = $this->normalizeOrigin($requestOrigin);

            if (! in_array($normalizedRequestOrigin, $allowedOrigins, true)) {
                return response()->json([
                    'message' => 'Origin not allowed.',
                ], 403);
            }
        }

        return $next($request);
    }

    private function normalizeOrigins($origins)
    {
        if (! $origins) {
            return [];
        }

        return array_values(array_filter(array_map(function ($origin) {
            return $this->normalizeOrigin($origin);
        }, $origins)));
    }

    private function normalizeOrigin($origin)
    {
        if (! $origin) {
            return null;
        }

        return strtolower(rtrim(trim($origin), '/'));
    }
}
