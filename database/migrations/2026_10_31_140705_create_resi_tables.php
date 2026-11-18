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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');                      // e.g. "Aruna Sportswear - Shopee"
            $table->string('brand');                     // "Aruna", "Moveda"
            $table->string('platform');                  // "Shopee", "TikTok Shop"
            $table->string('courier');                   // "SPX Express", "J&T Express"
            $table->string('sender_keywords')->nullable(); // e.g. "aruna", "bandana buff", "moveda"
            $table->timestamps();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('stodo_code')->unique()->nullable(); // e.g. "ARN-BDN-CAT", "ARN-REF-DFT"
            $table->string('sku')->nullable();                  // e.g. "Bandana", "Reflektive" (Shopee)
            $table->string('seller_sku')->nullable();           // e.g. "Bandana", "BandanaSoilBlack" (TikTok)
            $table->string('name');                             // e.g. "Bandana Bundling Paket Hemat"
            $table->string('variant')->nullable();              // e.g. "Cat Series", "Gray Rain"
            $table->string('brand')->default('Aruna');          // "Aruna", "Moveda", "General"
            $table->string('unit')->default('pcs');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('product_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_product_id')->constrained('master_products')->cascadeOnDelete();
            $table->string('platform')->nullable();      // "Shopee", "TikTok Shop", null for all
            $table->string('raw_sku')->nullable();       // e.g. "Bandana", "Reflektive"
            $table->string('raw_variant')->nullable();   // e.g. "Cat Series", "Default"
            $table->string('raw_product_name')->nullable();
            $table->timestamps();

            $table->index(['platform', 'raw_sku', 'raw_variant']);
        });

        Schema::create('upload_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique();
            $table->string('file_name');
            $table->integer('total_pages')->default(0);
            $table->integer('total_waybills')->default(0);
            $table->integer('total_qty')->default(0);
            $table->string('status')->default('completed'); // completed, unmapped_items
            $table->timestamps();
        });

        Schema::create('waybills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_batch_id')->constrained('upload_batches')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('platform');                  // "Shopee", "TikTok Shop"
            $table->string('courier');                   // "SPX Express", "J&T Express"
            $table->string('waybill_no')->index();       // No Resi
            $table->string('order_no')->nullable()->index();
            $table->string('sender')->nullable();
            $table->string('recipient')->nullable();
            $table->string('city')->nullable();
            $table->integer('page_number')->default(1);
            $table->integer('total_qty')->default(0);
            $table->timestamps();
        });

        Schema::create('waybill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waybill_id')->constrained('waybills')->cascadeOnDelete();
            $table->foreignId('master_product_id')->nullable()->constrained('master_products')->nullOnDelete();
            $table->string('raw_product_name');
            $table->string('raw_sku')->nullable();
            $table->string('raw_variant')->nullable();
            $table->integer('qty')->default(1);
            $table->boolean('is_mapped')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waybill_items');
        Schema::dropIfExists('waybills');
        Schema::dropIfExists('upload_batches');
        Schema::dropIfExists('product_mappings');
        Schema::dropIfExists('master_products');
        Schema::dropIfExists('stores');
    }
};
