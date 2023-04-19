<?php

namespace App\Console\Commands;

use App\Group;
use App\Helpers\Geocoder;
use App\Network;
use Illuminate\Console\Command;

class SetGroupNetworkData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remap:groupnetworkdata {networkname}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-geocode group locations and set network-data';

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
    public function handle()
    {
        $geocoder = new Geocoder();
        $networkname = $this->argument('networkname');

        // Find or fail network
        $network = Network::where('name', $networkname)->first();

        if ($network) {
            foreach ($network->groups as $group)
            {
                $this->info('Check group ' . $group->idgroups . ' ' . $group->name);

                if ($group->latitude || $group->longitude)
                {
                    $this->info("...geocode {$group->latitude}, {$group->longitude}");
                    $loc = $geocoder->reverseGeocode($group->latitude, $group->longitude);

                    if ($loc && $loc['place']) {
                        $this->info('...found place ' . $loc['place']);
                        $g = Group::findOrFail($group->idgroups);
                        $g->latitude = $loc['latitude'];
                        $g->longitude = $loc['longitude'];
                        $g->network_data = [
                            'place' => $loc['place'],
                        ];
                        $g->save();
                    } else {
                        $this->error($group->id . ' ' . $group->name . " couldn't reverse geocode");
                    }
                }
            }
        }
    }
}
