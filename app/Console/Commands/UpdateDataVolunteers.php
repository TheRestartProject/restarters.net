<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DiscourseService;
use App\User;
use App\UsersSkills;
use App\Skills;

use Illuminate\Support\Facades\DB;

class UpdateDataVolunteers extends Command
{
    private $discourseService;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:datavolunteers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'For those users with one of the data volunteering badges in Talk, give them the data volunteering skill in Restarters.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(DiscourseService $discourseService)
    {
        parent::__construct();
        $this->discourseService = $discourseService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $dataVolunteerSkill = Skills::where('skill_name', 'Data volunteering')->first();

        $this->info("\nProcessing data badgers...");
        $this->processDataBadgers($dataVolunteerSkill);

        $this->info("\nProcessing questers...");
        $this->processQuesters($dataVolunteerSkill);
    }

    public function processDataBadgers($dataVolunteerSkill)
    {
        $dataBadges = [];
        $dataBadges[] = ['name' => 'FaultCat', 'id' => 117];
        $dataBadges[] = ['name' => 'MiscCat', 'id' => 118];
        $dataBadges[] = ['name' => 'MobiFix', 'id' => 121];
        $dataBadges[] = ['name' => 'DataDelver', 'id' => 106];

        foreach ($dataBadges as $dataBadge) {
            $badgeId = $dataBadge['id'];
            $badgeName = $dataBadge['name'];

            $this->info("Processing {$badgeName} badge...");
            $badgers = $this->discourseService->getUserIdsByBadge($badgeId);

            foreach ($badgers as $badger) {
                $id = $badger['external_id'];
                $username = $badger['username'];

                $user = User::find($id);
                if (empty($user)) {
                    $this->error("Remote user '{$username}' not found locally with id #{$id}");
                    continue;
                }

                $this->assignSkillToUser($user, $dataVolunteerSkill);
            }
        }
    }

    public function processQuesters($dataVolunteerSkill)
    {
        $quests = [];
        $quests[] = ['name' => 'FaultCat', 'opinions_table' => 'devices_faults_opinions'];
        $quests[] = ['name' => 'MiscCat', 'opinions_table' => 'devices_misc_opinions'];
        $quests[] = ['name' => 'MobiFix', 'opinions_table' => 'devices_faults_mobiles_opinions'];
        $quests[] = ['name' => 'MobiFixOra', 'opinions_table' => 'devices_faults_mobiles_ora_opinions'];

        foreach ($quests as $quest) {
            $questName = $quest['name'];
            $questOpinionsTable = $quest['opinions_table'];

            $this->info("Processing {$questName} quest...");
            $userIds = DB::table($questOpinionsTable)
                   ->select('user_id')
                   ->where('user_id', '<>', 0)
                   ->groupBy('user_id')
                   ->get();

            foreach ($userIds as $userId) {
                $user = User::find($userId->user_id);
                $this->checkAndAssignSkillToUser($user, $dataVolunteerSkill);
            }
        }
    }

    public function checkAndAssignSkillToUser($user, $dataVolunteerSkill)
    {
        $this->info("Checking #{$user->id} ({$user->username})...");
        if ($user->hasSkill($dataVolunteerSkill)) {
            $this->warn("Already has data volunteering skill.");
        } else {
            $this->info("Attaching data volunteering skill");
            $user->assignSkill($dataVolunteerSkill);
        }
    }
}
