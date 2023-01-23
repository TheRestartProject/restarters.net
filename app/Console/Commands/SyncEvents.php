<?php

namespace App\Console\Commands;

use App\Group;
use App\Party;
use DateTime;
use Illuminate\Console\Command;

class SyncEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:events {--datefrom=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Performs a bulk sync of event details from restarters application to Wordpress front-end site.  Not designed to be run on a regular basis - sometimes needed for one-off bulk syncs if new fields are introduced to events in restarters that need uploading to wordpress.';

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
     *
     * @return mixed
     */
    public function handle()
    {
        $eventsQuery = Party::whereNotNull('wordpress_post_id')->where('approved', true);
        if (! is_null($this->option('datefrom'))) {
            $dateFrom = $this->option('datefrom');
            if (! $this->validateDate($dateFrom)) {
                $this->error('Date from does not seem to be valid');

                return;
            }
            $eventsQuery->where('event_start_utc', '>=', $this->option('datefrom'));
            $this->info('Starting from date: '.$dateFrom);
        }

        $events = $eventsQuery->get();

        $numberOfEvents = count($events);

        $currentEventNumber = 1;

        foreach ($events as $event) {
            try {
                $startTimestamp = strtotime($event->event_start_utc);
                $endTimestamp = strtotime($event->event_end_utc);

                $group = Group::where('idgroups', $event->group)->first();

                $custom_fields = [
                    ['key' => 'party_grouphash', 'value' => $event->group],
                    ['key' => 'party_groupcountry', 'value' => $group->country],
                    ['key' => 'party_groupcity', 'value' => $group->area],
                    ['key' => 'party_venue', 'value' => $event->venue],
                    ['key' => 'party_location', 'value' => $event->location],
                    ['key' => 'party_time', 'value' => $event->getEventStartEndLocal()],
                    ['key' => 'party_date', 'value' => $event->event_date_local],
                    ['key' => 'party_timestamp', 'value' => $startTimestamp],
                    ['key' => 'party_timestamp_end', 'value' => $endTimestamp],
                    ['key' => 'party_timezone', 'value' => $event->timezone],
                    ['key' => 'party_stats', 'value' => $event->idevents],
                    ['key' => 'party_lat', 'value' => $event->latitude],
                    ['key' => 'party_lon', 'value' => $event->longitude],
                ];

                $content = [
                    'post_type' => 'party',
                    'post_title' => ! empty($event->venue) ? $event->venue : $event->location,
                    'post_content' => $event->free_text,
                    'custom_fields' => $custom_fields,
                ];

                // We need to remap all custom fields because they all get unique IDs across all posts, so they don't get mixed up.
                $thePost = $this->wordpressClient->getPost($event->wordpress_post_id);

                foreach ($thePost['custom_fields'] as $field) {
                    foreach ($custom_fields as $k => $set_field) {
                        if ($field['key'] == $set_field['key']) {
                            $custom_fields[$k]['id'] = $field['id'];
                        }
                    }
                }

                $content['custom_fields'] = $custom_fields;
                $this->wordpressClient->editPost($event->wordpress_post_id, $content);

                $this->info('['.$currentEventNumber.'/'.$numberOfEvents.'] Synced '.$event->venue.' (idevents = '.$event->idevents.', wordpress_post_id = '.$event->wordpress_post_id.')');
            } catch (\Exception $e) {
                $this->error('Error: '.$event->venue.': '.$e->getMessage());
            }

            $currentEventNumber++;
        }
    }

    protected function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }
}
