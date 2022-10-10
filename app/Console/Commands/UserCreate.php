<?php

namespace App\Console\Commands;

use App\Role;
use App\Services\DiscourseService;
use App\User;
use App\WikiSyncStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create {name} {email} {password} {language} {repair_network_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a user.';

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
    public function handle(DiscourseService $discourseService)
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->argument('password');
        $language = $this->argument('language');
        $repair_network_id = $this->argument('repair_network_id');

        if (User::where('email', $email)->count() > 0)
        {
            $this->info("User $email already exists - leaving unmodified");
            return;
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ];

        $validator = Validator::make([
                                         'name' => $name,
                                         'email' => $email,
                                         'password' => $password,
                                     ], $rules);

        if ($validator->fails())
        {
            $this->error("Invalid parameters " . $validator->messages()->toJson());
        } else
        {
            $user = User::create([
                                     'name' => $name,
                                     'email' => $email,
                                     'password' => Hash::make($password),
                                     'role' => Role::RESTARTER,
                                     'recovery' => substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24),
                                     'recovery_expires' => strftime('%Y-%m-%d %X', time() + (24 * 60 * 60)),
                                     'calendar_hash' => Str::random(15),
                                     'username' => '',
                                     'wiki_sync_status' => WikiSyncStatus::CreateAtLogin,
                                     'language' => $language,
                                     'repair_network' => $repair_network_id,
                                 ]);

            if ($user)
            {
                $this->info("User created #" . $user->id);

                if (config('restarters.features.discourse_integration')) {
                    $discourseService->syncSso($user);
                }
            } else
            {
                $this->error("User creation failed");
            }

            $user->generateAndSetUsername();
        }
    }
}
