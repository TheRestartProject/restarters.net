<?php

namespace App\Providers;

use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class WordpressServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(WordpressClient::class, function () {
            try {
                $wpClient = new WordpressClient();
                $wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));

                return $wpClient;
            } catch (\Exception $ex) {
                Log::error('Failed to instantiate Wordpress API classes: '.$ex->getMessage());
            }
        });
    }
}
