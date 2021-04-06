<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PrintcatOraSetup extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {

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

        $data = base_path() . '/database/data_updates/devices_printcat_ora.php';
        if (file_exists($data)) {
            include($data);
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

    protected function _FaultTypes() {
        return [
            1 => [
              'title' => 'Card reader',
              'description' => '',
              'regex' => 'card|sim|socket',
              ],
            2 => [
              'title' => 'Cleaning',
              'description' => '',
              'regex' => 'dirt|clean|maintenance|clog|pollut',
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
              'regex' => 'case|shell|frame|hous|chass',
              ],
            6 => [
              'title' => 'Help/configuration',
              'description' => 'Advice, set up, tutoring...',
              'regex' => 'advi[cs]e|help|config|setting|install|program|support|internet|wi-?fi',
              ],
            7 => [
              'title' => 'Imaging unit / drum',
              'description' => '',
              'regex' => 'scan|drum',
              ],
            8 => [
              'title' => 'Ink cartdridge cover',
              'description' => '',
              'regex' => 'cover|cartridge',
              ],
            9 => [
              'title' => 'Ink cartridge',
              'description' => '',
              'regex' => 'ink|cartridge|leak',
              ],
            10 => [
              'title' => 'Internal damage',
              'description' => 'Problem with mechanisms such as gears',
              'regex' => 'noise|rattle|loud|gear|clank|contact|circuit|solder',
              ],
            11 => [
              'title' => 'Nozzles',
              'description' => 'Nozzles broken or blocked',
              'regex' => 'nozzle|block|clog',
              ],
            12 => [
              'title' => 'Paper feed',
              'description' => 'Won\'t feed paper ',
              'regex' => 'paper|feed|stuck|tray|transport|load',
              ],
            13 => [
              'title' => 'Paper output',
              'description' => 'Paper jam',
              'regex' => 'paper|jam|stuck|crease',
              ],
            14 => [
              'title' => 'Performance',
              'description' => 'Slow,
               blurry,
               wrong colours...',
              'regex' => 'slow|blur|fuz|crash|stripe|lines|streak|unclear|smear|loud|quality|colou?r|gr[ea]y',
              ],
            15 => [
              'title' => 'Power supply/connectors',
              'description' => '',
              'regex' => 'power|start|boot|switch|charg|plug|socket|current',
              ],
            16 => [
              'title' => 'Printhead',
              'description' => '',
              'regex' => 'head',
              ],
            17 => [
              'title' => 'Printing carriage',
              'description' => '',
              'regex' => 'carriage',
              ],
            18 => [
              'title' => 'Scanner',
              'description' => '',
              'regex' => 'scan',
              ],
            19 => [
              'title' => 'Software issue',
              'description' => 'Driver,
               app etc.',
              'regex' => 'software|app|install|driver|program|error|version',
              ],
            20 => [
              'title' => 'Software update',
              'description' => 'Problem after update, lack of updates...',
              'regex' => 'software|update',
              ],
            21 => [
              'title' => 'USB port/cable',
              'description' => 'Broken,
               loose,
               dirty...',
              'regex' => 'cable|connector|port|usb',
              ],
            22 => [
              'title' => 'Vendor lock-in',
              'description' => 'Features disabled',
              'regex' => 'subscription|lock',
              ],
            23 => [
              'title' => 'Waste toner box',
              'description' => '',
              'regex' => 'toner|waste',
              ],
            24 => [
              'title' => 'WiFi',
              'description' => 'Not connecting...',
              'regex' => 'wi-?fi|wireless|bluetooth',
              ],
            25 => [
              'title' => 'Other',
              'description' => 'Main fault is known but there is no option for it',
              'regex' => '',
              ],
            26 => [
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
    public function down() {
        Schema::dropIfExists('devices_faults_printers_ora_adjudicated');
        Schema::dropIfExists('devices_faults_printers_ora_opinions');
        Schema::dropIfExists('fault_types_printers');
        Schema::dropIfExists('devices_printcat_ora');
    }

}
