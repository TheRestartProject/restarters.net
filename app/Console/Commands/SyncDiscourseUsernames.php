<?php

namespace App\Console\Commands;

use App\Services\DiscourseService;
use App\User;
use Illuminate\Console\Command;

class SyncDiscourseUsernames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:discourseusernames';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieves username details from Discourse and updates Restarters DB to match.';

    private $discourseApiKey;
    private $discourseApiUser;
    private $discourseService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(DiscourseService $discourseService)
    {
        parent::__construct();
        $this->discourseService = $discourseService;

        $discourseApiKey = env('DISCOURSE_APIKEY');
        $discourseApiUser = env('DISCOURSE_APIUSER');

        if (is_null($discourseApiKey) || empty($discourseApiKey)) {
            $this->error('DISCOURSE_APIKEY is not set');
            exit();
        }

        if (is_null($discourseApiUser) || empty($discourseApiUser)) {
            $this->error('DISCOURSE_APIUSER is not set');
            exit();
        }

        $this->discourseApiKey = $discourseApiKey;
        $this->discourseApiUser = $discourseApiUser;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $usersFoundInRestarters = 0;
        $updatedUsers = 0;

        $usersFromDiscourse = $this->discourseService->getAllUsers();
        $discourseUserCount = count($usersFromDiscourse);

        foreach ($usersFromDiscourse as $discourseUser) {
            if (property_exists($discourseUser, 'single_sign_on_record') &&
                $discourseUser->single_sign_on_record &&
                property_exists($discourseUser->single_sign_on_record, 'external_id')) {
                $user = User::find($discourseUser->single_sign_on_record->external_id);

                if (! is_null($user)) {
                    $usersFoundInRestarters++;

                    if ($user->username == $discourseUser->username) {
                        $this::line('SKIPPING: '.$user->username.' (no username change required)');
                    } else {
                        $this::info('UPDATING: '.$user->username.': '.$user->username.' to '.$discourseUser->username);
                        $user->username = $discourseUser->username;
                        $user->save();

                        $updatedUsers++;
                    }
                } else {
                    $this->error($discourseUser->username.' not found in Restarters DB');
                }
            } else {
                $this::line('SKIPPING: '.$discourseUser->username.' (no email address)');
            }
        }

        $this->info('');
        $this->info('Users found in Discourse: '.$discourseUserCount);
        $this->info('Found user count: '.$usersFoundInRestarters);
        $this->info('Updated users: '.$updatedUsers);
    }
}
