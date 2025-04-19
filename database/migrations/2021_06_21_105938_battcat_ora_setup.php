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
        Schema::create('devices_faults_batteries_ora_opinions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_ords', 16)->index();
            $table->unsignedInteger('fault_type_id')->index();
            $table->string('session_id', 191);
            $table->ipAddress('ip_address');
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        Schema::create('devices_faults_batteries_ora_adjudicated', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_ords', 16)->index();
            $table->unsignedInteger('fault_type_id')->index();
            $table->timestamps();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        Schema::create('fault_types_batteries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255)->index();
            $table->text('description')->default('');
            $table->string('repair_status', 255);
            $table->timestamps();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
        DB::table('fault_types_batteries')->truncate();

        $fault_types = $this->_FaultTypes();

        foreach ($fault_types['Repairable'] as $v) {
            DB::table('fault_types_batteries')->insert([
                'title' => $v,
                'repair_status' => 'Repairable',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        foreach ($fault_types['End of life'] as $v) {
            DB::table('fault_types_batteries')->insert([
                'title' => $v,
                'repair_status' => 'End of life',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        Schema::create('devices_battcat_ora', function (Blueprint $table) {
            $table->string('id_ords', 16)->primary();
            $table->string('data_provider', 32)->index();
            $table->string('country', 3);
            $table->string('partner_product_category', 128);
            $table->string('product_category', 64);
            $table->string('brand', 32)->default('');
            $table->string('year_of_manufacture', 4)->default('');
            $table->string('repair_status', 12)->default('')->index();
            $table->string('event_date', 10);
            $table->string('problem', 1024);
            $table->string('language', 2);
            $table->string('translation', 1024);
            $table->unsignedInteger('fault_type_id')->index();
            $table->string('fault_type', 255)->index();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        $data = base_path().'/database/data_updates/devices_battcat_ora.php';
        if (file_exists($data)) {
            include $data;
            foreach ($devices_battcat_ora as $k => $v) {
                DB::table('devices_battcat_ora')->insert([
                    'id_ords' => $v['id'],
                    'data_provider' => $v['data_provider'],
                    'country' => $v['country'],
                    'partner_product_category' => $v['partner_product_category'],
                    'product_category' => $v['product_category'],
                    'brand' => $v['brand'],
                    'year_of_manufacture' => $v['year_of_manufacture'],
                    'repair_status' => $v['repair_status'],
                    'event_date' => $v['event_date'],
                    'problem' => $v['problem'],
                    'language' => $v['language'],
                    'translation' => $v['translation'],
                    'fault_type_id' => $v['fault_type_id'],
                    'fault_type' => $v['fault_type'],
                ]);
            }
        }
    }

    protected function _FaultTypes()
    {
        return [
            'Repairable' => [
                'title' => 'Clean battery contacts',
                'Fix connectors or casing',
                'Fix the charging port',
                'Replace with new battery',
                'Replace the charger or charging cable',
                'Battery is not the main problem',
                'Other',
                'Poor data',
            ],
            'End of life' => [
                'Battery not readily available',
                'Built-in or soldered battery, cannot remove',
                'Charger not readily available',
                'Damaged while replacing battery',
                'Difficult to remove battery',
                'Irrepairable corrosion, leakage, bulging',
                'New battery too expensive',
                'Battery is not the main problem',
                'Other',
                'Poor data',
            ],
        ];
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('devices_faults_batteries_ora_adjudicated');
        Schema::dropIfExists('devices_faults_batteries_ora_opinions');
        Schema::dropIfExists('fault_types_batteries');
        Schema::dropIfExists('devices_battcat_ora');
    }
};
