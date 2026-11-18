<?php

namespace App\Http\Controllers;

use App\Models\MasterProduct;
use App\Models\MasterSku;
use App\Models\MasterVariant;
use App\Models\ProductMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MasterProductController extends Controller
{
    public function index()
    {
        $products = MasterProduct::with('mappings')->orderBy('brand')->orderBy('name')->paginate(15);
        $variants = MasterVariant::orderBy('name')->get();
        $totalMappings = ProductMapping::count();

        return view('products.index', compact(
            'products',
            'variants',
            'totalMappings'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'stodo_code' => 'nullable|string|unique:master_products,stodo_code|max:50',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'variant' => 'required|string|max:100',
            'brand' => 'required|string|in:Aruna,Moveda,General',
            'unit' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['unit'] = $validated['unit'] ?: 'pcs';
        $validated['sku'] = $validated['sku'] ?: $validated['variant'];
        $validated['seller_sku'] = $validated['variant'];

        // Auto-generate stodo_code if empty
        if (empty($validated['stodo_code'])) {
            $brandPrefix = strtoupper(substr($validated['brand'], 0, 3));
            $varPrefix = strtoupper(substr(str_replace(' ', '', $validated['variant'] ?: 'DFT'), 0, 4));
            $generated = "{$brandPrefix}-{$varPrefix}";

            $count = MasterProduct::where('stodo_code', 'like', "{$generated}%")->count();
            $validated['stodo_code'] = $count > 0 ? "{$generated}-" . ($count + 1) : $generated;
        }

        $product = MasterProduct::create($validated);

        // Auto-register mappings
        ProductMapping::firstOrCreate([
            'master_product_id' => $product->id,
            'platform' => 'Shopee',
            'raw_sku' => $product->sku,
            'raw_variant' => $product->variant,
        ]);

        ProductMapping::firstOrCreate([
            'master_product_id' => $product->id,
            'platform' => 'TikTok Shop',
            'raw_sku' => $product->variant,
            'raw_variant' => 'Default',
        ]);

        return back()->with('success', "Master Produk {$product->name} ({$product->variant}) berhasil ditambahkan!");
    }

    public function update(Request $request, MasterProduct $product)
    {
        $validated = $request->validate([
            'stodo_code' => 'nullable|string|max:50|unique:master_products,stodo_code,' . $product->id,
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'variant' => 'required|string|max:100',
            'brand' => 'required|string|in:Aruna,Moveda,General',
            'unit' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['unit'] = $validated['unit'] ?: 'pcs';
        $validated['sku'] = $validated['sku'] ?: $validated['variant'];
        $validated['seller_sku'] = $validated['variant'];

        $product->update($validated);

        return back()->with('success', 'Master Produk berhasil diperbarui!');
    }

    public function destroy(MasterProduct $product)
    {
        $product->delete();
        return back()->with('success', 'Master Produk berhasil dihapus.');
    }

    // Lookup Store Methods
    public function storeSku(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:master_skus,name|max:100']);
        MasterSku::create(['name' => trim($request->name)]);
        return back()->with('success', 'Master SKU berhasil ditambahkan!');
    }

    public function destroySku(MasterSku $sku)
    {
        $sku->delete();
        return back()->with('success', 'Master SKU dihapus.');
    }

    public function storeVariant(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:master_variants,name|max:100']);
        MasterVariant::create(['name' => trim($request->name)]);
        return back()->with('success', 'Variasi / Seller SKU berhasil ditambahkan!');
    }

    public function destroyVariant(MasterVariant $variant)
    {
        $variant->delete();
        return back()->with('success', 'Variasi / Seller SKU dihapus.');
    }

    // Product Mapping Methods
    public function addMapping(Request $request)
    {
        $validated = $request->validate([
            'master_product_id' => 'required|exists:master_products,id',
            'platform' => 'nullable|string',
            'raw_sku' => 'nullable|string',
            'raw_variant' => 'nullable|string',
        ]);

        ProductMapping::firstOrCreate([
            'master_product_id' => $validated['master_product_id'],
            'platform' => $validated['platform'] ?: null,
            'raw_sku' => $validated['raw_sku'] ?: '',
            'raw_variant' => $validated['raw_variant'] ?: '',
        ]);

        return back()->with('success', 'Pemetaan alias berhasil ditambahkan!');
    }

    public function deleteMapping(ProductMapping $mapping)
    {
        $mapping->delete();
        return back()->with('success', 'Pemetaan alias dihapus.');
    }

    /**
     * Import Variasi / Seller SKU from CSV
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row) || !isset($row[0])) continue;
            $val = trim($row[0]);
            if ($val === '') continue;

            if (in_array(strtolower($val), ['seller sku', 'sku', 'variasi', 'variant', 'nama variasi'])) {
                continue;
            }

            $var = MasterVariant::firstOrCreate(['name' => $val]);
            if ($var->wasRecentlyCreated) {
                $imported++;
            } else {
                $skipped++;
            }
        }
        fclose($handle);

        return back()->with('success', "Berhasil mengimpor {$imported} Variasi / Seller SKU baru dari file CSV! ({$skipped} sudah ada di kamus).");
    }
}
