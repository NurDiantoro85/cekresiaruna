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

        // Gunakan Python dari env atau PATH sistem, atau path default lokal
        $python = env('PYTHON_PATH', 'python');
        $knownPython = 'C:\\Users\\ACER\\AppData\\Local\\Programs\\Python\\Python315\\python.exe';
        if ($python === 'python' && file_exists($knownPython)) {
            $python = $knownPython;
        }

        // Environment variables untuk mencegah error '_Py_HashRandomization_Init' di Windows/Apache
        $processEnv = array_filter([
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
            'WINDIR' => getenv('WINDIR') ?: 'C:\\Windows',
            'PATH' => getenv('PATH') ?: (isset($_SERVER['PATH']) ? $_SERVER['PATH'] : null),
            'TEMP' => getenv('TEMP') ?: sys_get_temp_dir(),
            'TMP' => getenv('TMP') ?: sys_get_temp_dir(),
            'PYTHONHASHSEED' => '0',
            'PYTHONIOENCODING' => 'utf-8',
        ]);

        // Run Python Parser script
        $result = Process::env($processEnv)->run([$python, $pythonScript, $pdfPath]);

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
            if (str_contains($senderLower, 'bandana buff') || str_contains($senderLower, 'moveda')) {
                return Store::where('name', 'like', '%Moveda%')->where('platform', 'like', '%TikTok%')->first()
                    ?? Store::where('brand', 'Moveda')->where('platform', 'like', '%TikTok%')->first()
                    ?? Store::where('id', 4)->first();
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
            $mapping = null;

            // Check exact SKU + Variant for this platform
            if ($rawSkuTrim !== '' && $rawVarTrim !== '') {
                $mapping = ProductMapping::where('platform', $platform)
                    ->where('raw_sku', $rawSkuTrim)
                    ->where('raw_variant', $rawVarTrim)
                    ->first();
                if (!$mapping) {
                    $mapping = ProductMapping::where(function ($q) use ($platform) {
                            $q->whereNull('platform')->orWhere('platform', '')->orWhere('platform', $platform);
                        })
                        ->where('raw_sku', $rawSkuTrim)
                        ->where('raw_variant', $rawVarTrim)
                        ->first();
                }
            }

            // Check exact variant for this platform or universal (when rawVarTrim is not empty)
            if (!$mapping && $rawVarTrim !== '') {
                $mapping = ProductMapping::where('platform', $platform)
                    ->where('raw_variant', $rawVarTrim)
                    ->first();
                if (!$mapping) {
                    $mapping = ProductMapping::where('raw_variant', $rawVarTrim)->first();
                }
            }

            // Check single-variant mapping (raw_variant is empty in resi)
            if (!$mapping && $rawVarTrim === '' && $rawSkuTrim !== '') {
                $mapping = ProductMapping::where('platform', $platform)
                    ->where('raw_sku', $rawSkuTrim)
                    ->where(function ($q) {
                        $q->where('raw_variant', '')->orWhereNull('raw_variant');
                    })
                    ->first();
                if (!$mapping) {
                    $mapping = ProductMapping::where('raw_sku', $rawSkuTrim)
                        ->where(function ($q) {
                            $q->where('raw_variant', '')->orWhereNull('raw_variant');
                        })
                        ->first();
                }
            }

            // Safety validation for mapping: Ensure category alignment
            if ($mapping && $mapping->masterProduct) {
                $mpVar = strtolower($mapping->masterProduct->variant ?? '');
                $rawVarLower = strtolower($rawVarTrim);
                $rawSkuLower = strtolower($rawSkuTrim);
                $rawAll = strtolower($rawName . ' ' . $rawSkuLower . ' ' . $rawVarLower);

                $contextHasHdbn = str_contains($rawAll, 'hdbn') || str_contains($rawAll, 'headband');
                $mpHasHdbn = str_starts_with($mpVar, 'hdbn');

                $contextHasArm = str_contains($rawAll, 'armsleeve') || str_contains($rawAll, 'arm') || str_contains($rawAll, 'manset');
                $mpHasArm = str_starts_with($mpVar, 'arm');

                // If input clearly specifies Headband but mapped MP is non-Headband (or vice versa), reject bad mapping
                $mismatchHdbn = ($contextHasHdbn && !$mpHasHdbn && !empty($rawVarTrim));
                $mismatchArm = ($contextHasArm && !$mpHasArm && !empty($rawVarTrim) && !str_contains($rawAll, 'alarm'));

                if (!$mismatchHdbn && !$mismatchArm) {
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
        $matchedVariantName = $this->matchAgainstVariantDictionary($primaryAcuan, $rawSkuTrim, $rawVarTrim, $rawName);

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
     * Match against MasterVariant dictionary using exact, normalized, category-aware, and substring matching.
     */
    protected function matchAgainstVariantDictionary(string $acuan, string $rawSku, string $rawVariant, ?string $rawName = null): ?string
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

        $allContext = strtolower(implode(' ', array_filter([$rawName, $rawSku, $rawVariant, $acuan])));

        $isHeadband = str_contains($allContext, 'headband') 
            || str_contains($allContext, 'hdbn') 
            || str_contains($allContext, 'ikat kepala') 
            || str_contains($allContext, 'bando');

        $isArm = str_contains($allContext, 'armsleeve') 
            || str_contains($allContext, 'arm sleeve') 
            || str_contains($allContext, 'manset') 
            || preg_match('/\barm\b/i', $allContext) 
            || str_starts_with(strtolower(trim($rawSku)), 'arm');

        $isJersey = str_contains($allContext, 'jersey');
        $isSlayer = str_contains($allContext, 'slayer');
        $isSet = preg_match('/\bset\b/i', $allContext) || str_contains($allContext, 'paket set');
        $isBelt = str_contains($allContext, 'running belt') || str_contains($allContext, 'belt');

        $candVar = preg_replace('/[^a-z0-9]/', '', strtolower($rawVariant));
        $candAcuan = preg_replace('/[^a-z0-9]/', '', strtolower($acuan));
        $candSku = preg_replace('/[^a-z0-9]/', '', strtolower($rawSku));

        // 1. Exact normalized match with category context checks
        foreach ([$candVar, $candAcuan, $candSku] as $cand) {
            if ($cand === '') continue;

            if (isset($variantsCache[$cand])) {
                $normMatched = $cand;

                if ($isHeadband && !str_starts_with($normMatched, 'hdbn') && isset($variantsCache['hdbn' . $normMatched])) {
                    return $variantsCache['hdbn' . $normMatched];
                }
                if ($isArm && !str_starts_with($normMatched, 'arm') && isset($variantsCache['arm' . $normMatched])) {
                    return $variantsCache['arm' . $normMatched];
                }
                if ($isSet && !str_starts_with($normMatched, 'set') && isset($variantsCache['set' . $normMatched])) {
                    return $variantsCache['set' . $normMatched];
                }
                if ($isJersey && !str_starts_with($normMatched, 'jersey') && isset($variantsCache['jersey' . $normMatched])) {
                    return $variantsCache['jersey' . $normMatched];
                }
                if ($isSlayer && !str_starts_with($normMatched, 'slayer') && isset($variantsCache['slayer' . $normMatched])) {
                    return $variantsCache['slayer' . $normMatched];
                }

                return $variantsCache[$cand];
            }

            if ($isHeadband && isset($variantsCache['hdbn' . $cand])) {
                return $variantsCache['hdbn' . $cand];
            }
            if ($isArm && isset($variantsCache['arm' . $cand])) {
                return $variantsCache['arm' . $cand];
            }
            if ($isSet && isset($variantsCache['set' . $cand])) {
                return $variantsCache['set' . $cand];
            }
            if ($isJersey && isset($variantsCache['jersey' . $cand])) {
                return $variantsCache['jersey' . $cand];
            }
            if ($isSlayer && isset($variantsCache['slayer' . $cand])) {
                return $variantsCache['slayer' . $cand];
            }
        }

        // 2. Suffix / Substring match on variant, acuan, or sku with strict category isolation
        foreach ($variantsCache as $norm => $origName) {
            if (strlen($norm) < 3) continue;

            $normIsHdbn = str_starts_with($norm, 'hdbn');
            $normIsArm = str_starts_with($norm, 'arm');
            $normIsSet = str_starts_with($norm, 'set');
            $normIsJersey = str_starts_with($norm, 'jersey');
            $normIsSlayer = str_starts_with($norm, 'slayer');

            // Category boundary protection:
            if (!$isHeadband && $normIsHdbn) continue;
            if ($isHeadband && !$normIsHdbn && isset($variantsCache['hdbn' . $norm])) continue;

            if (!$isArm && $normIsArm) continue;
            if ($isArm && !$normIsArm && isset($variantsCache['arm' . $norm])) continue;

            if (!$isSet && $normIsSet) continue;
            if ($isSet && !$normIsSet && isset($variantsCache['set' . $norm])) continue;

            if (!$isJersey && $normIsJersey) continue;
            if ($isJersey && !$normIsJersey && isset($variantsCache['jersey' . $norm])) continue;

            if (!$isSlayer && $normIsSlayer) continue;

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
        $prefixes = [
            'headband' => 'hdbn',
            'hdbn' => 'hdbn',
            'armsleeve' => 'arm',
            'arm' => 'arm',
            'manset' => 'arm',
            'jersey' => 'jersey',
            'slayer' => 'slayer',
            'set' => 'set',
            'bandana' => '',
            'headwear' => '',
            'reflektive' => 'arm',
            'baff' => '',
            'buff' => '',
            'outer' => 'outer',
        ];

        foreach ([$candVar, $candAcuan, $candSku] as $cand) {
            if ($cand === '') continue;
            foreach ($prefixes as $pfx => $targetPfx) {
                if (str_starts_with($cand, $pfx)) {
                    $stripped = substr($cand, strlen($pfx));
                    if (strlen($stripped) < 3) continue;

                    // If target prefix exists (e.g. hdbn, arm, set), check if prefixed variant exists
                    if ($targetPfx !== '') {
                        $prefixedNorm = $targetPfx . $stripped;
                        if (isset($variantsCache[$prefixedNorm])) {
                            return $variantsCache[$prefixedNorm];
                        }
                    }

                    // Check exact stripped in cache
                    if (isset($variantsCache[$stripped])) {
                        if ($targetPfx !== '' && isset($variantsCache[$targetPfx . $stripped])) {
                            return $variantsCache[$targetPfx . $stripped];
                        }
                        return $variantsCache[$stripped];
                    }

                    // Check substring on stripped
                    foreach ($variantsCache as $norm => $origName) {
                        if (strlen($norm) < 3) continue;
                        if ($targetPfx !== '' && !str_starts_with($norm, $targetPfx)) {
                            if (isset($variantsCache[$targetPfx . $norm])) {
                                continue;
                            }
                        }
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
