<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MisccatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [];
    }

    public function misc()
    {
        return $this->state(function () {
            return [
        'category' => 'Misc',
    ];
        });
    }

    public function mobile()
    {
        return $this->state(function () {
            return [
        'category' => 'Mobile',
    ];
        });
    }

    public function cat1()
    {
        return $this->state(function () {
            return [
        'category' => 'cat1',
    ];
        });
    }

    public function cat2()
    {
        return $this->state(function () {
            return [
        'category' => 'cat2',
    ];
        });
    }

    public function cat3()
    {
        return $this->state(function () {
            return [
        'category' => 'cat3',
    ];
        });
    }
}
