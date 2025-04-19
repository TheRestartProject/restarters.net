<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Closure;

class LogHTTPErrorsToSentry {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $httpCode = $response->getStatusCode();

        if ($httpCode >= 400 && $httpCode < 600) {
            // Log all 4xx and 5xx errors to Sentry.  Once we do that we'll find out if there are any we should
            // suppress.
            \Sentry\CaptureMessage("HTTP return code $httpCode");
        }

        return $response;
    }
}