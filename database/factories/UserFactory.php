<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Role;
use App\User;
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

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
        'name' => $this->faker->name(),
        'email' => $this->faker->unique()->safeEmail(),
        'username' => $this->faker->userName(),
        'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
        'remember_token' => Str::random(10),
        'consent_past_data' => new \DateTime(),
        'consent_future_data' => new \DateTime(),
        'consent_gdpr' => new \DateTime(),
        'number_of_logins' => 1,
        'age' => $this->faker->year(),
        'country' => $this->faker->countryCode(),
        'role' => Role::RESTARTER,
        'invites' => 1,
        'repairdir_role' => Role::REPAIR_DIRECTORY_NONE,
        'api_token' => \Illuminate\Support\Str::random(60)
    ];
    }

    public function restarter()
    {
        return $this->state(function () {
            return [
        'role' => Role::RESTARTER,
        'username' => '',
    ];
        });
    }

    public function host()
    {
        return $this->state(function () {
            return [
        'role' => Role::HOST,
        'username' => '',
    ];
        });
    }

    public function administrator()
    {
        return $this->state(function () {
            return [
        'role' => Role::ADMINISTRATOR,
        'username' => '',
    ];
        });
    }

    public function networkCoordinator()
    {
        return $this->state(function () {
            return [
        'role' => Role::NETWORK_COORDINATOR,
        'username' => '',
    ];
        });
    }
}
