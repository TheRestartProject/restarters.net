<?php

namespace App\Console\Commands;

use App\Helpers\FixometerHelper;
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
        $users = DB::select(DB::raw('SELECT * FROM users WHERE (latitude < -99 OR longitude < -99 OR latitude > 99 OR longitude > 99) AND deleted_at IS NULL;'));

        foreach ($users as $user) {
            $this->info("User {$user->id} {$user->country}:{$user->location}");
            $latlng = FixometerHelper::getLatLongFromCityCountry($user->location, $user->country);

            if (count($latlng) == 3) {
                $this->info("...{$latlng[0]}, {$latlng[1]}");
                DB::update(DB::raw("UPDATE users SET latitude = {$latlng[0]}, longitude={$latlng[1]} WHERE id = {$user->id};"));
            }
        }
    }
}
