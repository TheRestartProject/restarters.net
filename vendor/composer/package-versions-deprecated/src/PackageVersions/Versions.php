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
  'barryvdh/laravel-translation-manager' => 'v0.5.10@18ed550eb74f9e61d2fc72d06dfa576296d0d5cb',
  'caseyamcl/guzzle_retry_middleware' => 'v2.6.1@2d6c8e0bdc0c7102b3000ca157f535da48bd0bd0',
  'clue/stream-filter' => 'v1.5.0@aeb7d8ea49c7963d3b581378955dbf5bc49aa320',
  'composer/package-versions-deprecated' => '1.11.99.4@b174585d1fe49ceed21928a945138948cb394600',
  'cviebrock/discourse-php' => '0.9.3@9a56e2afec067ee7441663eb82db3d4446c25bc2',
  'cweagans/composer-patches' => '1.7.1@9888dcc74993c030b75f3dd548bb5e20cdbd740c',
  'doctrine/cache' => '2.1.1@331b4d5dbaeab3827976273e9356b3b453c300ce',
  'doctrine/dbal' => '2.13.4@2411a55a2a628e6d8dd598388ab13474802c7b6e',
  'doctrine/deprecations' => 'v0.5.3@9504165960a1f83cc1480e2be1dd0a0478561314',
  'doctrine/event-manager' => '1.1.1@41370af6a30faa9dc0368c4a6814d596e81aba7f',
  'doctrine/inflector' => '2.0.4@8b7ff3e4b7de6b2c84da85637b59fd2880ecaa89',
  'doctrine/lexer' => '1.2.1@e864bbf5904cb8f5bb334f99209b48018522f042',
  'dragonmantank/cron-expression' => 'v2.3.1@65b2d8ee1f10915efb3b55597da3404f096acba2',
  'egulias/email-validator' => '2.1.25@0dbf5d78455d4d6a41d186da50adc1122ec066f4',
  'fakerphp/faker' => 'v1.9.2@84220cf137a9344acffb10374e781fed785ff307',
  'fideloper/proxy' => '4.4.1@c073b2bd04d1c90e04dc1b787662b558dd65ade0',
  'filp/whoops' => '2.14.4@f056f1fe935d9ed86e698905a957334029899895',
  'firebase/php-jwt' => 'v5.5.1@83b609028194aa042ea33b5af2d41a7427de80e6',
  'fruitcake/laravel-cors' => 'v0.11.4@03492f1a3bc74a05de23f93b94ac7cc5c173eec9',
  'google/auth' => 'v1.18.0@21dd478e77b0634ed9e3a68613f74ed250ca9347',
  'google/cloud-core' => 'v1.43.1@60b47793e0c83f0e02a8197ef11ab1f599c348da',
  'google/cloud-translate' => 'v1.12.1@fe04d8ad8916951c868df2ea1638e597464a42dd',
  'google/common-protos' => '1.3.1@c348d1545fbeac7df3c101fdc687aba35f49811f',
  'google/gax' => 'v1.10.0@5222f7712e73d266490c742dc9bc602602ae00a5',
  'google/grpc-gcp' => 'v0.2.0@2465c2273e11ada1e95155aa1e209f3b8f03c314',
  'google/protobuf' => 'v3.19.1@83fe8edf7469ffdd83cb4b4e62249c154f961b9b',
  'grpc/grpc' => '1.39.0@101485614283d1ecb6b2ad1d5b95dc82495931db',
  'guzzlehttp/guzzle' => '6.5.5@9d4290de1cfd701f38099ef7e183b64b4b7b0c5e',
  'guzzlehttp/promises' => '1.5.1@fe752aedc9fd8fcca3fe7ad05d419d32998a06da',
  'guzzlehttp/psr7' => '1.8.5@337e3ad8e5716c15f9657bd214d16cc5e69df268',
  'hieu-le/wordpress-xmlrpc-client' => '2.6.0@4eced3821b41ba21ce314569b79d7302d65f4b16',
  'http-interop/http-factory-guzzle' => '1.2.0@8f06e92b95405216b237521cc64c804dd44c4a81',
  'intervention/image' => '2.7.0@9a8cc99d30415ec0b3f7649e1647d03a55698545',
  'jaybizzle/crawler-detect' => 'v1.2.108@69a38c09f99ee056e7cca9fe7c8b1952fd62b837',
  'jean85/pretty-package-versions' => '1.6.0@1e0104b46f045868f11942aea058cd7186d6c303',
  'jenssegers/agent' => 'v2.6.4@daa11c43729510b3700bc34d414664966b03bffe',
  'laravel/framework' => 'v6.20.38@1b301ac996e65d1319bc0d28716521ef6d1cd5c5',
  'laravel/tinker' => 'v2.6.2@c808a7227f97ecfd9219fbf913bad842ea854ddc',
  'laravelcollective/html' => 'v6.2.1@ae15b9c4bf918ec3a78f092b8555551dd693fde3',
  'league/commonmark' => '1.6.6@c4228d11e30d7493c6836d20872f9582d8ba6dcf',
  'league/csv' => '9.7.2@8544655c460fd01eed0ad258e514488d4b388645',
  'league/flysystem' => '1.1.5@18634df356bfd4119fe3d6156bdb990c414c14ea',
  'league/mime-type-detection' => '1.8.0@b38b25d7b372e9fddb00335400467b223349fd7e',
  'mariuzzo/laravel-js-localization' => 'v1.8.0@c3904018c656a11150949aa2ed00dcfcb1dfa3fc',
  'mcamara/laravel-localization' => '1.6.2@645819da9ef29f3ba7588d9b4598799caf0b2463',
  'mobiledetect/mobiledetectlib' => '2.8.37@9841e3c46f5bd0739b53aed8ac677fa712943df7',
  'monolog/monolog' => '2.3.5@fd4380d6fc37626e2f799f29d91195040137eba9',
  'msurguy/honeypot' => '1.1.6@8c782fde5f228619cc0a6107f443021236870a8e',
  'nesbot/carbon' => '2.54.0@eed83939f1aed3eee517d03a33f5ec587ac529b5',
  'nikic/php-parser' => 'v4.13.1@63a79e8daa781cac14e5195e63ed8ae231dd10fd',
  'nyholm/psr7' => '1.4.1@2212385b47153ea71b1c1b1374f8cb5e4f7892ec',
  'opis/closure' => '3.6.2@06e2ebd25f2869e54a306dda991f7db58066f7f6',
  'owen-it/laravel-auditing' => 'v12.0.0@5659c736f17aa75805b60c0d0a23783f3bf5c2a1',
  'paragonie/random_compat' => 'v9.99.100@996434e5492cb4c3edcb9168db6fbb1359ef965a',
  'php-http/client-common' => '2.4.0@29e0c60d982f04017069483e832b92074d0a90b2',
  'php-http/discovery' => '1.14.1@de90ab2b41d7d61609f504e031339776bc8c7223',
  'php-http/httplug' => '2.2.0@191a0a1b41ed026b717421931f8d3bd2514ffbf9',
  'php-http/message' => '1.12.0@39eb7548be982a81085fe5a6e2a44268cd586291',
  'php-http/message-factory' => 'v1.0.2@a478cb11f66a6ac48d8954216cfed9aa06a501a1',
  'php-http/promise' => '1.1.0@4c4c1f9b7289a2ec57cde7f1e9762a5789506f88',
  'phpoption/phpoption' => '1.8.0@5455cb38aed4523f99977c4a12ef19da4bfe2a28',
  'psr/cache' => '1.0.1@d11b50ad223250cf17b86e38383413f5a6764bf8',
  'psr/container' => '1.1.2@513e0666f7216c7459170d56df27dfcefe1689ea',
  'psr/http-client' => '1.0.1@2dfb5f6c5eff0e91e20e913f8c5452ed95b86621',
  'psr/http-factory' => '1.0.1@12ac7fcd07e5b077433f5f2bee95b3a771bf61be',
  'psr/http-message' => '1.0.1@f6561bf28d520154e4b0ec72be95418abe6d9363',
  'psr/log' => '1.1.4@d49695b909c3b7628b6289db5479a1c204601f11',
  'psr/simple-cache' => '1.0.1@408d5eafb83c57f6365a3ca330ff23aa4a5fa39b',
  'psy/psysh' => 'v0.10.9@01281336c4ae557fe4a994544f30d3a1bc204375',
  'ralouphie/getallheaders' => '3.0.3@120b605dfeb996808c31b6477290a714d356e822',
  'ramsey/uuid' => '3.9.6@ffa80ab953edd85d5b6c004f96181a538aad35a3',
  'rize/uri-template' => '0.3.4@2a874863c48d643b9e2e254ab288ec203060a0b8',
  'sentry/sdk' => '3.1.0@f03133b067fdf03fed09ff03daf3f1d68f5f3673',
  'sentry/sentry' => '3.3.4@ecbd09ea5d053a202cf773cb24ab28af820831bd',
  'sentry/sentry-laravel' => '2.9.0@3acb930f1abeb67046097c3912c7b4b9b4303f08',
  'soundasleep/html2text' => '1.1.0@3243a7107878a61685d2eccf99918d6479e039fc',
  'spatie/calendar-links' => '1.6.0@aa984fb84d218cc8962b8f89ef46437298534c24',
  'spinen/laravel-discourse-sso' => '2.5.3@2ebb313025a57984840e7c230ae9c5ccd1a9154a',
  'stichoza/google-translate-php' => 'v4.1.5@85039e0af473e58cc9f42d58e36d9d534a6a6431',
  'swiftmailer/swiftmailer' => 'v6.3.0@8a5d5072dca8f48460fce2f4131fcc495eec654c',
  'symfony/console' => 'v4.4.33@8dbd23ef7a8884051482183ddee8d9061b5feed0',
  'symfony/css-selector' => 'v5.3.4@7fb120adc7f600a59027775b224c13a33530dd90',
  'symfony/debug' => 'v4.4.31@43ede438d4cb52cd589ae5dc070e9323866ba8e0',
  'symfony/deprecation-contracts' => 'v2.4.0@5f38c8804a9e97d23e0c8d63341088cd8a22d627',
  'symfony/error-handler' => 'v4.4.30@51f98f7aa99f00f3b1da6bafe934e67ae6ba6dc5',
  'symfony/event-dispatcher' => 'v4.4.30@2fe81680070043c4c80e7cedceb797e34f377bac',
  'symfony/event-dispatcher-contracts' => 'v1.1.9@84e23fdcd2517bf37aecbd16967e83f0caee25a7',
  'symfony/finder' => 'v4.4.30@70362f1e112280d75b30087c7598b837c1b468b6',
  'symfony/http-client' => 'v5.3.10@710b69ed4bc9469900ec5ae5c3807b0509bee0dc',
  'symfony/http-client-contracts' => 'v2.4.0@7e82f6084d7cae521a75ef2cb5c9457bbda785f4',
  'symfony/http-foundation' => 'v4.4.33@b9a91102f548e0111f4996e8c622fb1d1d479850',
  'symfony/http-kernel' => 'v4.4.33@6f1fcca1154f782796549f4f4e5090bae9525c0e',
  'symfony/mime' => 'v5.3.8@a756033d0a7e53db389618653ae991eba5a19a11',
  'symfony/options-resolver' => 'v5.3.7@4b78e55b179003a42523a362cc0e8327f7a69b5e',
  'symfony/polyfill-ctype' => 'v1.23.0@46cd95797e9df938fdd2b03693b5fca5e64b01ce',
  'symfony/polyfill-iconv' => 'v1.23.0@63b5bb7db83e5673936d6e3b8b3e022ff6474933',
  'symfony/polyfill-intl-idn' => 'v1.23.0@65bd267525e82759e7d8c4e8ceea44f398838e65',
  'symfony/polyfill-intl-normalizer' => 'v1.23.0@8590a5f561694770bdcd3f9b5c69dde6945028e8',
  'symfony/polyfill-mbstring' => 'v1.23.1@9174a3d80210dca8daa7f31fec659150bbeabfc6',
  'symfony/polyfill-php72' => 'v1.23.0@9a142215a36a3888e30d0a9eeea9766764e96976',
  'symfony/polyfill-php73' => 'v1.23.0@fba8933c384d6476ab14fb7b8526e5287ca7e010',
  'symfony/polyfill-php80' => 'v1.23.1@1100343ed1a92e3a38f9ae122fc0eb21602547be',
  'symfony/polyfill-uuid' => 'v1.23.0@9165effa2eb8a31bb3fa608df9d529920d21ddd9',
  'symfony/process' => 'v4.4.30@13d3161ef63a8ec21eeccaaf9a4d7f784a87a97d',
  'symfony/psr-http-message-bridge' => 'v2.1.2@22b37c8a3f6b5d94e9cdbd88e1270d96e2f97b34',
  'symfony/routing' => 'v4.4.30@9ddf033927ad9f30ba2bfd167a7b342cafa13e8e',
  'symfony/service-contracts' => 'v2.4.0@f040a30e04b57fbcc9c6cbcf4dbaa96bd318b9bb',
  'symfony/translation' => 'v4.4.32@db0ba1e85280d8ff11e38d53c70f8814d4d740f5',
  'symfony/translation-contracts' => 'v2.4.0@95c812666f3e91db75385749fe219c5e494c7f95',
  'symfony/var-dumper' => 'v4.4.33@50286e2b7189bfb4f419c0731e86632cddf7c5ee',
  'tanmuhittin/laravel-google-translate' => '2.0.4@2f2d97b7cf0a1296b92a1aeb8cb965bac683c118',
  'tedivm/jshrink' => 'v1.4.0@0513ba1407b1f235518a939455855e6952a48bbc',
  'tijsverkoyen/css-to-inline-styles' => '2.2.3@b43b05cf43c1b6d849478965062b6ef73e223bb5',
  'twbs/bootstrap' => 'v4.1.0@8f7bd419935adfcd53c471a0202083464800619e',
  'vlucas/phpdotenv' => 'v3.6.9@a1bf4c9853d90ade427b4efe35355fc41b3d6988',
  'wouternl/laravel-drip' => '1.2.4@f9d96140ba62f4ddb4df909e20931e897e0edd54',
  'yandex/translate-api' => '1.5.2@c99e69cde3e688fc0f99c4d8a21585226a8e1938',
  'laravel/laravel' => 'dev-production@068408a6f10b1bb9d79dc4a4318a21741c26babc',
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
