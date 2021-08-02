<?php

use Faker\Generator as Faker;

$factory->define(App\Category::class, function (Faker $faker) {
    return [];
});

$factory->state(App\Category::class, 'Misc', function (Faker $faker) {
    return [
        'idcategories' => 46,
        'name' => 'Misc',
        'revision' => 1,
        'aggregate' => 0,
    ];
});

$factory->state(App\Category::class, 'Desktop computer', function (Faker $faker) {
    return [
        'idcategories' => 11,
        'name' => 'Desktop computer',
        'revision' => 1,
        'aggregate' => 0,
    ];
});

$factory->state(App\Category::class, 'Mobile', function (Faker $faker) {
    return [
        'idcategories' => 25,
        'name' => 'Mobile',
        'revision' => 1,
        'footprint' => 1,
        'weight' => 1,
        'aggregate' => 0,
    ];
});

$factory->state(App\Category::class, 'Cat1', function (Faker $faker) {
    return [
        'idcategories' => 111,
        'name' => 'Cat1',
        'revision' => 1,
        'footprint' => 1,
        'weight' => 1,
        'aggregate' => 0,
    ];
});

$factory->state(App\Category::class, 'Cat2', function (Faker $faker) {
    return [
        'idcategories' => 222,
        'name' => 'Cat2',
        'revision' => 1,
        'footprint' => 2,
        'weight' => 2,
        'aggregate' => 0,
    ];
});

$factory->state(App\Category::class, 'Cat3', function (Faker $faker) {
    return [
        'idcategories' => 333,
        'name' => 'Cat3',
        'revision' => 1,
        'footprint' => 3,
        'weight' => 3,
        'aggregate' => 0,
    ];
});
