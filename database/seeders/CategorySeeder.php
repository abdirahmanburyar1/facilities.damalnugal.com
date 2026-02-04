<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Tablet', 'description' => 'Dosage form: tablet'],
            ['name' => 'Capsule', 'description' => 'Dosage form: capsule'],
            ['name' => 'Syrup', 'description' => 'Dosage form: syrup'],
            ['name' => 'Ointment', 'description' => 'Dosage form: ointment'],
            ['name' => 'Injection', 'description' => 'Dosage form: injection']            
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        
        $dosages = [
            ['name' => 'mg', 'description' => 'milligram', 'category_id' => 1],
            ['name' => 'ml', 'description' => 'milliliter', 'category_id' => 3],
            ['name' => 'units', 'description' => 'units', 'category_id' => 1],
            ['name' => 'mcg', 'description' => 'microgram', 'category_id' => 2],
            ['name' => 'g', 'description' => 'gram', 'category_id' => 4],
        ];

        \App\Models\Dosage::insert($dosages);

        
        $faker = \Faker\Factory::create();

        $products = [];
        for ($i = 0; $i < 50; $i++) {
            $products[] = [
                'name' => $faker->unique()->word,
                'sku' => $faker->ean13,
                'barcode' => $faker->ean13,
                'description' => $faker->sentence,
                'category_id' => rand(1, 5),
                'dosage_id' => rand(1, 5),
                'is_active' => rand(0, 1),
            ];
        }

        \App\Models\Product::insert($products);

    }
}
