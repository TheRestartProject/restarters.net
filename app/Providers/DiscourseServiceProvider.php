<?php

namespace App\Providers;

use Auth;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class DiscourseServiceProvider extends ServiceProvider
{
    /**
     * Register Discourse API connection services.
     * For testing purposes, there's no need to specify a username - otherwise
     * call the service with a second argument, such as below:
     * app('discourse-client', ['username' => $username])
     *
     * @return \GuzzleHttp\Client
     */

    private $logger = null;

    public function register()
    {
        // We need to register the service whether or not the feature is turned on, so that we can mock it in testing.

        // We use the retry middleware to work around Discourse throttling requests.  This is necessary for UT and
        // could be necessary live under load.
        $this->app->bind('discourse-client', function ($app, $parameters) {
            $stack = HandlerStack::create();

            $stack->push(
                $this->createGuzzleLoggingMiddleware(" {method} {uri} HTTP/{version} {req_body}")
            );

            $stack->push(GuzzleRetryMiddleware::factory());

            return new Client([
                'base_uri' => config('discourse-api.base_url'),
                'headers' => [
                    'User-Agent' => 'restarters/1.0',
                    'Api-Key' => config('discourse-api.api_key'),
                    'Api-Username' => $parameters['username'] ?? config('discourse-api.api_username'),
                ],
                'http_errors' => false,
                'handler' => $stack,
                'timeout' => 5,
                'connect_timeout' => 5
            ]);
        });

        $this->app->bind('discourse-client-anonymous', function ($app, $parameters) {
            $stack = HandlerStack::create();
            $stack->push(
                $this->createGuzzleLoggingMiddleware(" {method} {uri} HTTP/{version} {req_body}")
            );

            $stack->push(GuzzleRetryMiddleware::factory());

            return new Client([
                                  'base_uri' => config('discourse-api.base_url'),
                                  'headers' => [
                                      'User-Agent' => 'restarters/1.0',
                                  ],
                                  'http_errors' => false,
                                  'handler' => $stack,
                              ]);
        });
    }

    private function createGuzzleLoggingMiddleware(string $messageFormat)
    {
        return \GuzzleHttp\Middleware::log(
            Log::channel('discourse'),
            new \GuzzleHttp\MessageFormatter($messageFormat)
        );
    }
}
