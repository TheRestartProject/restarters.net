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
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('aggregate');
        });

        foreach ([
            'Small kitchen item',
            'Power tool',
            'Hair & Beauty item',
            'TV and gaming-related accessories',
            'Handheld entertainment device',
            'PC Accessory',
            'Toy',
            'Decorative or safety lights',
            'Musical instrument',
            'Hi-Fi separates',
            'Hi-Fi integrated',
            'Misc',
                 ] as $cat) {
            DB::statement("UPDATE categories SET aggregate = 1 WHERE name LIKE '$cat';");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('aggregate');
        });
    }
};
