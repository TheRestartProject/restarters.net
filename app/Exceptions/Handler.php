<?php

namespace App\Exceptions;

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
        if ($request->wantsJson()) {
            if ($exception instanceof ValidationException) {
                return response()->json(
                    ['message' => $exception->getMessage(), 'errors' => $exception->errors()],
                    422);
            }

            // AuthenticationException / AuthorizationException don't implement
            // getStatusCode(), so without these the generic branch below rendered
            // them as 500 instead of the correct 401/403 for JSON API requests.
            if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json(['message' => $exception->getMessage()], 401);
            }

            if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return response()->json(['message' => $exception->getMessage()], 403);
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
