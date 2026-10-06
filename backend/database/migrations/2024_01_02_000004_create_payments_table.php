<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 0. Update users role column to allow 'staff'
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
                \Illuminate\Support\Facades\DB::statement('ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(50);');
                \Illuminate\Support\Facades\DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check;');
            } else {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('role', 50)->default('staff')->change();
                });
            }
        }

        // 1. Add payment tracking columns to orders table if missing
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'payment_status')) {
                    $table->string('payment_status', 30)->default('unpaid')->after('status')->index();
                }
                if (!Schema::hasColumn('orders', 'paid_amount')) {
                    $table->decimal('paid_amount', 14, 2)->default(0)->after('payment_status');
                }
            });
        }

        // 2. Create payments table
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('nominal', 14, 2);
                $table->string('metode', 50); // cash, transfer, qris, etc.
                $table->string('tipe', 50)->default('lunas'); // dp, lunas, cicilan, pelunasan
                $table->date('tanggal');
                $table->text('catatan')->nullable();
                $table->string('bukti_bayar')->nullable();
                $table->timestamps();

                $table->index('order_id');
                $table->index('tanggal');
            });
        }

        // 3. Link payments to finance_transactions
        if (Schema::hasTable('finance_transactions') && !Schema::hasColumn('finance_transactions', 'payment_id')) {
            Schema::table('finance_transactions', function (Blueprint $table) {
                $table->foreignId('payment_id')->nullable()->after('order_id')->constrained('payments')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('finance_transactions') && Schema::hasColumn('finance_transactions', 'payment_id')) {
            Schema::table('finance_transactions', function (Blueprint $table) {
                $table->dropForeign(['payment_id']);
                $table->dropColumn('payment_id');
            });
        }

        Schema::dropIfExists('payments');

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'paid_amount')) {
                    $table->dropColumn('paid_amount');
                }
                if (Schema::hasColumn('orders', 'payment_status')) {
                    $table->dropColumn('payment_status');
                }
            });
        }
    }
};
