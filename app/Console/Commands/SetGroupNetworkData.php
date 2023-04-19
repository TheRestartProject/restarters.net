<?php

namespace App\Console\Commands;

use App\Group;
use App\Helpers\Geocoder;
use Illuminate\Console\Command;

class SetGroupNetworkData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remap:groupnetworkdata';

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
        $groups = (new Group)->findAll();

        $geocoder = new Geocoder();

        foreach ($groups as $group) {
            $this->info('Check group '.$group->id.' '.$group->name);

            if ($group->latitude || $group->longitude) {
                $this->info("...geocode {$group->latitude}, {$group->longitude}");
                $loc = $geocoder->reverseGeocode($group->latitude, $group->longitude);

                if ($loc) {
                    $this->info('...found locality '.$loc['locality']);
                    $g = Group::find($group->id);
                    $g->latitude = $loc['latitude'];
                    $g->longitude = $loc['longitude'];
                    $g->network_data = [
                        'place' => $loc['locality'],
                    ];
                    $g->save();
                } else {
                    $this->error($group->id.' '.$group->name." couldn't reverse geocode");
                }
            }
        }
    }
}
