<?php

namespace App\Console\Commands;

use App\Party;
use App\Services\DiscourseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CrystalliseEventTimezone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:timezones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set timezone on past events';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(DiscourseService $discourseService)
    {
        parent::__construct();
        $this->discourseService = $discourseService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $events = Party::past()->where('timezone', null)->get();

        foreach ($events as $event) {
            $event->timezone = $event->theGroup->timezone;
            $event->save();
        }
    }
}
