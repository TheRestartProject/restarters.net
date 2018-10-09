<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Mail\NotifyAdminNoDevices;
use Carbon\Carbon;
use App\Party;
use App\User;
use Notification;
use App\Notifications\NotifyAdminNoDevices as Mail;

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

          $partys = Party::doesnthave('devices')
            ->where('event_date', '>=', date("Y-m-d", strtotime(Carbon::now()->subDays(env('NO_DATA_ENTERED')))))
              ->where('event_date', '<=', date("Y-m-d", strtotime(Carbon::now())))
                ->get();

          foreach ( $partys as $party ) {

            $all_admins = User::where('role', 2)->where('invites', 1)->get();

            foreach ($all_admins as $admin){
              $arr = [
                'event_venue' => $party->venue,
                'event_url' => url('/party/edit/'.$party->idevents),
              ];

              Notification::send($admin, new Mail($arr));
            }
          }

        })->timezone('Europe/London')->dailyAt('09:30');
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
