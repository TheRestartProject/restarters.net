<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each network's reporting dashboard lives at its own address.
        Schema::table('networks', function (Blueprint $table) {
            $table->string('reporting_url', 2048)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('networks', function (Blueprint $table) {
            $table->dropColumn('reporting_url');
        });
    }
};
