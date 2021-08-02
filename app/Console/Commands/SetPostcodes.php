<?php

namespace App\Console\Commands;

use App\Group;
use App\Helpers\Geocoder;
use Illuminate\Console\Command;

class SetPostcodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'postcodes:set';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set any missing postcodes';

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
            $this->info('Check group '.$group->id.' '.$group->name.' postcode '.$group->postcode);

            if (! $group->postcode) {
                if ($group->latitude || $group->longitude) {
                    $this->info("...no postcode, geocode {$group->latitude}, {$group->longitude}");
                    $results = $geocoder->reverseGeocode($group->latitude, $group->longitude);
                    $found = false;

                    if ($results->address_components) {
                        foreach ($results->address_components as $field) {
                            if ($field->types && $field->types[0] == 'postal_code') {
                                $this->info('...found postcode '.$field->long_name);
                                $found = true;
                                $g = Group::find($group->id);
                                $g->postcode = $field->long_name;
                                $g->save();
                            }
                        }
                    }

                    if (! $found) {
                        $this->error($group->id.' '.$group->name." couldn't geocode");
                    }
                } else {
                    $this->error($group->id.' '.$group->name.' has no lat/lng');
                }
            }
        }
    }
}
