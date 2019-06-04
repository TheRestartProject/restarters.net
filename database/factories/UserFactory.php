<?php

use App\Role;
use App\User;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

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

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'username' => $faker->userName,
        'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
        'remember_token' => Str::random(10),
        'consent_past_data' => new \DateTime(),
        'consent_future_data' => new \DateTime(),
        'consent_gdpr' => new \DateTime(),
        'number_of_logins' => 1,
        'age' => $faker->year(),
        'country' => $faker->countryCode,
        'role' => Role::RESTARTER
    ];
});

$factory->state(User::class, 'Restarter', function (Faker $faker) {
    return [
        'role' => Role::RESTARTER,
    ];
});

$factory->state(User::class, 'Host', function (Faker $faker) {
    return [
        'role' => Role::HOST,
    ];
});

$factory->state(User::class, 'Administrator', function (Faker $faker) {
    return [
        'role' => Role::ADMIN,
    ];
});
