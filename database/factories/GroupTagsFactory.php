<?php

use Faker\Generator as Faker;

$factory->define(App\GroupTags::class, function (Faker $faker) {
    return [
        'tag_name' => $faker->word,
        'description' => $faker->sentence,
    ];
});
