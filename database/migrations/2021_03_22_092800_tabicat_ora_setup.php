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
        if (! Schema::hasTable('devices_faults_tablets_ora_opinions')) {
            Schema::create('devices_faults_tablets_ora_opinions', function (Blueprint $table) {
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

            Schema::create('devices_faults_tablets_ora_adjudicated', function (Blueprint $table) {
                $table->increments('id');
                $table->string('id_ords', 16)->index();
                $table->unsignedInteger('fault_type_id')->index();
                $table->timestamps();
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
            });

            Schema::create('fault_types_tablets', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 255)->index();
                $table->text('description')->default('');
                $table->string('regex', 255);
                $table->timestamps();
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
            });
            DB::table('fault_types_tablets')->truncate();

            $fault_types = $this->_FaultTypes();

            foreach ($fault_types as $k => $v) {
                DB::table('fault_types_tablets')->insert([
                                                             'id' => $k,
                                                             'title' => $v['title'],
                                                             'description' => $v['description'],
                                                             'regex' => $v['regex'],
                                                             'created_at' => date('Y-m-d H:i:s'),
                                                             'updated_at' => date('Y-m-d H:i:s'),
                                                         ]);
            }
            Schema::create('devices_tabicat_ora', function (Blueprint $table) {
                $table->string('id_ords', 16)->primary();
                $table->string('data_provider', 32)->index();
                $table->string('country', 3);
                $table->string('partner_product_category', 128);
                $table->string('product_category', 16);
                $table->string('brand', 32)->default('');
                $table->string('year_of_manufacture', 4)->default('');
                $table->string('repair_status', 12)->default('');
                $table->string('event_date', 10);
                $table->string('problem', 1024);
                $table->string('translation', 1024);
                $table->string('language', 2);
                $table->unsignedInteger('fault_type_id')->index();
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
            });

            $data = base_path().'/database/data_updates/devices_tabicat_ora.php';
            if (file_exists($data)) {
                include $data;
                foreach ($devices_tabicat_ora as $k => $v) {
                    DB::table('devices_tabicat_ora')->insert([
                                                                 'id_ords' => $v['id_ords'],
                                                                 'data_provider' => $v['data_provider'],
                                                                 'country' => $v['country'],
                                                                 'partner_product_category' => $v['partner_product_category'],
                                                                 'product_category' => $v['product_category'],
                                                                 'brand' => $v['brand'],
                                                                 'year_of_manufacture' => $v['year_of_manufacture'],
                                                                 'repair_status' => $v['repair_status'],
                                                                 'event_date' => $v['event_date'],
                                                                 'problem' => $v['problem'],
                                                                 'translation' => $v['translation'],
                                                                 'language' => $v['language'],
                                                             ]);
                }
            }
        }
    }

    protected function _FaultTypes()
    {
        return [
            1 => [
                'title' => 'Power/battery',
                'description' => '',
                'regex' => 'batter|power|start|boot|switch|charg|plug|bricked',
            ],
            2 => [
                'title' => 'Screen',
                'description' => 'Fault involves screen assembly - glass, touch, LCD...',
                'regex' => 'sc(r)?een|display|touch|glass|lcd|reader|flicker|digiti|window',
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
                'regex' => 'headphone|jack|sound|audio|speaker|volume|socket',
            ],
            6 => [
                'title' => 'Speaker/amplifier',
                'description' => 'No sound, volume issues...',
                'regex' => 'sound|audio|speaker|volume',
            ],
            7 => [
                'title' => 'Charger',
                'description' => 'Problem with the charger not the phone itself',
                'regex' => 'charg|plug|socket',
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
                'regex' => 'card|sim|socket',
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
                'regex' => 'memory|card|slot|sim|ram|socket',
            ],
            18 => [
                'title' => 'External damage',
                'description' => 'Damage to the chassis/case',
                'regex' => 'case|dropped|rear|keyb|shell|frame|edge|housing',
            ],
            19 => [
                'title' => 'Internal damage',
                'description' => 'System board, internal component...',
                'regex' => 'board|dropped|component|contact|solder|keyb',
            ],
            20 => [
                'title' => 'Liquid damage',
                'description' => 'Spillage, submersion, humidity...',
                'regex' => 'water|humid|moist|liquid|coffee|drink|toilet',
            ],
            21 => [
                'title' => 'Performance',
                'description' => 'Slow, software crashes a lot...',
                'regex' => 'slow|crash',
            ],
            22 => [
                'title' => 'Software issue',
                'description' => 'Apps misbehaving',
                'regex' => 'software|app(s)?|game|version|mail|install',
            ],
            23 => [
                'title' => 'Access issue',
                'description' => 'Locked, forgot password...',
                'regex' => 'locked|password|account',
            ],
            24 => [
                'title' => 'Help/configuration',
                'description' => 'Advised, changed settings...',
                'regex' => 'advice|advise|help|config|setting|data|install|transfer|recover|backup|program|mail|support|network|internet|wifi|wi-fi',
            ],
            25 => [
                'title' => 'Unknown',
                'description' => 'Not enough info to determine the main fault',
                'regex' => '',
            ],
            26 => [
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
    public function down(): void
    {
        Schema::dropIfExists('devices_faults_tablets_ora_adjudicated');
        Schema::dropIfExists('devices_faults_tablets_ora_opinions');
        Schema::dropIfExists('fault_types_tablets');
        Schema::dropIfExists('devices_tabicat_ora');
    }
};
