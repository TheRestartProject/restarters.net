<?php

namespace Tests\Feature\Outbound;

use App\Role;
use Tests\TestCase;

class OutboundEmbedTest extends TestCase
{
    public function testLeafEmbedMountsVueWithoutJquery(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();

        foreach (['leaf', 'consume', 'manufacture'] as $format) {
            $response = $this->get("/outbound/info/group/$id/$format");
            $response->assertStatus(200);

            // The embed doesn't load the CDN jQuery/Leaflet scripts, so it must tell the bundle to mount Vue
            // directly; otherwise the visualisation never renders.
            $response->assertSee('window.restartersIframe = true', false);
            $response->assertDontSee('cdn.jsdelivr.net/npm/jquery', false);
        }

        $response = $this->get("/outbound/info/group/$id/leaf");
        $response->assertSee('<StatsShare', false);
    }
}
