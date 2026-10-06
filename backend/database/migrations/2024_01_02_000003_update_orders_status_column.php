<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'status')) {
            // In PostgreSQL or SQLite, alter column type to string(50) so it accepts all extended status values
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE orders ALTER COLUMN status TYPE VARCHAR(50);');
                // Drop old enum check constraint if PostgreSQL generated one
                DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check;');
            } else {
                Schema::table('orders', function (Blueprint $table) {
                    $table->string('status', 50)->default('pending')->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep string for safety
    }
};
