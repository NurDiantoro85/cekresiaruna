<?php

use App\Http\Controllers\MasterProductController;
use App\Http\Controllers\PdfMergeController;
use App\Http\Controllers\ResiController;
use Illuminate\Support\Facades\Route;

// Resi & Dashboard Routes
Route::get('/', [ResiController::class, 'index'])->name('resi.index');
Route::get('/resi/shopee', [ResiController::class, 'shopee'])->name('resi.shopee');
Route::get('/resi/tiktok', [ResiController::class, 'tiktok'])->name('resi.tiktok');
Route::post('/upload', [ResiController::class, 'upload'])->name('resi.upload');
Route::get('/batch/{batch}', [ResiController::class, 'showBatch'])->name('resi.batch');
Route::get('/batch/{batch}/pdf', [ResiController::class, 'viewPdf'])->name('resi.pdf');
Route::post('/batch/{batch}/quick-map', [ResiController::class, 'quickMap'])->name('resi.quickMap');
Route::get('/batch/{batch}/export-csv', [ResiController::class, 'exportCsv'])->name('resi.export');
Route::delete('/batch/{batch}', [ResiController::class, 'destroy'])->name('resi.delete');

// Fitur Penggabungan File PDF (Merge PDF)
Route::get('/merge-pdf', [PdfMergeController::class, 'index'])->name('pdf.merge.index');
Route::post('/merge-pdf', [PdfMergeController::class, 'merge'])->name('pdf.merge.action');

// Master Produk
Route::get('/products', [MasterProductController::class, 'index'])->name('products.index');
Route::post('/products', [MasterProductController::class, 'store'])->name('products.store');
Route::post('/products/import-csv', [MasterProductController::class, 'importCsv'])->name('products.importCsv');
Route::put('/products/{product}', [MasterProductController::class, 'update'])->name('products.update');
Route::delete('/products/{product}', [MasterProductController::class, 'destroy'])->name('products.destroy');

// File Templates
Route::get('/template-master-produk', function () {
    return response()->download(public_path('template_master_produk.csv'), 'template_master_produk.csv');
})->name('template.product');

Route::get('/contoh-rekap-stodo', function () {
    return response()->download(public_path('contoh_hasil_rekap_stok_stodo.csv'), 'contoh_hasil_rekap_stok_stodo.csv');
})->name('contoh.rekap');

// Master Lookups (Kamus Acuan: SKU, Seller SKU, Variasi)
Route::post('/skus', [MasterProductController::class, 'storeSku'])->name('skus.store');
Route::delete('/skus/{sku}', [MasterProductController::class, 'destroySku'])->name('skus.destroy');

Route::post('/seller-skus', [MasterProductController::class, 'storeSellerSku'])->name('seller_skus.store');
Route::delete('/seller-skus/{sellerSku}', [MasterProductController::class, 'destroySellerSku'])->name('seller_skus.destroy');

Route::delete('/variants/bulk-destroy', [MasterProductController::class, 'bulkDestroyVariants'])->name('variants.bulkDestroy');
Route::post('/variants', [MasterProductController::class, 'storeVariant'])->name('variants.store');
Route::delete('/variants/{variant}', [MasterProductController::class, 'destroyVariant'])->name('variants.destroy');

// Product Mappings (Alias Resi)
Route::post('/mappings', [MasterProductController::class, 'addMapping'])->name('mappings.store');
Route::delete('/mappings/{mapping}', [MasterProductController::class, 'deleteMapping'])->name('mappings.destroy');
