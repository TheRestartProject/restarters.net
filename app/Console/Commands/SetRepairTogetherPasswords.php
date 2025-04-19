<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SetRepairTogetherPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:repairtogetherpasswords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        $users = User::where('repair_network', 3)
               ->where('password', '')
               ->orderBy('name');

        $fp = fopen('file.csv', 'w');
        foreach ($users->get() as $user) {
            $password = Str::random(8);

            // write out the email and password
            $this->info('Writing user: '.$user->email);
            fputcsv($fp, [$user->name, $user->email, $password]);

            // set the password
            $user->password = Hash::make($password);
            $user->save();
        }
        fclose($fp);
    }
}
