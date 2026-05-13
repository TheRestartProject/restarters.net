<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Production already has this index added directly (2026-05-12).
        // This migration ensures dev/test environments and future deploys get it.
        Schema::table('notifications', function (Blueprint $table) {
            if (! $this->indexExists('notifications', 'idx_notifiable_created')) {
                $table->index(['notifiable_type', 'notifiable_id', 'created_at'], 'idx_notifiable_created');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifiable_created');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))->isNotEmpty();
    }
};
