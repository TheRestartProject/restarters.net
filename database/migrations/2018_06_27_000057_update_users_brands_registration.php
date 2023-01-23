<?php

use App\Skills;
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
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('consent_past_data')->nullable();
            $table->timestamp('consent_gdpr')->nullable();
            $table->timestamp('consent_future_data')->nullable();
            $table->dropColumn('consent');
            $table->string('username', 20);
            $table->dropColumn('lat');
            $table->dropColumn('lon');
            $table->decimal('latitude', 10, 8)->change();
            $table->decimal('longitude', 10, 8)->change();
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->integer('category')->after('skill_name')->nullable();
        });

        // $a = [1, 2];
      // $skills = Skills::all();
      // foreach( $skills as $skill ){
      //   Skills::find($skill->id)->update([
      //     'category' => $a[mt_rand(0, count($a) - 1)]
      //   ]);
      // }
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
            $table->dropColumn('consent_future_data');
            $table->timestamp('consent')->nullable();
            $table->dropColumn('username');
            $table->integer('lat')->nullable();
            $table->integer('lon')->nullable();
            $table->integer('latitude')->change();
            $table->integer('longitude')->change();
        });

        if (Schema::hasColumn('brands', 'category')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }

        if (Schema::hasColumn('skills', 'category')) {
            Schema::table('skills', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
