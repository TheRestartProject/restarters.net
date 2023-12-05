<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;

abstract class BaseEvent implements ShouldQueue {
    use Queueable;
}
