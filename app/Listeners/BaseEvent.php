<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;

abstract class BaseEvent implements ShouldQueue {
    use Queueable;
    
    /**
     * Handle the queued event invocation.
     * 
     * All event listeners need this method for queued events to work.
     */
    public function __invoke()
    {
        if (func_num_args() > 0) {
            $this->handle(func_get_arg(0));
        } else {
            Log::error(get_class($this) . '::__invoke() called without arguments');
        }
    }
}
