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
            $table->string('logo', 255)->nullable();
        });

        $networks = \App\Models\Network::all();

        foreach ($networks as $network) {
            // Find the logo.
            $logo = \App\Models\Xref::where('reference', $network->id)
                ->where('reference_type', config('restarters.xref_types.networks'))
                ->where('object_type', 5)
            ->first();

            if (is_object($logo) && is_object($logo->image)) {
                // Save in the network column.
                $network->logo = $logo->image->path;
                $network->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('networks', function (Blueprint $table) {
            $table->dropColumn('logo');
        });
    }
};
