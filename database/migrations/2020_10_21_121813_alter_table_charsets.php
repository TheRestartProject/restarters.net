<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private $charset = 'utf8mb4';
    private $collate = 'utf8mb4_unicode_ci';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Log::info('START MIGRATE AlterTableCharsets');

        $this->_report('DAT21_', '_a');

        $this->_alterTables();

        $this->_fixData();

        $this->_report('DAT21_', '_b');

        Log::info('== SEE '.storage_path().'/logs/DAT21*.log FOR RESULTS ===');
        Log::info('END MIGRATE AlterTableCharsets');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $tables = $this->_tableCharsets();
        foreach ($tables as $table) {
            $collate = $table['TABLE_COLLATION'];
            $name = $table['TABLE_NAME'];
            if ($collate != $this->collate) {
                if ($collate == 'latin1_swedish_ci') {
                    $charset = 'latin1';
                } elseif ($collate == 'utf8_general_ci') {
                    $charset = 'utf8';
                }
                DB::statement("ALTER TABLE `$name` CONVERT TO CHARACTER SET $charset COLLATE $collate");
            }
        }
    }

    private function _fixData()
    {
        $tables = $this->_dataTables();

        // This converts all characters to utf8.
        $format = 'UPDATE `%1$s` SET `%2$s`=convert(cast(convert(`%2$s` using latin1) as binary) using utf8)';

        foreach ($tables as $table => $fields) {
            foreach ($fields['fields'] as $field) {
                $qry = sprintf($format, $table, $field);
                DB::statement($qry);
            }
        }
    }

    private function _alterTables()
    {
        try {
            $tables = $this->_tableCharsets();
            foreach ($tables as $table) {
                if ($table['TABLE_COLLATION'] != $this->collate) {
                    $name = $table['TABLE_NAME'];
                    Log::info("ALTER TABLE `$name` CONVERT TO CHARACTER SET $this->charset COLLATE $this->collate");
                    DB::statement("ALTER TABLE `$name` CONVERT TO CHARACTER SET $this->charset COLLATE $this->collate");
                }
            }
        } catch (\Illuminate\Database\QueryException $e) {
            Log::info($e->getMessage());
        }
    }

    private function _dataTables()
    {
        return [
            'groups' => [
                'key' => 'idgroups',
                'fields' => [
                    'name',
                    'location',
                    'free_text',
                    'area',
                ],
            ],
            'devices' => [
                'key' => 'iddevices',
                'fields' => [
                    'brand',
                    'model',
                    'problem',
                    'notes',
                    'item_type',
                ],
            ],
            'events' => [
                'key' => 'idevents',
                'fields' => [
                    'location',
                    'venue',
                    'free_text',
                ],
            ],
            'networks' => [
                'key' => 'id',
                'fields' => [
                    'name',
                    'description',
                    'shortname',
                ],
            ],
            'users' => [
                'key' => 'id',
                'fields' => [
                    'name',
                    'username',
                    'location',
                    'mediawiki',
                ],
            ],
        ];
    }

    /**
     * Report char issues.
     *
     * @return void
     */
    private function _report($prefix = '', $suffix = ''): void
    {
        $tables = $this->_dataTables();
        $format = 'SELECT
 o.`%3$s` AS `id`,
 o.`%2$s` AS `val`
 FROM `%1$s` o
 WHERE o.`%2$s` <> CONVERT(o.`%2$s` USING ASCII)
 ORDER BY o.`%3$s` asc';
        foreach ($tables as $table => $fields) {
            $key = $fields['key'];
            foreach ($fields['fields'] as $field) {
                $qry = sprintf($format, $table, $field, $key);
                $result = DB::select(DB::raw($qry));
                $log = [];
                foreach ($result as $v) {
                    $log[$v->id] = $v->val;
                }
                $filename = $prefix.$table.'_'.$field.$suffix;
                file_put_contents(storage_path()."/logs/$filename.log", print_r($log, 1));
            }
        }
    }

    private function _tableCharsets()
    {
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
        /** unused tables / no migration
                  ['TABLE_NAME' => 'fault_types', 'TABLE_COLLATION' => 'utf8_general_ci'],
                  ['TABLE_NAME' => 'brands_models', 'TABLE_COLLATION' => 'utf8_general_ci'],
                  ['TABLE_NAME' => 'devices_models', 'TABLE_COLLATION' => 'utf8_unicode_ci']
         * */
        ];
    }
};
