<?php

namespace App\Services;

use Addwiki\Mediawiki\Api\Guzzle\ClientFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;

/**
 * The HTTP client for talking to the wiki's API as one of our users.
 */
class WikiHttpClient
{
    /**
     * Tests bind a Guzzle handler under this name to stand in for the wiki.
     */
    public const HANDLER = 'wiki.http.handler';

    public const RATE_LIMIT_RETRIES = 3;

    private static function turnedAway(?ResponseInterface $response, $exception): bool
    {
        if (! $response && $exception instanceof RequestException) {
            $response = $exception->getResponse();
        }

        return $response && in_array($response->getStatusCode(), [429, 503], true);
    }

    public static function make(?CookieJar $cookieJar = null): Client
    {
        $handler = app()->bound(self::HANDLER) ? app(self::HANDLER) : HandlerStack::create(new CurlHandler());

        return (new ClientFactory([
            'cookies' => $cookieJar ?? new CookieJar(),
            'timeout' => 10,
            'connect_timeout' => 5,
            'handler' => $handler,
            // The wiki's web server limits requests per address, and all of ours come from this server, so
            // creating an account and logging straight in to it can be turned away, with a 429 or a 503. The
            // client's own retry doesn't catch these: by the time it sees them they are exceptions, not responses.
            'middleware' => [
                Middleware::retry(
                    fn (int $retries, $request, ?ResponseInterface $response = null, $exception = null) => $retries < self::RATE_LIMIT_RETRIES && self::turnedAway($response, $exception),
                    // Half a second, then one, then one and a half: logging in to the wiki holds up logging in here.
                    fn (int $retries) => app()->runningUnitTests() ? 0 : 500 * $retries
                ),
            ],
        ]))->getClient();
    }
}
