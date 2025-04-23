<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MigrateWikiPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wiki:migratepasswords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move wiki accounts over to the new password format';

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
     */
    public function handle(): void
    {
        $users = User::whereNotNull('mediawiki')->whereNull('deleted_at')->get();

        foreach ($users as $user) {
            $this->info("php changePassword.php --user=\"{$user->mediawiki}\" --password='{$user->password}'");
        }
    }
}
