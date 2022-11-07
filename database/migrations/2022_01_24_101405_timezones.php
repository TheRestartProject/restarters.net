<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Party;

class Timezones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        # Timezones for groups and events.
        Schema::table('groups', function (Blueprint $table) {
//            $table->string('timezone', 64)->comment('TZ database name')->nullable()->default(null);
        });

        # The events table changes so that we have timestamp fields for start/end which are defined to be in UTC.
        Schema::table('events', function (Blueprint $table) {
            $table->datetime('event_start_utc')->comment('Timestamp of event start in UTC')->nullable()->default(null)->index()->after('group');
            $table->datetime('event_end_utc')->comment('Timestamp of event end in UTC')->nullable()->default(null)->index()->after('event_start_utc');
            $table->string('timezone', 64)->comment('TZ database name')->nullable()->default(null)->after('event_end_utc');
            $table->date('event_date_old')->comment('Old data before RES-1624')->nullable()->default(null);
            $table->time('start_old')->comment('Old data before RES-1624')->nullable()->default(null);
            $table->time('end_old')->comment('Old data before RES-1624')->nullable()->default(null);
        });

        # Back up the existing data.
        DB::statement(DB::raw('UPDATE events SET event_date_old = event_date'));
        DB::statement(DB::raw('UPDATE events SET start_old = start'));
        DB::statement(DB::raw('UPDATE events SET end_old = end'));

        # Set up the new timestamps.  Currently event_start/time/end are all implicitly localised to the timezone
        # of the group.
        $events = Party::withTrashed()->get();

        foreach ($events as $event) {
            $tz = $event->timezone;

            # Convert the start/end to UTC.
            $atts = $event->getAttributes();
            $startCarbon = Carbon::parse($atts['event_date'] . ' ' . $atts['start'], $tz);
            $startCarbon->setTimezone('UTC');
            $event_start_utc = $startCarbon->toIso8601String();
            $endCarbon = Carbon::parse($atts['event_date'] . ' ' . $atts['end'], $tz);
            $endCarbon->setTimezone('UTC');
            $event_end_utc = $endCarbon->toIso8601String();

            error_log("Event {$event->idevents} {$atts['event_date']} {$atts['start']}-{$atts['end']} => $event_start_utc - $event_end_utc");

            DB::statement(DB::raw("UPDATE events SET timezone = '$tz', event_start_utc = '$event_start_utc', event_end_utc = '$event_end_utc' WHERE idevents = {$event->idevents}"));
        }

        # Set up virtual generated columns which replicate event_date/start/end but generated from the new timestamps.
        # This means that any access to these fields will work.  We use mutators in Party to handle modifications.
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('event_date');
            $table->dropColumn('start');
            $table->dropColumn('end');
        });

        Schema::table('events', function (Blueprint $table) {
//            $table->date('event_date')->virtualAs("DATE(CONVERT_TZ(event_start_utc, 'GMT', timezone))");
//            $table->time('start')->virtualAs("TIME(CONVERT_TZ(event_start_utc, 'GMT', timezone))");
//            $table->time('end')->virtualAs("TIME(CONVERT_TZ(event_end_utc, 'GMT', timezone))");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        # Restore the event_date/state/end fields.
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('event_date');
            $table->dropColumn('start');
            $table->dropColumn('end');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->date('event_date');
            $table->time('start');
            $table->time('end');
        });

        DB::statement(DB::raw('UPDATE events SET event_date= event_date_old'));
        DB::statement(DB::raw('UPDATE events SET start = start_old'));
        DB::statement(DB::raw('UPDATE events SET start = end_old'));

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('timezone');
            $table->dropColumn('event_start_utc');
            $table->dropColumn('event_end_utc');
            $table->dropColumn('event_date_old');
            $table->dropColumn('start_old');
            $table->dropColumn('end_old');
        });
    }
}
