<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Symfony\Component\VarDumper\Dumper\esc;

class ImportMRES extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:mres {input}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign email addresses to MRES groups.';

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
     */
    public function handle(): void
    {
        $input = $this->argument('input');

        $inputFile = fopen($input, 'r');

        // First three lines are headers.
        fgetcsv($inputFile);
        fgetcsv($inputFile);
        fgetcsv($inputFile);

        while (!feof($inputFile))
        {
            $fields = fgetcsv($inputFile);

            if ($fields) {
                $fields = array_map("utf8_encode", $fields);
                $groupname = trim($fields[0]);
                $email = trim($fields[1]);

                if ($email) {
                    // Find group with name
                    $group = \App\Group::where('name', 'like', $groupname)->first();

                    if ($group) {
                        $this->info("Set email for $groupname to $email");
                        $group->email = $email;
                        $group->save();
                    } else {
                        $this->error("No group found for $groupname");
                    }
                }
            }
        }
    }
}
