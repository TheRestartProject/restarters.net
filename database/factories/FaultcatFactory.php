<?php

use Faker\Generator as Faker;

$factory->define(App\Faultcat::class, function (Faker $faker, $attributes) {
    return [];
});

$factory->state(App\Device::class, 'desktop', function (Faker $faker) {
    return [
        'category' => 11,
        'category_creation' => 11,
    ];
});

$factory->state(App\Device::class, 'laptop large', function (Faker $faker) {
    return [
        'category' => 15,
        'category_creation' => 15,
    ];
});

$factory->state(App\Device::class, 'laptop medium', function (Faker $faker) {
    return [
        'category' => 16,
        'category_creation' => 16,
    ];
});

$factory->state(App\Device::class, 'laptop small', function (Faker $faker) {
    return [
        'category' => 17,
        'category_creation' => 17,
    ];
});

$factory->state(App\Device::class, 'tablet', function (Faker $faker) {
    return [
        'category' => 26,
        'category_creation' => 26,
    ];
});
