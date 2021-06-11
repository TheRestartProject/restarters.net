<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SoftDeletePermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('permissions', function (Blueprint $table) {
          $table->softDeletes();
        });

        DB::table('permissions')->where('idpermissions', '<=', 4)->update([
          'deleted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
          $table->dropColumn('deleted_at');
        });
    }
}
