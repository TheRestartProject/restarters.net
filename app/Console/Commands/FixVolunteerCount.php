<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;
use App\Models\Party;

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
    protected $description = 'Fix the volunteer count for all events';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $events = Party::all();

        foreach ($events as $event) {
            $actual = DB::table('events_users')->where('event', $event->idevents)->where('status', 1)->count();

            if ($actual > $event->volunteers) {
                if ($event->volunteers < 0) {
                    $this->info("Event {$event->idevents} has negative count {$event->volunteers}, $actual have confirmed");
                } else {
                    $this->info("Event {$event->idevents} has count {$event->volunteers}, but more ($actual) have confirmed");
                }

                $event->volunteers = $actual;
                $event->save();
            } else if ($event->volunteers < 0) {
                $this->info("Event {$event->idevents} has negative count {$event->volunteers}, fewewr ($actual) have confirmed");
            }
        }
    }
}
