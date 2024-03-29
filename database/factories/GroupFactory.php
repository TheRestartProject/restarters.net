<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
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
