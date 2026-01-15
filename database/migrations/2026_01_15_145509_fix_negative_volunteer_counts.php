<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix volunteer counts that have become negative or incorrect.
     *
     * This migration corrects a bug in EventsUsersObserver::deleted() where the volunteer
     * count was decremented for ALL deleted events_users records, not just confirmed ones.
     *
     * The bug was on line 89: $this->removed($event, $user, true, $eu->status == 1);
     * The removed() method only takes 3 parameters, so the 4th parameter was silently ignored,
     * causing $count to always be true and incorrectly decrementing for invited (non-confirmed) users.
     *
     * @return void
     */
    public function up()
    {
        // Fix all events where the stored volunteer count doesn't match the actual
        // count of confirmed volunteers (status = '1' or NULL).
        DB::statement("
            UPDATE events e
            SET e.volunteers = (
                SELECT COUNT(*)
                FROM events_users eu
                WHERE eu.event = e.idevents
                AND (eu.status = '1' OR eu.status IS NULL)
            )
            WHERE e.volunteers != (
                SELECT COUNT(*)
                FROM events_users eu2
                WHERE eu2.event = e.idevents
                AND (eu2.status = '1' OR eu2.status IS NULL)
            )
        ");
    }

    /**
     * This migration cannot be reversed as we don't know the original incorrect values.
     *
     * @return void
     */
    public function down()
    {
        // Cannot reverse - the incorrect counts are not recoverable and shouldn't be restored anyway.
    }
};
