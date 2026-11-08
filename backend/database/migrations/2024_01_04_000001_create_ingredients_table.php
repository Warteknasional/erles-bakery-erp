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
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('kategori')->default('Tepung');
            $table->decimal('stok', 10, 2)->default(0);
            $table->string('satuan', 20)->default('kg');
            $table->decimal('minimum_stok', 10, 2)->default(0);
            $table->timestamps();

            $table->index('kategori');
            $table->index('kode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
