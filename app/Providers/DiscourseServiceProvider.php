<?php

namespace App\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Auth;

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
    public function register()
    {
        if (config('restarters.features.discourse_integration') === false) {
            return;
        }

        $this->app->bind('discourse-client', function ($app, $parameters) {
            return new Client([
                'base_uri' => config('discourse-api.base_url'),
                'headers' => [
                    'User-Agent' => 'restarters/1.0',
                    'Api-Key' => config('discourse-api.api_key'),
                    'Api-Username' => $parameters['username'] ?? config('discourse-api.api_username'),
                ],
                'http_errors' => false,
            ]);
        });
    }
}
