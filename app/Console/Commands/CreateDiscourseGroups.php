<?php

namespace App\Console\Commands;

use App\Group;
use Illuminate\Console\Command;

class CreateDiscourseGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'group:create_discourse_group';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Discourse groups for all Restarters groups';

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
        $groups = Group::where('approved', true)->whereNull('discourse_group')->get();

        foreach ($groups as $group) {
            $create = false;

            foreach ($group->networks as $network) {
                if ($network->discourse_group !== null) {
                    $create = true;
                }
            }

            if ($create) {
                $ret = $group->createDiscourseGroup();

                if ($ret) {
                    $this->info($group->name  . " created");
                } else {
                    $this->error($group->name  . " create failed");
                }
            }
        }
    }
}
