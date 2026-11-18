<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Feature switches (App\Services\GroupFeatures): only the switches someone has set.
        Schema::table('groups', function (Blueprint $table) {
            $table->json('features')->nullable();
            // The group's custom item fields (App\Services\CustomFields), including removed ones.
            $table->json('custom_fields')->nullable();
        });

        Schema::table('networks', function (Blueprint $table) {
            $table->json('features')->nullable();
        });

        // Values of the group's custom item fields, keyed by field key.
        Schema::table('devices', function (Blueprint $table) {
            $table->json('custom_values')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['features', 'custom_fields']);
        });

        Schema::table('networks', function (Blueprint $table) {
            $table->dropColumn('features');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('custom_values');
        });
    }
};
