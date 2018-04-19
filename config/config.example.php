<?php
/** Application Name **/
define( 'APPNAME',  'Fixometer');
define( 'APPKEY',   'l[56pOkjg_I8874.');  // should be a random string
define( 'APPEMAIL', 'your@email.org'); // auto generated emails are sent from this address
define( 'SUPPORT_CONTACT_EMAIL', "fry@planetexpress.com"); // address users can contact for help
define('NOTIFICATION_EMAIL', "hubert@planetexpress.com");
define('GA_TRACKING_ID', "UA-12345678-1");

/** Secret! **/
define( 'SECRET',   strrev(md5(APPKEY)));

/** system status: can be development or production **/
define( 'SYSTEM_STATUS', 'development');

/** system root path and directory separator **/
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', $_SERVER['DOCUMENT_ROOT'] );
/** system directories **/
define('DIR_UPLOADS', ROOT . DS . 'public' . DS . 'uploads');

/** session keys **/
define('SESSIONKEY', md5(APPKEY));
define('SESSIONNAME', md5(APPKEY . SESSIONKEY));
define('SESSIONSALT', strrev(SESSIONKEY));

/** urls **/
define('HTTP_PROTOCOL', 'http');
define('BASE_URL', HTTP_PROTOCOL . '://' . $_SERVER['HTTP_HOST']);
define('UPLOADS_URL', BASE_URL . '/' . 'uploads' . '/' );

/** date/time
 * w/out this PHP throws warnings all over the place.
 * Should be set to same timezone as MySQL server for consistency.
 * */
date_default_timezone_set('Europe/London');

/** Wordpress Remote Publishing endpoint **/

define('WP_XMLRPC_ENDPOINT', 'endpoint');
define('WP_XMLRPC_USER', 'wp_use');
define('WP_XMLRPC_PSWD', 'wp_pwd');


/** languages **/
define('DEFAULT_LANG', 'en');
define('LANGUAGE_COOKIE', APPNAME . '_language');
define('HIGHLIGHT_I18N', true);
$fixometer_languages = array(
  'en' => 'English',
  'de' => 'Deutsch',
  'it' => 'Italiano',
  'no' => 'Norsk',
);

/** feature toggles **/
define('FEATURE__LANGUAGE_SWITCHER', false);
define('FEATURE__DEVICE_PHOTOS', false);
define('FEATURE__DEVICE_AGE', false);
