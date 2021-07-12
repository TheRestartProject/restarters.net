<?php

namespace App\Console\Commands;

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

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

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
     *
     * @return mixed
     */
    public function handle()
    {
        $discourseUserCount = 0;
        $usersFoundInRestarters = 0;
        $updatedUsers = 0;

        $resultsPage = 1;
        while (true) {
            $this->line('');
            $this->info('Results page '.$resultsPage);
            $this->line('');

            $rawJson = $this->retrieveDiscourseUsersJson($resultsPage);
            $usersFromDiscourse = json_decode($rawJson);

            $pagedResultsCount = count($usersFromDiscourse);
            $discourseUserCount += $pagedResultsCount;

            foreach ($usersFromDiscourse as $discourseUser) {
                $user = User::where('email', $discourseUser->email)->first();

                if ( ! is_null($user)) {
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
            }

            if ($pagedResultsCount < 100) {
                break;
            }

            $resultsPage++;
        }

        $this->info('');
        $this->info('Users found in Discourse: '.$discourseUserCount);
        $this->info('Found user count: '.$usersFoundInRestarters);
        $this->info('Updated users: '.$updatedUsers);
    }

    protected function retrieveDiscourseUsersJson($apiPage)
    {
        $endpoint = env('DISCOURSE_URL') . 't/admin/users/list/all.json?show_emails=true&page='.$apiPage.'&api_key='.$this->discourseApiKey.'&api_username='.$this->discourseApiUser;

        return file_get_contents($endpoint);
    }
}
