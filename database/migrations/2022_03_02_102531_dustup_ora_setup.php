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
                'regex' => '',
            ],
            2 => [
                'title' => 'Power/battery',
                'description' => '',
                'regex' => 'batter|power|start|switch|charg|plug',
            ],
            3 => [
                'title' => 'Brush',
                'description' => '',
                'regex' => '',
            ],
            4 => [
                'title' => 'Button/switch',
                'description' => '',
                'regex' => '',
            ],
            5 => [
                'title' => 'Cable/cord',
                'description' => '',
                'regex' => '',
            ],
            6 => [
                'title' => 'Blockage',
                'description' => '',
                'regex' => '',
            ],
            7 => [

                'title' => 'Display/LED',
                'description' => '',
                'regex' => '',
            ],
            8 => [
                'title' => 'Dustbag/canister',
                'description' => '',
                'regex' => '',
            ],
            9 => [
                'title' => 'External damage',
                'description' => 'Damage to the chassis/case',
                'regex' => 'case|dropped|rear|keyb|shell|frame|edge|housing',
            ],
            10 => [
                'title' => 'Filter',
                'description' => '',
                'regex' => '',
            ],
            11 => [
                'title' => 'Hose/tube/pipe',
                'description' => '',
                'regex' => '',
            ],
            12 => [
                'title' => 'Internal damage',
                'description' => 'System board, internal component...',
                'regex' => 'board|circuit|component|contact|solder',
            ],
            13 => [
                'title' => 'Motor',
                'description' => '',
                'regex' => '',
            ],
            14 => [
                'title' => 'Overheating',
                'description' => '',
                'regex' => '',
            ],
            15 => [
                'title' => 'Liquid damage',
                'description' => '',
                'regex' => '',
            ],
            16 => [
                'title' => 'Wheels/rollers',
                'description' => '',
                'regex' => '',
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
