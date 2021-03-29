<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DiscourseService;
use App\User;
use App\UsersSkills;
use App\Skills;

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
        $this->info("Processing FaultCat Analysts...");
        $faultcatters = $this->discourseService->getUserIdsByBadge(117);
        $dataVolunteerSkill = Skills::where('skill_name', 'Data volunteering')->first();

        foreach ($faultcatters as $discourseUser) {
            $id = $discourseUser['external_id'];
            $username = $discourseUser['username'];

            $user = User::find($id);

            if (empty($user)) {
                $this->error("Remote user '{$username}' not found locally with id #{$id}");
                continue;
            }

            $this->info("Updating user #{$user->id} ({$user->username})...");
            if (!$user->skills->contains($dataVolunteerSkill->id)) {
                $user->skills()->attach($dataVolunteerSkill->id);
            }
        }
    }
}
