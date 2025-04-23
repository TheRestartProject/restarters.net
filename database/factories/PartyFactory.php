<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Group;
use Carbon\Carbon;

class PartyFactory extends Factory
{
    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function ($model)
        {
            if ($model->event_date) {
                $startTime = $model->start ? $model->start : $this->faker->time();
                $endTime = $model->end ? $model->end : $this->faker->time();
                $start = Carbon::parse($model->event_date . ' ' . $startTime, 'UTC');
                $end = Carbon::parse($model->event_date . ' ' . $endTime, 'UTC');
                $model->event_date = null;
                $model->start = null;
                $model->end = null;
            } else {
                if ($model->event_start_utc) {
                    $start = Carbon::parse($model->event_start_utc);
                    $end = Carbon::parse($model->event_end_utc);
                } else {
                    // Fake an event that's two hours long.
                    $start = Carbon::parse($this->faker->iso8601());
                    $end = $start;
                    $end->addHours(2);
                }
            }

            $model->event_start_utc = $start->toIso8601String();
            $model->event_end_utc = $end->toIso8601String();
            return $model;
        })->afterCreating(function ($model)
        {
            // We want to refresh the model before returning it.  This is so that we pick up the virtual columns.
            $model->refresh();
            return $model;
        });
    }

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            // Need to force the location otherwise the random one may not be geocodable and therefore the event may not
            // get created.
            'location' => 'International House, 3Space, 6 Canterbury Cres, London SW9 7QD',
            'group' => function ()
            {
                return Group::factory()->create()->idgroups;
            },
            'venue' => $this->faker->streetName(),
            'free_text' => $this->faker->paragraph(),
            'timezone' => 'Europe/London'
        ];
    }

    public function moderated()
    {
        return $this->state(function ()
        {
            return [
                'approved' => true
            ];
        });
    }
}
