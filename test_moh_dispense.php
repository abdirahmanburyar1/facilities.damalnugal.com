<?php

// Quick MOH Dispense Test Script
// Run with: php test_moh_dispense.php

require_once 'vendor/autoload.php';

// Test 1: Check if classes exist
echo "=== Testing Class Availability ===\n";
$classes = [
    'App\Services\MohDispenseInventoryService',
    'App\Notifications\InsufficientInventoryNotification',
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "✓ {$class} exists\n";
    } else {
        echo "✗ {$class} NOT found\n";
    }
}

// Test 2: Check if methods exist
echo "\n=== Testing Method Availability ===\n";
$service = new App\Services\MohDispenseInventoryService();
$methods = ['processMohDispense', 'validateInventory'];

foreach ($methods as $method) {
    if (method_exists($service, $method)) {
        echo "✓ MohDispenseInventoryService::{$method}() exists\n";
    } else {
        echo "✗ MohDispenseInventoryService::{$method}() NOT found\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "If all checks pass, you can proceed with functional testing.\n";
