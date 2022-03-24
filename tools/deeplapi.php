<?php

/**
 * Script to pull translations from DeepL for microtasks.
 * DeepL API https://www.deepl.com/docs-api
 * Requires DeepL API key.
 * Assumes db access with table that has all source and target columns.
 * Example microtask table structure:
 *
 * CREATE TABLE `devices_foo` (
 *   `id_ords` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
 *   `data_provider` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
 *   `country` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
 *   `partner_product_category` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
 *   `product_category` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
 *   `brand` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
 *   `year_of_manufacture` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
 *   `product_age` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
 *   `repair_status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
 *   `event_date` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
 *   `problem` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
 *   `googletrans` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
 *   `language` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
 *   `fault_type_id` int(10) UNSIGNED NOT NULL,
 *   `en` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
 *   `de` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
 *   `nl` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
 *   `fr` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
 *   `it` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
 *   `es` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 *
 */

global $dbh, $fh, $key, $table, $langs;

$domfree = 'https://api-free.deepl.com';
$keyfree = '<free_api_key>';
$dompaid = 'https://api.deepl.com';
$keypaid = '<paid_api_key>';
$version = '/v2';
$langs = ['EN', 'DE', 'NL', 'FR', 'IT', 'ES'];
$table = '<table_name>';
$cfg = [
    'host' => '127.0.0.1',
    'username' => '',
    'password' => '',
    'dbname' => 'restarters.test',
];

$dom = $dompaid . $version;
$key = $keypaid;

$date = date("YmdHis");

file_put_contents("./deeplusage_{$date}.json", get_usage());

exit();

if (!$dbh = connect($cfg)) {
    exit(0);
}
if (!$fh = fopen("./deepl_{$date}.log", 'w')) {
    exit(0);
}

foreach ($langs as $lang) {
    try {
        // If `en` translation empty assume resuming after pause/stop/error/quota exceeded/...
        $sql = "SELECT `id_ords`, `problem`, `language` FROM `{$table}` WHERE `language` = '" . strtolower($lang) . "' AND `en` = ''";
        if ($res = $dbh->query($sql)) {
            $data = $res->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($data)) {
                fetch_translations($data);
            } else {
                deepl_log("No records found for language {$lang}");
            }
        }
    } catch (Exception $e) {
        deepl_log($e->getMessage());
        exit(0);
    }
}

/**
 * For each record fetch a translation for each target language and store in table.
 */
function fetch_translations($data)
{
    global $dbh, $table, $langs, $key, $dom;
    $url = "{$dom}/translate";

    foreach ($data as $row) {
        $s_lang = strtoupper($row['language']);
        deepl_log("{$row['id_ords']}");
        foreach ($langs as $t_lang) {
            $col = strtolower($t_lang);
            $stm = $dbh->prepare("UPDATE {$table} SET {$col} = :text WHERE id_ords = :id_ords");
            if ($s_lang == $t_lang) {
                $text = $row['problem'];
            } else {
                $params = [
                    'auth_key' => $key,
                    'source_lang' => $s_lang,
                    'target_lang' => $t_lang,
                    'text' => $row['problem'],
                ];
                $res = json_decode(curl_post($url, $params));
                if (!is_object($res)) {
                    deepl_log("Non-object returned from DeepL");
                    exit(0);
                }
                if (property_exists($res, 'message')) {
                    // Quota exceeded or too many requests?
                    deepl_log($res->message);
                    deepl_log(get_usage());
                    exit(0);
                }
                if (property_exists($res, 'translations')) {
                    $text = $res->translations[0]->text;
                    if ($res->translations[0]->detected_source_language !== $s_lang) {
                        deepl_log("{$res->translations[0]->detected_source_language} detected for {$row['id_ords']}");
                    }
                } else {
                    deepl_log("{$row['id_ords']} no translations");
                    continue;
                }
            }
            try {
                $stm->execute(['text' => $text, 'id_ords' => $row['id_ords']]);
            } catch (PDOException $e) {
                deepl_log($e->getMessage());
                exit(0);
            }
        }
    }
}
deepl_log("FINISHED");
deepl_log(get_usage());

/**
 * Fetch the current usage status.
 */
function get_usage() {
    global $key, $dom;
    return curl_post("{$dom}/usage", ['auth_key' => $key]);
}

/**
 * Print and log a message.
 *
 * @param string $msg Message to record.
 */
function deepl_log($msg)
{
    global $fh;
    print "{$msg}\n";
    fwrite($fh, "{$msg}\n");
}

/**
 * @param string $url The URL to make the request to
 * @param array $params The parameters to use for the POST body
 *
 * @return string The response body
 */
function curl_post($url, $params)
{
    $ch = curl_init();

    $opts = [
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => '',
    ];
    $opts[CURLOPT_POSTFIELDS] = http_build_query($params);
    $opts[CURLOPT_URL] = $url;
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    if ($response === false) {
        $e = [
            'error_code' => curl_errno($ch),
            'error' => array(
                'message' => curl_error($ch),
                'type' => 'CurlException',
            ),
        ];
        curl_close($ch);
        $info = curl_getinfo($ch);
        file_put_contents("log/curlinfo.log", print_r($info, 1));
        throw $e;
    }
    curl_close($ch);
    return $response;
}

/**
 * Connect to the database.
 *
 * @params $cfg Array of config options.
 */
function &connect($cfg)
{
    $con = "mysql:host={$cfg['host']};dbname={$cfg['dbname']};charset=utf8mb4";
    try {
        $dbh = new PDO($con, $cfg['username'], $cfg['password'], [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8", PDO::MYSQL_ATTR_LOCAL_INFILE => TRUE]);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        print $e->getMessage() . "\n";
        exit(0);
    }
    return $dbh;
}
