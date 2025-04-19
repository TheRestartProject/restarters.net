<?php

namespace App\Console\Commands;

use App\Group;
use Illuminate\Console\Command;

class CheckGroupLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:grouplocations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check that all group locations are geocodeable';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        $groups = Group::where('approved', true)->get();
        $geocoder = new \App\Helpers\Geocoder();

        foreach ($groups as $group) {
            if (! $group->location) {
                $this->error("Group {$group->idgroups} {$group->name} has no location");
            } else {
                $geocoded = $geocoder->geocode($group->location);

                if (empty($geocoded)) {
                    $this->error("Group {$group->idgroups} {$group->name} location {$group->location} fails to geocode");
                } else {
                    // Check that the geocoded location matches the current location of the group.
                    if (round($group->latitude, 1) != round($geocoded['latitude'], 1) || round($group->longitude, 1) != round($geocoded['longitude'], 1)) {
                        $this->error("Group {$group->idgroups} {$group->name} location {$group->location} geocodes to {$geocoded['latitude']},{$geocoded['longitude']} rather than {$group->latitude},{$group->longitude}");
                    }
                    //$this->info("Group {$group->idgroups} {$group->name} location {$group->location} geocodes ok");
                }
            }
        }
    }
}
