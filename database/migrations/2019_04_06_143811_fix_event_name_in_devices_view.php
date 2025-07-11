<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_devices_list');
        DB::statement('CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `view_devices_list` AS select `devices`.`iddevices` AS `id`,`categories`.`name` AS `category_name`,`categories`.`idcategories` AS `idcategory`,`devices`.`brand` AS `brand`,`devices`.`model` AS `model`,`devices`.`problem` AS `problem`,`groups`.`idgroups` AS `idgroup`,`groups`.`name` AS `group_name`,COALESCE(`events`.`venue`,`events`.`location`) AS event_name, `events`.`location` AS `event_location`,`events`.`latitude` AS `event_latitude`,`events`.`longitude` AS `event_longitude`,unix_timestamp(`events`.`event_date`) AS `event_date`,`users`.`name` AS `restarter`,`devices`.`repair_status` AS `repair_status`,`devices`.`created_at` AS `sorter` from ((((`devices` join `categories` on((`categories`.`idcategories` = `devices`.`category`))) join `events` on((`events`.`idevents` = `devices`.`event`))) join `groups` on((`groups`.`idgroups` = `events`.`group`))) join `users` on((`users`.`id` = `devices`.`repaired_by`)));');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_devices_list');
        DB::statement('CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `view_devices_list` AS select `devices`.`iddevices` AS `id`,`categories`.`name` AS `category_name`,`categories`.`idcategories` AS `idcategory`,`devices`.`brand` AS `brand`,`devices`.`model` AS `model`,`devices`.`problem` AS `problem`,`groups`.`idgroups` AS `idgroup`,`groups`.`name` AS `group_name`,`events`.`location` AS `event_location`,`events`.`latitude` AS `event_latitude`,`events`.`longitude` AS `event_longitude`,unix_timestamp(`events`.`event_date`) AS `event_date`,`users`.`name` AS `restarter`,`devices`.`repair_status` AS `repair_status`,`devices`.`created_at` AS `sorter` from ((((`devices` join `categories` on((`categories`.`idcategories` = `devices`.`category`))) join `events` on((`events`.`idevents` = `devices`.`event`))) join `groups` on((`groups`.`idgroups` = `events`.`group`))) join `users` on((`users`.`id` = `devices`.`repaired_by`)));');
    }
};
