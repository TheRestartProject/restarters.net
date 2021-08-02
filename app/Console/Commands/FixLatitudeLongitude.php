<?php

namespace App\Console\Commands;

use App\Helpers\Fixometer;
use App\Helpers\Geocoder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixLatitudeLongitude extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:latitudelongitude';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix latitude and longitude for some users';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Geocoder $geocoder)
    {
        $users = DB::select(DB::raw('SELECT * FROM users WHERE (latitude < -99 OR longitude < -99 OR latitude > 99 OR longitude > 99) AND deleted_at IS NULL;'));

        foreach ($users as $user) {
            $this->info("User {$user->id} {$user->country}:{$user->location}");
            $geocoded = $geocoder->geocode($user->location.','.$user->country);

            if (! empty($geocoded)) {
                $this->info("...{$geocoded['latitude']}, {$geocoded['longitude']}");
                DB::update(DB::raw("UPDATE users SET latitude = {$geocoded['latitude']}, longitude={$geocoded['longitude']} WHERE id = {$user->id};"));
            }
        }
    }
}
