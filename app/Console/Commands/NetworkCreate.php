<?php

namespace App\Console\Commands;

use App\Network;
use App\User;
use Illuminate\Console\Command;

class NetworkCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:create {name} {shortname} {description} {--website=} {--language=en} {--timezone=Europe/London} {--wordpress} {--zapier} {--auto-approve-events}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a network.';

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
        $name = $this->argument('name');
        $shortname = $this->argument('shortname');
        $description = $this->argument('description');
        $website = $this->option('website');
        $language = $this->option('language');
        $timezone = $this->option('timezone');
        $wordpress = $this->option('wordpress');
        $zapier = $this->option('zapier');
        $autoApproveEvents = $this->option('auto-approve-events');

        $network = new Network();
        $network->name = $name;
        $network->shortname = $shortname;
        $network->description = $description;
        $network->website = $website;
        $network->default_language = $language;
        $network->timezone = $timezone;
        $network->events_push_to_wordpress = $wordpress;
        $network->include_in_zapier = $zapier;
        $network->auto_approve_events = $autoApproveEvents;

        if ($network->save()) {
            $this->info("Created network {$network->id}");
        } else {
            $this->error("Create network failed");
        }
    }
}
