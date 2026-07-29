<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category; 
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
   {
    $categories = [
        'Seeds', 
        'Common Plants', 
        'Rare Plants', 
        'Flowers', 
        'Decorative Plants', 
        'Herbs'
    ];

    foreach ($categories as $name) {
        Category::create([
            'name' => $name,
            'slug' => Str::slug($name)
        ]);
    }
}
}