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
        'devices_barriers',
        'laravel_sessions',
        'password_resets',
        'users_permissions',
        'users_preferences'
    ];

    public function up()
    {
        // Add auto-increment primary keys to tables which don't have them.
        //
        // Name the primary key to include the table name to avoid conflicts
        // within exising queries.
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                // Check if the primary key already exists
                $idname = 'id_' . $table->getTable();
                if (!Schema::hasColumn($table->getTable(), $idname)) {
                    $table->increments($idname)->first();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $idname = 'id_' . $table->getTable();

                if (Schema::hasColumn($table->getTable(), $idname)) {
                    $table->dropColumn($idname);
                }
            });
        }
    }
};
