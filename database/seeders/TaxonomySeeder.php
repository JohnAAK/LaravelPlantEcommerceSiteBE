<?php
// database/seeders/TaxonomySeeder.php
use App\Models\Category;
use App\Models\Attribute;

class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        $categories = ['Seeds', 'Common Plants', 'Rare Plants', 'Flowers', 'Decorative Plants', 'Herbs'];
        foreach ($categories as $cat) {
            Category::firstOrCreate(['name' => $cat]);
        }

        // Care Attributes
        $light = Attribute::firstOrCreate(['name' => 'Light Requirement']);
        foreach (['Low Light', 'Indirect Light', 'Full Sun'] as $val) {
            $light->values()->firstOrCreate(['value' => $val]);
        }

        $water = Attribute::firstOrCreate(['name' => 'Watering Frequency']);
        foreach (['Daily', 'Weekly', 'Bi-Weekly', 'Low Water'] as $val) {
            $water->values()->firstOrCreate(['value' => $val]);
        }

        $environment = Attribute::firstOrCreate(['name' => 'Environment']);
        foreach (['Indoor', 'Outdoor', 'Both'] as $val) {
            $environment->values()->firstOrCreate(['value' => $val]);
        }
    }
}