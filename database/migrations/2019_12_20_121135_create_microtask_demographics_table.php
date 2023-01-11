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
        Schema::create('microtask_demographics', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            // 191 seems like an odd length, but using the same length as the id column in laravel_sessions table.
            $table->string('session_id', 191);
            $table->ipAddress('ip_address');
            $table->string('age', 8);
            $table->string('country', 8);
            $table->string('task', 64);

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('microtask_demographics');
    }
};
