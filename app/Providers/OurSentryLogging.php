<?php

namespace App\Providers;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Sentry\Severity;
use Sentry\State\Scope;

/**
 * Sends errors written to the log to Sentry, with the log context attached.
 */
class OurSentryLogging extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(MessageLogged::class, function (MessageLogged $e) {
            if (self::shouldCapture($e)) {
                \Sentry\withScope(function (Scope $scope) use ($e) {
                    if (! empty($e->context)) {
                        $scope->setContext('log', self::printable($e->context));
                    }

                    \Sentry\captureMessage($e->message, Severity::error());
                });
            }
        });
    }

    /**
     * An exception that reaches the exception handler is logged with the exception in its context, and the handler
     * has already sent it to Sentry; sending the log line too made a second issue for every exception.
     */
    public static function shouldCapture(MessageLogged $e): bool
    {
        return $e->level === 'error' && ! (($e->context['exception'] ?? null) instanceof \Throwable);
    }

    private static function printable(array $context): array
    {
        return array_map(fn ($value) => is_scalar($value) || is_null($value) ? $value : json_encode($value), $context);
    }
}
