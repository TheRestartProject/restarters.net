<?php

use Tests\TestCase;

class SentryConfigTest extends TestCase
{
    public function testTestRunsDoNotReportToSentry(): void
    {
        // Test runs raised thousands of expected errors (no Discourse, no geocoder) into the production project.
        $this->assertNull(config('sentry.dsn'));
    }

    public function testAboutRouteHasItsOwnName(): void
    {
        // Two routes named 'home' made route:cache fail on every deploy.
        $this->assertEquals(url('/about'), route('about'));
        $this->assertEquals(url('/'), route('home'));
    }
}
