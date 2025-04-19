<?php

namespace App\Console\Commands;

use App\Listeners\CreateWordpressPostForEvent;
use App\Party;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WordpressCreateEventFailed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wordpress:event:create_failed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push an events to WordPress for all events which ought to have one, but don\'t.';

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
    public function handle(): void
    {
        $wordpressClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
        $wordpressClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));

        $events = Party::whereDate('created_at', '>=', now()->subMonths(13))->where('wordpress_post_id', null)->get();

        foreach ($events as $event) {
            $event = Party::findOrFail($event->idevents);

            if ($event->shouldPushToWordpress()) {
                try {
                    Log::info("Creating missing event " . $event->idevents . " on WordPress");
                    $l = new CreateWordpressPostForEvent($wordpressClient);
                    $l->createEventOnWordpress($event);
                } catch (\Exception $e) {
                    Log::error("Failed to create event " . $event->idevents . " on WordPress: " . $e->getMessage());
                }
            }
        }
    }
}
