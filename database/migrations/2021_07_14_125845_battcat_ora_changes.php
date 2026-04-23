<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('fault_types_batteries')->where('id', '=', 14)->update([
            'title' => 'Unrepairable corrosion or leakage',
        ]);

        $fault_types = $this->_FaultTypes();

        DB::table('fault_types_batteries')->truncate();

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
    }

    protected function _FaultTypes()
    {
        return [
            'Repairable' => [
                'Battery is not the main problem',
                'Clean battery contacts',
                'Fix connectors or casing',
                'Fix the charging port',
                'Replace the charger or charging cable',
                'Replace with new battery',
                'Other',
                'Poor data',
            ],
            'End of life' => [
                'Battery is not the main problem',
                'Battery not readily available',
                'Built-in or soldered battery, cannot remove',
                'Charger not readily available',
                'Damaged while replacing battery',
                'Difficult to remove battery',
                'New battery too expensive',
                'Unrepairable corrosion or leakage',
                'Other',
                'Poor data',
            ],
        ];
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
