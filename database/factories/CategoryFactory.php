<?php

use Faker\Generator as Faker;

$factory->define(App\Category::class, function (Faker $faker) {
    return [];
});

$factory->state(App\Category::class, 'Misc', function (Faker $faker) {
    return [
        'idcategories' => env('MISC_CATEGORY_ID_POWERED'),
        'name' => 'Misc',
        'revision' => 1,
        'weight' => 1,
        'powered' => 1
    ];
});

$factory->state(App\Category::class, 'Desktop computer', function (Faker $faker) {
    return [
        'idcategories' => 11,
        'name' => 'Desktop computer',
        'revision' => 1,
        'weight' => 10,
        'powered' => 1
    ];
});

$factory->state(App\Category::class, 'Mobile', function (Faker $faker) {
    return [
        'idcategories' => 25,
        'name' => 'Mobile',
        'revision' => 1,
        'footprint' => 1,
        'weight' => 0.2,
        'powered' => 1
    ];
});

$factory->state(App\Category::class, 'Cat1', function (Faker $faker) {
    return [
        'idcategories' => 111,
        'name' => 'Cat1',
        'revision' => 1,
        'footprint' => 1.1,
        'weight' => 1,
        'powered' => 1
    ];
});

$factory->state(App\Category::class, 'Cat2', function (Faker $faker) {
    return [
        'idcategories' => 222,
        'name' => 'Cat2',
        'revision' => 1,
        'footprint' => 2.2,
        'weight' => 2,
        'powered' => 1
    ];
});

$factory->state(App\Category::class, 'Cat3', function (Faker $faker) {
    return [
        'idcategories' => 333,
        'name' => 'Cat3',
        'revision' => 1,
        'footprint' => 3.3,
        'weight' => 3,
        'powered' => 1
    ];
});

$factory->state(App\Category::class, 'MiscU', function (Faker $faker) {
    return [
        'idcategories' => env('MISC_CATEGORY_ID_UNPOWERED'),
        'name' => 'Misc',
        'revision' => 1,
        'powered' => 0,
        'weight' => 1,
    ];
});

$factory->state(App\Category::class, 'Cat4', function (Faker $faker) {
    return [
        'idcategories' => 444,
        'name' => 'Cat4',
        'revision' => 1,
        'powered' => 0
    ];
});

$factory->state(App\Category::class, 'Cat5', function (Faker $faker) {
    return [
        'idcategories' => 555,
        'name' => 'Cat5',
        'revision' => 1,
        'powered' => 0
    ];
});

$factory->state(App\Category::class, 'Cat6', function (Faker $faker) {
    return [
        'idcategories' => 666,
        'name' => 'Cat6',
        'revision' => 1,
        'powered' => 0
    ];
});