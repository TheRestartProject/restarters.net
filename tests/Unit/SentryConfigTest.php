<?php

use Tests\TestCase;

class SentryConfigTest extends TestCase
{
    public function testTestRunsDoNotReportToSentry(): void
    {
        // Test runs raised thousands of expected errors (no Discourse, no geocoder) into the production project.
        $this->assertNull(config('sentry.dsn'));
    }
}
