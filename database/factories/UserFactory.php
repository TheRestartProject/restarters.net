<?php

use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(App\User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
        'remember_token' => str_random(10),
        'consent_past_data' => new \DateTime(),
        'consent_future_data' => new \DateTime(),
        'consent_gdpr' => new \DateTime(),
        'number_of_logins' => 1,
        'age' => $faker->year(),
        'country' => $faker->countryCode
    ];
});

$factory->state(App\User::class, 'Administrator', function (Faker $faker) {
    return [
        'role' => 2,
    ];
});
