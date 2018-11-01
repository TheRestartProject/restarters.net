<?php

namespace App\Console;

use App\Party;
use App\Mail\NotifyAdminNoDevices;
use App\Notifications\NotifyAdminNoDevices as Mail;
use App\User;
use Carbon\Carbon;
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
        // $schedule->call(function () {
        //   App\Http\Controllers\PartyController::emailHosts();
        // })->dailyAt('11:00');

        $schedule->call(function () {

          $parties = Party::doesnthave('devices')
            ->where('event_date', '>=', date("Y-m-d", strtotime(Carbon::now()->subDays(env('NO_DATA_ENTERED', 5)))))
              ->where('event_date', '<=', date("Y-m-d", strtotime(Carbon::now())))
                ->get();

          foreach ( $parties as $party ) {

            $all_admins = User::where('role', 2)->where('invites', 1)->get();
            Notification::send($all_admins, new Mail([
              'event_venue' => $party->venue,
              'event_url' => url('/party/edit/'.$party->idevents),
            ]));
            
          }

        })->timezone('Europe/London')->dailyAt('09:30');
        // replace '->timezone('Europe/London')->dailyAt('09:30');' with '->everyMinute(); for testing purposes'
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
