<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles & Core Users
        $admin = User::factory()->create([
            'name'     => 'System Admin',
            'email'    => 'admin@plantmarket.com',
            'password' => bcrypt('password'),
            'role'     => 'admin',
        ]);

        $buyer = User::factory()->create([
            'name'     => 'Jane Doe',
            'email'    => 'buyer@plantmarket.com',
            'password' => bcrypt('password'),
            'role'     => 'buyer',
        ]);

        // 2. Categories
        $indoorCategory = Category::create([
            'name' => 'Indoor Plants',
            'slug' => 'indoor-plants',
        ]);

        $succulentCategory = Category::create([
            'name' => 'Succulents & Cacti',
            'slug' => 'succulents-cacti',
        ]);

        // 3. Plant Attributes & Values
        $lightAttr = Attribute::create([
            'name' => 'Light Requirement',
            'slug' => 'light-requirement',
        ]);

        $brightLight = AttributeValue::create([
            'attribute_id' => $lightAttr->id,
            'value'        => 'Bright Indirect Light',
            'slug'         => 'bright-indirect-light',
        ]);

        $lowLight = AttributeValue::create([
            'attribute_id' => $lightAttr->id,
            'value'        => 'Low Light Tolerant',
            'slug'         => 'low-light-tolerant',
        ]);

        // 4. Vendors & Stores
        $vendor1 = User::factory()->create([
            'name'     => 'Green Thumb Nursery',
            'email'    => 'vendor1@plantmarket.com',
            'password' => bcrypt('password'),
            'role'     => 'vendor',
        ]);

        $store1 = Store::create([
            'user_id' => $vendor1->id,
            'name'    => 'Botanical Haven',
            'slug'    => 'botanical-haven',
            'phone'   => '+1234567890',
            'address' => '123 Flora St, Garden City',
            'status'  => 'approved',
        ]);

        $vendor2 = User::factory()->create([
            'name'     => 'Desert Oasis Botanicals',
            'email'    => 'vendor2@plantmarket.com',
            'password' => bcrypt('password'),
            'role'     => 'vendor',
        ]);

        $store2 = Store::create([
            'user_id' => $vendor2->id,
            'name'    => 'Desert Oasis',
            'slug'    => 'desert-oasis',
            'phone'   => '+0987654321',
            'address' => '456 Cactus Rd, Arid Springs',
            'status'  => 'approved',
        ]);

        // 5. Products
        $monstera = Product::create([
            'store_id'    => $store1->id,
            'category_id' => $indoorCategory->id,
            'name'        => 'Monstera Deliciosa',
            'slug'        => 'monstera-deliciosa',
            'description' => 'Iconic tropical plant with signature natural leaf splits.',
            'price'       => 45.00,
            'stock'       => 20,
            'is_active'   => true,
        ]);
        $monstera->attributeValues()->attach([$brightLight->id]);

        $snakePlant = Product::create([
            'store_id'    => $store2->id,
            'category_id' => $succulentCategory->id,
            'name'        => 'Snake Plant (Sansevieria)',
            'slug'        => 'snake-plant-sansevieria',
            'description' => 'Hardy, air-purifying plant virtually impossible to kill.',
            'price'       => 25.00,
            'stock'       => 35,
            'is_active'   => true,
        ]);
        $snakePlant->attributeValues()->attach([$lowLight->id]);
    }
}