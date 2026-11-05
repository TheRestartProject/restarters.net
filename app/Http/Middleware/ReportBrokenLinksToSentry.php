<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Sentry\Severity;
use Sentry\State\Scope;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reports a 404 to Sentry when one of our own sites linked to it, because that is a bug we can fix.
 *
 * Other 404s are not worth reporting.  In the live logs they are overwhelmingly scanners probing for secrets and
 * admin pages, and crawlers asking for groups and events that have since been deleted.  So, like Django's
 * BrokenLinkEmailsMiddleware, we only report a 404 whose referrer is one of our pages.  Pages that used to exist and
 * are still linked from elsewhere are better found in search engine "not found" reports than here.
 *
 * Each broken link is reported at most once a day, and at most DAILY_LIMIT a day in all, so a busy page with a broken
 * link shows up as one issue that keeps recurring rather than as thousands of events.
 */
class ReportBrokenLinksToSentry
{
    private const OUR_HOSTS = ['restarters.net', 'therestartproject.org'];

    // Scanners fake referrers, so ignore requests for things no page of ours links to: server-side scripts, config
    // and credential files, other CMSs and tools, and anything with a colon (MediaWiki titles from a crawler that
    // resolves wiki links against this host).
    private const PROBE = '~(\.(php\d?|phtml|asp|aspx|jsp|cgi|pl|env|ini|log|sql|bak|old|orig|save|swp|ya?ml|toml|conf|cfg|config|properties|pem|key|crt|tfstate|tfvars|git|svn|ds_store|zip|tar|gz|tgz|rar|7z|sh|py|rb|json|map|txt|md|lock|prisma|ts|axd|xml)$)|wp-|/wp/|wordpress|xmlrpc|phpmyadmin|cgi-bin|/\.|/@fs/|/proc/|/etc/|serviceaccount|graphql|/vendor/|actuator|_profiler|_ignition|telescope|/console|/debug|/backup|/server-status|/manager/html|:~i';

    public const DAILY_LIMIT = 50;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 404) {
            $brokenLink = self::brokenLink($request);

            if ($brokenLink) {
                $this->reportOncePerDay($request, ...$brokenLink);
            }
        }

        return $response;
    }

    /**
     * If this 404 was linked from one of our pages, return the referring host and the missing path with ids and
     * hashes generalised, which together identify the broken link.
     */
    public static function brokenLink(Request $request): ?array
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return null;
        }

        $referrer = (string) $request->headers->get('referer');
        $parts = parse_url($referrer);

        // All our sites are https only, so a browser on one of them never sends an http referrer.  Scanners do.
        if (($parts['scheme'] ?? '') !== 'https' || ! self::isOurs(strtolower($parts['host'] ?? ''))) {
            return null;
        }

        $host = strtolower($parts['host']);
        $referrerPath = $parts['path'] ?? '/';
        $path = $request->getPathInfo();

        // A script as the referrer means a crawler pulling strings out of our JavaScript, not a link.  A page
        // referring to itself is a reload or a bot.
        if (str_ends_with($referrerPath, '.js')
            || ($host === strtolower($request->getHost()) && rtrim($referrerPath, '/') === rtrim($path, '/'))
            || preg_match(self::PROBE, $path)) {
            return null;
        }

        return [$host, self::shape($path)];
    }

    /**
     * Generalise a path so that each broken link is one Sentry issue however many ids or builds it covers.
     */
    public static function shape(string $path): string
    {
        // Vite build hashes: app-B5x9Yq2k.js
        $path = preg_replace('~-[A-Za-z0-9_]{6,}\.(js|css)$~', '-HASH.$1', $path);

        // Upload names and tokens: 1663695050...ff612055.png
        $path = preg_replace('~/[A-Za-z0-9_-]{16,}(\.\w+)?$~', '/HASH$1', $path);

        return preg_replace('~\d+~', 'N', $path);
    }

    private static function isOurs(string $host): bool
    {
        // The app's own host covers previews and the yesterday app.
        $hosts = array_filter([...self::OUR_HOSTS, parse_url((string) config('app.url'), PHP_URL_HOST)]);

        foreach ($hosts as $ours) {
            if ($host === $ours || str_ends_with($host, '.' . $ours)) {
                return true;
            }
        }

        return false;
    }

    private function reportOncePerDay(Request $request, string $host, string $shape): void
    {
        $day = now()->format('Y-m-d');

        if (! Cache::add("broken-link:$day:" . sha1("$host $shape"), true, now()->addDay())) {
            return;
        }

        Cache::add("broken-links:$day", 0, now()->addDays(2));

        if (Cache::increment("broken-links:$day") > self::DAILY_LIMIT) {
            return;
        }

        $this->capture("Broken link from $host to $shape", ['broken-link', $host, $shape], [
            'referrer' => $request->headers->get('referer'),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    protected function capture(string $message, array $fingerprint, array $context): void
    {
        \Sentry\withScope(function (Scope $scope) use ($message, $fingerprint, $context) {
            $scope->setLevel(Severity::warning());
            $scope->setFingerprint($fingerprint);
            $scope->setTag('referrer_host', $fingerprint[1]);
            $scope->setContext('broken_link', $context);
            \Sentry\captureMessage($message);
        });
    }
}
