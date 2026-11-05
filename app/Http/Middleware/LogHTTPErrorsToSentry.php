<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Closure;

class LogHTTPErrorsToSentry {
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $httpCode = $response->getStatusCode();

        if ($httpCode >= 400 && $httpCode < 600 && $httpCode !== 404) {
            // Log 4xx and 5xx errors to Sentry.  404s are mostly crawlers and scanners, so only the ones that our
            // own pages link to are reported, by ReportBrokenLinksToSentry.
            \Sentry\CaptureMessage("HTTP return code $httpCode");
        }

        return $response;
    }
}