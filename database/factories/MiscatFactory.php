<?php

use Faker\Generator as Faker;

$factory->define(App\Misccat::class, function (Faker $faker) {
    return [];
});

$factory->state(App\Misccat::class, 'misc', function (Faker $faker) {
    return [
        'category' => 'Misc',
    ];
});

$factory->state(App\Misccat::class, 'mobile', function (Faker $faker) {
    return [
        'category' => 'Mobile',
    ];
});

$factory->state(App\Misccat::class, 'cat1', function (Faker $faker) {
    return [
        'category' => 'cat1',
    ];
});

$factory->state(App\Misccat::class, 'cat2', function (Faker $faker) {
    return [
        'category' => 'cat2',
    ];
});

$factory->state(App\Misccat::class, 'cat3', function (Faker $faker) {
    return [
        'category' => 'cat3',
    ];
});
