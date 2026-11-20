<?php

namespace App\Http\Controllers;

use App\Models\MasterProduct;
use App\Models\ProductMapping;
use App\Models\Store;
use App\Models\UploadBatch;
use App\Models\Waybill;
use App\Models\WaybillItem;
use App\Services\ResiParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResiController extends Controller
{
    public function __construct(
        protected ResiParserService $parserService
    ) {}

    /**
     * Dashboard & Upload View (Overview)
     */
    public function index(Request $request)
    {
        $platform = $request->query('platform');
        $query = UploadBatch::latest();
        if ($platform) {
            $query->where('platform', $platform);
        }
        $batches = $query->paginate(10);
        $totalBatches = UploadBatch::count();
        $totalWaybills = UploadBatch::sum('total_waybills');
        $totalItems = UploadBatch::sum('total_qty');
        $totalProducts = MasterProduct::count();
        $stores = Store::all();

        return view('resi.index', compact(
            'batches',
            'totalBatches',
            'totalWaybills',
            'totalItems',
            'totalProducts',
            'stores',
            'platform'
        ));
    }

    /**
     * Dedicated Shopee Resi View (Acuan: Variasi)
     */
    public function shopee()
    {
        $batches = UploadBatch::where('platform', 'Shopee')->latest()->paginate(10);
        $totalBatches = UploadBatch::where('platform', 'Shopee')->count();
        $totalWaybills = UploadBatch::where('platform', 'Shopee')->sum('total_waybills');
        $totalItems = UploadBatch::where('platform', 'Shopee')->sum('total_qty');
        $stores = Store::where('platform', 'Shopee')->get();

        return view('resi.shopee', compact(
            'batches',
            'totalBatches',
            'totalWaybills',
            'totalItems',
            'stores'
        ));
    }

    /**
     * Dedicated TikTok / Tokopedia Resi View (Acuan: Seller SKU)
     */
    public function tiktok()
    {
        $batches = UploadBatch::where('platform', 'TikTok Shop')->latest()->paginate(10);
        $totalBatches = UploadBatch::where('platform', 'TikTok Shop')->count();
        $totalWaybills = UploadBatch::where('platform', 'TikTok Shop')->sum('total_waybills');
        $totalItems = UploadBatch::where('platform', 'TikTok Shop')->sum('total_qty');
        $stores = Store::where('platform', 'TikTok Shop')->get();

        return view('resi.tiktok', compact(
            'batches',
            'totalBatches',
            'totalWaybills',
            'totalItems',
            'stores'
        ));
    }

    /**
     * Handle PDF Upload
     */
    public function upload(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:102400', // max 100MB
            'target_platform' => 'nullable|string|in:Shopee,TikTok Shop,Semua Toko',
        ]);

        try {
            $file = $request->file('pdf_file');
            $originalName = $file->getClientOriginalName();
            $targetPlatform = $request->input('target_platform') ?: 'Semua Toko';
            
            // Save temporary
            $tempPath = $file->storeAs('temp_resi', uniqid() . '_' . $originalName);
            $fullPath = Storage::path($tempPath);

            $batch = $this->parserService->processPdf($fullPath, $originalName, $targetPlatform);

            // Store PDF for page inspection & verification
            $destDir = storage_path('app/batches_pdf');
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            @copy($fullPath, $destDir . '/' . $batch->batch_code . '.pdf');

            // Cleanup temp file
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }

            return redirect()->route('resi.batch', $batch->id)
                ->with('success', "Berhasil memproses {$batch->total_waybills} resi ({$batch->total_qty} pcs barang keluar) dari file {$originalName}!");

        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Batch Detail & Aggregated StoDo Report
     */
    public function showBatch(UploadBatch $batch)
    {
        $batch->load(['waybills.items.masterProduct', 'waybills.store']);

        $platformsInBatch = $batch->waybills->pluck('platform')->unique()->filter()->values()->all();
        $isAllStores = ($batch->platform === 'Semua Toko' || $batch->platform === 'Mixed' || count($platformsInBatch) > 1);
        $isShopee = !$isAllStores && ($batch->platform === 'Shopee' || (count($platformsInBatch) === 1 && $platformsInBatch[0] === 'Shopee'));
        $isTiktok = !$isAllStores && ($batch->platform === 'TikTok Shop' || (count($platformsInBatch) === 1 && $platformsInBatch[0] === 'TikTok Shop'));

        $batchPlatform = $isAllStores ? 'Semua Toko' : ($isShopee ? 'Shopee' : 'TikTok Shop');

        // 1. Rekapitulasi per Master Produk (StoDo Ready)
        $mappedSummary = [];
        $unmappedSummary = [];

        foreach ($batch->waybills as $wb) {
            $storeName = $wb->store?->name ?? $wb->platform;
            $wbPlatform = $wb->platform ?: 'Shopee';
            
            foreach ($wb->items as $item) {
                // Kolom ke-2 dari kanan: Shopee = Variasi, TikTok = Seller SKU (prioritaskan nama variasi bersih dari kamus)
                $cleanVariant = (!empty($item->masterProduct?->variant) && $item->masterProduct->variant !== '-') ? $item->masterProduct->variant : null;
                $code2ndFromRight = ($wbPlatform === 'Shopee')
                    ? ($cleanVariant ?: ($item->raw_variant ?: ($item->raw_sku ?: '-')))
                    : ($cleanVariant ?: ($item->raw_sku ?: ($item->masterProduct?->seller_sku ?: ($item->raw_variant ?: '-'))));

                if ($item->is_mapped && $item->masterProduct) {
                    $mp = $item->masterProduct;
                    $key = 'MP_' . $mp->id;

                    if (!isset($mappedSummary[$key])) {
                        $mappedSummary[$key] = [
                            'id' => $mp->id,
                            'name' => $mp->name,
                            'sku' => $mp->sku ?: '-',
                            'seller_sku' => $mp->seller_sku ?: ($mp->sku ?: '-'),
                            'variant' => $mp->variant ?: '-',
                            'brand' => $mp->brand,
                            'unit' => $mp->unit,
                            'total_qty' => 0,
                            'stores' => [],
                            'is_mapped' => true,
                            'packing_codes' => [],
                            'primary_acuan' => $isShopee 
                                ? ($mp->variant ?: 'Produk Tunggal') 
                                : ($isTiktok ? ($mp->seller_sku ?: ($mp->sku ?: '-')) : $code2ndFromRight),
                        ];
                    }

                    if (!in_array($code2ndFromRight, $mappedSummary[$key]['packing_codes'][$wbPlatform] ?? [])) {
                        $mappedSummary[$key]['packing_codes'][$wbPlatform][] = $code2ndFromRight;
                    }

                    $mappedSummary[$key]['total_qty'] += $item->qty;
                    $mappedSummary[$key]['stores'][$storeName] = ($mappedSummary[$key]['stores'][$storeName] ?? 0) + $item->qty;

                } else {
                    $key = 'UNM_' . md5($item->raw_sku . '_' . $item->raw_variant . '_' . $item->raw_product_name);

                    if (!isset($unmappedSummary[$key])) {
                        $unmappedSummary[$key] = [
                            'raw_sku' => $item->raw_sku ?: '-',
                            'raw_variant' => $item->raw_variant ?: '-',
                            'raw_product_name' => $item->raw_product_name,
                            'platform' => $wb->platform,
                            'packing_code' => $code2ndFromRight,
                            'total_qty' => 0,
                            'stores' => [],
                            'is_mapped' => false,
                            'primary_acuan' => $code2ndFromRight,
                        ];
                    }

                    $unmappedSummary[$key]['total_qty'] += $item->qty;
                    $unmappedSummary[$key]['stores'][$storeName] = ($unmappedSummary[$key]['stores'][$storeName] ?? 0) + $item->qty;
                }
            }
        }

        // Sort by total_qty desc
        uasort($mappedSummary, fn($a, $b) => $b['total_qty'] <=> $a['total_qty']);
        uasort($unmappedSummary, fn($a, $b) => $b['total_qty'] <=> $a['total_qty']);

        // 2. Rekapitulasi per Toko & Urutan Halaman PDF
        $storeGroups = [];
        foreach ($batch->waybills()->with('items.masterProduct', 'store')->orderBy('page_number')->get() as $wb) {
            $stName = $wb->store?->name ?? 'Lainnya';
            if (!isset($storeGroups[$stName])) {
                $storeGroups[$stName] = [
                    'store' => $wb->store,
                    'store_name' => $stName,
                    'platform' => $wb->store?->platform ?? $wb->platform,
                    'courier' => $wb->store?->courier ?? $wb->courier,
                    'first_page' => $wb->page_number,
                    'last_page' => $wb->page_number,
                    'total_qty' => 0,
                    'total_waybills' => 0,
                    'items' => [],
                ];
            }

            $storeGroups[$stName]['total_waybills']++;
            $storeGroups[$stName]['total_qty'] += $wb->total_qty;
            $storeGroups[$stName]['last_page'] = max($storeGroups[$stName]['last_page'], $wb->page_number);

            foreach ($wb->items as $it) {
                $varName = $it->masterProduct?->variant ?: ($it->raw_variant ?: ($it->raw_sku ?: $it->raw_product_name));
                $itemKey = $varName;

                if (!isset($storeGroups[$stName]['items'][$itemKey])) {
                    $storeGroups[$stName]['items'][$itemKey] = [
                        'variant_name' => $varName,
                        'first_page' => $wb->page_number,
                        'total_qty' => 0,
                        'waybill_items' => [],
                    ];
                }

                $storeGroups[$stName]['items'][$itemKey]['total_qty'] += $it->qty;
                $storeGroups[$stName]['items'][$itemKey]['waybill_items'][] = [
                    'waybill_id' => $wb->id,
                    'waybill_no' => $wb->waybill_no,
                    'order_no' => $wb->order_no,
                    'page_number' => $wb->page_number,
                    'recipient' => $wb->recipient,
                    'city' => $wb->city,
                    'courier' => $wb->courier,
                    'platform' => $wb->platform,
                    'raw_product_name' => $it->raw_product_name,
                    'raw_sku' => $it->raw_sku ?: '-',
                    'raw_variant' => $it->raw_variant ?: '-',
                    'qty' => $it->qty,
                ];
            }
        }

        // Sort stores by PDF appearance
        uasort($storeGroups, fn($a, $b) => $a['first_page'] <=> $b['first_page']);

        // Sort items inside each store by PDF appearance
        foreach ($storeGroups as $stName => &$group) {
            uasort($group['items'], fn($a, $b) => $a['first_page'] <=> $b['first_page']);
        }
        unset($group);

        $storeBreakdown = $storeGroups;

        $allMasterProducts = MasterProduct::orderBy('name')->get();

        return view('resi.batch', compact(
            'batch',
            'mappedSummary',
            'unmappedSummary',
            'storeGroups',
            'storeBreakdown',
            'allMasterProducts',
            'batchPlatform',
            'isShopee',
            'isTiktok',
            'isAllStores'
        ));
    }

    /**
     * Preview / View Original PDF
     */
    public function viewPdf(UploadBatch $batch)
    {
        $path = storage_path('app/batches_pdf/' . $batch->batch_code . '.pdf');
        if (!file_exists($path)) {
            if (file_exists('d:/Test.pdf')) {
                $path = 'd:/Test.pdf';
            } else {
                abort(404, 'File PDF tidak ditemukan di server.');
            }
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    /**
     * Quick map unmapped item to Master Product
     */
    public function quickMap(Request $request, UploadBatch $batch)
    {
        $request->validate([
            'master_product_id' => 'required|exists:master_products,id',
            'raw_sku' => 'nullable|string',
            'raw_variant' => 'nullable|string',
            'platform' => 'nullable|string',
        ]);

        $rawSku = trim($request->raw_sku ?: '');
        $rawVariant = trim($request->raw_variant ?: '');
        if ($rawSku === '-') $rawSku = '';
        if (in_array(strtolower($rawVariant), ['-', 'default', 'none', 'null', 'kosong'])) {
            $rawVariant = '';
        }

        ProductMapping::firstOrCreate([
            'master_product_id' => $request->master_product_id,
            'platform' => $request->platform ?: null,
            'raw_sku' => $rawSku,
            'raw_variant' => $rawVariant,
        ]);

        // Remap items in batch
        $this->parserService->remapBatch($batch);

        return back()->with('success', 'Produk berhasil dihubungkan ke Master Barang! Rekapitulasi otomatis diperbarui.');
    }

    /**
     * Export rekapitulasi as CSV
     */
    public function exportCsv(UploadBatch $batch): StreamedResponse
    {
        $batch->load(['waybills.items.masterProduct', 'waybills.store']);

        $storeGroups = [];
        foreach ($batch->waybills()->with('items.masterProduct', 'store')->orderBy('page_number')->get() as $wb) {
            $stName = $wb->store?->name ?? 'Lainnya';
            if (!isset($storeGroups[$stName])) {
                $storeGroups[$stName] = [
                    'store_name' => $stName,
                    'first_page' => $wb->page_number,
                    'items' => [],
                ];
            }

            foreach ($wb->items as $it) {
                $varName = $it->masterProduct?->variant ?: ($it->raw_variant ?: ($it->raw_sku ?: $it->raw_product_name));
                $itemKey = $varName;

                if (!isset($storeGroups[$stName]['items'][$itemKey])) {
                    $storeGroups[$stName]['items'][$itemKey] = [
                        'variant_name' => $varName,
                        'first_page' => $wb->page_number,
                        'total_qty' => 0,
                    ];
                }

                $storeGroups[$stName]['items'][$itemKey]['total_qty'] += $it->qty;
            }
        }

        uasort($storeGroups, fn($a, $b) => $a['first_page'] <=> $b['first_page']);
        foreach ($storeGroups as &$group) {
            uasort($group['items'], fn($a, $b) => $a['first_page'] <=> $b['first_page']);
        }
        unset($group);

        $platformSlug = Str::slug($batch->platform ?: 'rekap');
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"rekap-stok-{$platformSlug}-{$batch->batch_code}.csv\"",
        ];

        return response()->stream(function () use ($storeGroups) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            foreach ($storeGroups as $stName => $group) {
                fputcsv($file, [$stName]);
                fputcsv($file, ['No.', 'ceklis', 'varian/seller sku', 'qty']);
                $no = 1;
                foreach ($group['items'] as $it) {
                    fputcsv($file, [$no++ . '.', '', $it['variant_name'], $it['total_qty']]);
                }
                fputcsv($file, []);
            }
            fclose($file);
        }, 200, $headers);
    }

    /**
     * Delete Batch
     */
    public function destroy(UploadBatch $batch)
    {
        $batch->delete();
        return redirect()->route('resi.index')->with('success', 'Riwayat upload berhasil dihapus.');
    }
}

