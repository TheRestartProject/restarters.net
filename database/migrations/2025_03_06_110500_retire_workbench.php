<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    private $tables = [
        'devices_battcat_ora',
        'devices_dustup_ora',
        'devices_faults_adjudicated',
        'devices_faults_batteries_ora_adjudicated',
        'devices_faults_batteries_ora_opinions',
        'devices_faults_events',
        'devices_faults_mobiles_adjudicated',
        'devices_faults_mobiles_opinions',
        'devices_faults_mobiles_ora_adjudicated',
        'devices_faults_mobiles_ora_opinions',
        'devices_faults_opinions',
        'devices_faults_printers_ora_adjudicated',
        'devices_faults_printers_ora_opinions',
        'devices_faults_tablets_ora_adjudicated',
        'devices_faults_tablets_ora_opinions',
        'devices_faults_vacuums_ora_adjudicated',
        'devices_faults_vacuums_ora_opinions',
        'devices_misc_adjudicated',
        'devices_misc_opinions',
        'devices_mobifix_ora',
        'devices_printcat_ora',
        'devices_tabicat_ora',
        'fault_types',
        'fault_types_batteries',
        'fault_types_mobiles',
        'fault_types_printers',
        'fault_types_tablets',
        'fault_types_vacuums',
        'microtask_demographics',
        'microtask_surveys',
    ];

    public function up(): void
    {
        // For each table, rename to 'archived__' + table name.
        // Using two underscores triggers phpMyAdmin table grouping.
        foreach ($this->tables as $table) {
            try {
                Schema::rename($table, 'archived__' . $table);
            } catch (Exception $e) {
                // Ignore if table does not exist.  Some such tables exist on the live system but aren't in
                // migrations and therefore don't exist in CircleCI.
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For each table, rename back to original name
        foreach ($this->tables as $table) {
            try {
                Schema::rename('archived__' . $table, $table);
            } catch (Exception $e) {
                // Ignore if table does not exist
            }
        }
    }
};
