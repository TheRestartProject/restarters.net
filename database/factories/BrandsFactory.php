<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BrandsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'brand_name' => $this->faker->unique()->company(),
        ];
    }
}
