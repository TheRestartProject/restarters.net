<?php
/**
 * Ensure all categories are seeded before autocomplete test
 * Run with: php artisan tinker ensure-categories-seeded.php
 */

use App\Category;
use Illuminate\Support\Facades\DB;

echo "Checking if all categories exist...\n";

$categoryCount = Category::count();
echo "Found {$categoryCount} categories\n";

if ($categoryCount < 40) {
    echo "Not enough categories found. Re-running category migration INSERT...\n";

    // Re-run just the category INSERT from the initial migration
    // Using INSERT IGNORE so it won't fail if some categories already exist
    DB::statement("INSERT IGNORE INTO `categories` (`idcategories`, `name`, `powered`, `weight`, `footprint`, `footprint_reliability`, `lifecycle`, `lifecycle_reliability`, `extendend_lifecycle`, `extendend_lifecycle_reliability`, `revision`, `cluster`) VALUES
                       (11, 'Desktop computer', 1, 9.15, 398.4, 5, NULL, NULL, NULL, NULL, 2, 1),
                       (12, 'Flat screen 15-17\"', 1, 2.7, 72.4, 2, NULL, NULL, NULL, NULL, 2, 1),
                       (13, 'Flat screen 19-20\"', 1, 3.72, 102.93, 5, NULL, NULL, NULL, NULL, 2, 1),
                       (14, 'Flat screen 22-24\"', 1, 5, 167.8, 5, NULL, NULL, NULL, NULL, 2, 1),
                       (15, 'Laptop large', 1, 2.755, 322.79, 5, NULL, NULL, NULL, NULL, 2, 1),
                       (16, 'Laptop medium', 1, 2.26, 258.25, 5, NULL, NULL, NULL, NULL, 2, 1),
                       (17, 'Laptop small', 1, 2.14, 142.18, 4, NULL, NULL, NULL, NULL, 2, 1),
                       (18, 'Paper shredder', 1, 7, 47.7, 2, NULL, NULL, NULL, NULL, 2, 1),
                       (19, 'PC Accessory', 1, 1.185, 18.87, 4, NULL, NULL, NULL, NULL, 2, 1),
                       (20, 'Printer/scanner', 1, 7.05, 47.7, 4, NULL, NULL, NULL, NULL, 2, 1),
                       (21, 'Digital Compact Camera', 1, 0.113, 6.13, 4, NULL, NULL, NULL, NULL, 2, 2),
                       (22, 'DLSR / Video Camera', 1, 0.27, 4.05, 4, NULL, NULL, NULL, NULL, 2, 2),
                       (23, 'Handheld entertainment device', 1, 0.149, 13, 4, NULL, NULL, NULL, NULL, 2, 2),
                       (24, 'Headphones', 1, 0.26, 4.05, 3, NULL, NULL, NULL, NULL, 2, 2),
                       (25, 'Mobile', 1, 0.14, 35.82, 4, NULL, NULL, NULL, NULL, 2, 2),
                       (26, 'Tablet', 1, 0.51, 107.76, 5, NULL, NULL, NULL, NULL, 2, 2),
                       (27, 'Flat screen 26-30\"', 1, 10.6, 284.25, 1, NULL, NULL, NULL, NULL, 2, 3),
                       (28, 'Flat screen 32-37\"', 1, 18.7, 359.4, 3, NULL, NULL, NULL, NULL, 2, 3),
                       (29, 'Hi-Fi integrated', 1, 10.9, 109.5, 3, NULL, NULL, NULL, NULL, 2, 3),
                       (30, 'Hi-Fi separates', 1, 10.9, 109.5, 4, NULL, NULL, NULL, NULL, 2, 3),
                       (31, 'Musical instrument', 1, 10.9, 109.5, 3, NULL, NULL, NULL, NULL, 2, 3),
                       (32, 'Portable radio', 1, 2.5, 66, 2, NULL, NULL, NULL, NULL, 2, 3),
                       (33, 'Projector', 1, 2.35, 46, 4, NULL, NULL, NULL, NULL, 2, 3),
                       (34, 'TV and gaming-related accessories', 1, 1, 25, 4, NULL, NULL, NULL, NULL, 2, 3),
                       (35, 'Aircon/Dehumidifier', 1, 18.5, 109.53, 2, NULL, NULL, NULL, NULL, 2, 4),
                       (36, 'Decorative or safety lights', 1, 0.015, 13.43, 4, NULL, NULL, NULL, NULL, 2, 4),
                       (37, 'Fan', 1, 0.88, 4.52, 2, NULL, NULL, NULL, NULL, 2, 4),
                       (38, 'Hair & Beauty item', 1, 0.69, 6, 4, NULL, NULL, NULL, NULL, 2, 4),
                       (39, 'Kettle', 1, 1.4, 17.1, 4, NULL, NULL, NULL, NULL, 2, 4),
                       (40, 'Lamp', 1, 0.703, 4.62, 2, NULL, NULL, NULL, NULL, 2, 4),
                       (41, 'Power tool', 1, 2.84, 26.6, 3, NULL, NULL, NULL, NULL, 2, 4),
                       (42, 'Small kitchen item', 1, 2.7, 15.8, 4, NULL, NULL, NULL, NULL, 2, 4),
                       (43, 'Toaster', 1, 1.04, 5, 2, NULL, NULL, NULL, NULL, 2, 4),
                       (44, 'Toy', 1, 1.27, 15, 4, NULL, NULL, NULL, NULL, 2, 4),
                       (45, 'Vacuum', 1, 7.78, 41, 4, NULL, NULL, NULL, NULL, 2, 4),
                       (46, 'Misc', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL)"
       );

    $newCount = Category::count();
    echo "Categories seeded. Now have {$newCount} categories\n";
} else {
    echo "All categories already exist\n";
}

echo "Category check complete!\n";
