<?php

namespace Database\Seeders\Inventory;

use App\Models\Inventory\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['General Merchandise', 'Consulting Services'] as $name) {
            ProductCategory::updateOrCreate(['name' => $name]);
        }
    }
}
