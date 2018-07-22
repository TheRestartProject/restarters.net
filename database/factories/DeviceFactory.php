<?php

use App\Category;
use App\Party;

use Faker\Generator as Faker;

$factory->define(App\Device::class, function (Faker $faker, $attributes) {
    return [
        'event' => factory(Party::class)->create()->idevents,
        'category' => 11,
        'category_creation' => 11,
    ];
});

$factory->state(App\Device::class, 'misc', function (Faker $faker) {
    return [
        'category' => 46,
        'category_creation' => 46,
    ];
});

$factory->state(App\Device::class, 'mobile', function (Faker $faker) {
    return [
        'category' => 25,
        'category_creation' => 25,
    ];
});

$factory->state(App\Device::class, 'fixed', function (Faker $faker) {
    return [
        'repair_status' => 1,
    ];
});

$factory->state(App\Device::class, 'repairable', function (Faker $faker) {
    return [
        'repair_status' => 2,
    ];
});

$factory->state(App\Device::class, 'end', function (Faker $faker) {
    return [
        'repair_status' => 2,
    ];
});
