<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // A blank weight means nobody weighed the item. It is stored as NULL, so it can be told apart from a
        // weight someone entered. Before this, a blank weight was stored as 0.
        DB::statement('ALTER TABLE devices MODIFY estimate DECIMAL(6,3) UNSIGNED NULL DEFAULT NULL');
        DB::statement('UPDATE devices SET estimate = NULL WHERE estimate = 0');
    }

    public function down(): void
    {
        DB::statement('UPDATE devices SET estimate = 0 WHERE estimate IS NULL');
        DB::statement('ALTER TABLE devices MODIFY estimate DECIMAL(6,3) UNSIGNED ZEROFILL NOT NULL DEFAULT 0');
    }
};
