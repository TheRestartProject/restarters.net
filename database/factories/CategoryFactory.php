<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [];
    }

    public function misc()
    {
        return $this->state(function () {
            return [
        'idcategories' => 46,
        'name' => 'Misc',
        'revision' => 2,
        'aggregate' => 0,
    ];
        });
    }

    public function desktopComputer()
    {
        return $this->state(function () {
            return [
        'idcategories' => 11,
        'name' => 'Desktop computer',
        'revision' => 2,
        'aggregate' => 0,
    ];
        });
    }

    public function mobile()
    {
        return $this->state(function () {
            return [
        'idcategories' => 25,
        'name' => 'Mobile',
        'revision' => 2,
        'footprint' => 1,
        'weight' => 1,
        'aggregate' => 0,
    ];
        });
    }

    public function cat1()
    {
        return $this->state(function () {
            return [
        'idcategories' => 111,
        'name' => 'Cat1',
        'revision' => 2,
        'footprint' => 1,
        'weight' => 1,
        'aggregate' => 0,
        'powered' => 0
    ];
        });
    }

    public function cat2()
    {
        return $this->state(function () {
            return [
        'idcategories' => 222,
        'name' => 'Cat2',
        'revision' => 2,
        'footprint' => 2,
        'weight' => 2,
        'aggregate' => 0,
        'powered' => 1
    ];
        });
    }

    public function cat3()
    {
        return $this->state(function () {
            return [
        'idcategories' => 333,
        'name' => 'Cat3',
        'revision' => 2,
        'footprint' => 3,
        'weight' => 3,
        'aggregate' => 0,
    ];
        });
    }
}
