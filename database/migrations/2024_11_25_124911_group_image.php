<?php

use App\Group;
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
        Schema::table('groups', function (Blueprint $table) {
            $table->string('image', 255)->nullable();
        });

        $groups = Group::all();

        foreach ($groups as $group) {
            $group_image = $group->groupImage;

            $group->image = (is_object($group_image) && is_object($group_image->image)) ?
                $group_image->image->path : null;
            $group->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
