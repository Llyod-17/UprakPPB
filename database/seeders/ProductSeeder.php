<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // 3 Kategori
        $categories = [
            ['name' => 'Elektronik',  'slug' => 'elektronik',  'description' => 'Produk elektronik dan gadget'],
            ['name' => 'Pakaian',     'slug' => 'pakaian',     'description' => 'Pakaian pria dan wanita'],
            ['name' => 'Makanan',     'slug' => 'makanan',     'description' => 'Makanan dan minuman'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insertOrIgnore([
                ...$category,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $elektronikId = DB::table('categories')->where('slug', 'elektronik')->value('id');
        $pakaianId    = DB::table('categories')->where('slug', 'pakaian')->value('id');
        $makananId    = DB::table('categories')->where('slug', 'makanan')->value('id');

        // 10 Produk
        $products = [
            // Elektronik
            ['category_id' => $elektronikId, 'name' => 'Smartphone X1',     'price' => 3500000, 'stock' => 20],
            ['category_id' => $elektronikId, 'name' => 'Laptop Pro 14',      'price' => 12000000, 'stock' => 10],
            ['category_id' => $elektronikId, 'name' => 'Headphone Wireless', 'price' => 450000,  'stock' => 35],
            ['category_id' => $elektronikId, 'name' => 'Smartwatch Z2',      'price' => 1200000, 'stock' => 15],
            // Pakaian
            ['category_id' => $pakaianId,    'name' => 'Kaos Polos Hitam',   'price' => 85000,   'stock' => 100],
            ['category_id' => $pakaianId,    'name' => 'Kemeja Flannel',      'price' => 175000,  'stock' => 60],
            ['category_id' => $pakaianId,    'name' => 'Celana Chino Slim',   'price' => 220000,  'stock' => 50],
            // Makanan
            ['category_id' => $makananId,    'name' => 'Kopi Arabika 250g',   'price' => 65000,   'stock' => 200],
            ['category_id' => $makananId,    'name' => 'Coklat Premium Box',  'price' => 120000,  'stock' => 80],
            ['category_id' => $makananId,    'name' => 'Granola Oat 500g',    'price' => 95000,   'stock' => 150],
        ];

        foreach ($products as $product) {
            DB::table('products')->insertOrIgnore([
                ...$product,
                'slug'        => Str::slug($product['name']),
                'description' => 'Deskripsi untuk ' . $product['name'],
                'foto'        => null,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}