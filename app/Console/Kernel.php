<?php

namespace App\Console;

use App\Mail\NotifyAdminNoDevices;
use App\Notifications\NotifyAdminNoDevices as Mail;
use App\Party;
use App\User;
use Carbon\Carbon;
use App\Helpers\Fixometer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Notification;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $parties = Party::doesnthave('devices')
            ->where('event_date', '>=', date('Y-m-d', strtotime(Carbon::now()->subDays(env('NO_DATA_ENTERED', 5)))))
              ->where('event_date', '<=', date('Y-m-d', strtotime(Carbon::now())))
                ->get();

            foreach ($parties as $party) {
                $all_admins = Fixometer::usersWhoHavePreference('admin-no-devices');
                Notification::send($all_admins, new Mail([
                'event_venue' => $party->venue,
                'event_url' => url('/party/edit/'.$party->idevents),
                ]));
            }
        })->cron('0 0 */3 * *');

        $schedule->command('sync:discourseusernames')
            ->daily()
            ->sendOutputTo(storage_path().'/logs/discourse_usernames.log')
            ->emailOutputTo(env('SEND_COMMAND_LOGS_TO'), 'tech@therestartproject.org');

        $schedule->command('faultcat:sync')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
