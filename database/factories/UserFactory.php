<?php

namespace Database\Factories;

use Illuminate\Support\Facades\Hash;
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
    protected static ?string $password;

    public function definition(): array
    {
        return [
        'name' => $this->faker->name(),
        'email' => $this->faker->unique()->safeEmail(),
        'username' => $this->faker->userName(),
        'password' => static::$password ??= Hash::make('secret'),
        'remember_token' => Str::random(10),
        'consent_past_data' => new \DateTime(),
        'consent_future_data' => new \DateTime(),
        'consent_gdpr' => new \DateTime(),
        'number_of_logins' => 1,
        'age' => $this->faker->year(),
        'country_code' => $this->faker->countryCode(),
        'invites' => 1,
        'repairdir_role' => Role::REPAIR_DIRECTORY_NONE,
    ];
    }

    // role and api_token are excluded from $fillable (security: C2/M1) so they
    // must be set via direct assignment after the record is created.
    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            if (is_null($user->role)) {
                $user->role = Role::RESTARTER;
            }
            if (is_null($user->api_token)) {
                $user->api_token = Str::random(60);
            }
            $user->saveQuietly();
        });
    }

    public function restarter()
    {
        return $this->afterCreating(function (User $user) {
            $user->role = Role::RESTARTER;
            $user->username = '';
            $user->saveQuietly();
        });
    }

    public function host()
    {
        return $this->afterCreating(function (User $user) {
            $user->role = Role::HOST;
            $user->username = '';
            $user->saveQuietly();
        });
    }

    public function administrator()
    {
        return $this->afterCreating(function (User $user) {
            $user->role = Role::ADMINISTRATOR;
            $user->username = '';
            $user->saveQuietly();
        });
    }

    public function networkCoordinator()
    {
        return $this->afterCreating(function (User $user) {
            $user->role = Role::NETWORK_COORDINATOR;
            $user->username = '';
            $user->saveQuietly();
        });
    }
}
