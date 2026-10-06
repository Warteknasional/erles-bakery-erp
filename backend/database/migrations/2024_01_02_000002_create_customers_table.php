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
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('phone', 30)->index();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->text('notes')->nullable();
                $table->integer('total_orders')->default(0);
                $table->decimal('total_spent', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'customer_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'customer_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn('customer_id');
            });
        }

        Schema::dropIfExists('customers');
    }
};
