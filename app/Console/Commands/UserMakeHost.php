<?php

namespace App\Console\Commands;

use App\Role;
use App\User;
use App\WikiSyncStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserMakeHost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:makehost {email} {groupname}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a user a host of a group.';

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
        $email = $this->argument('email');
        $groupname = $this->argument('groupname');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User $email not found.");
            return;
        }

        $group = \App\Group::where('name', $groupname)->first();
        if (!$group) {
            $this->error("Group $groupname not found.");
            return;
        }

        if ($group->isVolunteer($user->id)) {
            $this->info("User $email is already a volunteer for group $groupname.");
        } else {
            $group->addVolunteer($user);
            $group->makeMemberAHost($user);
            $this->info("User $email is now a host of group $groupname.");
        }
    }
}
