<?php

namespace App\Services;

use App\Models\MasterProduct;
use App\Models\ProductMapping;
use App\Models\Store;
use App\Models\UploadBatch;
use App\Models\Waybill;
use App\Models\WaybillItem;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class ResiParserService
{
    /**
     * Process an uploaded PDF file and save into database.
     */
    public function processPdf(string $pdfPath, string $originalFileName, ?string $targetPlatform = null): UploadBatch
    {
        $pythonScript = base_path('app/Services/Parser/merge_pdfs.py'); // Note: parser is parse_waybills.py
        $pythonScript = base_path('app/Services/Parser/parse_waybills.py');

        // Gunakan Python 3.15 terpasang secara eksplisit jika ada
        $python = 'python';
        $knownPython = 'C:\\Users\\ACER\\AppData\\Local\\Programs\\Python\\Python315\\python.exe';
        if (file_exists($knownPython)) {
            $python = $knownPython;
        }

        // Run Python Parser script
        $result = Process::run([$python, $pythonScript, $pdfPath]);

        if (!$result->successful()) {
            $err = $result->errorOutput() ?: $result->output();
            throw new \Exception("Gagal memproses file PDF: " . $err);
        }

        $parsed = json_decode($result->output(), true);

        if (!$parsed || ($parsed['status'] ?? '') !== 'success') {
            throw new \Exception("Format output parser tidak valid: " . ($parsed['message'] ?? 'Unknown error'));
        }

        // Determine batch platform (Shopee vs TikTok Shop vs Semua Toko)
        $batchPlatform = $targetPlatform;
        if (empty($batchPlatform) || $batchPlatform === 'Semua Toko') {
            if (!empty($parsed['waybills'])) {
                $platforms = array_unique(array_filter(array_column($parsed['waybills'], 'platform')));
                if (count($platforms) > 1) {
                    $batchPlatform = 'Semua Toko';
                } elseif (count($platforms) === 1) {
                    $batchPlatform = reset($platforms);
                } else {
                    $batchPlatform = 'Semua Toko';
                }
            } else {
                $batchPlatform = 'Semua Toko';
            }
        }

        // Create Upload Batch
        $batchCode = 'BATCH-' . date('Ymd-His') . '-' . Str::upper(Str::random(4));
        $batch = UploadBatch::create([
            'batch_code' => $batchCode,
            'file_name' => $originalFileName,
            'platform' => $batchPlatform ?: 'Shopee',
            'total_pages' => $parsed['total_pages'] ?? 0,
            'total_waybills' => $parsed['total_waybills'] ?? 0,
            'total_qty' => $parsed['total_qty'] ?? 0,
            'status' => 'completed',
        ]);

        $hasUnmapped = false;

        // Process Waybills (Grouping by order_no so 1 order = 1 waybill)
        $existingByOrder = [];
        foreach ($parsed['waybills'] as $wbData) {
            $orderNo = trim($wbData['order_no'] ?? '');
            $waybillNo = trim($wbData['waybill_no'] ?? '');

            // Check if already created in this batch with the same order_no
            $waybill = null;
            if ($orderNo !== '' && isset($existingByOrder[$orderNo])) {
                $waybill = $existingByOrder[$orderNo];
                $waybill->increment('total_qty', $wbData['total_qty'] ?? 0);
                if (empty($waybill->waybill_no) && !empty($waybillNo)) {
                    $waybill->update(['waybill_no' => $waybillNo]);
                }
            } else {
                $store = $this->resolveStore($wbData['sender'] ?? '', $wbData['platform'] ?? '', $wbData['courier'] ?? '');
                $finalWaybillNo = !empty($waybillNo) ? $waybillNo : ($orderNo ?: 'NO-RESI-' . Str::random(8));

                $waybill = Waybill::create([
                    'upload_batch_id' => $batch->id,
                    'store_id' => $store?->id,
                    'platform' => $wbData['platform'] ?? 'Unknown',
                    'courier' => $wbData['courier'] ?? 'Unknown',
                    'waybill_no' => $finalWaybillNo,
                    'order_no' => $orderNo ?: null,
                    'sender' => $wbData['sender'] ?? null,
                    'recipient' => $wbData['recipient'] ?? null,
                    'city' => $wbData['city'] ?? null,
                    'page_number' => $wbData['page'] ?? 1,
                    'total_qty' => $wbData['total_qty'] ?? 0,
                ]);

                if ($orderNo !== '') {
                    $existingByOrder[$orderNo] = $waybill;
                }
            }

            foreach ($wbData['items'] as $itemData) {
                $masterProduct = $this->findMasterProduct(
                    $wbData['platform'] ?? '',
                    $itemData['sku'] ?? '',
                    $itemData['variant'] ?? '',
                    $itemData['product_name'] ?? '',
                    $wbData['sender'] ?? ''
                );

                if (!$masterProduct) {
                    $hasUnmapped = true;
                }

                WaybillItem::create([
                    'waybill_id' => $waybill->id,
                    'master_product_id' => $masterProduct?->id,
                    'raw_product_name' => $itemData['product_name'] ?? '',
                    'raw_sku' => $itemData['sku'] ?? '',
                    'raw_variant' => $itemData['variant'] ?? '',
                    'qty' => $itemData['qty'] ?? 1,
                    'is_mapped' => $masterProduct ? true : false,
                ]);
            }
        }

        $batch->update([
            'total_waybills' => $batch->waybills()->count(),
            'status' => $hasUnmapped ? 'unmapped_items' : 'completed',
        ]);

        return $batch;
    }

    /**
     * Resolve which store this waybill belongs to.
     */
    protected function resolveStore(string $sender, string $platform, string $courier): ?Store
    {
        $senderLower = strtolower($sender);
        $platformLower = strtolower($platform);
        $courierLower = strtolower($courier);

        // TikTok Shop / J&T Express
        if (str_contains($platformLower, 'tiktok') || str_contains($platformLower, 'tokopedia') || str_contains($courierLower, 'j&t')) {
            if (str_contains($senderLower, 'bandana buff')) {
                return Store::where('name', 'like', '%Bandana Buff%')->first()
                    ?? Store::where('platform', 'like', '%TikTok%')->where('brand', 'Moveda')->first()
                    ?? Store::where('id', 4)->first();
            }
            if (str_contains($senderLower, 'moveda')) {
                return Store::where('platform', 'like', '%TikTok%')->where('brand', 'Moveda')->first()
                    ?? Store::where('name', 'like', '%Bandana Buff%')->first();
            }
            return Store::where('brand', 'Aruna')->where('platform', 'like', '%TikTok%')->first()
                ?? Store::where('id', 2)->first()
                ?? Store::first();
        }

        // Shopee / SPX Express
        if (str_contains($senderLower, 'moveda') || str_contains($senderLower, 'bandana buff')) {
            return Store::where('brand', 'Moveda')->where('platform', 'like', '%Shopee%')->first()
                ?? Store::where('id', 3)->first();
        }

        return Store::where('brand', 'Aruna')->where('platform', 'like', '%Shopee%')->first()
            ?? Store::where('id', 1)->first()
            ?? Store::first();
    }

    /**
     * Match raw waybill item to MasterProduct.
     */
    public function findMasterProduct(string $platform, string $rawSku, string $rawVariant, string $rawName, ?string $sender = null): ?MasterProduct
    {
        $rawSkuTrim = trim($rawSku);
        $rawVarTrim = trim($rawVariant);

        // Normalize default/empty variants for single-variant products
        if (in_array(strtolower($rawVarTrim), ['default', '-', 'none', 'standar', 'standard', 'null', 'kosong', 'normal', ''])) {
            $rawVarTrim = '';
        }

        // 1. Check ProductMapping table (Kamus Penerjemah / Alias)
        if ($rawSkuTrim !== '' || $rawVarTrim !== '') {
            // Check specific platform mapping
            $mapping = ProductMapping::where('platform', $platform)
                ->where('raw_sku', $rawSkuTrim)
                ->where(function ($q) use ($rawVarTrim) {
                    $q->where('raw_variant', $rawVarTrim)
                      ->orWhere('raw_variant', '')
                      ->orWhereNull('raw_variant');
                })
                ->first();

            if ($mapping && $mapping->masterProduct) {
                return $mapping->masterProduct;
            }

            // Check universal platform mapping
            $mapping = ProductMapping::where(function ($q) use ($platform) {
                    $q->whereNull('platform')->orWhere('platform', '')->orWhere('platform', $platform);
                })
                ->where('raw_sku', $rawSkuTrim)
                ->where(function ($q) use ($rawVarTrim) {
                    $q->where('raw_variant', $rawVarTrim)
                      ->orWhere('raw_variant', '')
                      ->orWhereNull('raw_variant');
                })
                ->first();

            if ($mapping && $mapping->masterProduct) {
                return $mapping->masterProduct;
            }

            // Also check mapping where raw_variant is matched against rawVarTrim
            if ($rawVarTrim !== '') {
                $mapping = ProductMapping::where('raw_variant', $rawVarTrim)->first();
                if ($mapping && $mapping->masterProduct) {
                    return $mapping->masterProduct;
                }
            }
        }

        // 2. Direct match on sku + variant (when both are present)
        if ($rawSkuTrim !== '' && $rawVarTrim !== '') {
            $mp = MasterProduct::where('sku', $rawSkuTrim)->where('variant', $rawVarTrim)->first();
            if ($mp) return $mp;

            $mp = MasterProduct::where('seller_sku', $rawSkuTrim)->where('variant', $rawVarTrim)->first();
            if ($mp) return $mp;
        }

        // 3. Match against MasterVariant Dictionary
        $primaryAcuan = ($platform === 'Shopee') ? ($rawVarTrim ?: $rawSkuTrim) : ($rawSkuTrim ?: $rawVarTrim);
        $matchedVariantName = $this->matchAgainstVariantDictionary($primaryAcuan, $rawSkuTrim, $rawVarTrim);

        if ($matchedVariantName) {
            $mp = MasterProduct::where('variant', $matchedVariantName)->first();
            if (!$mp) {
                $cleanName = trim($rawName) ?: "Produk {$matchedVariantName}";
                $brand = 'Aruna';
                if (str_contains(strtolower($cleanName . ' ' . $sender), 'moveda') || str_contains(strtolower($cleanName . ' ' . $sender), 'bandana buff')) {
                    $brand = 'Moveda';
                }
                $mp = MasterProduct::create([
                    'name' => $cleanName,
                    'variant' => $matchedVariantName,
                    'sku' => $matchedVariantName,
                    'seller_sku' => $rawSkuTrim ?: $matchedVariantName,
                    'brand' => $brand,
                    'unit' => 'pcs',
                ]);
            }
            if ($mp) {
                ProductMapping::firstOrCreate([
                    'master_product_id' => $mp->id,
                    'platform' => $platform,
                    'raw_sku' => $rawSkuTrim,
                    'raw_variant' => $rawVarTrim,
                ]);
                return $mp;
            }
        }

        // 4. CASE A: Single-Variant Product (Variasi in resi is EMPTY and not in dictionary)
        if ($rawVarTrim === '') {
            // Check if product exists by SKU
            if ($rawSkuTrim !== '') {
                $existing = MasterProduct::where('sku', $rawSkuTrim)
                    ->orWhere('seller_sku', $rawSkuTrim)
                    ->first();
                if ($existing) {
                    return $existing;
                }
            }

            // Auto-create single-variant MasterProduct (Produk Tunggal)
            $cleanName = trim($rawName) ?: ($rawSkuTrim ?: 'Produk Tanpa Nama');
            $brand = 'Aruna';
            if (str_contains(strtolower($cleanName . ' ' . $sender), 'moveda') || str_contains(strtolower($cleanName . ' ' . $sender), 'bandana buff')) {
                $brand = 'Moveda';
            }

            $mp = MasterProduct::create([
                'name' => $cleanName,
                'variant' => null,
                'sku' => $rawSkuTrim ?: null,
                'seller_sku' => $rawSkuTrim ?: null,
                'brand' => $brand,
                'unit' => 'pcs',
            ]);

            if ($rawSkuTrim !== '') {
                ProductMapping::firstOrCreate([
                    'master_product_id' => $mp->id,
                    'platform' => $platform,
                    'raw_sku' => $rawSkuTrim,
                    'raw_variant' => '',
                ]);
            }

            return $mp;
        }

        // 5. CASE B: Multi-Variant Product (Variasi in resi is NOT empty and not in dictionary)
        $finalVariant = $rawVarTrim;

        // Check existing MasterProduct with this variant
        $mp = MasterProduct::where('variant', $finalVariant)->first();
        if ($mp) {
            ProductMapping::firstOrCreate([
                'master_product_id' => $mp->id,
                'platform' => $platform,
                'raw_sku' => $rawSkuTrim,
                'raw_variant' => $rawVarTrim,
            ]);
            return $mp;
        }

        // Auto-create MasterProduct with this variant
        $cleanName = trim($rawName) ?: "Produk {$finalVariant}";
        $brand = 'Aruna';
        if (str_contains(strtolower($cleanName . ' ' . $sender), 'moveda')) {
            $brand = 'Moveda';
        }

        $mp = MasterProduct::create([
            'name' => $cleanName,
            'variant' => $finalVariant,
            'sku' => $rawSkuTrim ?: $finalVariant,
            'seller_sku' => $rawSkuTrim ?: $finalVariant,
            'brand' => $brand,
            'unit' => 'pcs',
        ]);

        ProductMapping::firstOrCreate([
            'master_product_id' => $mp->id,
            'platform' => $platform,
            'raw_sku' => $rawSkuTrim,
            'raw_variant' => $rawVarTrim,
        ]);

        return $mp;
    }

    /**
     * Match against MasterVariant dictionary using exact, normalized, and substring matching.
     */
    protected function matchAgainstVariantDictionary(string $acuan, string $rawSku, string $rawVariant): ?string
    {
        static $variantsCache = null;
        if ($variantsCache === null) {
            $all = \App\Models\MasterVariant::all();
            $variantsCache = [];
            foreach ($all as $v) {
                $norm = preg_replace('/[^a-z0-9]/', '', strtolower($v->name));
                if ($norm !== '') {
                    $variantsCache[$norm] = $v->name;
                }
            }
            // Sort by length descending so longer compound names match first
            uksort($variantsCache, function ($a, $b) {
                return strlen($b) <=> strlen($a);
            });
        }

        $candAcuan = preg_replace('/[^a-z0-9]/', '', strtolower($acuan));
        $candVar = preg_replace('/[^a-z0-9]/', '', strtolower($rawVariant));
        $candSku = preg_replace('/[^a-z0-9]/', '', strtolower($rawSku));

        // 1. Exact normalized match on variant or acuan
        foreach ([$candVar, $candAcuan, $candSku] as $cand) {
            if ($cand !== '' && isset($variantsCache[$cand])) {
                return $variantsCache[$cand];
            }
        }

        // 2. Suffix / Substring match on variant, acuan, or sku
        foreach ($variantsCache as $norm => $origName) {
            if (strlen($norm) < 3) continue;
            if ($candVar !== '' && (str_ends_with($candVar, $norm) || str_contains($candVar, $norm))) {
                return $origName;
            }
            if ($candAcuan !== '' && (str_ends_with($candAcuan, $norm) || str_contains($candAcuan, $norm))) {
                return $origName;
            }
            if ($candSku !== '' && (str_ends_with($candSku, $norm) || str_contains($candSku, $norm))) {
                return $origName;
            }
        }

        // 3. Try stripping known category prefixes (e.g. Bandana, Headband, ArmSleeve, Slayer, etc.)
        $prefixes = ['bandana', 'headband', 'armsleeve', 'arm', 'slayer', 'headwear', 'reflektive', 'baff', 'buff', 'outer', 'jersey'];
        foreach ([$candVar, $candAcuan, $candSku] as $cand) {
            if ($cand === '') continue;
            foreach ($prefixes as $pfx) {
                if (str_starts_with($cand, $pfx)) {
                    $stripped = substr($cand, strlen($pfx));
                    if (isset($variantsCache[$stripped])) {
                        return $variantsCache[$stripped];
                    }
                    foreach ($variantsCache as $norm => $origName) {
                        if (strlen($norm) < 3) continue;
                        if (str_ends_with($stripped, $norm) || str_contains($stripped, $norm)) {
                            return $origName;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Remap all unmapped items in a batch after user adds a new mapping or updates master data.
     */
    public function remapBatch(UploadBatch $batch): void
    {
        $items = WaybillItem::whereHas('waybill', function ($q) use ($batch) {
            $q->where('upload_batch_id', $batch->id);
        })->where('is_mapped', false)->get();

        $allMapped = true;
        foreach ($items as $item) {
            $platform = $item->waybill->platform;
            $sender = $item->waybill->sender ?? '';
            $mp = $this->findMasterProduct(
                $platform,
                $item->raw_sku ?? '',
                $item->raw_variant ?? '',
                $item->raw_product_name ?? '',
                $sender
            );
            if ($mp) {
                $item->update([
                    'master_product_id' => $mp->id,
                    'is_mapped' => true,
                ]);
            } else {
                $allMapped = false;
            }
        }

        $batch->update([
            'status' => $allMapped ? 'completed' : 'unmapped_items'
        ]);
    }
}
