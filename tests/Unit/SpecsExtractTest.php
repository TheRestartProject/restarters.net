<?php

namespace Tests\Unit;

use Tests\TestCase;

class SpecsExtractTest extends TestCase
{
    /**
     * Every public controller method needs #[UserStory] or #[NoStory], @story: references must be
     * unambiguous, and docs/specs/manifest.json must match the code.  Run `php artisan specs:extract`
     * and commit the result if this fails.
     *
     * @test
     */
    public function specs_manifest_is_complete_and_current(): void
    {
        $this->artisan('specs:extract', ['--check' => true])->assertExitCode(0);
    }
}
