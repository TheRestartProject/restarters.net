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
        Schema::table('networks', function (Blueprint $table) {
            $table->boolean('auto_approve_events')->default(false);
        });

        $rt = \App\Network::where('name', 'Repair Together')->first();

        if ($rt) {
            $rt->auto_approve_events = true;
            $rt->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('networks', function (Blueprint $table) {
            $table->dropColumn('auto_approve_events');
        });
    }
};
