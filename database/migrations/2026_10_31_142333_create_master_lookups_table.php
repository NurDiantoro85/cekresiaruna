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
        // 1. Master SKU (Pilihan SKU Shopee / Standar)
        Schema::create('master_skus', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Master Seller SKU (Pilihan Seller SKU TikTok / Tokopedia)
        Schema::create('master_seller_skus', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 3. Master Variasi (Pilihan Varian: Cat Series, Gray Rain, dll)
        Schema::create('master_variants', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_variants');
        Schema::dropIfExists('master_seller_skus');
        Schema::dropIfExists('master_skus');
    }
};
