<?php

namespace App\Support;

use Sentry\Event;
use Sentry\EventHint;

class SentryBeforeSend
{
    public static function handle(Event $event, ?EventHint $hint): ?Event
    {
        if ($hint !== null && $hint->exception !== null && str_contains($hint->exception->getMessage(), 'Could not reach the remote Mailgun server')) {
            // Mailgun unreachable events are retried via queue, no need to log to Sentry.
            return null;
        }

        return $event;
    }
}
