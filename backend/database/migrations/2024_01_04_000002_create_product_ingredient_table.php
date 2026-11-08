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
        Schema::create('product_ingredient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('qty_used', 10, 2)->default(0.10);
            $table->timestamps();

            $table->unique(['product_id', 'ingredient_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('ingredients_deducted_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('ingredients_deducted_at');
        });

        Schema::dropIfExists('product_ingredient');
    }
};

