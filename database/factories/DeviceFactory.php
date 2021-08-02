<?php

use App\Category;
use App\Party;
use Faker\Generator as Faker;

$factory->define(App\Device::class, function (Faker $faker, $attributes) {
    return [
        'event' => factory(Party::class)->create()->idevents,
        'category' => 11,
        'category_creation' => 11,
        'problem' => '',
    ];
});

$factory->state(App\Device::class, 'misc', function (Faker $faker) {
    return [
        'category' => 46,
        'category_creation' => 46,
        'problem' => '',
    ];
});

$factory->state(App\Device::class, 'mobile', function (Faker $faker) {
    return [
        'category' => 25,
        'category_creation' => 25,
        'problem' => '',
    ];
});

$factory->state(App\Device::class, 'fixed', function (Faker $faker) {
    return [
        'category' => 111,
        'category_creation' => 111,
        'repair_status' => 1,
        'problem' => '',
    ];
});

$factory->state(App\Device::class, 'repairable', function (Faker $faker) {
    return [
        'category' => 111,
        'category_creation' => 111,
        'repair_status' => 2,
        'problem' => '',
    ];
});

$factory->state(App\Device::class, 'end', function (Faker $faker) {
    return [
        'category' => 111,
        'category_creation' => 111,
        'repair_status' => 2,
        'problem' => '',
    ];
});

$factory->state(App\Device::class, 'misccat', function (Faker $faker) {
    return [
        'category' => 46,
        'category_creation' => 46,
        'problem' => $faker->sentence(6, true),
    ];
});
