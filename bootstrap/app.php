<?php

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \Mariuzzo\LaravelJsLocalization\LaravelJsLocalizationServiceProvider::class,
        \Msurguy\Honeypot\HoneypotServiceProvider::class,
        \Intervention\Image\Laravel\ServiceProvider::class,
        \OwenIt\Auditing\AuditingServiceProvider::class,
        \Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider::class,
        \Barryvdh\TranslationManager\ManagerServiceProvider::class,
        \Sentry\Laravel\ServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(AppServiceProvider::HOME);

        $middleware->encryptCookies(except: [
            'UseCDNCache',
            'UseDC',
            'wiki_db_mw__session',
            'wiki_db_mw_Token',
            'wiki_db_mw_UserID',
            'wiki_db_mw_UserName',
            'wiki_test_session',
            'wiki_testToken',
            'wiki_testUserID',
            'wiki_testUserName',
            'wiki_devToken',
            'wiki_devUserID',
            'wiki_devUserName',
            'wiki_dev_mw__session',
            'wiki_dev_mw_Token',
            'wiki_dev_mw_UserID',
            'wiki_dev_mw_UserName',
            'authenticated',
            'restarters_apitoken'
        ]);

        $middleware->append(\App\Http\Middleware\HttpsProtocol::class);

        $middleware->web([
            \App\Http\Middleware\CheckForRepairNetwork::class,
            \App\Http\Middleware\LanguageSwitcher::class,
            \App\Http\Middleware\LogHTTPErrorsToSentry::class,
        ]);

        $middleware->throttleApi();

        $middleware->group('translation', [
            \App\Http\Middleware\VerifyTranslationAccess::class,
        ]);

        $middleware->alias([
            'AcceptUserInvites' => \App\Http\Middleware\AcceptUserInvites::class,
            'ensureAPIToken' => \App\Http\Middleware\EnsureAPIToken::class,
            'localeSessionRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
            'localeViewPath' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
            'localizationRedirect' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
            'localize' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
            'verifyUserConsent' => \App\Http\Middleware\VerifyUserConsent::class,
        ]);

        $middleware->priority([
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\Authenticate::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
