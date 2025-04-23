<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Addwiki\Mediawiki\Api\Client\Auth\UserAndPassword;
use Addwiki\Mediawiki\Api\Client\Action\ActionApi;
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
        if (env('FEATURE__WIKI_INTEGRATION') == true) {
            return;
        }

        $this->app->singleton(MediawikiFactory::class, function() {
            try {
                Log::debug('Connect to Mediawiki');
                $apiUrl = env('WIKI_URL').'/api.php';
                $auth = new UserAndPassword(env('WIKI_APIUSER'), env('WIKI_APIPASSWORD'));

                $api = new ActionApi($apiUrl, $auth);
                Log::debug('Connected to Mediawiki');

                return new MediawikiFactory($api);
            } catch (\Exception $ex) {
                Log::error('Failed to create ActionApi: '.$ex->getMessage());
                return null;
            }
        });

        $this->app->bind(UserCreator::class, function($app) {
            $mw = $app->make(MediawikiFactory::class);
            if ($mw) {
                return $mw->newUserCreator();
            }

            return null;
        });
    }
}
