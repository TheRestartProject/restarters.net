<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
        'name' => $this->faker->unique()->company(),
        'free_text' => $this->faker->sentence(),
        'facebook' => '',
        'postcode' => '',
        'timezone' => 'Europe/London'
    ];
    }
}
