<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Symfony\Component\VarDumper\Dumper\esc;

class AlertCreate extends Command
{
    /**
     * Example: php artisan alert:create 'Test alert' '<p>Testing</p>' 'today' 'next year' 'Click here' 'https://therestartproject.org'
     *
     * @var string
     */
    protected $signature = 'alert:create {title} {html}  {start} {end} {variant?} {ctatitle?} {ctalink?}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a system-wide alert';

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
    public function handle(): void
    {
        $title = trim($this->argument('title'));
        $html = trim($this->argument('html'));
        $ctatitle = trim($this->argument('ctatitle'));
        $ctalink = trim($this->argument('ctalink'));
        $start = trim($this->argument('start'));
        $end = trim($this->argument('end'));
        $variant = trim($this->argument('variant'));
        $variant = $variant ? $variant : 'secondary';

        $alert = new \App\Alert();
        $alert->title = $title;
        $alert->html = $html;
        $alert->variant = $variant;

        if ($ctatitle && $ctalink) {
            $alert->ctatitle = $ctatitle;
            $alert->ctalink = $ctalink;
        }

        // Parse $start as a date and set it in the alert.
        $start = \Carbon\Carbon::parse($start);
        $start->setTimezone('UTC');
        $end = \Carbon\Carbon::parse($end);
        $end->setTimezone('UTC');

        $alert->start = $start;
        $alert->end = $end;

        $alert->save();

        $this->info("Created alert " . $alert->id);
    }
}
