<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecordApproval extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->boolean('approved')->default(false);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->boolean('approved')->default(false);
        });

        DB::statement(DB::raw("UPDATE groups SET approved = wordpress_post_id IS NOT NULL"));
        DB::statement(DB::raw("UPDATE events SET approved = wordpress_post_id IS NOT NULL"));
        DB::statement(DB::raw("UPDATE groups SET wordpress_post_id = NULL WHERE wordpress_post_id = '99999'"));
        DB::statement(DB::raw("UPDATE events SET wordpress_post_id = NULL WHERE wordpress_post_id = '99999'"));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('approved');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('approved');
        });
    }
}
