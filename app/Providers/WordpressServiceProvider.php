<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

use HieuLe\WordpressXmlrpcClient\WordpressClient;

class WordpressServiceProvider extends ServiceProvider
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
        $this->app->singleton(WordpressClient::class, function ($app) {
            try {
                $wpClient = new WordpressClient();
                $wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));

                return $wpClient;
            } catch (\Exception $ex) {
                Log::error("Failed to instantiate Wordpress API classes: " . $ex->getMessage());
            }
        });
    }
}
