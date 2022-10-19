<?php

namespace App\Console\Commands;

use App\Group;
use App\Network;
use App\User;
use Illuminate\Console\Command;

class NetworkGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:group:add {networkname} {groupname}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a group to a network.';

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
        $groupname = $this->argument('groupname');

        // Find or fail network
        $network = Network::where('name', $networkname)->first();

        if (!$network) {
            $this->error('Network not found.');
            return;
        }

        $group = Group::where('name', $groupname)->first();

        if (!$group) {
            $this->error('Group not found.');
            return;
        }

        $network->addGroup($group);
        $this->info("Added group to network");
    }
}
