<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DustupOraSetup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('devices_faults_vacuums_ora_opinions')) {
            Schema::create('devices_faults_vacuums_ora_opinions', function (Blueprint $table) {
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

            Schema::create('devices_faults_vacuums_ora_adjudicated', function (Blueprint $table) {
                $table->increments('id');
                $table->string('id_ords', 16)->index();
                $table->unsignedInteger('fault_type_id')->index();
                $table->timestamps();
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
            });

            Schema::create('fault_types_vacuums', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 255)->index();
                $table->text('description')->default('');
                $table->string('regex', 255);
                $table->timestamps();
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
            });
            DB::table('fault_types_vacuums')->truncate();

            $fault_types = $this->_FaultTypes();

            foreach ($fault_types as $k => $v) {
                DB::table('fault_types_vacuums')->insert([
                    'id' => $k,
                    'title' => $v['title'],
                    'description' => $v['description'],
                    'regex' => $v['regex'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            Schema::create('devices_dustup_ora', function (Blueprint $table) {
                $table->string('id_ords', 16)->primary();
                $table->string('data_provider', 32)->index();
                $table->string('country', 3);
                $table->string('partner_product_category', 128);
                $table->string('product_category', 16);
                $table->string('brand', 32)->default('');
                $table->string('year_of_manufacture', 4)->default('');
                $table->string('product_age', 8)->default('');
                $table->string('repair_status', 12)->default('');
                $table->string('event_date', 10);
                $table->string('problem', 1024);
                $table->string('googletrans', 1024);
                $table->string('language', 2);
                $table->unsignedInteger('fault_type_id')->index();
                $table->string('en', 1024);
                $table->string('de', 1024);
                $table->string('nl', 1024);
                $table->string('fr', 1024);
                $table->string('it', 1024);
                $table->string('es', 1024);
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
            });

            $data = base_path() . '/database/data_updates/devices_dustup_ora.php';
            if (file_exists($data)) {
                include $data;
                foreach ($devices_dustup_ora as $k => $v) {
                    DB::table('devices_dustup_ora')->insert([
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
                        'googletrans' => $v['googletrans'],
                        'language' => $v['language'],
                        'en' => $v['en'],
                        'de' => $v['de'],
                        'nl' => $v['nl'],
                        'it' => $v['it'],
                        'fr' => $v['fr'],
                        'es' => $v['es'],
                    ]);
                }
            }
        }
    }

    protected function _FaultTypes()
    {
        return [
            1 => [
                'title' => 'Accessories/attachments',
                'description' => '',
                'regex' => 'nozzle|attachment',
            ],
            2 => [
                'title' => 'Blockage',
                'description' => '',
                'regex' => 'suck|suction|dirt|clog|dust|block|jam|hair|fur|tangle|fluff',
            ],
            3 => [
                'title' => 'Brush',
                'description' => '',
                'regex' => 'brush|roller|bristle',
            ],
            4 => [
                'title' => 'Button/switch',
                'description' => '',
                'regex' => 'switch|button|trigger|lever',
            ],
            5 => [
                'title' => 'Cable/cord',
                'description' => '',
                'regex' => 'cord|cable|wire| wind| lead|retract|reel',
            ],
            6 => [
                'title' => 'Display/LED',
                'description' => '',
                'regex' => ' led |light',
            ],
            7 => [
                'title' => 'Dustbag/canister',
                'description' => '',
                'regex' => 'bag|container|canist',
            ],
            8 => [
                'title' => 'External damage',
                'description' => 'Damage to the chassis/case',
                'regex' => 'crack|case|body|handle|cover|housing|plastic|clip',
            ],
            9 => [
                'title' => 'Filter',
                'description' => '',
                'regex' => 'filter|hair|fur|fluff',
            ],
            10 => [
                'title' => 'Hose/tube/pipe',
                'description' => '',
                'regex' => 'hose|tube|pipe|flex',
            ],
            11 => [
                'title' => 'Internal damage',
                'description' => 'Problem with a circuit board or other internal component',
                'regex' => 'board|circuit|component|contact|bearing|capacitor|solder',
            ],
            12 => [
                'title' => 'Liquid damage',
                'description' => 'Water/fluid leaks or has caused damage',
                'regex' => 'water|leak|wet|pump',
            ],
            13 => [
                'title' => 'Motor',
                'description' => 'Motor, engine, drive belt...',
                'regex' => 'motor|engine|speed|belt|drive|spark',
            ],
            14 => [
                'title' => 'Overheating',
                'description' => 'Device is hot, emits burning smell...',
                'regex' => 'burn|heat|hot|warm|smoke|smell',
            ],
            15 => [
                'title' => 'Power/battery',
                'description' => '',
                'regex' => 'batter|power|start|switch|charg|plug|fuse|mains|adapter|electric',
            ],
            16 => [
                'title' => 'Wheels/rollers',
                'description' => 'Problem with external rotating elements',
                'regex' => ' roll|wheel|spin|sweep',
            ],
            25 => [
                'title' => 'Poor data',
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
    public function down()
    {
        Schema::dropIfExists('devices_faults_vacuums_ora_adjudicated');
        Schema::dropIfExists('devices_faults_vacuums_ora_opinions');
        Schema::dropIfExists('fault_types_vacuums');
        Schema::dropIfExists('devices_dustup_ora');
    }
}
