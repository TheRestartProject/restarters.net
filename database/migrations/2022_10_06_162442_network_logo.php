<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NetworkLogo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('networks', function (Blueprint $table) {
            $table->string('logo', 255)->nullable();
        });

        $networks = \App\Network::all();

        foreach ($networks as $network) {
            $logo = \App\Xref::where('reference', $network->id)
                ->where('reference_type', config('restarters.xref_types.networks'))
                ->where('object_type', 5)
            ->first();

            if (is_object($logo) && is_object($logo->image)) {
                $network->logo = $logo->image->path;
                error_log("{$network->id} logo {$network->logo}");
                $network->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('networks', function (Blueprint $table) {
            $table->dropColumn('logo');
        });
    }
}
