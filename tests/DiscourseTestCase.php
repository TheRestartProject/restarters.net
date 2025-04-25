<?php

namespace Tests;

/**
 * Base class for all Discourse-related tests
 * Will automatically skip tests when Discourse integration is disabled
 */
abstract class DiscourseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        if (!config('restarters.features.discourse_integration')) {
            $this->markTestSkipped('Discourse integration is disabled.');
        }
    }
} 