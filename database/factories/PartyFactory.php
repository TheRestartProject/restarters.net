<?php

use App\Group;
use Faker\Generator as Faker;

$factory->define(App\Party::class, function (Faker $faker) {
    // Need to force the location otherwise the random one may not be geocodable and therefore the event may not
    // get created.
    return [
        'location' => 'International House, 3Space, 6 Canterbury Cres, London SW9 7QD',
        'group' => function () {
            return factory(Group::class)->create()->idgroups;
        },
        'venue' => $faker->streetName,
        'event_date' => $faker->date(),
        'start' => $faker->time(),
        'end' => $faker->time(),
        'free_text' => $faker->paragraph,
        'timezone' => 'Europe/London'
    ];
});

$factory->state(App\Party::class, 'moderated', function (Faker $faker) {
    return [
        'wordpress_post_id' => $faker->randomNumber(),
    ];
});

$factory->afterCreating(App\Party::class, function($model, $faker) {
    // We want to refresh the model before returning it.  This is so that we pick up the virtual columns.
    $model->refresh();
    return $model;
});