<?php

use Faker\Generator as Faker;

$factory->define(App\Category::class, function (Faker $faker) {
    return [];
});

$factory->state(App\Category::class, 'Misc', function (Faker $faker) {
    return [
        'idcategories' => 46,
        'name' => 'Misc',
    ];
});

$factory->state(App\Category::class, 'Mobile', function (Faker $faker) {
    return [
        'idcategories' => 25,
        'name' => 'Mobile',
    ];
});

$factory->state(App\Category::class, 'Cat1', function (Faker $faker) {
    return [
        'idcategories' => 111,
        'name' => 'Cat1',
    ];
});

$factory->state(App\Category::class, 'Cat2', function (Faker $faker) {
    return [
        'idcategories' => 222,
        'name' => 'Cat2',
    ];
});

$factory->state(App\Category::class, 'Cat3', function (Faker $faker) {
    return [
        'idcategories' => 333,
        'name' => 'Cat3',
    ];
});

