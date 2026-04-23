<?php

namespace App\Console\Commands;

use App\Group;
use App\Helpers\Geocoder;
use App\Services\DiscourseService;
use App\User;
use Illuminate\Console\Command;

class DiscourseAnonymiseUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discourse:anonymise {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Anonymise a user in Discourse';

    private $discourseApiKey;
    private $discourseApiUser;
    private $discourseService;

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
     */
    public function handle(): int
    {
        $discourseApiKey = env('DISCOURSE_APIKEY');
        $discourseApiUser = env('DISCOURSE_APIUSER');

        if (empty($discourseApiKey)) {
            $this->error('DISCOURSE_APIKEY is not set');
            return 1;
        }

        if (empty($discourseApiUser)) {
            $this->error('DISCOURSE_APIUSER is not set');
            return 1;
        }

        $this->discourseApiKey = $discourseApiKey;
        $this->discourseApiUser = $discourseApiUser;

        $id = $this->argument('id');
        $user = User::findOrFail($id);

        $this->discourseService->anonymise($user);

        return 0;
    }
}
