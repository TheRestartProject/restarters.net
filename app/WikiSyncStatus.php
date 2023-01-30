<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class WikiSyncStatus
{
    use HasFactory;

    const DoNotCreate = 0;
    const CreateAtLogin = 1;
    const Created = 2;
}
