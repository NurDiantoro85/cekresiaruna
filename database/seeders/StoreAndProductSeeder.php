<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;
use App\Models\MasterSku;
use App\Models\MasterVariant;
use App\Models\MasterProduct;
use App\Models\ProductMapping;

class StoreAndProductSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Stores
        $stores = [
            [
                'name' => 'Aruna Sportswear - Shopee',
                'brand' => 'Aruna',
                'platform' => 'Shopee',
                'courier' => 'SPX Express',
                'sender_keywords' => 'aruna',
            ],
            [
                'name' => 'Aruna Sportswear - TikTok / Tokopedia',
                'brand' => 'Aruna',
                'platform' => 'TikTok Shop',
                'courier' => 'J&T Express',
                'sender_keywords' => 'aruna',
            ],
            [
                'name' => 'Moveda Activewear - Shopee',
                'brand' => 'Moveda',
                'platform' => 'Shopee',
                'courier' => 'SPX Express',
                'sender_keywords' => 'moveda, bandana buff',
            ],
            [
                'name' => 'Moveda Activewear (TikTok Shop/Tokped)',
                'brand' => 'Moveda',
                'platform' => 'TikTok Shop',
                'courier' => 'J&T Express',
                'sender_keywords' => 'bandana buff, bandana buff outdoor, moveda',
            ],
        ];

        foreach ($stores as $s) {
            Store::firstOrCreate(['name' => $s['name']], $s);
        }

        // 2. Master SKUs (Kode Produk / Kategori Utama)
        $skus = ['Bandana', 'Headband', 'Reflektive', 'ArmSleeve', 'Slayer', 'Headwear'];
        foreach ($skus as $sku) {
            MasterSku::firstOrCreate(['name' => $sku]);
        }

        // 3. Master Variasi / Seller SKU (Disatukan: Warna, Motif, atau Seller SKU)
        $variants = [
            "2 in 1 Long Size L",
            "2 in 1 Long Size XL",
            "2 in 1 Short Size L",
            "2 in 1 Short Size XL",
            "Arm Black Hole",
            "Arm Blue Mozaik",
            "Arm Burned Spirit",
            "Arm Cream",
            "Arm Gray",
            "Arm Gray Rain",
            "Arm Grey Waves",
            "Arm Japanese",
            "Arm Marlin",
            "Arm Navy",
            "Arm Red",
            "Arm Red Tomato",
            "Arm Reflective",
            "Arm Soil Black",
            "Arm Tuna",
            "Basic Short Black L",
            "Basic Short Black M",
            "Basic Short Black XL",
            "Basic Short Black XXL",
            "Belt Army",
            "Belt Gray",
            "Belt Hitam",
            "Blue Seaweed",
            "Cat Series",
            "Claw Blue",
            "Earthquake",
            "Equator",
            "Flower Cream",
            "Flower Pink",
            "Gray Rain",
            "Grey Waves",
            "Hdbn Claw Blue",
            "Hdbn Earthquake",
            "Hdbn Flower Cream",
            "Hdbn Flower Pink",
            "Hdbn Gray Rain",
            "Hdbn Grey Waves",
            "Hdbn Line Gray",
            "Hdbn Merah Putih",
            "Hdbn Red Devil",
            "Hdbn Seaweed",
            "Hdbn Solid Black",
            "Hdbn Splash Pink",
            "Hdbn Splash Tosca",
            "Hdbn Sweet Pink",
            "Japanese",
            "Jersey Abstrac Gray",
            "Jersey Red Devil",
            "Lavender",
            "Leg Sleeve Black",
            "Marlin",
            "Ocean Waves",
            "One Piece",
            "Outer Black Jumbo",
            "Outer Black Reguler",
            "Outer Pink Jumbo",
            "Outer Pink Reguler",
            "Outer White Jumbo",
            "Outer White Reguler",
            "Rainbow Smoke",
            "Red Devil",
            "Red Tomato",
            "Running Belt L",
            "Running Belt M",
            "Running Belt Size L",
            "Running Belt Size M",
            "Running Belt Size XL",
            "Running Belt XL",
            "Set Gray Rain",
            "Set Red Tomato",
            "Set Tuna",
            "Slayer Black Wolf",
            "Slayer Blue Brown",
            "Slayer Chess Spirit",
            "Slayer Deep Blue",
            "Slayer Leopart",
            "Slayer Mahagoni",
            "Slayer Red",
            "Slayer Soft Pink",
            "Slayer White Smoke",
            "Sleeveless L",
            "Sleeveless M",
            "Sleeveless XL",
            "Socks Anti Slip",
            "Socks Black Stripes",
            "Socks White Stripes",
            "Splash Brown",
            "Splash Green",
            "Starlight",
            "Sweet Pink",
            "Tuna",
            "Volcano",
            "Yellow Leaf"
        ];
        foreach ($variants as $var) {
            MasterVariant::firstOrCreate(['name' => $var]);
        }

        // 4. Master Products (Hanya menggunakan SKU dan Variasi/Seller SKU)
        $products = [
            [
                'sku' => 'Bandana',
                'variant' => 'Cat Series',
                'stodo_code' => 'ARN-BDN-CAT',
                'name' => 'Bandana Bundling Paket Hemat',
                'brand' => 'Aruna',
                'mappings' => [
                    ['platform' => 'Shopee', 'raw_sku' => 'Bandana', 'raw_variant' => 'Cat Series'],
                    ['platform' => 'TikTok Shop', 'raw_sku' => 'Bandana', 'raw_variant' => 'Cat Series'],
                ]
            ],
            [
                'sku' => 'Bandana',
                'variant' => 'Gray Rain',
                'stodo_code' => 'ARN-BDN-GRAY',
                'name' => 'Bandana Bundling Paket Hemat',
                'brand' => 'Aruna',
                'mappings' => [
                    ['platform' => 'Shopee', 'raw_sku' => 'Bandana', 'raw_variant' => 'Gray Rain'],
                    ['platform' => 'TikTok Shop', 'raw_sku' => 'Bandana', 'raw_variant' => 'Gray Rain'],
                ]
            ],
            [
                'sku' => 'Bandana',
                'variant' => 'Claw Blue',
                'stodo_code' => 'ARN-BDN-CLAW',
                'name' => 'Bandana Bundling Paket Hemat',
                'brand' => 'Aruna',
                'mappings' => [
                    ['platform' => 'Shopee', 'raw_sku' => 'Bandana', 'raw_variant' => 'Claw Blue'],
                    ['platform' => 'TikTok Shop', 'raw_sku' => 'Bandana', 'raw_variant' => 'Claw Blue'],
                ]
            ],
            [
                'sku' => 'Bandana',
                'variant' => 'Red Tomato',
                'stodo_code' => 'ARN-BDN-TOMATO',
                'name' => 'Bandana Bundling Paket Hemat',
                'brand' => 'Aruna',
                'mappings' => [
                    ['platform' => 'Shopee', 'raw_sku' => 'Bandana', 'raw_variant' => 'Red Tomato'],
                ]
            ],
            [
                'sku' => 'Bandana',
                'variant' => 'One Piece',
                'stodo_code' => 'ARN-BDN-ONEPIECE',
                'name' => 'Bandana Bundling Paket Hemat',
                'brand' => 'Aruna',
                'mappings' => [
                    ['platform' => 'Shopee', 'raw_sku' => 'Bandana', 'raw_variant' => 'One Piece'],
                ]
            ],
            [
                'sku' => 'Bandana',
                'variant' => 'Japanese',
                'stodo_code' => 'ARN-BDN-JPN',
                'name' => 'Bandana Bundling Paket Hemat',
                'brand' => 'Aruna',
                'mappings' => [
                    ['platform' => 'TikTok Shop', 'raw_sku' => 'Bandana', 'raw_variant' => 'Japanese'],
                ]
            ],
            [
                'sku' => 'Reflektive',
                'variant' => 'Default',
                'stodo_code' => 'ARN-REF-DFT',
                'name' => 'Aruna Arm Relfektive Safety Band',
                'brand' => 'Aruna',
                'mappings' => [
                    ['platform' => 'TikTok Shop', 'raw_sku' => 'Reflektive', 'raw_variant' => 'Default'],
                ]
            ],
            [
                'sku' => 'Headband',
                'variant' => 'Hdbn Seaweed',
                'stodo_code' => 'ARN-HDB-SEA',
                'name' => 'Aruna Headband Olahraga Quick Dry',
                'brand' => 'Aruna',
                'mappings' => [
                    ['platform' => 'Shopee', 'raw_sku' => 'HeadbandSe aweed', 'raw_variant' => ''],
                    ['platform' => 'Shopee', 'raw_sku' => 'Headband', 'raw_variant' => 'Seaweed'],
                    ['platform' => 'Shopee', 'raw_sku' => 'Headband', 'raw_variant' => 'Hdbn Seaweed'],
                ]
            ],
            [
                'sku' => 'Headband',
                'variant' => 'Hdbn Gray Rain',
                'stodo_code' => 'ARN-HDB-GRAY',
                'name' => 'Aruna Headband Olahraga Quick Dry',
                'brand' => 'Aruna',
                'mappings' => [
                    ['platform' => 'Shopee', 'raw_sku' => 'Headband', 'raw_variant' => 'Hdbn Gray Rain'],
                ]
            ],
            [
                'sku' => 'Headwear',
                'variant' => 'BandanaSoilBlack',
                'stodo_code' => 'ARN-MSK-SOIL',
                'name' => 'Aruna Headwear Masker Bandana Stretch',
                'brand' => 'Aruna',
                'mappings' => [
                    ['platform' => 'TikTok Shop', 'raw_sku' => 'BandanaSoilBlack', 'raw_variant' => 'Default'],
                ]
            ],
            [
                'sku' => 'ArmSleeve',
                'variant' => 'Arm Soil Black',
                'stodo_code' => 'ARN-ARM-SOIL',
                'name' => 'Aruna Manset Tangan Arm Sleeve',
                'brand' => 'Aruna',
                'mappings' => [
                    ['platform' => 'TikTok Shop', 'raw_sku' => 'ArmSoilBlack', 'raw_variant' => 'Default'],
                ]
            ],
            [
                'sku' => 'ArmSleeve',
                'variant' => 'Arm Gray Rain',
                'stodo_code' => 'ARN-ARM-GRAY',
                'name' => 'Aruna Manset Tangan Arm Sleeve',
                'brand' => 'Aruna',
                'mappings' => [
                    ['platform' => 'TikTok Shop', 'raw_sku' => 'ArmGrayRain', 'raw_variant' => 'Default'],
                ]
            ],
            [
                'sku' => 'Slayer',
                'variant' => 'Slayer Blue Brown',
                'stodo_code' => 'ARN-SLY-BLUBRN',
                'name' => 'Aruna Slayer Bandana Paisley',
                'brand' => 'Aruna',
                'mappings' => [
                    ['platform' => 'TikTok Shop', 'raw_sku' => 'SlayerBlueBrown', 'raw_variant' => 'Default'],
                ]
            ],
        ];

        // Clear and re-populate
        MasterProduct::query()->delete();
        ProductMapping::query()->delete();

        foreach ($products as $pData) {
            $mappings = $pData['mappings'];
            unset($pData['mappings']);

            $product = MasterProduct::create($pData);

            foreach ($mappings as $m) {
                ProductMapping::firstOrCreate([
                    'master_product_id' => $product->id,
                    'platform' => $m['platform'],
                    'raw_sku' => $m['raw_sku'],
                    'raw_variant' => $m['raw_variant'] ?? '',
                ]);
            }
        }
    }
}
