<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ItemType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('itemtypes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255)->index();
            $table->timestamps();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedInteger('itemtype_id')->nullable()->index();
            $table->foreign('itemtype_id')->references('id')->on('itemtypes');
        });

        $devices = \App\Device::all();

        foreach ($devices as $device) {
            $item_type = $device->item_type;
            error_log($item_type);

            if ($item_type) {
                $existing = \App\ItemType::where('name', $item_type)->first();

                if (!$existing) {
                    $existing = new \App\ItemType();
                    $existing->name = $item_type;
                    $existing->save();
                }

                error_log("Existing {$existing->id}");
                $device->itemtype_id = $existing->id;
                $device->save();
            }
        }

        // TODO drop item_type.
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign('devices_itemtype_id_foreign');
            $table->dropColumn('itemtype_id');
        });

        Schema::dropIfExists('itemtypes');

        // TODO Add item_type back in, populated from the itemtypes table
    }
}
