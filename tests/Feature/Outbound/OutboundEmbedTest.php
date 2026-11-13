<?php

namespace Tests\Feature\Outbound;

use App\Role;
use Tests\TestCase;

class OutboundEmbedTest extends TestCase
{
    public function testEmbedsRenderWithoutJquery(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();

        foreach (['leaf', 'consume', 'manufacture'] as $format) {
            $response = $this->get("/outbound/info/group/$id/$format");
            $response->assertStatus(200);

            // The embeds are plain server-rendered pages (the leaf one draws its own canvas), so they don't need
            // the CDN jQuery/Leaflet scripts.
            $response->assertDontSee('cdn.jsdelivr.net/npm/jquery', false);
        }

        $response = $this->get("/outbound/info/group/$id/leaf");
        $response->assertSee('data-stats-share', false);
    }
}
