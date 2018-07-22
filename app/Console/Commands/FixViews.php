<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;

class FixViews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:fixviews';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replace the hard-coded DEFINER that mysqldump includes in exports with the DB details from the .env file';

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
        if (env('APP_ENV') !== 'local') {
            $this->error('This command is only designed to be run during local development.');
            return;
        }

        $results = DB::select(DB::raw('
SELECT table_name as viewName, CONCAT("ALTER DEFINER=`'. env('DB_USERNAME') . '`@`' . env('DB_HOST') . '` VIEW `",table_name,"` AS ", view_definition,";") as alter_statement
FROM information_schema.views
WHERE table_schema="' . env('DB_DATABASE') . '"'));

        foreach ($results as $result) {
            DB::unprepared(DB::raw($result->alter_statement));
            $this->info('Fixed ' . $result->viewName);
        }
    }
}
