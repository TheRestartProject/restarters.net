<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DevCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev 
                            {--host=0.0.0.0 : The host address to serve the application on}
                            {--port=80 : The port to serve the application on}
                            {--no-server : Do not start the development server}
                            {--no-queue : Do not start the queue worker}
                            {--no-logs : Do not start the log watcher}
                            {--no-mix : Do not start the Laravel Mix watcher}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the development environment with all necessary services';

    /**
     * Process color mappings
     */
    protected $colors = [
        'server' => 'blue',
        'queue' => 'magenta',
        'logs' => 'red',
        'mix' => 'yellow',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting development environment...');
        
        // Set timeout to unlimited
        set_time_limit(0);
        
        // Build the commands array based on options
        $commands = [];
        $names = [];
        
        if (!$this->option('no-server')) {
            $host = $this->option('host');
            $port = $this->option('port');
            $commands[] = "php artisan serve --host={$host} --port={$port}";
            $names[] = 'server';
        }
        
        if (!$this->option('no-queue')) {
            $commands[] = "php artisan queue:listen --tries=1";
            $names[] = 'queue';
        }
        
        if (!$this->option('no-logs')) {
            $commands[] = "php artisan pail --timeout=0";
            $names[] = 'logs';
        }
        
        if (!$this->option('no-mix')) {
            $commands[] = "npm run watch";
            $names[] = 'mix';
        }
        
        if (empty($commands)) {
            $this->error('No services selected to run. Exiting...');
            return 1;
        }
        
        // Build the concurrently command
        $colorOptions = array_map(function($name) {
            return $this->colors[$name];
        }, $names);
        
        $concurrentlyCmd = $this->buildConcurrentlyCommand($commands, $names, $colorOptions);
        
        // Run the concurrently command
        $this->info('Starting services with concurrently...');
        $process = Process::fromShellCommandline($concurrentlyCmd);
        $process->setTimeout(null);
        $process->setTty(true);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });
        
        return $process->getExitCode();
    }
    
    /**
     * Build the concurrently command string
     */
    protected function buildConcurrentlyCommand($commands, $names, $colors)
    {
        // Escape commands for shell
        $escapedCmds = array_map(function($cmd) {
            return escapeshellarg($cmd);
        }, $commands);
        
        // Build the names string
        $namesArg = '--names ' . escapeshellarg(implode(',', $names));
        
        // Build the colors string
        $colorsArg = '-c ' . escapeshellarg(implode(',', $colors));
        
        // Combine everything into the final command
        return 'npx concurrently ' . $colorsArg . ' ' . $namesArg . ' ' . implode(' ', $escapedCmds);
    }
}
