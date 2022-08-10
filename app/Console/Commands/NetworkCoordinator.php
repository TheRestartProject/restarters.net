<?php

namespace App\Console\Commands;

use App\Group;
use App\Network;
use App\User;
use Illuminate\Console\Command;

class NetworkCoordinator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:coordinator:add {networkname} {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a network coordinator.';

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
        $networkname = $this->argument('networkname');

        $network = Network::where('name', $networkname)->first();

        if (!$network) {
            $this->error('Network not found.');
            return;
        }

        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error('User not found.');
            return;
        }

        $network->addCoordinator($user);
        $this->info("Added network coordinator");
    }
}
