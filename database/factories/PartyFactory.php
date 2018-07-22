<?php

use App\Group;

use Faker\Generator as Faker;

$factory->define(App\Party::class, function (Faker $faker) {
    return [
        'location' => $faker->name,
        'group' => function () {
            return factory(Group::class)->create()->idgroups;
        }
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
