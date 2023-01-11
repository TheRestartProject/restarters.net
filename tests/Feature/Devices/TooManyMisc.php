<?php

namespace Tests\Feature;

use App\Device;
use App\Notifications\AdminAbnormalDevices;
use App\Party;
use App\User;
use DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TooManyTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testTooMany($count, $notif)
    {
        Notification::fake();

        $this->event = Party::factory()->create();
        $this->admin = User::factory()->administrator()->create();
        $this->admin->addPreference('admin-abnormal-devices');

        $this->device_inputs = Device::factory()->raw([
                                                               'event_id' => $this->event->idevents,
                                                               'quantity' => 1,
                                                               'category' => env('MISC_CATEGORY_ID_POWERED'),
                                                               'category_creation' => env('MISC_CATEGORY_ID_POWERED'),
                                                           ]);
        $this->actingAs($this->admin);

        for ($i = 0; $i < $count; $i++) {
            $rsp = $this->post('/device/create', $this->device_inputs);
            self::assertTrue($rsp['success']);
        }

        if ($notif) {
            Notification::assertSentTo(
                [$this->admin],
                AdminAbnormalDevices::class
            );
        }

        self::assertTrue(true);
    }

    public function provider() {
        return [
            [ env('DEVICE_ABNORMAL_MISC_COUNT', 5) - 1, false, ],
            [ env('DEVICE_ABNORMAL_MISC_COUNT', 5), true, ]
        ];
    }
}
