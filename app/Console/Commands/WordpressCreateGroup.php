<?php

namespace App\Console\Commands;

use App\Group;
use App\Listeners\CreateWordpressPostForGroup;
use Illuminate\Console\Command;

class WordpressCreateGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wordpress:group:create {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push a group to WordPress';

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
        $id = $this->argument('id');
        $group = Group::findOrFail($id);

        $wordpressClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
        $wordpressClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));
        $l = new CreateWordpressPostForGroup($wordpressClient);
        $l->createGroupOnWordpress($group);
    }
}
