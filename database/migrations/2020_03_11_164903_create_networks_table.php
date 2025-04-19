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
        Schema::create('networks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255)->nullable(false);
            $table->text('description')->nullable();
            $table->string('website', 255)->nullable();
            $table->string('default_language', 8)->nullable()->default('en');
            $table->string('timezone', 64)->comment('TZ database name')->nullable()->default('Europe/London');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('networks');
    }
};
