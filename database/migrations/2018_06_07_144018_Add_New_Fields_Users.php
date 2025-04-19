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
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country')->nullable();
            $table->tinyInteger('newsletter')->default(0);
            $table->tinyInteger('invites')->default(0);
            $table->string('biography')->nullable();
            $table->timestamp('consent')->nullable();
            $table->integer('lat')->nullable();
            $table->integer('lon')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('country');
            $table->dropColumn('newsletter');
            $table->dropColumn('invites');
            $table->dropColumn('biography');
            $table->dropColumn('consent');
            $table->dropColumn('lat');
            $table->dropColumn('lon');
        });
    }
};
