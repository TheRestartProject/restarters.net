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
    protected $signature = 'network:group {networkid} {groupid}';

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
        $networkid = $this->argument('networkid');
        $groupid = $this->argument('groupid');

        $network = Network::findOrFail($networkid);
        $group = Group::findOrFail($groupid);

        $network->addGroup($group);
        $this->info("Added group to network");
    }
}
