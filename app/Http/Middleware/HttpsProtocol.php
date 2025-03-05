<?php

namespace App\Http\Middleware;

use Closure;

class HttpsProtocol
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // If we're behind a proxy that sets X-Forwarded-Proto
        if ($request->header('X-Forwarded-Proto') === 'https') {
            // Force URL generation to use HTTPS
            \URL::forceScheme('https');
            return $next($request);
        }

        if (! $request->secure() && (env('APP_ENV') === 'development' || env('APP_ENV') === 'production')) {
            // Force URL generation to use HTTPS
            \URL::forceScheme('https');
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
