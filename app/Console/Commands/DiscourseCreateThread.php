<?php

namespace App\Console\Commands;

use App\Events\ApproveEvent;
use App\Group;
use App\Helpers\Geocoder;
use App\Listeners\CreateDiscourseThreadForEvent;
use App\Party;
use App\Services\DiscourseService;
use Illuminate\Console\Command;
use Riverline\MultiPartParser\Part;

class DiscourseCreateThread extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discourse:create:thread {partyid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry creating a Discourse thread for a party';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(DiscourseService $discourseService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(DiscourseService $discourseService)
    {
        $id = $this->argument('partyid');

        $party = Party::findOrFail($id);
        $event = new ApproveEvent($party);
        (new CreateDiscourseThreadForEvent())->handle($event);
    }
}
