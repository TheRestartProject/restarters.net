<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Throwable;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        // /api/v2 is an API-only surface (the Nuxt SPA + documented OpenAPI
        // contract): always render errors as JSON there, even when the caller
        // didn't send an Accept: application/json header, so the response shape
        // matches the documented #/components/responses/* error schemas.
        if ($request->wantsJson() || $request->is('api/v2/*')) {
            if ($exception instanceof ValidationException) {
                return response()->json(
                    ['message' => $exception->getMessage(), 'errors' => $exception->errors()],
                    422);
            }

            // AuthenticationException / AuthorizationException don't implement
            // getStatusCode(), so without these the generic branch below rendered
            // them as 500 instead of the correct 401/403 for JSON API requests.
            if ($exception instanceof AuthenticationException) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            if ($exception instanceof AuthorizationException) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            if ($exception instanceof ModelNotFoundException) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }

            return response()->json(
                ['message' => $exception->getMessage()],
                method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500);
        }

        return parent::render($request, $exception);
    }

    public function report(Throwable $exception)
    {
        if (app()->bound('sentry') && app()->environment('production') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }
}
