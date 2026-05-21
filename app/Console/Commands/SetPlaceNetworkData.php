<?php

namespace App\Console\Commands;

use App\Group;
use App\Network;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Geocoder\Provider\Mapbox\Mapbox;
use Illuminate\Console\Command;

class SetPlaceNetworkData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:networkdata:place {networkname}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the place field in network_data';

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
     */
    public function handle(): void
    {
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
                    $loc = [];
                    $geocodeResponse = app('geocoder')->reverseQuery(ReverseQuery::fromCoordinates($group->latitude, $group->longitude)->withData('location_type', [ Mapbox::TYPE_PLACE ])->withLocale($network->default_language));
                    $addressCollection = $geocodeResponse->get();
                    $address = $addressCollection->get(0);
                    if ($address) {
                        $loc['place'] = $address->getStreetName();
                    }

                    if ($loc && $loc['place']) {
                        $this->info('...found place ' . $loc['place']);
                        $g = Group::findOrFail($group->idgroups);
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
