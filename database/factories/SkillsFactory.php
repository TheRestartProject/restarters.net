<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SkillsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'skill_name' => $this->faker->unique()->jobTitle(),
            'category' => $this->faker->randomElement([1, 2]),
            'description' => $this->faker->sentence(),
        ];
    }
}
