<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\WarehouseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create user first so we can assign roles
        User::factory()->create([
            'name' => 'Super Admin',
            'username' => 'admin',
            'email' => 'buryar313@gmail.com',
            'password' => \Hash::make('password'),
        ]);

        $this->call([
            CategorySeeder::class,
            WarehouseSeeder::class,
        ]);
        
        // User::factory(10)->create();
    }
}
