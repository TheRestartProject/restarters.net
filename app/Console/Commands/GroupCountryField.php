<?php

namespace App\Console\Commands;

use App\Group;
use App\Helpers\Fixometer;
use Illuminate\Console\Command;

class GroupCountryField extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'groups:country';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the group country field from the country_code field';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $groups = Group::all();

        foreach ($groups as $group) {
            $group->country = Fixometer::getCountryFromCountryCode($group->country_code);
            $group->save();
        }
    }
}
