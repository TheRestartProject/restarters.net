<?php

namespace Tests\Feature\Sentry;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Sentry\ClientBuilder;
use Sentry\Event;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Tests\TestCase;

/**
 * Each problem should reach Sentry once.
 */
class DuplicateReportsTest extends TestCase
{
    /** @var Event[] */
    private array $sent = [];

    private HubInterface $realHub;

    protected function setUp(): void
    {
        parent::setUp();

        // A real client, so the reporting code runs as it does live, but whatever it would send is kept here.
        $this->realHub = SentrySdk::getCurrentHub();
        SentrySdk::setCurrentHub($hub = new Hub(ClientBuilder::create([
            'dsn' => 'https://public@sentry.example.com/1',
            'before_send' => function (Event $event) {
                $this->sent[] = $event;

                return null;
            },
        ])->getClient()));

        // The exception handler sends exceptions through the container's hub.
        $this->app->instance('sentry', $hub);

        Route::middleware('web')->get('/_sentry_test/throws', fn () => throw new \RuntimeException('Something broke'));
        Route::middleware('web')->get('/_sentry_test/unavailable', fn () => response('Down for maintenance', 503));
    }

    protected function tearDown(): void
    {
        SentrySdk::setCurrentHub($this->realHub);

        parent::tearDown();
    }

    private function messages(): array
    {
        return array_map(fn (Event $e) => $e->getMessage(), $this->sent);
    }

    public function testAnErrorLoggedIsReportedWithItsContext(): void
    {
        Log::error('Could not create event thread on Discourse', ['event' => 123, 'host' => 'jane']);

        $this->assertEquals(['Could not create event thread on Discourse'], $this->messages());
        $this->assertEquals(['event' => 123, 'host' => 'jane'], $this->sent[0]->getContexts()['log']);
    }

    public function testOnlyErrorsAreReported(): void
    {
        Log::warning('Could not change the wiki password');
        Log::info('Logged in to wiki');

        $this->assertEmpty($this->sent);
    }

    public function testAnExceptionLoggedByTheExceptionHandlerIsNotReportedAgain(): void
    {
        // The handler sends the exception itself, then logs it with the exception in the context.
        Log::error('Something broke', ['exception' => new \RuntimeException('Something broke')]);

        $this->assertEmpty($this->sent);
    }

    public function testAServerErrorFromAnExceptionIsNotReportedAgain(): void
    {
        // The exception handler reports the exception, with its stack trace; "HTTP return code 500" was a copy.
        $this->withExceptionHandling();

        $this->get('/_sentry_test/throws')->assertStatus(500);

        $this->assertEmpty($this->messages());
    }

    public function testAServerErrorIsReportedOnceAsItsException(): void
    {
        // The handler only sends exceptions to Sentry in production.
        $this->withExceptionHandling();
        $this->app['env'] = 'production';

        try {
            $this->get('/_sentry_test/throws')->assertStatus(500);
        } finally {
            $this->app['env'] = 'testing';
        }

        $this->assertCount(1, $this->sent);
        $this->assertEquals('Something broke', $this->sent[0]->getExceptions()[0]->getValue());
    }

    public function testAnErrorResponseWithoutAnExceptionIsReportedForItsRoute(): void
    {
        $this->get('/_sentry_test/unavailable')->assertStatus(503);

        $this->assertEquals(['HTTP return code 503 from /_sentry_test/unavailable'], $this->messages());
    }

    public function testAMissingApiDocsSourceMapIsNotFound(): void
    {
        // l5-swagger allows the source maps, but the Swagger UI package doesn't ship them, so reading one failed.
        $this->withExceptionHandling();

        $this->get('/docs/asset/swagger-ui-standalone-preset.js.map')->assertNotFound();
        $this->get('/docs/asset/swagger-ui.css')->assertSuccessful();

        $this->assertEmpty($this->sent);
    }
}
