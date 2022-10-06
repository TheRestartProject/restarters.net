<?php

namespace App\Console\Commands;

use App\Listeners\CreateDiscourseGroupForGroup;
use App\Listeners\CreateWordpressPostForGroup;
use App\Network;
use App\User;
use Illuminate\Console\Command;

class WordpressCreateEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wordpress:event:create {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push an event to WordPress';

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
    public function handle()
    {
        $id = $this->argument('id');
        $event = Party::findOrFail($id);

        $wordpressClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
        $wordpressClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));
        $l = new CreateWordpressPostForEvent($wordpressClient);
        $l->createEventOnWordpress($event);
    }
}
