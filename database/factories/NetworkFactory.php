<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NetworkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
        'name' => $this->faker->unique()->company(),
    ];
    }
}
