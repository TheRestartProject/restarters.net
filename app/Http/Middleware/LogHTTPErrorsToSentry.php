<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Closure;

class LogHTTPErrorsToSentry {
    /**
     * Report 4xx and 5xx responses to Sentry, one issue per status code and route.
     *
     * Not reported:
     * - 404s: mostly crawlers and scanners, so only the ones our own pages link to are reported, by
     *   ReportBrokenLinksToSentry.
     * - Error responses rendered from an exception the exception handler reports: the exception is already in
     *   Sentry with its stack trace, and this message would be a second copy of it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $httpCode = $response->getStatusCode();

        if ($httpCode >= 400 && $httpCode < 600 && $httpCode !== 404 && ! $this->alreadyReported($response)) {
            $route = $request->route() ? '/'.ltrim($request->route()->uri(), '/') : '/'.$request->path();
            \Sentry\captureMessage("HTTP return code $httpCode from $route");
        }

        return $response;
    }

    private function alreadyReported(Response $response): bool
    {
        $exception = $response->exception ?? null;

        return $exception instanceof \Throwable
            && app(\Illuminate\Contracts\Debug\ExceptionHandler::class)->shouldReport($exception);
    }
}
