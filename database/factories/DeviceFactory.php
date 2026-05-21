<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Category;
use App\Party;

class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
        'event' => Party::factory()->create()->idevents,
        'category' => 11,
        'category_creation' => 11,
        'problem' => '',
    ];
    }

    public function misc()
    {
        return $this->state(function () {
            return [
        'category' => 46,
        'category_creation' => 46,
        'problem' => '',
    ];
        });
    }

    public function mobile()
    {
        return $this->state(function () {
            return [
        'category' => 25,
        'category_creation' => 25,
        'problem' => '',
    ];
        });
    }

    public function fixed()
    {
        return $this->state(function () {
            return [
        'category' => 111,
        'category_creation' => 111,
        'repair_status' => 1,
        'problem' => '',
    ];
        });
    }

    public function repairable()
    {
        return $this->state(function () {
            return [
        'category' => 111,
        'category_creation' => 111,
        'repair_status' => 2,
        'problem' => '',
    ];
        });
    }

    public function end()
    {
        return $this->state(function () {
            return [
        'category' => 111,
        'category_creation' => 111,
        'repair_status' => 2,
        'problem' => '',
    ];
        });
    }

    public function misccat()
    {
        return $this->state(function () {
            return [
        'category' => 46,
        'category_creation' => 46,
        'problem' => $this->faker->sentence(6, true),
    ];
        });
    }

    public function desktop()
    {
        return $this->state(function () {
            return [
        'category' => 11,
        'category_creation' => 11,
    ];
        });
    }

    public function laptop_large()
    {
        return $this->state(function () {
            return [
        'category' => 15,
        'category_creation' => 15,
    ];
        });
    }

    public function laptop_medium()
    {
        return $this->state(function () {
            return [
        'category' => 16,
        'category_creation' => 16,
    ];
        });
    }

    public function laptop_small()
    {
        return $this->state(function () {
            return [
        'category' => 17,
        'category_creation' => 17,
    ];
        });
    }

    public function tablet()
    {
        return $this->state(function () {
            return [
        'category' => 26,
        'category_creation' => 26,
    ];
        });
    }
}
