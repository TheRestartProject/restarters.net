<?php

use App\Cluster;
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
        // New cluster for non-powered categories.
        DB::table('clusters')->insert([
            'name' => 'Non-Powered Items',
        ]);

        Schema::table('categories', function (Blueprint $table) {
            // New boolean column ‘powered’ added.
            // ‘Powered’ column set to true for all existing categories.
            $table->boolean('powered')->after('name')->default(true);
        });

        // New non-powered categories added to categories table, ‘powered’ set to false, cluster set to ‘non-powered’.
        $nonPoweredClusterId = Cluster::where('name', 'Non-Powered Items')->first()->idclusters;
        DB::table('categories')->insert([
            'name' => 'Furniture',
            'revision' => 1,
            'cluster' => $nonPoweredClusterId,
            'powered' => false,
        ]);
        DB::table('categories')->insert([
            'name' => 'Bicycle',
            'revision' => 1,
            'cluster' => $nonPoweredClusterId,
            'powered' => false,
        ]);
        DB::table('categories')->insert([
            'name' => 'Clothing/textile',
            'revision' => 1,
            'cluster' => $nonPoweredClusterId,
            'powered' => false,
        ]);
        DB::table('categories')->insert([
            'name' => 'Misc',
            'revision' => 1,
            'cluster' => $nonPoweredClusterId,
            'powered' => false,
        ]);

        Schema::table('devices', function (Blueprint $table) {
            $table->string('item_type', 255)->after('estimate')->nullable();
            $table->text('notes')->after('problem')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('item_type');
            $table->dropColumn('notes');
        });

        // Removed non-powered categories.
        DB::table('categories')->where('powered', false)->delete();

        // Removed powered column.
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('powered');
        });

        // Remove cluster for non-powered categories.
        DB::table('clusters')->where('name', 'Non-Powered Items')->delete();
    }
};
