<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Mediawiki\Api\ApiUser;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\MediawikiFactory;
use Mediawiki\Api\Service\UserCreator;

class MediawikiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        if (env('FEATURE__WIKI_INTEGRATION') === false) {
            return;
        }

        $this->app->singleton(MediawikiFactory::class, function () {
            try {
                Log::debug('Connect to Mediawiki');
                $api = new MediawikiApi(env('WIKI_URL').'/api.php');
                Log::debug('Log in');
                $api->login(new ApiUser(env('WIKI_APIUSER'), env('WIKI_APIPASSWORD')));
                Log::debug('...connected');

                return new MediawikiFactory($api);
            } catch (\Exception $ex) {
                Log::error('Failed to instantiate Wiki API classes: '.$ex->getMessage());
            }
        });

        $this->app->bind(UserCreator::class, function ($app) {
            $mw = $app->make(MediawikiFactory::class);
            if ($mw) {
                return $mw->newUserCreator();
            }
        });
    }
}
