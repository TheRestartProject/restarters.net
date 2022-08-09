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
    protected $signature = 'network:coordinator {id} {--add=}';

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
        $id = $this->argument('id');
        $add = $this->option('add');

        $network = Network::findOrFail($id);

        $user = User::findOrFail($add);
        $network->addCoordinator($user);
        $this->info("Added network coordinator");
    }
}
