<?php

namespace App\Console\Commands;

use App\Network;
use App\User;
use Illuminate\Console\Command;

class SyncNetworkUsersToDiscourseGroup extends Command
{
    private $discourseClient;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discourse:syncnetwork {network}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs all users in a network to that network\'s Discourse group';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        if (! config('restarters.features.discourse_integration')) {
            return;
        }

        $this->discourseClient = app('discourse-client');
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (! config('restarters.features.discourse_integration')) {
            return;
        }

        $networkName = $this->argument('network');

        $network = Network::where('shortname', $networkName)->first();
        $discourseGroupName = $network->discourse_group;

        foreach ($network->groups as $group) {
            $this->info("Check group {$group->name}");
            $users = $group->membersJoined()->get();
            $this->info("..." . $users->count() . " members");

            foreach ($users as $user) {
                try {
                    $this->info("...syncing #{$user->id} {$user->name}");
                    $this->syncUserToGroup($user, $discourseGroupName);
                } catch (\Exception $ex) {
                    $this->error($ex->getMessage());
                }
                // Sleep to avoid Discourse rate limiting of 60 requests per minute.
                // See https://meta.discourse.org/t/global-rate-limits-and-throttling-in-discourse/78612
                sleep(1);
            }
        }
    }

    protected function syncUserToGroup($user, $groupName)
    {
        $endpoint = '/admin/users/sync_sso';

        // see https://meta.discourse.org/t/sync-sso-user-data-with-the-sync-sso-route/84398 for details on the sync_sso route.
        $sso_secret = config('discourse-api.sso_secret');

        // We have to send all these details, even if they are not
        // being updated, as otherwise they are blanked in the Discourse
        // SSO values.  Discourse is currently configured to take only email from SSO values, but better not to blank them regardless.
        $sso_params = [
            'external_id' => $user->id,
            'email' => $user->email,
            'username' => $user->username,
            'name' => $user->name,
            'add_groups' => $groupName,
        ];
        $sso_payload = base64_encode(http_build_query($sso_params));
        $sig = hash_hmac('sha256', $sso_payload, $sso_secret);

        $response = $this->discourseClient->request(
            'POST',
            $endpoint,
            [
                'form_params' => [
                    'sso' => $sso_payload,
                    'sig' => $sig,
                ],
            ]
        );
        $this->info($response->getReasonPhrase());
        if ($response->getReasonPhrase() !== 'OK') {
            $this->error($response->getReasonPhrase());
        }

        if (! $response->getStatusCode() === 200) {
            $this->error('Could not sync '.$user->id.' to Discourse: '.$response->getReasonPhrase());
        }
    }
}
