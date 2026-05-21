<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;
use App\Party;

class FixVolunteerCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:fixvolunteercount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix negative volunteer counts for events';

    /**
     * Execute the console command.
     *
     * Volunteer counts can be manually incremented/decremented, so we only
     * fix cases where the count has gone negative - that is always wrong.
     */
    public function handle(): void
    {
        $events = Party::where('volunteers', '<', 0)->get();

        if ($events->isEmpty()) {
            $this->info('No events with negative volunteer counts found.');
            return;
        }

        foreach ($events as $event) {
            $actual = DB::table('events_users')->where('event', $event->idevents)->where('status', 1)->count();
            $this->info("Event {$event->idevents}: volunteer count is {$event->volunteers}, setting to {$actual}");
            $event->volunteers = $actual;
            $event->save();
        }

        $this->info("Fixed {$events->count()} event(s).");
    }
}
