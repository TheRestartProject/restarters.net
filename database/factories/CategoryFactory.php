<?php

use Faker\Generator as Faker;

$factory->define(App\Category::class, function (Faker $faker) {
    return [];
});

$factory->state(App\Category::class, 'misc', function (Faker $faker) {
    return [
        'idcategories' => 46,
        'name' => 'Misc',
    ];
});

$factory->state(App\Category::class, 'cat1', function (Faker $faker) {
    return [
        'idcategories' => 1,
        'name' => 'cat1',
    ];
});

$factory->state(App\Category::class, 'cat2', function (Faker $faker) {
    return [
        'idcategories' => 2,
        'name' => 'cat2',
    ];
});

$factory->state(App\Category::class, 'cat3', function (Faker $faker) {
    return [
        'idcategories' => 3,
        'name' => 'cat3',
    ];
});

