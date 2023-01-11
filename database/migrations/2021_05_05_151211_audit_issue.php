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
        Schema::table('audits', function (Blueprint $table) {
            $table->string('old_values', '4294967295')->change();
        });
        Schema::table('audits', function (Blueprint $table) {
            $table->string('new_values', '4294967295')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->string('old_values', '16777215')->change();
        });
        Schema::table('audits', function (Blueprint $table) {
            $table->string('new_values', '16777215')->change();
        });
    }
};
