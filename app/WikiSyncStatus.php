<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class WikiSyncStatus
{
    use HasFactory;

    const DoNotCreate = 0;
    const CreateAtLogin = 1;
    const Created = 2;

    // The wiki account exists, but its password isn't the user's password hash here. See LogInToWiki::passwordOutOfSync.
    const PasswordOutOfSync = 3;
}
