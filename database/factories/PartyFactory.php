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
    ];
});

$factory->state(App\Party::class, 'moderated', function (Faker $faker) {
    return [
        'wordpress_post_id' => $faker->randomNumber(),
    ];
});

/*$factory->state(App\Party::class, 'with-device', function (Faker $faker) {
    $device = factory(Device::class)->create([
        'event' => $event->idevents,
        'repair_status' => 1,
        'category' => $category->idcategories,
        'category_creation' => $category->idcategories,
    ]);
    return [
        'location' => $faker->name,
        'group' => function () {
            return factory(Group::class)->create()->idgroups;
        }
    ];
});
*/
