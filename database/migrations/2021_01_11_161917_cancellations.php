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
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('cancelled')->after('deleted_at')->default(false);
            $table->string('cancellation_reason')->after('cancelled')->nullable()->default(null);
            $table->boolean('no_data')->after('cancellation_reason')->default(false);
            $table->string('no_data_reason')->after('no_data')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('cancelled');
            $table->dropColumn('cancellation_reason');
            $table->dropColumn('no_data');
            $table->dropColumn('no_data_reason');
        });
    }
};
