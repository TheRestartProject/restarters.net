<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Addwiki\Mediawiki\Api\Client\Auth\UserAndPassword;
use Addwiki\Mediawiki\Api\Client\MediaWiki;
use Addwiki\Mediawiki\Api\MediawikiFactory;
use Addwiki\Mediawiki\Api\Service\UserCreator;

class MediawikiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        if (env('FEATURE__WIKI_INTEGRATION') === false || empty(env('WIKI_URL'))) {
            return;
        }

        $this->app->singleton(MediawikiFactory::class, function () {
            try {
                Log::debug('Connect to Mediawiki');
                $mw = MediaWiki::newFromEndpoint(
                    env('WIKI_URL').'/api.php',
                    new UserAndPassword(env('WIKI_APIUSER'), env('WIKI_APIPASSWORD'))
                );
                Log::debug('...connected');

                return new MediawikiFactory($mw->action());
            } catch (\Throwable $ex) {
                Log::error('Failed to instantiate Wiki API classes: '.$ex->getMessage());
            }
        });

        $this->app->bind(UserCreator::class, function ($app) {
            try {
                $mw = $app->make(MediawikiFactory::class);
                if ($mw) {
                    return $mw->newUserCreator();
                }
            } catch (\Throwable $ex) {
                Log::error('Failed to create Wiki UserCreator: '.$ex->getMessage());
            }

            // Return null if Wiki connection is not available
            return null;
        });
    }
}
