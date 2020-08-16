<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MobifixOraSetup extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {

        Schema::create('devices_faults_mobiles_ora_opinions', function (Blueprint $table) {
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

        Schema::create('devices_faults_mobiles_ora_adjudicated', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_ords', 16)->index();
            $table->unsignedInteger('fault_type_id')->index();
            $table->timestamps();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        Schema::create('fault_types_mobiles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255)->index();
            $table->text('description')->default('');
            $table->string('regex', 255);
            $table->timestamps();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
        DB::table('fault_types_mobiles')->truncate();

        $fault_types = $this->_FaultTypes();

        foreach ($fault_types as $k => $v) {
            DB::table('fault_types_mobiles')->insert([
                'id' => $k,
                'title' => $v['title'],
                'description' => $v['description'],
                'regex' => $v['regex'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        Schema::create('devices_mobifix_ora', function (Blueprint $table) {
            $table->string('id_ords', 16)->primary();
            $table->string('data_provider', 16)->index();
            $table->string('country', 3);
            $table->string('product_category', 8);
            $table->string('brand', 16)->default('');
            $table->string('model', 48)->default('');
            $table->string('year_of_manufacture', 4)->default('');
            $table->string('repair_status', 12)->default('');
            $table->string('group_identifier', 8);
            $table->string('date', 10);
            $table->string('problem', 1024);
            $table->string('translation', 1024);
            $table->string('language', 2);
            $table->unsignedInteger('fault_type_id')->index();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        $data = base_path() . '/database/data_updates/devices_mobifix_ora.php';
        if (file_exists($data)) {
            include($data);
            foreach ($mobifixora_data as $k => $v) {
                DB::table('devices_mobifix_ora')->insert([
                    'id_ords' => $v['id'],
                    'data_provider' => $v['data_provider'],
                    'country' => $v['country'],
                    'product_category' => $v['product_category'],
                    'brand' => $v['brand'],
                    'model' => $v['model'],
                    'year_of_manufacture' => $v['year_of_manufacture'],
                    'repair_status' => $v['repair_status'],
                    'group_identifier' => $v['group_identifier'],
                    'date' => $v['date'],
                    'problem' => $v['problem'],
                    'translation' => $v['translation'],
                    'language' => $v['language'],
                ]);
            }
        }
    }

    protected function _FaultTypes() {
        return [
            1 => [
                'title' => 'Power/battery',
                'description' => '',
                'regex' => 'battery|power|start|boot|switch|charg|plug'
            ],
            2 => [
                'title' => 'Screen',
                'description' => 'Fault involves screen assembly - glass, touch, LCD...',
                'regex' => 'sc(r)?een|display|touch|glass|lcd|reader|digiti',
            ],
            3 => [
                'title' => 'Stuck booting',
                'description' => 'Powers on but OS does not load/errors',
                'regex' => 'start|boot|memory|app(s)?| off| on|software|update|reset|slow|virus|bricked|ram',
            ],
            4 => [
                'title' => 'Camera',
                'description' => '',
                'regex' => 'camera|lens|picture|photo|video',
            ],
            5 => [
                'title' => 'Headphone jack',
                'description' => 'Broken, loose, dirty...',
                'regex' => 'headphone|jack|sound|audio|speaker|volume',
            ],
            6 => [
                'title' => 'Speaker/amplifier',
                'description' => 'No sound, volume issues...',
                'regex' => 'sound|audio|speaker|volume',
            ],
            7 => [
                'title' => 'Charger',
                'description' => 'Problem with the charger not the phone itself',
                'regex' => 'charg|plug',
            ],
            8 => [
                'title' => 'On/Off button',
                'description' => '',
                'regex' => ' off| on|button',
            ],
            9 => [
                'title' => 'Volume buttons',
                'description' => '',
                'regex' => 'volume|button|sound|audio|speaker',
            ],
            10 => [
                'title' => 'Other buttons',
                'description' => '',
                'regex' => 'button|switch',
            ],
            11 => [
                'title' => 'Software update',
                'description' => 'Problem after update, lack of updates...',
                'regex' => 'start|boot|memory|app(s)?|software|slow|update|reset|virus|bricked|ram',
            ],
            12 => [
                'title' => 'Storage problem',
                'description' => 'Run out of storage space, corrupted storage...',
                'regex' => 'card|sim|storage|space|full|app(s)?|software|slow|virus|bricked',
            ],
            13 => [
                'title' => 'USB/charging port',
                'description' => 'Broken, loose, dirty...',
                'regex' => 'cable|connector|port|usb|charg|plug',
            ],
            14 => [
                'title' => 'Sim card slot',
                'description' => '',
                'regex' => 'card|sim',
            ],
            15 => [
                'title' => 'Microphone',
                'description' => '',
                'regex' => 'mic',
            ],
            16 => [
                'title' => 'Bluetooth',
                'description' => '',
                'regex' => 'bluetooth',
            ],
            17 => [
                'title' => 'Memory card slot',
                'description' => '',
                'regex' => 'memory|card|slot|sim|ram',
            ],
            18 => [
                'title' => 'Unknown',
                'description' => 'Not enough info to determine the main fault',
                'regex' => '',
            ],
            19 => [
                'title' => 'Other',
                'description' => 'Main fault is known but there is no option for it',
                'regex' => '',
            ],
        ];
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('devices_faults_mobiles_ora_adjudicated');
        Schema::dropIfExists('devices_faults_mobiles_ora_opinions');
        Schema::dropIfExists('fault_types_mobiles');
        Schema::dropIfExists('devices_mobifix_ora');
    }

}
