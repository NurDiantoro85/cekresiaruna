<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Fix master_products variants for Headband and Arm Sleeve
        DB::table('master_products')
            ->where(function ($q) {
                $q->where('name', 'like', '%Headband%')
                  ->orWhere('id', 9);
            })
            ->where('variant', 'Gray Rain')
            ->update([
                'variant' => 'Hdbn Gray Rain',
                'sku' => 'Headband',
            ]);

        DB::table('master_products')
            ->where(function ($q) {
                $q->where('name', 'like', '%Headband%')
                  ->orWhere('id', 8);
            })
            ->where('variant', 'Seaweed')
            ->update([
                'variant' => 'Hdbn Seaweed',
                'sku' => 'Headband',
            ]);

        DB::table('master_products')
            ->where('variant', 'ArmGrayRain')
            ->update(['variant' => 'Arm Gray Rain']);

        DB::table('master_products')
            ->where('variant', 'ArmSoilBlack')
            ->update(['variant' => 'Arm Soil Black']);

        DB::table('master_products')
            ->where('variant', 'SlayerBlueBrown')
            ->update(['variant' => 'Slayer Blue Brown']);

        // Find canonical MasterProduct for Hdbn Gray Rain
        $hdbnGrayRainProduct = DB::table('master_products')
            ->where('variant', 'Hdbn Gray Rain')
            ->first();

        if ($hdbnGrayRainProduct) {
            // Update product_mappings that point Hdbn Gray Rain to wrong product
            DB::table('product_mappings')
                ->where(function ($q) {
                    $q->where('raw_variant', 'Hdbn Gray Rain')
                      ->orWhere('raw_sku', 'Hdbn Gray Rain');
                })
                ->update(['master_product_id' => $hdbnGrayRainProduct->id]);

            // Update waybill_items that were incorrectly linked to Gray Rain
            DB::table('waybill_items')
                ->where(function ($q) {
                    $q->where('raw_variant', 'Hdbn Gray Rain')
                      ->orWhere('raw_sku', 'Hdbn Gray Rain')
                      ->orWhere(function ($sub) {
                          $sub->where('raw_product_name', 'like', '%headband%')
                              ->where('raw_variant', 'like', '%gray rain%');
                      });
                })
                ->update(['master_product_id' => $hdbnGrayRainProduct->id]);
        }

        // Find canonical MasterProduct for Hdbn Grey Waves
        $hdbnGreyWavesProduct = DB::table('master_products')
            ->where('variant', 'Hdbn Grey Waves')
            ->first();

        if ($hdbnGreyWavesProduct) {
            DB::table('product_mappings')
                ->where(function ($q) {
                    $q->where('raw_variant', 'Hdbn Grey Waves')
                      ->orWhere('raw_sku', 'Hdbn Grey Waves');
                })
                ->update(['master_product_id' => $hdbnGreyWavesProduct->id]);

            DB::table('waybill_items')
                ->where(function ($q) {
                    $q->where('raw_variant', 'Hdbn Grey Waves')
                      ->orWhere('raw_sku', 'Hdbn Grey Waves')
                      ->orWhere(function ($sub) {
                          $sub->where('raw_product_name', 'like', '%headband%')
                              ->where('raw_variant', 'like', '%grey waves%');
                      });
                })
                ->update(['master_product_id' => $hdbnGreyWavesProduct->id]);
        }

        // Find canonical MasterProduct for Hdbn Seaweed
        $hdbnSeaweedProduct = DB::table('master_products')
            ->where('variant', 'Hdbn Seaweed')
            ->first();

        if ($hdbnSeaweedProduct) {
            DB::table('product_mappings')
                ->where('raw_variant', 'Hdbn Seaweed')
                ->update(['master_product_id' => $hdbnSeaweedProduct->id]);

            DB::table('waybill_items')
                ->where(function ($q) {
                    $q->where('raw_variant', 'Hdbn Seaweed')
                      ->orWhere('raw_sku', 'Hdbn Seaweed');
                })
                ->update(['master_product_id' => $hdbnSeaweedProduct->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe to leave as is or revert variants if needed
    }
};
