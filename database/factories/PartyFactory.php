<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Group;

class PartyFactory extends Factory
{
    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function($model) {
    // We want to refresh the model before returning it.  This is so that we pick up the virtual columns.
    $model->refresh();
    return $model;
});
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        if (array_key_exists('event_date', $attributes)) {
        $startTime = array_key_exists('start', $attributes) ? $attributes['start'] : $this->faker->time();
        $endTime = array_key_exists('end', $attributes) ? $attributes['end'] : $this->faker->time();
        $start = Carbon\Carbon::parse($attributes['event_date'] . ' ' . $startTime, 'UTC');
        $end = Carbon\Carbon::parse($attributes['event_date'] . ' ' . $endTime, 'UTC');
        unset($attributes['event_date']);
        unset($attributes['start']);
        unset($attributes['end']);
    } else if (array_key_exists('event_start_utc', $attributes)) {
        $start = Carbon\Carbon::parse($attributes['event_start_utc']);
        $end = Carbon\Carbon::parse($attributes['event_end_utc']);
    } else {
        // Fake an event that's two hours long.
        $start = Carbon\Carbon::parse($this->faker->iso8601());
        $end = $start;
        $end->addHours(2);
    }

    return [
        // Need to force the location otherwise the random one may not be geocodable and therefore the event may not
        // get created.
        'location' => 'International House, 3Space, 6 Canterbury Cres, London SW9 7QD',
        'group' => function () {
            return Group::factory()->create()->idgroups;
        },
        'venue' => $this->faker->streetName,
        'event_start_utc' => $start->toIso8601String(),
        'event_end_utc' => $end->toIso8601String(),
        'free_text' => $this->faker->paragraph,
        'timezone' => 'Europe/London'
    ];
    }

    public function moderated()
    {
        return $this->state(function () {
            return [
        'wordpress_post_id' => $this->faker->randomNumber(),
    ];
        });
    }
}
