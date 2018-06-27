<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUsersBrandsRegistration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('users', function (Blueprint $table) {
          $table->timestamp('consent_past_data')->after('consent')->nullable();
          $table->timestamp('consent_gdpr')->after('consent_past_data')->nullable();
          $table->renameColumn('consent', 'consent_future_data');
          $table->string('username', 20);
      });

      Schema::table('brands', function (Blueprint $table) {
          $table->integer('category')->nullable();
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('users', function (Blueprint $table) {
          $table->dropColumn('consent_gdpr');
          $table->dropColumn('consent_past_data');
          $table->renameColumn('consent_future_data', 'consent');
          $table->dropColumn('username');
      });

      Schema::table('brands', function (Blueprint $table) {
          $table->dropColumn('category');
      });
    }
}
