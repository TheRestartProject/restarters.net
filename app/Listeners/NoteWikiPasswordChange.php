<?php

namespace App\Listeners;

use App\Events\PasswordChanged;

/**
 * Runs as the password changes, before the queued ChangeWikiPassword gets to it: until then a login can't use the
 * new password on the wiki, and shouldn't take that to mean the wiki is out of sync.
 */
class NoteWikiPasswordChange
{
    public function handle(PasswordChanged $event): void
    {
        ChangeWikiPassword::markPending($event->user);
    }
}
