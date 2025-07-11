<?php

namespace App\Console\Commands;

use App\Group;
use App\Helpers\Geocoder;
use App\Services\DiscourseService;
use Illuminate\Console\Command;

class DiscourseChangeSetting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discourse:setting {setting} {value}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change Discourse setting';

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

        $discourseApiKey = env('DISCOURSE_APIKEY');
        $discourseApiUser = env('DISCOURSE_APIUSER');

        if (is_null($discourseApiKey) || empty($discourseApiKey)) {
            $this->error('DISCOURSE_APIKEY is not set');
            exit();
        }

        if (is_null($discourseApiUser) || empty($discourseApiUser)) {
            $this->error('DISCOURSE_APIUSER is not set');
            exit();
        }

        $this->discourseApiKey = $discourseApiKey;
        $this->discourseApiUser = $discourseApiUser;
    }

    /**
     * Execute the console command.
     */
    public function handle(DiscourseService $discourseService): void
    {
        $setting = $this->argument('setting');
        $value = $this->argument('value');

        $this->discourseService->setSetting($setting, $value);
    }
}
