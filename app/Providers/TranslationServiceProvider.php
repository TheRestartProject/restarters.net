<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\File;

class TranslationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Maps domains to their corresponding translation site folders
     */
    protected $siteTranslations = [
        [
            'site' => 'fixitclinic',
            'domain' => 'restarters-dev.cominor.com'
        ]
        // Add more site mappings as needed:
        // ['site' => 'othersite', 'domain' => 'other-domain.com']
    ];

    public function register()
    {
        $this->registerLoader();

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];
            $locale = $app->getLocale();
            $trans = new Translator($loader, $locale);
            $trans->setFallback($app->getFallbackLocale());
            return $trans;
        });
    }

    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            $loader = new FileLoader($app['files'], base_path('lang'));
            
            // Register all site namespaces
            foreach ($this->siteTranslations as $site) {
                $loader->addNamespace($site['site'], base_path("lang/{$site['site']}"));
            }
            
            return $loader;
        });
    }

    public function provides()
    {
        return ['translator', 'translation.loader'];
    }

    public function boot()
    {
        $site = $this->getCurrentSite();
        if (!$site) {
            return;
        }

        $locale = app()->getLocale();
        $customPath = base_path("lang/{$site['site']}/{$locale}");
        $translator = app('translator');
        
        // Load and merge all override files
        foreach (File::glob("{$customPath}/*.php") as $file) {
            $group = basename($file, '.php');
            $baseTranslations = $translator->getLoader()->load($locale, $group) ?: [];
            $overrideTranslations = require $file;
            
            $translator->addLines(
                collect(array_merge($baseTranslations, $overrideTranslations))
                    ->mapWithKeys(fn($value, $key) => ["{$group}.{$key}" => $value])
                    ->toArray(),
                $locale
            );
        }
    }

    /**
     * Get the current site configuration based on the domain
     * 
     * @return array|null The site configuration or null if no match
     */
    protected function getCurrentSite()
    {
        $currentDomain = request()->getHost();
        
        foreach ($this->siteTranslations as $site) {
            if ($site['domain'] === $currentDomain) {
                return $site;
            }
        }
        // No match is found
        return null;
    }
} 