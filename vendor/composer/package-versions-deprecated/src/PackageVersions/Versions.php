<?php

declare(strict_types=1);

namespace PackageVersions;

use Composer\InstalledVersions;
use OutOfBoundsException;

class_exists(InstalledVersions::class);

/**
 * This class is generated by composer/package-versions-deprecated, specifically by
 * @see \PackageVersions\Installer
 *
 * This file is overwritten at every run of `composer install` or `composer update`.
 *
 * @deprecated in favor of the Composer\InstalledVersions class provided by Composer 2. Require composer-runtime-api:^2 to ensure it is present.
 */
final class Versions
{
    /**
     * @deprecated please use {@see self::rootPackageName()} instead.
     *             This constant will be removed in version 2.0.0.
     */
    const ROOT_PACKAGE_NAME = 'laravel/laravel';

    /**
     * Array of all available composer packages.
     * Dont read this array from your calling code, but use the \PackageVersions\Versions::getVersion() method instead.
     *
     * @var array<string, string>
     * @internal
     */
    const VERSIONS          = array (
  'addwiki/mediawiki-api' => '0.7.3@cd1321526235b0a0507f92259254bd738ffb39d3',
  'addwiki/mediawiki-api-base' => '2.8.0@d8eff31b54fd39d90eb14457e5ce478cfdf08394',
  'addwiki/mediawiki-datamodel' => '0.8.0@ed644d977f96bd9f1b165fbb9f186dbdf0c0ff7d',
  'asm89/stack-cors' => '1.3.0@b9c31def6a83f84b4d4a40d35996d375755f0e08',
  'barryvdh/laravel-translation-manager' => 'v0.6.3@b21c18afdb1315ab616005b6d33104802a405ebc',
  'caseyamcl/guzzle_retry_middleware' => 'v2.7@e6717d8460e5ef40db6d2e7218069a2826f69138',
  'clue/stream-filter' => 'v1.6.0@d6169430c7731d8509da7aecd0af756a5747b78e',
  'composer/package-versions-deprecated' => '1.11.99.5@b4f54f74ef3453349c24a845d22392cd31e65f1d',
  'cviebrock/discourse-php' => '0.9.3@9a56e2afec067ee7441663eb82db3d4446c25bc2',
  'cweagans/composer-patches' => '1.7.2@e9969cfc0796e6dea9b4e52f77f18e1065212871',
  'doctrine/cache' => '2.2.0@1ca8f21980e770095a31456042471a57bc4c68fb',
  'doctrine/dbal' => '2.13.9@c480849ca3ad6706a39c970cdfe6888fa8a058b8',
  'doctrine/deprecations' => 'v1.0.0@0e2a4f1f8cdfc7a92ec3b01c9334898c806b30de',
  'doctrine/event-manager' => '1.1.1@41370af6a30faa9dc0368c4a6814d596e81aba7f',
  'doctrine/inflector' => '2.0.4@8b7ff3e4b7de6b2c84da85637b59fd2880ecaa89',
  'doctrine/lexer' => '1.2.3@c268e882d4dbdd85e36e4ad69e02dc284f89d229',
  'dragonmantank/cron-expression' => 'v2.3.1@65b2d8ee1f10915efb3b55597da3404f096acba2',
  'egulias/email-validator' => '2.1.25@0dbf5d78455d4d6a41d186da50adc1122ec066f4',
  'fakerphp/faker' => 'v1.9.2@84220cf137a9344acffb10374e781fed785ff307',
  'fideloper/proxy' => '4.4.1@c073b2bd04d1c90e04dc1b787662b558dd65ade0',
  'filp/whoops' => '2.14.5@a63e5e8f26ebbebf8ed3c5c691637325512eb0dc',
  'fruitcake/laravel-cors' => 'v0.11.4@03492f1a3bc74a05de23f93b94ac7cc5c173eec9',
  'guzzlehttp/guzzle' => '6.5.8@a52f0440530b54fa079ce76e8c5d196a42cad981',
  'guzzlehttp/promises' => '1.5.1@fe752aedc9fd8fcca3fe7ad05d419d32998a06da',
  'guzzlehttp/psr7' => '1.9.0@e98e3e6d4f86621a9b75f623996e6bbdeb4b9318',
  'hieu-le/wordpress-xmlrpc-client' => '2.6.0@4eced3821b41ba21ce314569b79d7302d65f4b16',
  'http-interop/http-factory-guzzle' => '1.2.0@8f06e92b95405216b237521cc64c804dd44c4a81',
  'intervention/image' => '2.7.2@04be355f8d6734c826045d02a1079ad658322dad',
  'jaybizzle/crawler-detect' => 'v1.2.111@d572ed4a65a70a2d2871dc5137c9c5b7e69745ab',
  'jean85/pretty-package-versions' => '1.6.0@1e0104b46f045868f11942aea058cd7186d6c303',
  'jenssegers/agent' => 'v2.6.4@daa11c43729510b3700bc34d414664966b03bffe',
  'laravel/framework' => 'v6.20.44@505ebcdeaa9ca56d6d7dbf38ed4f53998c973ed0',
  'laravel/tinker' => 'v2.7.2@dff39b661e827dae6e092412f976658df82dbac5',
  'laravelcollective/html' => 'v6.3.0@78c3cb516ac9e6d3d76cad9191f81d217302dea6',
  'league/commonmark' => '1.6.7@2b8185c13bc9578367a5bf901881d1c1b5bbd09b',
  'league/csv' => '9.8.0@9d2e0265c5d90f5dd601bc65ff717e05cec19b47',
  'league/flysystem' => '1.1.9@094defdb4a7001845300334e7c1ee2335925ef99',
  'league/mime-type-detection' => '1.11.0@ff6248ea87a9f116e78edd6002e39e5128a0d4dd',
  'mariuzzo/laravel-js-localization' => 'v1.9.0@12531672e995296e280378251efb37f3d1285693',
  'mcamara/laravel-localization' => 'v1.7.0@27565315c585e90d8d93aa06afd49d6a5992fe5a',
  'mobiledetect/mobiledetectlib' => '2.8.39@0fd6753003fc870f6e229bae869cc1337c99bc45',
  'monolog/monolog' => '2.7.0@5579edf28aee1190a798bfa5be8bc16c563bd524',
  'msurguy/honeypot' => '1.1.7@6e0d37201f936a8d8f3e2825dc038666d140fcd7',
  'nesbot/carbon' => '2.59.1@a9000603ea337c8df16cc41f8b6be95a65f4d0f5',
  'nikic/php-parser' => 'v4.14.0@34bea19b6e03d8153165d8f30bba4c3be86184c1',
  'nyholm/psr7' => '1.5.1@f734364e38a876a23be4d906a2a089e1315be18a',
  'opis/closure' => '3.6.3@3d81e4309d2a927abbe66df935f4bb60082805ad',
  'owen-it/laravel-auditing' => 'v12.2.1@98f1cfddbc4ed257e5644fe02e97db5674c7571a',
  'paragonie/random_compat' => 'v9.99.100@996434e5492cb4c3edcb9168db6fbb1359ef965a',
  'php-http/client-common' => '2.5.0@d135751167d57e27c74de674d6a30cef2dc8e054',
  'php-http/discovery' => '1.14.2@c8d48852fbc052454af42f6de27635ddd916b959',
  'php-http/httplug' => '2.3.0@f640739f80dfa1152533976e3c112477f69274eb',
  'php-http/message' => '1.13.0@7886e647a30a966a1a8d1dad1845b71ca8678361',
  'php-http/message-factory' => 'v1.0.2@a478cb11f66a6ac48d8954216cfed9aa06a501a1',
  'php-http/promise' => '1.1.0@4c4c1f9b7289a2ec57cde7f1e9762a5789506f88',
  'phpoption/phpoption' => '1.8.1@eab7a0df01fe2344d172bff4cd6dbd3f8b84ad15',
  'psr/container' => '1.1.2@513e0666f7216c7459170d56df27dfcefe1689ea',
  'psr/http-client' => '1.0.1@2dfb5f6c5eff0e91e20e913f8c5452ed95b86621',
  'psr/http-factory' => '1.0.1@12ac7fcd07e5b077433f5f2bee95b3a771bf61be',
  'psr/http-message' => '1.0.1@f6561bf28d520154e4b0ec72be95418abe6d9363',
  'psr/log' => '1.1.4@d49695b909c3b7628b6289db5479a1c204601f11',
  'psr/simple-cache' => '1.0.1@408d5eafb83c57f6365a3ca330ff23aa4a5fa39b',
  'psy/psysh' => 'v0.11.5@c23686f9c48ca202710dbb967df8385a952a2daf',
  'ralouphie/getallheaders' => '3.0.3@120b605dfeb996808c31b6477290a714d356e822',
  'ramsey/uuid' => '3.9.6@ffa80ab953edd85d5b6c004f96181a538aad35a3',
  'sentry/sdk' => '3.2.0@6d78bd83b43efbb52f81d6824f4af344fa9ba292',
  'sentry/sentry' => '3.6.1@5b8f2934b0b20bb01da11c76985ceb5bd6c6af91',
  'sentry/sentry-laravel' => '2.12.1@bf7b4e6d43f0cf0c320041bb7d3a2a28c7edca57',
  'soundasleep/html2text' => '1.1.0@3243a7107878a61685d2eccf99918d6479e039fc',
  'spatie/calendar-links' => '1.7.2@bb96f1db8478009ad8f206a3bafb2b9aa6b648c3',
  'spinen/laravel-discourse-sso' => '2.7.0@1246acb95cd437d3f3387e8ad5c9838b0036d286',
  'swiftmailer/swiftmailer' => 'v6.3.0@8a5d5072dca8f48460fce2f4131fcc495eec654c',
  'symfony/console' => 'v4.4.43@8a2628d2d5639f35113dc1b833ecd91e1ed1cf46',
  'symfony/css-selector' => 'v5.4.3@b0a190285cd95cb019237851205b8140ef6e368e',
  'symfony/debug' => 'v4.4.41@6637e62480b60817b9a6984154a533e8e64c6bd5',
  'symfony/deprecation-contracts' => 'v2.5.2@e8b495ea28c1d97b5e0c121748d6f9b53d075c66',
  'symfony/error-handler' => 'v4.4.41@529feb0e03133dbd5fd3707200147cc4903206da',
  'symfony/event-dispatcher' => 'v4.4.42@708e761740c16b02c86e3f0c932018a06b895d40',
  'symfony/event-dispatcher-contracts' => 'v1.1.13@1d5cd762abaa6b2a4169d3e77610193a7157129e',
  'symfony/finder' => 'v4.4.41@40790bdf293b462798882ef6da72bb49a4a6633a',
  'symfony/http-client' => 'v5.4.9@dc0b15e42b762c040761c1eb9ce86a55d47cf672',
  'symfony/http-client-contracts' => 'v2.5.2@ba6a9f0e8f3edd190520ee3b9a958596b6ca2e70',
  'symfony/http-foundation' => 'v4.4.43@4441dada27f9208e03f449d73cb9253c639e53c5',
  'symfony/http-kernel' => 'v4.4.43@c4c33fb9203e6f166ac0f318ce34e00686702522',
  'symfony/mime' => 'v5.4.10@02265e1e5111c3cd7480387af25e82378b7ab9cc',
  'symfony/options-resolver' => 'v5.4.3@cc1147cb11af1b43f503ac18f31aa3bec213aba8',
  'symfony/polyfill-ctype' => 'v1.26.0@6fd1b9a79f6e3cf65f9e679b23af304cd9e010d4',
  'symfony/polyfill-iconv' => 'v1.26.0@143f1881e655bebca1312722af8068de235ae5dc',
  'symfony/polyfill-intl-idn' => 'v1.26.0@59a8d271f00dd0e4c2e518104cc7963f655a1aa8',
  'symfony/polyfill-intl-normalizer' => 'v1.26.0@219aa369ceff116e673852dce47c3a41794c14bd',
  'symfony/polyfill-mbstring' => 'v1.26.0@9344f9cb97f3b19424af1a21a3b0e75b0a7d8d7e',
  'symfony/polyfill-php72' => 'v1.26.0@bf44a9fd41feaac72b074de600314a93e2ae78e2',
  'symfony/polyfill-php73' => 'v1.26.0@e440d35fa0286f77fb45b79a03fedbeda9307e85',
  'symfony/polyfill-php80' => 'v1.26.0@cfa0ae98841b9e461207c13ab093d76b0fa7bace',
  'symfony/polyfill-uuid' => 'v1.26.0@a41886c1c81dc075a09c71fe6db5b9d68c79de23',
  'symfony/process' => 'v4.4.41@9eedd60225506d56e42210a70c21bb80ca8456ce',
  'symfony/psr-http-message-bridge' => 'v2.1.2@22b37c8a3f6b5d94e9cdbd88e1270d96e2f97b34',
  'symfony/routing' => 'v4.4.41@c25e38d403c00d5ddcfc514f016f1b534abdf052',
  'symfony/service-contracts' => 'v2.5.2@4b426aac47d6427cc1a1d0f7e2ac724627f5966c',
  'symfony/translation' => 'v4.4.41@dcb67eae126e74507e0b4f0b9ac6ef35b37c3331',
  'symfony/translation-contracts' => 'v2.5.2@136b19dd05cdf0709db6537d058bcab6dd6e2dbe',
  'symfony/var-dumper' => 'v4.4.42@742aab50ad097bcb62d91fccb613f66b8047d2ca',
  'tedivm/jshrink' => 'v1.4.0@0513ba1407b1f235518a939455855e6952a48bbc',
  'tijsverkoyen/css-to-inline-styles' => '2.2.4@da444caae6aca7a19c0c140f68c6182e337d5b1c',
  'twbs/bootstrap' => 'v4.1.0@8f7bd419935adfcd53c471a0202083464800619e',
  'vlucas/phpdotenv' => 'v3.6.10@5b547cdb25825f10251370f57ba5d9d924e6f68e',
  'wouternl/laravel-drip' => '1.2.4@f9d96140ba62f4ddb4df909e20931e897e0edd54',
  'laravel/laravel' => 'dev-production@b0f865ba6f850144d34275e38b70e44416f629b5',
);

