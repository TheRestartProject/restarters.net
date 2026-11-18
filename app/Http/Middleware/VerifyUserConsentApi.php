<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * API-layer port of VerifyUserConsent (which gates the Blade app at the page
 * level): authenticated users who have not given the required data consents
 * may read but not mutate. The SPA reads consent state from GET /api/v2/session
 * and routes such users to the consent-completion page; this middleware is the
 * server-side enforcement behind that.
 *
 * Reads are allowed (the client needs /session and reference data to render
 * the consent flow); the auth family is exempt so login/registration/consent
 * itself always work.
 */
class VerifyUserConsentApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        if ($request->is('api/v2/auth/*') || $request->is('api/v2/session')) {
            return $next($request);
        }

        $user = Auth::user() ?? auth('sanctum')->user() ?? auth('api')->user();

        if ($user && ! $user->hasUserGivenConsent()) {
            return response()->json([
                'message' => 'Data consent is required before making changes.',
                'reason' => 'consent_required',
            ], 403);
        }

        return $next($request);
    }
}
