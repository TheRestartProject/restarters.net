<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableCharsets extends Migration {

    private $charset = 'utf8mb4';
    private $collate = 'utf8mb4_unicode_ci';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {

        $tables = $this->_tableCharsets();
        foreach ($tables as $table) {
            if ($table['TABLE_COLLATION'] <> $collate) {
                $name = $table['TABLE_NAME'];
                Log::info("ALTER TABLE `$name` CONVERT TO CHARACTER SET $this->charset COLLATE $this->collate");
                DB::statement("ALTER TABLE `$name` CONVERT TO CHARACTER SET $this->charset COLLATE $this->collate");
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        $tables = $this->_tableCharsets();
        foreach ($tables as $table) {
            $collate = $table['TABLE_COLLATION'];
            $name = $table['TABLE_NAME'];
            if ($collate <> $this->collate) {
                if ($collate == 'latin1_swedish_ci') {
                    $charset = 'latin1';
                } else if ($collate == 'utf8_general_ci') {
                    $charset = 'utf8';
                }
                Log::info("ALTER TABLE `$name` CONVERT TO CHARACTER SET $charset COLLATE $collate");
                DB::statement("ALTER TABLE `$name` CONVERT TO CHARACTER SET $charset COLLATE $collate");
            }
        }
    }

    private function _fixData() {

        "UPDATE `groups` SET `name` = REPLACE(`name`, 'Ã©', 'é')";
        "UPDATE `devices` SET `brand` = REPLACE(`brand`, 'Ã©', 'é')";
        "UPDATE `devices` SET `model` = REPLACE(`model`, 'Ã©', 'é')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'Ã©', 'é')";

        "UPDATE `groups` SET `name` = REPLACE(`name`, 'Ã‰', 'É')";
        "UPDATE `devices` SET `brand` = REPLACE(`brand`, 'Ã‰', 'É')";
        "UPDATE `devices` SET `model` = REPLACE(`model`, 'Ã‰', 'É')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'Ã‰', 'É')";

        "UPDATE `groups` SET `name` = REPLACE(`name`, 'Ã¨', 'è')";
        "UPDATE `devices` SET `brand` = REPLACE(`brand`, 'Ã¨', 'è')";
        "UPDATE `devices` SET `model` = REPLACE(`model`, 'Ã¨', 'è')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'Ã¨', 'è')";

        "UPDATE `groups` SET `name` = REPLACE(`name`, 'Ã§', 'ç')";
        "UPDATE `devices` SET `brand` = REPLACE(`brand`, 'Ã§', 'ç')";
        "UPDATE `devices` SET `model` = REPLACE(`model`, 'Ã§', 'ç')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'Ã§', 'ç')";

        "UPDATE `groups` SET `name` = REPLACE(`name`, 'Ã¥', 'å')";
        "UPDATE `devices` SET `brand` = REPLACE(`brand`, 'Ã¥', 'å')";
        "UPDATE `devices` SET `model` = REPLACE(`model`, 'Ã¥', 'å')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'Ã¥', 'å')";

        "UPDATE `groups` SET `name` = REPLACE(`name`, 'Ã±', 'ñ')";
        "UPDATE `devices` SET `brand` = REPLACE(`brand`, 'Ã±', 'ñ')";
        "UPDATE `devices` SET `model` = REPLACE(`model`, 'Ã±', 'ñ')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'Ã±', 'ñ')";

        "UPDATE `groups` SET `name` = REPLACE(`name`, 'Ã³', 'ó')";
        "UPDATE `devices` SET `brand` = REPLACE(`brand`, 'Ã³', 'ó')";
        "UPDATE `devices` SET `model` = REPLACE(`model`, 'Ã³', 'ó')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'Ã³', 'ó')";

        "UPDATE `groups` SET `name` = REPLACE(`name`, 'Ã¼', 'ü')";
        "UPDATE `devices` SET `brand` = REPLACE(`brand`, 'Ã¼', 'ü')";
        "UPDATE `devices` SET `model` = REPLACE(`model`, 'Ã¼', 'ü')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'Ã¼', 'ü')";

        "UPDATE `groups` SET `name` = REPLACE(`name`, 'Ã¶', 'ö')";
        "UPDATE `devices` SET `brand` = REPLACE(`brand`, 'Ã¶', 'ö')";
        "UPDATE `devices` SET `model` = REPLACE(`model`, 'Ã¶', 'ö')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'Ã¶', 'ö')";

        "UPDATE `groups` SET `name` = REPLACE(`name`, 'Ã–', 'Ö')";
        "UPDATE `devices` SET `brand` = REPLACE(`brand`, 'Ã–', 'Ö')";
        "UPDATE `devices` SET `model` = REPLACE(`model`, 'Ã–', 'Ö')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'Ã–', 'Ö')";

        "UPDATE `groups` SET `name` = REPLACE(`name`, 'Ã¤', 'ä')";
        "UPDATE `devices` SET `brand` = REPLACE(`brand`, 'Ã¤', 'ä')";
        "UPDATE `devices` SET `model` = REPLACE(`model`, 'Ã¤', 'ä')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'Ã¤', 'ä')";

        "UPDATE `groups` SET `name` = REPLACE(`name`, 'ÃŸ', 'ß')";
        "UPDATE `devices` SET `brand` = REPLACE(`brand`, 'ÃŸ', 'ß')";
        "UPDATE `devices` SET `model` = REPLACE(`model`, 'ÃŸ', 'ß')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'ÃŸ', 'ß')";

        "UPDATE `groups` SET `name` = REPLACE(`name`, 'â€™', '\'')";
        "UPDATE `devices` SET `brand` = REPLACE(`brand`, 'â€™', '\'')";
        "UPDATE `devices` SET `model` = REPLACE(`model`, 'â€™', '\'')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'â€™', '\'')";

        "UPDATE `devices` SET `model` = REPLACE(`model`, 'â€ž', '\"')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'â€ž', '\"')";

        "UPDATE `devices` SET `model` = REPLACE(`model`, 'â€œ', '\"')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'â€œ', '\"')";

        "UPDATE `groups` SET `name` = REPLACE(`name`, 'Cafe', 'Café')";

        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'Â£', '£')";
        "UPDATE `devices` SET `problem` = REPLACE(`problem`, 'â‚¬', '€')";
        /*
          SELECT * FROM devices WHERE problem LIKE CONCAT("%", UNHEX('C2A3'), "%");

          SELECT
          o.`iddevices`
          ,CASE WHEN o.`brand` <> CONVERT(o.`brand` USING ASCII) THEN o.`brand` END AS in_brand
          FROM `devices` o
          WHERE o.`brand` <> CONVERT(o.`brand` USING ASCII)
          ORDER BY in_brand DESC;

          SELECT
          o.`iddevices`
          ,CASE WHEN o.`model` <> CONVERT(o.`model` USING ASCII) THEN o.`model` END AS in_model
          FROM `devices` o
          WHERE o.`model` <> CONVERT(o.`model` USING ASCII)
          ORDER BY in_model DESC;

          SELECT
          o.`iddevices`
          ,CASE WHEN o.`problem` <> CONVERT(o.`problem` USING ASCII) THEN o.`problem` END AS in_problem
          FROM `devices` o
          WHERE o.`problem` <> CONVERT(o.`problem` USING ASCII)
          ORDER BY in_problem DESC;

          SELECT
          o.`idgroups`
          ,CASE WHEN o.`name` <> CONVERT(o.`name` USING ASCII) THEN o.`name` END AS in_name
          FROM `groups` o
          WHERE o.`name` <> CONVERT(o.`name` USING ASCII)
          ORDER BY in_name DESC;

          SELECT
          o.`idgroups`
          ,CASE WHEN o.`location` <> CONVERT(o.`location` USING ASCII) THEN o.`location` END AS in_location
          FROM `groups` o
          WHERE o.`location` <> CONVERT(o.`location` USING ASCII)
          ORDER BY in_location DESC;

          SELECT
          o.`idgroups`
          ,CASE WHEN o.`freetext` <> CONVERT(o.`freetext` USING ASCII) THEN o.`freetext` END AS in_freetext
          FROM `groups` o
          WHERE o.`freetext` <> CONVERT(o.`freetext` USING ASCII)
          ORDER BY in_freetext DESC;

          SELECT
          o.`idevents`
          ,CASE WHEN o.`location` <> CONVERT(o.`location` USING ASCII) THEN o.`location` END AS in_location
          FROM `events` o
          WHERE o.`location` <> CONVERT(o.`location` USING ASCII)
          ORDER BY in_location DESC;

          SELECT
          o.`idevents`
          ,CASE WHEN o.`freetext` <> CONVERT(o.`freetext` USING ASCII) THEN o.`freetext` END AS in_freetext
          FROM `events` o
          WHERE o.`freetext` <> CONVERT(o.`freetext` USING ASCII)
          ORDER BY in_freetext DESC;

          SELECT
          o.`id`
          ,CASE WHEN o.`name` <> CONVERT(o.`name` USING ASCII) THEN o.`name` END AS in_name
          FROM `networks` o
          WHERE o.`name` <> CONVERT(o.`name` USING ASCII)
          ORDER BY in_name DESC;

          SELECT
          o.`id`
          ,CASE WHEN o.`description` <> CONVERT(o.`description` USING ASCII) THEN o.`description` END AS in_description
          FROM `networks` o
          WHERE o.`description` <> CONVERT(o.`description` USING ASCII)
          ORDER BY in_description DESC;

          SELECT
          o.`id`
          ,CASE WHEN o.`name` <> CONVERT(o.`name` USING ASCII) THEN o.`name` END AS in_name
          FROM `users` o
          WHERE o.`name` <> CONVERT(o.`name` USING ASCII)
          ORDER BY in_name DESC;


         * */
    }

    private function _tableCharsets() {
        return [
            ['TABLE_NAME' => 'users_preferences', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'jobs', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'brands', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'users_permissions', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'failed_jobs', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'invites', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'barriers', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'password_resets', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'audits', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'notifications', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'grouptags_groups', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'devices_urls', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'user_network', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'skills', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'networks', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'devices_barriers', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'migrations', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'group_tags', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'ltm_translations', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'group_network', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'preferences', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'users_skills', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'laravel_sessions', 'TABLE_COLLATION' => 'latin1_swedish_ci'],
            ['TABLE_NAME' => 'devices_faults_mobiles_ora_adjudicated', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'],
            ['TABLE_NAME' => 'devices_faults_mobiles_opinions', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'],
            ['TABLE_NAME' => 'devices_faults_mobiles_adjudicated', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'],
            ['TABLE_NAME' => 'devices_faults_events', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'],
            ['TABLE_NAME' => 'devices_faults_adjudicated', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'],
            ['TABLE_NAME' => 'devices_mobifix_ora', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'],
            ['TABLE_NAME' => 'devices_misc_opinions', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'],
            ['TABLE_NAME' => 'microtask_demographics', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'],
            ['TABLE_NAME' => 'devices_misc_adjudicated', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'],
            ['TABLE_NAME' => 'devices_faults_opinions', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'],
            ['TABLE_NAME' => 'fault_types_mobiles', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'],
            ['TABLE_NAME' => 'devices_faults_mobiles_ora_opinions', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'],
            ['TABLE_NAME' => 'fault_types', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'users_groups', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'events_users', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'images', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'users', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'events', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'groups', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'sessions_old', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'devices', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'xref', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'roles_permissions', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'clusters', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'roles', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'category_revisions', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'links', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'categories', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'permissions', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'brands_models', 'TABLE_COLLATION' => 'utf8_general_ci'],
            ['TABLE_NAME' => 'devices_models', 'TABLE_COLLATION' => 'utf8_unicode_ci']
        ];
    }

}
