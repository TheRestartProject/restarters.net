<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class AnonymiseUsersForTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:anonymiseusersfortest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (env('APP_ENV') !== 'local') {
            $this->error('This command is only designed to be run during local development.');

            return;
        }

        // We want to leave the accounts with a trp.org email.
        $users = User::where('email', 'not like', '%therestartproject.org')->get();

        $faker = \Faker\Factory::create();

        foreach ($users as $user) {
            echo "\nAnonymising #".$user->id;
            $user->name = $faker->name();
            $user->email = $faker->unique()->safeEmail();
            $user->biography = $faker->text();
            $username = $faker->userName();
            $user->username = $username;
            $user->mediawiki = $username;
            $user->location = $faker->city();
            $user->latitude = $faker->latitude();
            $user->longitude = $faker->longitude();
            $user->age = $faker->year(2000);
            $user->password = Hash::make('secret');

            $user->save();

            // TODO: anonymise photos.
        }
    }
}
