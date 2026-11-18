<?php

namespace Tests\Feature\Sentry;

use App\Http\Middleware\ReportBrokenLinksToSentry;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Tests\TestCase;

class BrokenLinkReportingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 404s must be rendered as responses for the middleware to see them.
        $this->withExceptionHandling();
    }

    private function expectReports(int $times): void
    {
        $this->partialMock(ReportBrokenLinksToSentry::class, function (MockInterface $mock) use ($times) {
            $mock->shouldAllowMockingProtectedMethods()->shouldReceive('capture')->times($times);
        });
    }

    private function fetch(string $path, ?string $referrer, string $method = 'GET')
    {
        return $this->call($method, $path, [], [], [], $referrer ? ['HTTP_REFERER' => $referrer] : []);
    }

    public function testMissingFileLinkedFromOurWikiIsReported(): void
    {
        $this->partialMock(ReportBrokenLinksToSentry::class, function (MockInterface $mock) {
            $mock->shouldAllowMockingProtectedMethods()->shouldReceive('capture')->once()->with(
                'Broken link from wiki.restarters.net to /global/js/app.js',
                ['broken-link', 'wiki.restarters.net', '/global/js/app.js'],
                \Mockery::on(fn ($context) => $context['referrer'] === 'https://wiki.restarters.net/Main_Page')
            );
        });

        $this->fetch('/global/js/app.js', 'https://wiki.restarters.net/Main_Page')->assertNotFound();
    }

    public function testEachBrokenLinkIsReportedOnceADay(): void
    {
        $this->expectReports(1);

        // Different deleted groups, same broken link: an orphaned post on therestartproject.org.
        $this->fetch('/outbound/info/group/999991', 'https://therestartproject.org/')->assertNotFound();
        $this->fetch('/outbound/info/group/999992', 'https://therestartproject.org/')->assertNotFound();
        $this->fetch('/outbound/info/group/999993', 'https://therestartproject.org/some-post/')->assertNotFound();
    }

    public function testReportsStopAtTheDailyLimit(): void
    {
        $this->expectReports(ReportBrokenLinksToSentry::DAILY_LIMIT);

        for ($i = 0; $i <= ReportBrokenLinksToSentry::DAILY_LIMIT; $i++) {
            // Letters rather than numbers, which would make them all the same broken link.
            $this->fetch('/missing-' . str_repeat('a', $i + 1) . '/x', 'https://restarters.net/dashboard')->assertNotFound();
        }
    }

    public function testOtherNotFoundsAreNotReported(): void
    {
        $this->expectReports(0);

        // A crawler asking for a deleted event, with no referrer.
        $this->fetch('/party/view/999999', null)->assertNotFound();

        // Linked from someone else's site.
        $this->fetch('/global/js/app.js', 'https://example.com/')->assertNotFound();

        // Our hosts over http, which our https-only sites never send: a faked referrer.
        $this->fetch('/global/js/app.js', 'http://restarters.net/')->assertNotFound(); // NOSONAR: an http referrer is the case under test

        // A look-alike host.
        $this->fetch('/global/js/app.js', 'https://notrestarters.net/')->assertNotFound();

        // Scanners probing for credentials and other CMSs, with a faked referrer.
        $this->fetch('/gcp-credentials.json', 'https://restarters.net/')->assertNotFound();
        $this->fetch('/wp-login.php', 'https://restarters.net/')->assertNotFound();
        $this->fetch('/special:whatlinkshere/steam_irons', 'https://wiki.restarters.net/Steam_Irons')->assertNotFound();

        // A crawler pulling strings out of our JavaScript.
        $this->fetch('/MM/DD/YYYY', 'https://restarters.net/build/assets/app-88d976fd.js')->assertNotFound();

        // A page referring to itself.
        $this->fetch('/party/view/999999', 'https://localhost/party/view/999999')->assertNotFound();

        // Not a fetch.
        $this->fetch('/global/js/app.js', 'https://restarters.net/dashboard', 'POST');
    }

    public function testPagesThatExistAreNotReported(): void
    {
        $this->expectReports(0);

        // Pages are the Nuxt client's; Laravel serves the API and a few routes such as the stats widgets.
        $this->fetch('/api/v2/groups/names', 'https://restarters.net/')->assertSuccessful();
    }

    public function testTheSitesOwnApiCallsAreNotLinks(): void
    {
        // The Nuxt client asks the API for a record that has gone (a stale page, another tab deleted it). That
        // isn't a link, and the client reports its own API errors.
        $this->expectReports(0);
        $site = 'https://'.parse_url(config('app.url'), PHP_URL_HOST).'/group/view/999999';

        $this->fetch('/api/v2/groups/999999', $site)->assertNotFound();
    }

    /**
     * @dataProvider shapes
     */
    public function testShapeGeneralisesIdsAndHashes(string $path, string $shape): void
    {
        $this->assertEquals($shape, ReportBrokenLinksToSentry::shape($path));
    }

    public function shapes(): array
    {
        return [
            ['/outbound/info/group/1112', '/outbound/info/group/N'],
            ['/build/assets/app-B5x9Yq2k.js', '/build/assets/app-HASH.js'],
            ['/16636950505b1291afec1d2c97cb86a90baa231000bfec1ff612055.png', '/HASH.png'],
            ['/global/js/app.js', '/global/js/app.js'],
            ['/mstile-150x150.png', '/mstile-NxN.png'],
        ];
    }

    public function testBrokenLinkIdentifiesReferringHostAndPathShape(): void
    {
        $request = Request::create('https://restarters.net/api/group/1404/stats', 'GET', [], [], [], [
            'HTTP_REFERER' => 'https://www.therestartproject.org/groups/some-group/',
        ]);

        $this->assertEquals(['www.therestartproject.org', '/api/group/N/stats'], ReportBrokenLinksToSentry::brokenLink($request));
    }
}
