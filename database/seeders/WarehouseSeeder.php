<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get category IDs
        $generalStorageId = Category::where('name', 'General Storage')->first()->id ?? null;
        $coldStorageId = Category::where('name', 'Cold Storage')->first()->id ?? null;
        $hazardousId = Category::where('name', 'Hazardous Materials')->first()->id ?? null;
        $ecommerceId = Category::where('name', 'E-commerce')->first()->id ?? null;
        $distributionId = Category::where('name', 'Distribution Center')->first()->id ?? null;

        $warehouses = [
            [
                'name' => 'Garowe Central Warehouse',
                'code' => 'GCW001',
                'address' => 'Main Street, Garowe',
                'city' => 'Garowe',
                'state' => 'Nugal',
                'country' => 'Somalia',
                'postal_code' => '00100',
                'manager_name' => 'Ahmed Mohamed',
                'manager_email' => 'ahmed.mohamed@example.com',
                'manager_phone' => '+252 61 123 4567',
                'latitude' => 8.4054,
                'longitude' => 48.5342,
                'capacity' => 15000,
                'temperature_min' => 20,
                'temperature_max' => 35,
                'humidity_min' => 30,
                'humidity_max' => 60,
                'status' => 'active',
                'has_cold_storage' => false,
                'has_hazardous_storage' => false,
                'is_active' => true,
                'notes' => 'Main distribution hub for Nugal region',
            ],
            [
                'name' => 'Eyl Cold Storage Facility',
                'code' => 'ECS002',
                'address' => 'Port Road, Eyl',
                'city' => 'Eyl',
                'state' => 'Nugal',
                'country' => 'Somalia',
                'postal_code' => '00200',
                'manager_name' => 'Fatima Hassan',
                'manager_email' => 'fatima.hassan@example.com',
                'manager_phone' => '+252 61 234 5678',
                'latitude' => 7.9803,
                'longitude' => 49.8164,
                'capacity' => 8000,
                'temperature_min' => -5,
                'temperature_max' => 5,
                'humidity_min' => 70,
                'humidity_max' => 85,
                'status' => 'active',
                'has_cold_storage' => true,
                'has_hazardous_storage' => false,
                'is_active' => true,
                'notes' => 'Specialized cold storage facility for seafood and perishable goods from the coast',
            ],
            [
                'name' => 'Burtinle Distribution Center',
                'code' => 'BDC003',
                'address' => 'Central Avenue, Burtinle',
                'city' => 'Burtinle',
                'state' => 'Nugal',
                'country' => 'Somalia',
                'postal_code' => '00300',
                'manager_name' => 'Omar Abdi',
                'manager_email' => 'omar.abdi@example.com',
                'manager_phone' => '+252 61 345 6789',
                'latitude' => 8.9911,
                'longitude' => 48.6950,
                'capacity' => 12000,
                'temperature_min' => 20,
                'temperature_max' => 35,
                'humidity_min' => 25,
                'humidity_max' => 50,
                'status' => 'active',
                'has_cold_storage' => false,
                'has_hazardous_storage' => false,
                'is_active' => true,
                'notes' => 'General purpose warehouse serving the western Nugal region',
            ],
            [
                'name' => 'Dangorayo Agricultural Storage',
                'code' => 'DAS004',
                'address' => 'Farm Road, Dangorayo',
                'city' => 'Dangorayo',
                'state' => 'Nugal',
                'country' => 'Somalia',
                'postal_code' => '00400',
                'manager_name' => 'Amina Yusuf',
                'manager_email' => 'amina.yusuf@example.com',
                'manager_phone' => '+252 61 456 7890',
                'latitude' => 8.1383,
                'longitude' => 48.6828,
                'capacity' => 10000,
                'temperature_min' => 18,
                'temperature_max' => 30,
                'humidity_min' => 30,
                'humidity_max' => 55,
                'status' => 'active',
                'has_cold_storage' => false,
                'has_hazardous_storage' => false,
                'is_active' => true,
                'notes' => 'Specialized for agricultural product storage and distribution',
            ],
            [
                'name' => 'Garowe E-commerce Fulfillment',
                'code' => 'GEF005',
                'address' => 'Technology Park, Garowe',
                'city' => 'Garowe',
                'state' => 'Nugal',
                'country' => 'Somalia',
                'postal_code' => '00100',
                'manager_name' => 'Ibrahim Ali',
                'manager_email' => 'ibrahim.ali@example.com',
                'manager_phone' => '+252 61 567 8901',
                'latitude' => 8.4104,
                'longitude' => 48.5392,
                'capacity' => 7500,
                'temperature_min' => 20,
                'temperature_max' => 30,
                'humidity_min' => 35,
                'humidity_max' => 55,
                'status' => 'active',
                'has_cold_storage' => false,
                'has_hazardous_storage' => false,
                'is_active' => true,
                'notes' => 'Modern facility for e-commerce order fulfillment with digital inventory management',
            ],
            [
                'name' => 'Garowe Medical Supplies',
                'code' => 'GMS006',
                'address' => 'Hospital Road, Garowe',
                'city' => 'Garowe',
                'state' => 'Nugal',
                'country' => 'Somalia',
                'postal_code' => '00100',
                'manager_name' => 'Sahra Mohamed',
                'manager_email' => 'sahra.mohamed@example.com',
                'manager_phone' => '+252 61 678 9012',
                'latitude' => 8.4154,
                'longitude' => 48.5242,
                'capacity' => 5000,
                'temperature_min' => 15,
                'temperature_max' => 25,
                'humidity_min' => 40,
                'humidity_max' => 60,
                'status' => 'active',
                'has_cold_storage' => true,
                'has_hazardous_storage' => true,
                'is_active' => true,
                'notes' => 'Specialized facility for storing medical supplies and pharmaceuticals with temperature control',
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::create($warehouse);
        }
    }
}
