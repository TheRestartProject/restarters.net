<?php

/**
 * Script to pull translations into the ltm manager.
 */

global $dbh;

$cfg = [
    'host' => '127.0.0.1',
    'username' => 'admin',
    'password' => 'admin',
    'dbname' => 'restarters.test',
];

if (!$dbh = connect($cfg)) {
    exit(0);
}

$infile = 'done.csv';

process(get_rows($infile));

exit();


function get_rows($file)
{
    $result = [];
    if ($fh = @fopen($file, 'r')) {
        $cols = fgetcsv($fh);
        while ($row = fgetcsv($fh)) {
            $item = [];
            foreach ($row as $k => $v) {
                $item[$cols[$k]] = $v;
            }
            $result[] = $item;
        }
    } else {
        print "ERROR: failed to open file {$file}\n";
        exit(0);
    }
    return $result;
}

/**
 * For each row, update string in translation manager (or test table).
 */
function process($data)
{
    global $dbh;

    $stm = $dbh->prepare("UPDATE ltm_translations SET `value` = :trans WHERE `locale` = :locale AND `group` = :group AND `key` = :key");
    // $stm = $dbh->prepare("UPDATE ltm_translations_todo SET `trans` = :trans WHERE `lang` = :locale AND `group` = :group AND `key` = :key");

    foreach ($data as $row) {
        try {
            $stm->execute(['locale' => $row['lang'], 'group' => $row['group'], 'key' => $row['key'], 'trans' => $row['trans']]);
        } catch (PDOException $e) {
            print_r($e->getMessage());
            exit(0);
        }
    }
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

/**
 * Trying to update the files is messy.
 */
function lang_files_FORGET_THIS()
{
    $root = '../../resources/lang';
    $langs = ['en', 'de', 'nl', 'nl-BE', 'fr', 'fr-BE', 'it', 'es'];
    foreach ($langs as $lang) {
        $file = "$root/$lang.json";
        if (file_exists($file)) {
            $content = json_decode(file_get_contents($file));
            $json["$lang.json"] = $content;
        } else {
            echo "Not found $file";
        }
    }
    foreach ($langs as $lang) {
        $php[$lang] = [];
        $phpfiles = scandir("$root/$lang");
        foreach ($phpfiles as $file) {
            if (substr($file, 0, 1) == '.') {
                continue;
            }
            $content = include_once("$root/$lang/$file");
            $php[$lang][$file] = $content;
        }
    }
    return;
}