    private function __construct()
    {
    }

    /**
     * @psalm-pure
     *
     * @psalm-suppress ImpureMethodCall we know that {@see InstalledVersions} interaction does not
     *                                  cause any side effects here.
     */
    public static function rootPackageName() : string
    {
        if (!self::composer2ApiUsable()) {
            return self::ROOT_PACKAGE_NAME;
        }

        return InstalledVersions::getRootPackage()['name'];
    }

    /**
     * @throws OutOfBoundsException If a version cannot be located.
     *
     * @psalm-param key-of<self::VERSIONS> $packageName
     * @psalm-pure
     *
     * @psalm-suppress ImpureMethodCall we know that {@see InstalledVersions} interaction does not
     *                                  cause any side effects here.
     */
    public static function getVersion(string $packageName): string
    {
        if (self::composer2ApiUsable()) {
            return InstalledVersions::getPrettyVersion($packageName)
                . '@' . InstalledVersions::getReference($packageName);
        }

        if (isset(self::VERSIONS[$packageName])) {
            return self::VERSIONS[$packageName];
        }

        throw new OutOfBoundsException(
            'Required package "' . $packageName . '" is not installed: check your ./vendor/composer/installed.json and/or ./composer.lock files'
        );
    }

    private static function composer2ApiUsable(): bool
    {
        if (!class_exists(InstalledVersions::class, false)) {
            return false;
        }

        if (method_exists(InstalledVersions::class, 'getAllRawData')) {
            $rawData = InstalledVersions::getAllRawData();
            if (count($rawData) === 1 && count($rawData[0]) === 0) {
                return false;
            }
        } else {
            $rawData = InstalledVersions::getRawData();
            if ($rawData === null || $rawData === []) {
                return false;
            }
        }

        return true;
    }
}
