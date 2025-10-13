<?php
/**
 * Setup script for autocomplete test data
 * Run with: php artisan tinker setup-autocomplete-test-data.php
 */

use App\User;
use App\Group;
use App\Party;
use App\Device;
use App\Category;

echo "Setting up autocomplete test data...\n";

// Find or create a test user (admin role)
$testUser = User::where('email', 'test@restarters.test')->first();
if (!$testUser) {
    echo "Creating test user...\n";
    $testUser = User::create([
        'name' => 'Test User',
        'email' => 'test@restarters.test',
        'password' => bcrypt('password'),
        'role' => 2, // Admin role
        'invites' => 1,
        'country' => 'GB',
        'city' => 'London',
    ]);
}

// Find or create a test group
$testGroup = Group::where('name', 'Autocomplete Test Group')->first();
if (!$testGroup) {
    echo "Creating test group...\n";
    $testGroup = Group::create([
        'name' => 'Autocomplete Test Group',
        'location' => 'London, UK',
        'latitude' => 51.5074,
        'longitude' => -0.1278,
        'free_text' => 'Test group for autocomplete functionality',
    ]);
}

// Find or create a test event
$testEvent = Party::where('venue', 'Autocomplete Test Venue')->first();
if (!$testEvent) {
    echo "Creating test event...\n";
    $testEvent = Party::create([
        'venue' => 'Autocomplete Test Venue',
        'location' => 'London, UK',
        'latitude' => 51.5074,
        'longitude' => -0.1278,
        'event_start_utc' => now()->subDays(1)->setTime(10, 0),
        'event_end_utc' => now()->subDays(1)->setTime(16, 0),
        'group' => $testGroup->idgroups,
        'free_text' => 'Test event for autocomplete functionality',
        'wordpress_post_id' => 1,
    ]);
}

// Get categories for mapping
$categories = Category::all()->keyBy('name');

// Test data: item types and their expected suggested categories
$testCases = [
    ['itemType' => 'Food processor', 'expectedCategory' => 'Small kitchen item', 'powered' => true],
    ['itemType' => 'Blender', 'expectedCategory' => 'Small kitchen item', 'powered' => true],
    ['itemType' => 'TV', 'expectedCategory' => 'Flat screen 32-37"', 'powered' => true],
    ['itemType' => 'Phone', 'expectedCategory' => 'Mobile', 'powered' => true],
    ['itemType' => 'Printer', 'expectedCategory' => 'Printer/scanner', 'powered' => true],
    ['itemType' => 'Television', 'expectedCategory' => 'Flat screen 32-37"', 'powered' => true],
    ['itemType' => 'Télévision', 'expectedCategory' => 'Flat screen 32-37"', 'powered' => true],
    ['itemType' => 'Toaster', 'expectedCategory' => 'Toaster', 'powered' => true],
    ['itemType' => 'Microwave oven', 'expectedCategory' => 'Misc', 'powered' => true],
    ['itemType' => 'Heater', 'expectedCategory' => 'Misc', 'powered' => true],
];

$deviceCount = 0;

// Create the expected mappings (3 devices each to ensure they win the count algorithm)
foreach ($testCases as $testCase) {
    echo "Creating 3 devices for '{$testCase['itemType']}' → '{$testCase['expectedCategory']}'\n";

    // Find category that matches both name AND powered status
    $category = Category::where('name', $testCase['expectedCategory'])
                        ->where('powered', $testCase['powered'] ? 1 : 0)
                        ->first();
    if (!$category) {
        echo "Warning: Category '{$testCase['expectedCategory']}' (powered={$testCase['powered']}) not found, using first category\n";
        $category = $categories->first();
    }
    
    for ($i = 0; $i < 3; $i++) {
        Device::create([
            'category' => $category->idcategories,
            'category_creation' => $category->idcategories,
            'estimate' => 100,
            'item_type' => $testCase['itemType'],
            'brand' => 'Test Brand',
            'model' => 'Test Model ' . ($i + 1),
            'age' => 5,
            'repair_status' => 1, // Fixed
            'spare_parts' => 1,
            'event' => $testEvent->idevents,
            'problem' => 'Test problem description',
            'wiki' => 1,
            'do_it_yourself' => 0,
        ]);
        $deviceCount++;
    }
}

// Create some conflicting data with fewer items to ensure our expected categories win
$conflictingData = [
    ['itemType' => 'Food processor', 'category' => 'Misc', 'count' => 2],
    ['itemType' => 'TV', 'category' => 'Flat screen 15-17"', 'count' => 1],
    ['itemType' => 'Phone', 'category' => 'Handheld entertainment device', 'count' => 2],
    ['itemType' => 'Printer', 'category' => 'PC accessory', 'count' => 1],
    ['itemType' => 'Toaster', 'category' => 'Small kitchen item', 'count' => 2],
];

foreach ($conflictingData as $conflict) {
    echo "Creating {$conflict['count']} conflicting devices for '{$conflict['itemType']}' → '{$conflict['category']}'\n";
    
    $category = $categories->get($conflict['category']);
    if (!$category) {
        echo "Warning: Category '{$conflict['category']}' not found, using first category\n";
        $category = $categories->first();
    }
    
    for ($i = 0; $i < $conflict['count']; $i++) {
        Device::create([
            'category' => $category->idcategories,
            'category_creation' => $category->idcategories,
            'estimate' => 100,
            'item_type' => $conflict['itemType'],
            'brand' => 'Conflict Brand',
            'model' => 'Conflict Model ' . ($i + 1),
            'age' => 5,
            'repair_status' => 1, // Fixed
            'spare_parts' => 1,
            'event' => $testEvent->idevents,
            'problem' => 'Conflict test problem description',
            'wiki' => 1,
            'do_it_yourself' => 0,
        ]);
        $deviceCount++;
    }
}

echo "Created {$deviceCount} test devices successfully\n";
echo "Test group ID: {$testGroup->idgroups}\n";
echo "Test event ID: {$testEvent->idevents}\n";
echo "Test data setup complete!\n";