<?php

namespace App\Console\Commands;

use App\Group;
use App\Party;
use FixometerHelper;
use Illuminate\Console\Command;

class PopulateUniqueCodeToEventsAndGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'populate:shareable_code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate Shareable Code column on events and groups';

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
        $groups = Group::whereNull('shareable_code')
                    ->get();

        foreach ($groups as $group) {
            $unique_shareable_code = FixometerHelper::generateUniqueShareableCode('App\Group', 'shareable_code');

            if (isset($unique_shareable_code) && ! empty($unique_shareable_code)) {
                $update = Group::where('idgroups', $group->idgroups)->update([
                    'shareable_code' => $unique_shareable_code,
                ]);
            }
        }
        $events = Party::whereNull('shareable_code')
                    ->get();

        foreach ($events as $event) {
            $unique_shareable_code = FixometerHelper::generateUniqueShareableCode('App\Party', 'shareable_code');

            if (isset($unique_shareable_code) && ! empty($unique_shareable_code)) {
                $update = Party::where('idevents', $event->idevents)->update([
                    'shareable_code' => $unique_shareable_code,
                ]);
            }
        }
    }
}
