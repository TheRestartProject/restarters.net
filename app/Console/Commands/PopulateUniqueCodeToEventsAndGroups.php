<?php

namespace App\Console\Commands;

use App\Group;
use App\Party;
use App\Helpers\Fixometer;
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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $groups = Group::whereNull('shareable_code')
                    ->get();

        foreach ($groups as $group) {
            $unique_shareable_code = Fixometer::generateUniqueShareableCode(\App\Group::class, 'shareable_code');

            if (isset($unique_shareable_code) && ! empty($unique_shareable_code)) {
                Group::where('idgroups', $group->idgroups)->update([
                    'shareable_code' => $unique_shareable_code,
                ]);
            }
        }
        $events = Party::whereNull('shareable_code')
                    ->get();

        foreach ($events as $event) {
            $unique_shareable_code = Fixometer::generateUniqueShareableCode(\App\Party::class, 'shareable_code');

            if (isset($unique_shareable_code) && ! empty($unique_shareable_code)) {
                $update = Party::where('idevents', $event->idevents)->update([
                    'shareable_code' => $unique_shareable_code,
                ]);
            }
        }
    }
}
