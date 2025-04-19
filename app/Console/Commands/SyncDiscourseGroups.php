<?php

namespace App\Console\Commands;

use App\Services\DiscourseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncDiscourseGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discourse:syncgroups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sychronise Restarters and Discourse group memberships';

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
    public function handle(): void
    {
        $this->discourseService->syncGroups();
    }
}
