<?php

namespace App\Console\Commands;

use App\Services\DiscourseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncDiscourseGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discourse:syncgroups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sychronise Restarters and Discourse group memberships';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(DiscourseService $discourseService)
    {
        parent::__construct();
        $this->discourseService = $discourseService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
//        error_log("Handle");
//        $client = app('discourse-client', [
//            'username' => 'neil',
//        ]);
//
//        // Restricted characters allowed in name.
//        $fullname = 'Test Group ' . date('Y-m-d H:i:s');
//        error_log("Fullname $fullname");
//        $name = str_replace(' ', '_', $fullname);
//        $name = preg_replace("/[^A-Za-z0-9_]/", '', $name);
//        $name = substr($name, 0, 25);
//
//        $params = [
//            'group' => [
//                'name' => $name,
//                'full_name' => $fullname,
//                'mentionable_level' => 3,
//                'messageable_level' => 99,
//                'visibility_level' => 0,
//                'members_visibility_level' => 0,
//                'automatic_membership_email_domains' => null,
//                'automatic_membership_retroactive' => false,
//                'primary_group' => false,
////                    'flair_url' => $group->groupImagePath(),
//                'flair_bg_color' => null,
//                'flair_color' => null,
//                'bio_raw' => 'Test description',
//                'public_admission' => true,
//                'public_exit' => true,
//                'default_notification_level' => 3,
//                'publish_read_state' => true,
//                'owner_usernames' => 'Jane_bloggs'
//            ]
//        ];
//        error_log(json_encode($params));
//
//        $endpoint = '/admin/groups.json';
//
//        Log::info('Creating group : '.json_encode($params));
//        $response = $client->request(
//            'POST',
//            $endpoint,
//            [
//                'form_params' => $params,
//            ]
//        );
//
//        error_log('Response status: '.$response->getStatusCode());
//        error_log('Response body: '.$response->getBody());

        $this->discourseService->syncUsersToGroups();
    }
}
