<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        $exception = $this->mapException($exception);

        if ($request->wantsJson()) {
            if ($exception instanceof ValidationException) {
                return response()->json(
                    ['message' => $exception->getMessage(), 'errors' => $exception->errors()],
                    422);
            }

            // These don't implement getStatusCode(), so without these the generic branch below rendered them as
            // 500 instead of the correct status for JSON requests - and unreported, as none of them is a bug.
            if ($exception instanceof AuthenticationException) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            if ($exception instanceof AuthorizationException) {
                // A policy can say why; keep that.
                return response()->json(['message' => $exception->getMessage() ?: 'Unauthorized.'], 403);
            }

            if ($exception instanceof ModelNotFoundException) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }

            if ($exception instanceof TokenMismatchException) {
                // The session expired with the page still open.
                return response()->json(['message' => $exception->getMessage()], 419);
            }

            return response()->json(
                ['message' => $exception->getMessage()],
                method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500);
        }

        return parent::render($request, $exception);
    }

    public function register(): void
    {
        // l5-swagger allows the API docs' source maps, but the Swagger UI package no longer ships them, so a
        // request for one (crawlers, open devtools) failed reading a file that isn't there. That's a 404.
        $this->map(function (FileNotFoundException $e) {
            return request()->is('docs/asset/*') ? new NotFoundHttpException('No such API docs asset', $e) : $e;
        });
    }

    public function report(Throwable $exception)
    {
        $exception = $this->mapException($exception);

        if (app()->bound('sentry') && app()->environment('production') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }
}
