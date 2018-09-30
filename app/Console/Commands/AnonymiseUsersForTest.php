<?php

namespace App\Console\Commands;

use App\User;

use Illuminate\Console\Command;

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
        if (env('APP_ENV') !== 'local') {
            $this->error('This command is only designed to be run during local development.');
            return;
        }

        // We want to leave the accounts with a trp.org email.
        $users = User::where('email', 'not like', '%therestartproject.org')->get();

        $faker = \Faker\Factory::create();

        foreach ($users as $user) {
            echo "\nAnonymising #" . $user->id;
            $user->name = $faker->name;
            $user->email = $faker->unique()->safeEmail;
            $user->biography = $faker->text;
            $user->username = $faker->userName;
            $user->age = $faker->year(2000);

            $user->save();

            // TODO: anonymise photos.
        }
    }
}
