<?php

use Faker\Generator as Faker;

$factory->define(App\Group::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->company,
        'free_text' => $faker->sentence,
        'facebook' => '',
        'postcode' => ''
    ];
});
