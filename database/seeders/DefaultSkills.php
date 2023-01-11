<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class DefaultSkills extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('skills')->truncate();

        $data = [
            ['skill_name' => 'Publicising events', 'category' => 1],
            ['skill_name' => 'Recruiting volunteers', 'category' => 1],
            ['skill_name' => 'Managing events', 'category' => 1],
            ['skill_name' => 'Finding venues', 'category' => 1],

            ['skill_name' => 'Software/OS', 'category' => 2],
            ['skill_name' => 'Changing a fuse', 'category' => 2],
            ['skill_name' => 'Using a multimeter', 'category' => 2],
            ['skill_name' => 'Laptop disassembly', 'category' => 2],
            ['skill_name' => 'Replacing PCB components', 'category' => 2],
            ['skill_name' => 'Headphones', 'category' => 2],
            ['skill_name' => 'Electronics safety', 'category' => 2],
            ['skill_name' => 'Replacing screens', 'category' => 2],
        ];

        DB::table('skills')->insert($data);
    }
}
