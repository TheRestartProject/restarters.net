<?php

/**
 * Script to pull translations from DeepL for strings in a csv file.
 * DeepL API https://www.deepl.com/docs-api
 * Requires DeepL API key.
 */

global $fh, $key;

$domfree = 'https://api-free.deepl.com';
$keyfree = '<free_api_key>';
$dompaid = 'https://api.deepl.com';
$keypaid = '';
$version = '/v2';

$dom = $dompaid . $version;
$key = $keypaid;

$date = date("YmdHis");

file_put_contents("./deeplusage_{$date}.json", get_usage());

if (!$fh = fopen("./deepl_{$date}.log", 'w')) {
    exit(0);
}

// Norwegian not supported by DeepL
// $infile = 'todo_no.csv';
// $outfile = 'done_no.csv';

$infile = 'todo.csv';
$outfile = 'done.csv';
fetch_translations(get_rows($infile), $outfile);

exit();


function get_rows($infile)
{
    $result = [];
    if ($fh = @fopen($infile, 'r')) {
        $cols = fgetcsv($fh);
        while ($row = fgetcsv($fh)) {
            $item = [];
            foreach ($row as $k => $v) {
                $item[$cols[$k]] = $v;
            }
            $result[] = $item;
        }
    } else {
        print "ERROR: failed to open file {$infile}\n";
        exit(0);
    }
    return $result;
}

/**
 * For each row fetch a translation.
 */
function fetch_translations($data, $outfile)
{
    global $key, $dom;
    $url = "{$dom}/translate";
    $s_lang = 'EN';
    $fp = fopen($outfile, 'w');
    $cols = array_keys($data[0]);
    fputcsv($fp, $cols);
    foreach ($data as $i => $row) {
        $params = [
            'auth_key' => $key,
            'source_lang' => 'EN',
            'target_lang' => strtoupper($row['alias']),
            'text' => $row['en'],
        ];
        $res = json_decode(curl_post($url, $params));
        if (!is_object($res)) {
            deepl_log("Non-object returned from DeepL for row $i");
            exit(0);
        }
        if (property_exists($res, 'message')) {
            // Quota exceeded or too many requests?
            deepl_log("No message for row $i");
            deepl_log($res->message);
            deepl_log(get_usage());
            exit(0);
        }
        if (property_exists($res, 'translations')) {
            $row['trans'] = $res->translations[0]->text;
            if ($res->translations[0]->detected_source_language !== $s_lang) {
                deepl_log("{$res->translations[0]->detected_source_language} detected for {$params}");
            }
            fputcsv($fp, $row);
        } else {
            deepl_log("$params no translations for row $i");
            continue;
        }
        continue;
    }
    fclose($fp);
}
deepl_log("FINISHED");
deepl_log(get_usage());

/**
 * Fetch the current usage status.
 */
function get_usage()
{
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

