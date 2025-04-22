<?php

use Illuminate\Support\Facades\Schedule;
use Notification;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Carbon\Carbon;
use App\User;
use App\Party;
use App\Notifications\NotifyAdminNoDevices as Mail;
use App\Mail\NotifyAdminNoDevices;
use App\Helpers\Fixometer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::call(function () {
    $parties = Party::doesnthave('devices')
    ->where('event_start_utc', '>=', date('Y-m-d', strtotime(Carbon::now()->subDays(env('NO_DATA_ENTERED', 5)))))
      ->where('event_end_utc', '<', date('Y-m-d', strtotime(Carbon::now())))
        ->get();

    foreach ($parties as $party) {
        $all_admins = Fixometer::usersWhoHavePreference('admin-no-devices');
        Notification::send($all_admins, new Mail([
        'event_venue' => $party->venue,
        'event_url' => url('/party/edit/'.$party->idevents),
        ]));
    }
})->cron('0 0 */3 * *');

Schedule::command('sync:discourseusernames')
    ->daily()
    ->sendOutputTo(storage_path().'/logs/discourse_usernames.log')
    ->emailOutputTo(env('SEND_COMMAND_LOGS_TO'), 'tech@therestartproject.org');

Schedule::command('groups:country')->hourly();

Schedule::command('event:timezones')->hourly();

Schedule::command('wordpress:event:create_failed')->daily();
