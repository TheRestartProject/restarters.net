<?php

use App\Group;
use Faker\Generator as Faker;

$factory->define(App\Party::class, function (Faker $faker, $attributes = []) {
    // Many tests use the old event_date/start/end method.  Convert those here to the new event_start_utc/
    // event_end_utc approach.  This avoids changing lots of tests.
    if (array_key_exists('event_date', $attributes)) {
        $startTime = array_key_exists('start', $attributes) ? $attributes['start'] : $faker->time();
        $endTime = array_key_exists('end', $attributes) ? $attributes['end'] : $faker->time();
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
        $start = Carbon\Carbon::parse($faker->iso8601());
        $end = $start;
        $end->addHours(2);
    }

    return [
        // Need to force the location otherwise the random one may not be geocodable and therefore the event may not
        // get created.
        'location' => 'International House, 3Space, 6 Canterbury Cres, London SW9 7QD',
        'group' => function () {
            return factory(Group::class)->create()->idgroups;
        },
        'venue' => $faker->streetName,
        'event_start_utc' => $start->toIso8601String(),
        'event_end_utc' => $end->toIso8601String(),
        'free_text' => $faker->paragraph,
        'timezone' => 'Europe/London'
    ];
});

$factory->state(App\Party::class, 'moderated', function (Faker $faker) {
    return [
        'approved' => true
    ];
});

$factory->afterCreating(App\Party::class, function($model, $faker) {
    // We want to refresh the model before returning it.  This is so that we pick up the virtual columns.
    $model->refresh();
    return $model;
});