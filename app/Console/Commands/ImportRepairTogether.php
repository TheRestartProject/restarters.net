<?php

namespace App\Console\Commands;

use App\Group;
use App\GroupTags;
use App\Helpers\Fixometer;
use App\Network;
use App\Role;
use App\User;
use App\UserGroups;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
    protected $description = 'Imports groups and hosts from Repair Together\'s spreadsheet';

    protected $restartersNetwork;
    protected $repairTogetherNetwork;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $restartersNetworkId = 1;
        $repairTogetherNetworkId = 3;

        $this->restartersNetwork = Network::find($restartersNetworkId);
        if (is_null($this->restartersNetwork)) {
            throw new \Exception('Could not find Restarters network in the database.');
        }

        $this->repairTogetherNetwork = Network::find($repairTogetherNetworkId);
        if (is_null($this->repairTogetherNetwork)) {
            throw new \Exception('Could not find Repair Together network in the database.');
        }

        $this->assignExistingGroupsToRestartNetwork();
        $this->setupNetworkCoordinators();
        $this->importGroups();
        $this->importHosts();
    }

    public function assignExistingGroupsToRestartNetwork()
    {
        $groupIds = Group::all()->pluck('idgroups');
        foreach ($groupIds as $groupId) {
            DB::table('group_network')->insert([
                ['group_id' => $groupId, 'network_id' => $this->restartersNetwork->id],
            ]);
        }
    }

    public function setupNetworkCoordinators()
    {
        $coordinatorEmails = [
            'jonathan.vigne@repairtogether.be',
            'luc.deriez@repairtogether.be',
            'emmanuel@repairtogether.be',
            'florine.paquay@repairtogether.be',
        ];

        foreach ($coordinatorEmails as $coordinatorEmail) {
            $coordinator = User::where('email', $coordinatorEmail)->first();
            if (is_null($coordinator)) {
                $this->error("Couldn't find user with email: ".$coordinatorEmail);
                break;
            }
            $coordinator->role = Role::NETWORK_COORDINATOR;
            $coordinator->save();
            $this->repairTogetherNetwork->addCoordinator($coordinator);
        }
    }

    public function importGroups()
    {
        $csv = Reader::createFromPath('./repairtogether-groups.csv', 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        foreach ($records as $row) {
            $name = trim($row['Group Name - Nom du Repair Café']);
            $website = trim($row['Website']);
            $facebook = trim($row['Facebook URL']);
            $location = trim($row['Name of the place (optional)'].', '.$row['Street'].', '.$row['City']);
            $area = trim($row['Province']);
            $latitude = $row['Latitude'];
            $longitude = $row['Longitude'];
            $country = 'Belgium';
            $free_text = trim(nl2br($row['Description']));
            $external_id = $row['Group ID'];

            $data = [
                'name' => $name,
                'website' => $website,
                'facebook' => $facebook,
                'location' => $location,
                'area' => $area,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'country' => $country,
                'free_text' => $free_text,
                'shareable_code' => Fixometer::generateUniqueShareableCode(\App\Group::class, 'shareable_code'),
                //'network_id' => $repairTogetherNetworkId,
                'external_id' => $external_id,
            ];

            try {
                $this->info('Creating group: '.$name);
                $group = Group::create($data);
                $this->repairTogetherNetwork->addGroup($group);
            } catch (\Exception $ex) {
                // Show message, but carry on with other groups.
                $this->error($ex->getMessage());
            }
        }
    }

    public function importHosts()
    {
        $csv = Reader::createFromPath('./repairtogether-hosts.csv', 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        foreach ($records as $row) {
            $name = trim($row['Host name']);
            $email = trim($row['Host email']);
            $groupId = $row['Group ID'];
            $yearOfBirth = $row['Year of Birth'];
            $townOrCity = trim($row['Host Location']);
            $country = 'BE';
            // TODO: add in facebook?
            // TODO: which one should really map to area?  province?  city?

            $data = [
                'name' => $name,
                'role' => Role::HOST,
                'email' => $email,
                'location' => $townOrCity,
                'age' => $yearOfBirth,
                'language' => 'fr',
                'country' => $country,
                'repair_network' => $this->repairTogetherNetwork->id,
            ];

            try {
                if (User::where('email', $email)->exists()) {
                    $this->info('User '.$email.' already exists.  Not creating.');
                } else {
                    $this->info('Creating user: '.$name);
                    $user = User::create($data);
                    $user->generateAndSetUsername();
                }

                $this->info('Adding to group');
                $group = Group::where('external_id', $groupId)->first();
                UserGroups::create([
                    'user' => $user->id,
                    'group' => $group->idgroups,
                    'status' => 1,
                    'role' => Role::HOST,
                ]);
            } catch (\Exception $ex) {
                // Show message, but carry on with other groups.
                $this->error($ex->getMessage());
            }
        }
    }
}
