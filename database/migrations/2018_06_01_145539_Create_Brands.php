<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->increments('id');
            $table->string('brand_name');
            $table->timestamps();
        });
        Schema::table('devices', function (Blueprint $table) {
            $table->string('device_brand')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('device_brand');
        });
    }
};
