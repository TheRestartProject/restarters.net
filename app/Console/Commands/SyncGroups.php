<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Helpers\Fixometer;
use Illuminate\Console\Command;

class SyncGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:groups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Performs a bulk sync of group details from restarters application to Wordpress front-end site.  Not designed to be run on a regular basis - sometimes needed for one-off bulk syncs if new fields are introduced to groups in restarters that need uploading to wordpress.';

    /**
     * The name and signature of the console command.
     *
     * @var WordpressClient
     */
    protected $wordpressClient;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->wordpressClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
        $this->wordpressClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $groups = Group::where('approved', true)->whereNotNull('wordpress_post_id')->get();

        foreach ($groups as $group) {
            try {
                $custom_fields = [
                    ['key' => 'group_city', 'value' => $group->area],
                    ['key' => 'group_country', 'value' => Fixometer::getCountryFromCountryCode($group->country_code)],
                    ['key' => 'group_website', 'value' => $group->website],
                    ['key' => 'group_hash', 'value' => $group->idgroups],
                    ['key' => 'group_latitude', 'value' => $group->latitude],
                    ['key' => 'group_longitude', 'value' => $group->longitude],
                ];

                $content = [
                    'post_type' => 'group',
                    'post_title' => $group->name,
                    'post_content' => $group->free_text,
                    'custom_fields' => $custom_fields,
                ];

                if (! empty($group->wordpress_post_id)) {
                    // We need to remap all custom fields because they all get unique IDs across all posts, so they don't get mixed up.
                    $existingPost = $this->wordpressClient->getPost($group->wordpress_post_id);

                    foreach ($existingPost['custom_fields'] as $field) {
                        foreach ($custom_fields as $k => $set_field) {
                            if ($field['key'] == $set_field['key']) {
                                $custom_fields[$k]['id'] = $field['id'];
                            }
                        }
                    }

                    $content['custom_fields'] = $custom_fields;
                    $this->wordpressClient->editPost($group->wordpress_post_id, $content);

                    $this->info('Synced '.$group->name.' (idgroups = '.$group->idgroups.', wordpress_post_id = '.$group->wordpress_post_id.')');
                } else {
                    $wpid = $this->wordpressClient->newPost($group->name, $group->free_text, $content);
                    $group->wordpress_post_id = $wpid;
                    $group->save();
                }
            } catch (\Exception $e) {
                $this->error('Error: '.$group->name.': '.$e->getMessage());
            }
        }
    }
}
