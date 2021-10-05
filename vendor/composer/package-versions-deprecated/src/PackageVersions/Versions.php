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
  'barryvdh/laravel-cors' => 'v0.11.4@03492f1a3bc74a05de23f93b94ac7cc5c173eec9',
  'barryvdh/laravel-translation-manager' => 'v0.5.10@18ed550eb74f9e61d2fc72d06dfa576296d0d5cb',
  'caseyamcl/guzzle_retry_middleware' => 'v2.6.1@2d6c8e0bdc0c7102b3000ca157f535da48bd0bd0',
  'clue/stream-filter' => 'v1.5.0@aeb7d8ea49c7963d3b581378955dbf5bc49aa320',
  'composer/package-versions-deprecated' => '1.11.99.2@c6522afe5540d5fc46675043d3ed5a45a740b27c',
  'cviebrock/discourse-php' => '0.9.3@9a56e2afec067ee7441663eb82db3d4446c25bc2',
  'cweagans/composer-patches' => '1.7.1@9888dcc74993c030b75f3dd548bb5e20cdbd740c',
  'dnoegel/php-xdg-base-dir' => 'v0.1.1@8f8a6e48c5ecb0f991c2fdcf5f154a47d85f9ffd',
  'doctrine/cache' => '2.1.1@331b4d5dbaeab3827976273e9356b3b453c300ce',
  'doctrine/dbal' => '2.13.2@8dd39d2ead4409ce652fd4f02621060f009ea5e4',
  'doctrine/deprecations' => 'v0.5.3@9504165960a1f83cc1480e2be1dd0a0478561314',
  'doctrine/event-manager' => '1.1.1@41370af6a30faa9dc0368c4a6814d596e81aba7f',
  'doctrine/inflector' => '1.4.4@4bd5c1cdfcd00e9e2d8c484f79150f67e5d355d9',
  'doctrine/lexer' => '1.2.1@e864bbf5904cb8f5bb334f99209b48018522f042',
  'dragonmantank/cron-expression' => 'v2.3.1@65b2d8ee1f10915efb3b55597da3404f096acba2',
  'egulias/email-validator' => '2.1.25@0dbf5d78455d4d6a41d186da50adc1122ec066f4',
  'erusev/parsedown' => '1.7.4@cb17b6477dfff935958ba01325f2e8a2bfa6dab3',
  'fideloper/proxy' => '4.4.1@c073b2bd04d1c90e04dc1b787662b558dd65ade0',
  'filp/whoops' => '2.14.0@fdf92f03e150ed84d5967a833ae93abffac0315b',
  'firebase/php-jwt' => 'v5.4.0@d2113d9b2e0e349796e72d2a63cf9319100382d2',
  'fzaninotto/faker' => 'v1.9.2@848d8125239d7dbf8ab25cb7f054f1a630e68c2e',
  'google/auth' => 'v1.16.0@c747738d2dd450f541f09f26510198fbedd1c8a0',
  'google/cloud-core' => 'v1.42.2@f3fff3ca4af92c87eb824e5c98aaf003523204a2',
  'google/cloud-translate' => 'v1.10.1@ea5f24247e77ec590b1c1833589df2fa48415c6d',
  'google/common-protos' => '1.3.1@c348d1545fbeac7df3c101fdc687aba35f49811f',
  'google/gax' => 'v1.7.1@48cd41dbea7b8fece8c41100022786d149de64ca',
  'google/grpc-gcp' => '0.1.5@bb9bdbf62f6ae4e73d5209d85b1d0a0b9855ff36',
  'google/protobuf' => 'v3.17.3@ae9282cf11dd2933b7e71a611f9590f07d53d3f3',
  'grpc/grpc' => '1.39.0@101485614283d1ecb6b2ad1d5b95dc82495931db',
  'guzzlehttp/guzzle' => '6.5.5@9d4290de1cfd701f38099ef7e183b64b4b7b0c5e',
  'guzzlehttp/promises' => '1.4.1@8e7d04f1f6450fef59366c399cfad4b9383aa30d',
  'guzzlehttp/psr7' => '1.8.2@dc960a912984efb74d0a90222870c72c87f10c91',
  'hieu-le/wordpress-xmlrpc-client' => '2.6.0@4eced3821b41ba21ce314569b79d7302d65f4b16',
  'http-interop/http-factory-guzzle' => '1.2.0@8f06e92b95405216b237521cc64c804dd44c4a81',
  'intervention/image' => '2.6.1@0925f10b259679b5d8ca58f3a2add9255ffcda45',
  'jaybizzle/crawler-detect' => 'v1.2.106@78bf6792cbf9c569dc0bf2465481978fd2ed0de9',
  'jean85/pretty-package-versions' => '1.6.0@1e0104b46f045868f11942aea058cd7186d6c303',
  'jenssegers/agent' => 'v2.6.4@daa11c43729510b3700bc34d414664966b03bffe',
  'laravel/framework' => 'v5.8.38@78eb4dabcc03e189620c16f436358d41d31ae11f',
  'laravel/helpers' => 'v1.4.1@febb10d8daaf86123825de2cb87f789a3371f0ac',
  'laravel/tinker' => 'v1.0.10@ad571aacbac1539c30d480908f9d0c9614eaf1a7',
  'laravelcollective/html' => 'v5.8.1@3a1c9974ea629eed96e101a24e3852ced382eb29',
  'laravelista/lumen-vendor-publish' => '2.1.0@7684f731187277ac2ca7747558ec2ce3835bfc91',
  'league/csv' => '9.7.1@0ec57e8264ec92565974ead0d1724cf1026e10c1',
  'league/flysystem' => '1.1.4@f3ad69181b8afed2c9edf7be5a2918144ff4ea32',
  'league/mime-type-detection' => '1.7.0@3b9dff8aaf7323590c1d2e443db701eb1f9aa0d3',
  'mariuzzo/laravel-js-localization' => 'v1.8.0@c3904018c656a11150949aa2ed00dcfcb1dfa3fc',
  'mcamara/laravel-localization' => '1.6.1@4f0bfd89e5ee8100cb8cff8ca2cc3b985ed46694',
  'mobiledetect/mobiledetectlib' => '2.8.37@9841e3c46f5bd0739b53aed8ac677fa712943df7',
  'monolog/monolog' => '1.26.1@c6b00f05152ae2c9b04a448f99c7590beb6042f5',
  'msurguy/honeypot' => '1.1.6@8c782fde5f228619cc0a6107f443021236870a8e',
  'nesbot/carbon' => '2.51.1@8619c299d1e0d4b344e1f98ca07a1ce2cfbf1922',
  'nikic/php-parser' => 'v4.12.0@6608f01670c3cc5079e18c1dab1104e002579143',
  'nyholm/psr7' => '1.4.1@2212385b47153ea71b1c1b1374f8cb5e4f7892ec',
  'opis/closure' => '3.6.2@06e2ebd25f2869e54a306dda991f7db58066f7f6',
  'owen-it/laravel-auditing' => 'v9.3.2@38de27e8d8cdb63b51a7b0a4f8865af529bb0c44',
  'paragonie/random_compat' => 'v9.99.99@84b4dfb120c6f9b4ff7b3685f9b8f1aa365a0c95',
  'php-http/client-common' => '2.4.0@29e0c60d982f04017069483e832b92074d0a90b2',
  'php-http/discovery' => '1.14.0@778f722e29250c1fac0bbdef2c122fa5d038c9eb',
  'php-http/httplug' => '2.2.0@191a0a1b41ed026b717421931f8d3bd2514ffbf9',
  'php-http/message' => '1.11.2@295c82867d07261f2fa4b3a26677519fc6f7f5f6',
  'php-http/message-factory' => 'v1.0.2@a478cb11f66a6ac48d8954216cfed9aa06a501a1',
  'php-http/promise' => '1.1.0@4c4c1f9b7289a2ec57cde7f1e9762a5789506f88',
  'php-parallel-lint/php-console-color' => 'v0.3@b6af326b2088f1ad3b264696c9fd590ec395b49e',
  'php-parallel-lint/php-console-highlighter' => 'v0.5@21bf002f077b177f056d8cb455c5ed573adfdbb8',
  'phpoption/phpoption' => '1.7.5@994ecccd8f3283ecf5ac33254543eb0ac946d525',
  'psr/cache' => '1.0.1@d11b50ad223250cf17b86e38383413f5a6764bf8',
  'psr/container' => '1.1.1@8622567409010282b7aeebe4bb841fe98b58dcaf',
  'psr/http-client' => '1.0.1@2dfb5f6c5eff0e91e20e913f8c5452ed95b86621',
  'psr/http-factory' => '1.0.1@12ac7fcd07e5b077433f5f2bee95b3a771bf61be',
  'psr/http-message' => '1.0.1@f6561bf28d520154e4b0ec72be95418abe6d9363',
  'psr/log' => '1.1.4@d49695b909c3b7628b6289db5479a1c204601f11',
  'psr/simple-cache' => '1.0.1@408d5eafb83c57f6365a3ca330ff23aa4a5fa39b',
  'psy/psysh' => 'v0.9.12@90da7f37568aee36b116a030c5f99c915267edd4',
  'ralouphie/getallheaders' => '3.0.3@120b605dfeb996808c31b6477290a714d356e822',
  'ramsey/uuid' => '3.9.3@7e1633a6964b48589b142d60542f9ed31bd37a92',
  'rize/uri-template' => '0.3.3@6e0b97e00e0f36c652dd3c37b194ef07de669b82',
  'sentry/sdk' => '3.1.0@f03133b067fdf03fed09ff03daf3f1d68f5f3673',
  'sentry/sentry' => '3.3.2@3d733139a3ba2d1d3a8011580e3acf0ba1d0dc9b',
  'sentry/sentry-laravel' => '2.7.0@91d6644fee3ba447769dc73eda2487a93218c04e',
  'soundasleep/html2text' => '1.1.0@3243a7107878a61685d2eccf99918d6479e039fc',
  'spatie/calendar-links' => '1.6.0@aa984fb84d218cc8962b8f89ef46437298534c24',
  'spinen/laravel-discourse-sso' => '2.5.3@2ebb313025a57984840e7c230ae9c5ccd1a9154a',
  'stichoza/google-translate-php' => 'v4.1.5@85039e0af473e58cc9f42d58e36d9d534a6a6431',
  'swiftmailer/swiftmailer' => 'v6.2.7@15f7faf8508e04471f666633addacf54c0ab5933',
  'symfony/console' => 'v4.4.29@8baf0bbcfddfde7d7225ae8e04705cfd1081cd7b',
  'symfony/css-selector' => 'v5.3.4@7fb120adc7f600a59027775b224c13a33530dd90',
  'symfony/debug' => 'v4.4.27@2f9160e92eb64c95da7368c867b663a8e34e980c',
  'symfony/deprecation-contracts' => 'v2.4.0@5f38c8804a9e97d23e0c8d63341088cd8a22d627',
  'symfony/error-handler' => 'v4.4.27@16ac2be1c0f49d6d9eb9d3ce9324bde268717905',
  'symfony/event-dispatcher' => 'v4.4.27@958a128b184fcf0ba45ec90c0e88554c9327c2e9',
  'symfony/event-dispatcher-contracts' => 'v1.1.9@84e23fdcd2517bf37aecbd16967e83f0caee25a7',
  'symfony/finder' => 'v4.4.27@42414d7ac96fc2880a783b872185789dea0d4262',
  'symfony/http-client' => 'v5.3.4@67c177d4df8601d9a71f9d615c52171c98d22d74',
  'symfony/http-client-contracts' => 'v2.4.0@7e82f6084d7cae521a75ef2cb5c9457bbda785f4',
  'symfony/http-foundation' => 'v4.4.29@7016057b01f0ed3ec3ba1f31a580b6661667c2e1',
  'symfony/http-kernel' => 'v4.4.29@752b170e1ba0dd4104e7fa17c1cef1ec8a7fc506',
  'symfony/mime' => 'v5.3.4@633e4e8afe9e529e5599d71238849a4218dd497b',
  'symfony/options-resolver' => 'v5.3.4@a603e5701bd6e305cfc777a8b50bf081ef73105e',
  'symfony/polyfill-ctype' => 'v1.23.0@46cd95797e9df938fdd2b03693b5fca5e64b01ce',
  'symfony/polyfill-iconv' => 'v1.23.0@63b5bb7db83e5673936d6e3b8b3e022ff6474933',
  'symfony/polyfill-intl-idn' => 'v1.23.0@65bd267525e82759e7d8c4e8ceea44f398838e65',
  'symfony/polyfill-intl-normalizer' => 'v1.23.0@8590a5f561694770bdcd3f9b5c69dde6945028e8',
  'symfony/polyfill-mbstring' => 'v1.23.1@9174a3d80210dca8daa7f31fec659150bbeabfc6',
  'symfony/polyfill-php72' => 'v1.23.0@9a142215a36a3888e30d0a9eeea9766764e96976',
  'symfony/polyfill-php73' => 'v1.23.0@fba8933c384d6476ab14fb7b8526e5287ca7e010',
  'symfony/polyfill-php80' => 'v1.23.1@1100343ed1a92e3a38f9ae122fc0eb21602547be',
  'symfony/polyfill-uuid' => 'v1.23.0@9165effa2eb8a31bb3fa608df9d529920d21ddd9',
  'symfony/process' => 'v4.4.27@0b7dc5599ac4aa6d7b936c8f7d10abae64f6cf7f',
  'symfony/psr-http-message-bridge' => 'v2.1.1@c9012994c4b4fb23e7c57dd86b763a417a04feba',
  'symfony/routing' => 'v4.4.27@244609821beece97167fa7ba4eef49d2a31862db',
  'symfony/service-contracts' => 'v2.4.0@f040a30e04b57fbcc9c6cbcf4dbaa96bd318b9bb',
  'symfony/translation' => 'v4.4.27@2e3c0f2bf704d635ba862e7198d72331a62d82ba',
  'symfony/translation-contracts' => 'v2.4.0@95c812666f3e91db75385749fe219c5e494c7f95',
  'symfony/var-dumper' => 'v4.4.27@391d6d0e7a06ab54eb7c38fab29b8d174471b3ba',
  'tanmuhittin/laravel-google-translate' => '2.0.4@2f2d97b7cf0a1296b92a1aeb8cb965bac683c118',
  'tedivm/jshrink' => 'v1.4.0@0513ba1407b1f235518a939455855e6952a48bbc',
  'tijsverkoyen/css-to-inline-styles' => '2.2.3@b43b05cf43c1b6d849478965062b6ef73e223bb5',
  'twbs/bootstrap' => 'v4.1.0@8f7bd419935adfcd53c471a0202083464800619e',
  'vlucas/phpdotenv' => 'v3.6.8@5e679f7616db829358341e2d5cccbd18773bdab8',
  'wouternl/laravel-drip' => '1.2.4@f9d96140ba62f4ddb4df909e20931e897e0edd54',
  'yandex/translate-api' => '1.5.2@c99e69cde3e688fc0f99c4d8a21585226a8e1938',
  'laravel/laravel' => 'dev-production@be38e3bea26a5f6c23a1e81be99bf201fdd21748',
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
        if (!class_exists(InstalledVersions::class, false) || !(method_exists(InstalledVersions::class, 'getAllRawData') ? InstalledVersions::getAllRawData() : InstalledVersions::getRawData())) {
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
        if (class_exists(InstalledVersions::class, false) && (method_exists(InstalledVersions::class, 'getAllRawData') ? InstalledVersions::getAllRawData() : InstalledVersions::getRawData())) {
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
}
