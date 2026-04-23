<?php

namespace App\Console\Commands;

use App\Listeners\CreateWordpressPostForEvent;
use App\Party;
use Illuminate\Console\Command;

class WordpressCreateEventMissing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wordpress:event:missing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push missing events to WordPress';

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
        $events = Party::future()->where('approved', true)->where('wordpress_post_id', null)->get();

        foreach ($events as $event) {
            if ($event->shouldPushToWordpress()) {
                echo "Missing event for WordPress: {$event->idevents} " . $event->title . "\n";
                $wordpressClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
                $wordpressClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'),
                    env('WP_XMLRPC_PSWD'));
                $l = new CreateWordpressPostForEvent($wordpressClient);
                $l->createEventOnWordpress($event);
            }
        }
    }
}
