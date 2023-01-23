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
    public function up()
    {
        if (! Schema::hasTable('devices_faults_printers_ora_opinions')) {
            Schema::create('devices_faults_printers_ora_opinions', function (Blueprint $table) {
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

            Schema::create('devices_faults_printers_ora_adjudicated', function (Blueprint $table) {
                $table->increments('id');
                $table->string('id_ords', 16)->index();
                $table->unsignedInteger('fault_type_id')->index();
                $table->timestamps();
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
            });

            Schema::create('fault_types_printers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 255)->index();
                $table->text('description')->default('');
                $table->string('regex', 255);
                $table->timestamps();
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
            });
            DB::table('fault_types_printers')->truncate();

            $fault_types = $this->_FaultTypes();

            foreach ($fault_types as $k => $v) {
                DB::table('fault_types_printers')->insert([
                                                              'id' => $k,
                                                              'title' => $v['title'],
                                                              'description' => $v['description'],
                                                              'regex' => $v['regex'],
                                                              'created_at' => date('Y-m-d H:i:s'),
                                                              'updated_at' => date('Y-m-d H:i:s'),
                                                          ]);
            }
            Schema::create('devices_printcat_ora', function (Blueprint $table) {
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

            $data = base_path().'/database/data_updates/devices_printcat_ora.php';
            if (file_exists($data)) {
                include $data;
                foreach ($devices_printcat_ora as $k => $v) {
                    DB::table('devices_printcat_ora')->insert([
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
                'title' => 'Card reader',
                'description' => '',
                'regex' => 'card|sim|socket',
            ],
            2 => [
                'title' => 'Configuration',
                'description' => 'Advice, set up, tutoring...',
                'regex' => 'advi[cs]e|help|config|setting|install|program|support|internet|wi-?fi',
            ],
            3 => [
                'title' => 'Control buttons',
                'description' => '',
                'regex' => 'button|switch',
            ],
            4 => [
                'title' => 'Display panel',
                'description' => 'LCD/LED screen problem',
                'regex' => 'screen|display|glass|lcd',
            ],
            5 => [
                'title' => 'External damage',
                'description' => 'Damage to the housing',
                'regex' => 'case|shell|frame|hous|chass| cover',
            ],
            6 => [
                'title' => 'Imaging unit/drum',
                'description' => '',
                'regex' => 'scan|drum',
            ],
            7 => [
                'title' => 'Ink cartridge',
                'description' => '',
                'regex' => 'ink|cartridge|leak',
            ],
            8 => [
                'title' => 'Internal damage',
                'description' => 'Problem with mechanisms such as gears',
                'regex' => 'noise|rattle|loud|gear|clank|contact|circuit|solder',
            ],
            9 => [
                'title' => 'Paper feed',
                'description' => "Won't feed paper ",
                'regex' => 'paper|feed|stuck|tray|carriage|transport|load',
            ],
            10 => [
                'title' => 'Paper output',
                'description' => 'Paper jammed, creased, skewed...',
                'regex' => 'paper|jam|stuck|output|crease',
            ],
            11 => [
                'title' => 'Power supply/connectors',
                'description' => '',
                'regex' => 'power|start|boot|switch|charg|plug|cable|socket|current',
            ],
            12 => [
                'title' => 'Print quality',
                'description' => 'Blurry, stripes, faded...',
                'regex' => 'blur|fuz|stripe|lines|streak|unclear|smear|quality|colou?r|gr[ea]y',
            ],
            13 => [
                'title' => 'Printhead cleaning',
                'description' => '',
                'regex' => 'dirt|clean|nozzle|block|clog|maintenance|pollut',
            ],
            14 => [
                'title' => 'Printhead failure',
                'description' => '',
                'regex' => 'head',
            ],
            15 => [
                'title' => 'Scanner',
                'description' => '',
                'regex' => 'scan',
            ],
            16 => [
                'title' => 'Software issue/update',
                'description' => 'Driver, app, update etc.',
                'regex' => 'software|crash|app|install|update|driver|program|error|version',
            ],
            17 => [
                'title' => 'Toner',
                'description' => '',
                'regex' => 'toner|laser',
            ],
            18 => [
                'title' => 'USB port/cable',
                'description' => 'Broken, loose, dirty...',
                'regex' => 'cable|connector|port|usb',
            ],
            19 => [
                'title' => 'Vendor lock-in',
                'description' => 'Features disabled',
                'regex' => 'lock|subscription|vendor|manufact',
            ],
            20 => [
                'title' => 'Waste toner/ink box',
                'description' => '',
                'regex' => 'toner|waste',
            ],
            21 => [
                'title' => 'WiFi',
                'description' => 'Not connecting...',
                'regex' => 'wi-?fi|wireless|bluetooth',
            ],
            22 => [
                'title' => 'Other',
                'description' => 'Main fault is known but there is no option for it',
                'regex' => '',
            ],
            23 => [
                'title' => 'Unknown',
                'description' => 'Not enough info to determine the main fault',
                'regex' => '',
            ],
        ];
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('devices_faults_printers_ora_adjudicated');
        Schema::dropIfExists('devices_faults_printers_ora_opinions');
        Schema::dropIfExists('fault_types_printers');
        Schema::dropIfExists('devices_printcat_ora');
    }
};
