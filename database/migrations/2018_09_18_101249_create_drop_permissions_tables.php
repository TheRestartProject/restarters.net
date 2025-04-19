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
        DB::table('users_preferences')->delete();
        DB::table('preferences')->delete();

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('purpose')->nullable();
            $table->string('slug', 100);
        });

        Schema::table('preferences', function ($table) {
            $table->string('purpose')->nullable()->change();
            $table->string('slug', 100);
        });

        Schema::table('users_preferences', function (Blueprint $table) {
            $table->dropColumn('id');
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
            $table->dropColumn('deleted_at');
        });

        Schema::create('users_permissions', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('permission_id');
            $table->foreign('permission_id')->references('idpermissions')->on('permissions');
        });

        DB::table('permissions')->insert([
          'permission' => 'Repair Directory',
          'purpose' => 'Show and hide the item in the main drop down menu',
          'slug' => 'repair-directory',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('users_permissions');
    }
};
