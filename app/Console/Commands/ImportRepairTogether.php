<?php

namespace App\Console\Commands;

use App\Group;
use App\GroupTags;
use App\Helpers\FixometerHelper;

use Illuminate\Console\Command;
use League\Csv\Reader;

class ImportRepairTogether extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:repairtogether';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $this->importGroups();
        $this->importUsers();
    }


    public function importGroups()
    {
        $csv = Reader::createFromPath('./repairtogether-groups.csv', 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        $repairTogetherTag = GroupTags::find(12);
        $repairTogetherNetworkId = 3;

        foreach ($records as $index => $row) {
            $name = $row['Group Name - Nom du Repair Café'];
            $website = $row['Website'];
            $location = $row['Name of the place (optional)'].', '.$row['Street'];
            $area = $row['City'];
            $latitude = $row['Latitude'];
            $longitude = $row['Longitude'];
            $country = 'Belgium';
            $free_text = nl2br($row['Description']);
            $external_id = $row['Group ID'];
            // TODO: add in facebook?
            // TODO: which one should really map to area?  province?  city?

            $data = [
                'name' => $name,
                'website' => $website,
                'location' => $location,
                'area' => $area,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'country' => $country,
                'free_text' => $free_text,
                'shareable_code' => FixometerHelper::generateUniqueShareableCode('App\Group', 'shareable_code'),
                'network_id' => $repairTogetherNetworkId,
                'external_id' => $external_id,
            ];

            try {
                $this->info('Creating group: '.$name);
                $group = Group::create($data);
                $group->addTag($repairTogetherTag);
            } catch (\Exception $ex) {
                // Show message, but carry on with other groups.
                $this->error($ex->getMessage());
            }
        }
    }
}
